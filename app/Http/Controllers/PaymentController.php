<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Installment;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Events\OrderPaid;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function payByAlipay(Order $order, Request $request)
    {
        $this->authorize('own', $order);
        if ($order->paid_at || $order->closed){
            throw new InvalidRequestException('订单状态不正确');
        }

        return app('alipay')->web([
           'out_trade_no' => $order->no,
           'total_amount' => $order->total_amount,
           'subject' =>'支付 Laravel Shop 的订单:'.$order->no,
        ]);
    }

    //前端回调
    public function alipayReturn()
    {
        try{
            $data = app('alipay')->verify();
        } catch (\Exception $e){
            return view('pages.error', ['msg' => '数据不正确']);
        }
        return view('pages.success', ['msg' => '付款成功']);
    }

    public function alipayNotify()
    {
        $data = app('alipay')->verify();
        $order = Order::where('no', $data->out_trade_no)->first();

        if (!$order){
            return 'fail';
        }

        if ($order->pait_at){
            return app('alipay')->success();
        }

        $order->update([
           'paid_at' => Carbon::now(),
           'payment_method' => 'alipay',
           'payment_no' => $data->trade_no,
        ]);

        $this->afterPaid($order);
        return app('alipay')->success();
    }

    protected function afterPaid(Order $order)
    {
        event(new OrderPaid($order));
    }

    public function payByInstallment(Order $order, Request $request)
    {
        $this->authorize('own', $order);

        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }

        $this->validate($request, [
           'count' => ['required', Rule::in(array_keys(config('app.installment_fee_rate')))],
        ]);

        Installment::query()
            ->where('order_id', $order->id)
            ->where('status', Installment::STATUS_PENDING)
            ->delete();

        $count = $request->input('count');

        $installment  = new Installment([
            'total_amount' => $order->total_amount,
            'count' => $count,
            'fee_rate' => config('app.installment_fee_rate')[$count],
            'fine_rate' => config('app.installment_fine_rate'),
        ]);

        $installment->user()->associate($request->user());
        $installment->order()->associate($order);
        $installment->save();

        $dueDate = Carbon::tomorrow();

        $base = big_number($order->total_amount)->divide($count)->getValue();

        // 计算每一期的手续费
        $fee = big_number($base)->multiply($installment->fee_rate)->divide(100)->getValue();

        // 根据用户选择的还款期数，创建对应数量的还款计划
        for ($i = 0; $i < $count; $i++) {
            // 最后一期的本金需要用总本金减去前面几期的本金
            if ($i === $count - 1) {
                $base = big_number($order->total_amount)->subtract(big_number($base)->multiply($count - 1));
            }
            $installment->items()->create([
                'sequence' => $i,
                'base'     => $base,
                'fee'      => $fee,
                'due_date' => $dueDate,
            ]);
            // 还款截止日期加 30 天
            $dueDate = $dueDate->copy()->addDays(30);
        }

        return $installment;
    }
}
