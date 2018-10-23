<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Installment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Events\OrderPaid;

class InstallmentsController extends Controller
{
    public function index(Request $request)
    {
        $installments = Installment::query()
            ->where('user_id', $request->user()->id)
            ->paginate(10);

        return view('installments.index', ['installments' => $installments]);
    }

    public function show(Installment $installment)
    {

        $this->authorize('own', $installment);
        $items = $installment->items()->orderBy('sequence')->get();

        return view('installments.show', [
           'installment' => $installment,
           'items' => $items,
           'nextItem' => $items->where('paid_at', null)->first(),
        ]);
    }

    public function payByAlipay(Installment $installment)
    {
        if ($installment->order->closed)
        {
            throw new InvalidRequestException('对应的商品订单已被关闭');
        }

        if ($installment->status === Installment::STATUS_FINISHED){
            throw new InvalidRequestException('该分期订单已还清');
        }

        if (!$nextItem = $installment->items()->whereNull('paid_at')->orderBy('sequence')->first()){
            throw new InvalidRequestException('该分期订单已还清');
        }

        return app('alipay')->web([
           'out_trade_no' => $installment->no.'_'.$nextItem->sequence,
            'total_amount' => $nextItem->total,
            'subject' => '支付Laravel Shop的分期订单: '.$nextItem->sequence,
            'notify_url'   => ngrok_url('installments.alipay.notify'),
            'return_url'   => route('installments.alipay.return'),
        ]);
    }

    //支付宝前端回调
    public function alipayReturn()
    {
        try{
            app('alipay')->verify();
        }catch (\Exception $e) {
            return view('pages.error', ['msg' => '数据不正确']);
        }

        return view('pages.success', ['msg' => '付款成功']);
    }

    public function alipayNotify()
    {
        $data = app('alipay')->verify();

        list($no, $sequence) = explode('_', $data->out_trade_no);

        if (!$installment = Installment::where('no', $no)->first()){
            return 'fail';
        }

        if (!$item = $installment->items()->where('sequence', $sequence)->first()){
            return 'fail';
        }

        if ($item->paid_at){
            return app('alipay')->success();
        }

        $item->update([
           'paid_at' => Carbon::now(),
            'payment_method' => 'alipay',
            'payment_no' => $data->trade_no
        ]);

        if ($item->sequence === 0){
            $installment->update(['status' => Installment::STATUS_REPAYING]);

            $installment->order->update([
               'paid_at'  => Carbon::now(),
               'payment_method' => 'installment',
               'payment_no' => $no
            ]);
            // 触发商品订单已支付的事件
            event(new OrderPaid($installment->order));
        }

        // 如果这是最后一笔还款
        if ($item->sequence === $installment->count - 1) {
            // 将分期付款状态改为已结清
            $installment->update(['status' => Installment::STATUS_FINISHED]);
        }

        return app('alipay')->success();
    }


}
