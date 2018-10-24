<?php

namespace App\Jobs;

use App\Exceptions\InternalException;
use App\Models\InstallmentItem;
use App\Models\Installment;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

// ShouldQueue 代表这是一个异步任务
class RefundInstallmentOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle()
    {
        // 如果商品订单支付方式不是分期付款、订单未支付、订单退款状态不是退款中，则不执行后面的逻辑
        if ($this->order->payment_method !== 'installment'
            || !$this->order->paid_at
            || $this->order->refund_status !== Order::REFUND_STATUS_PROCESSING) {
            return;
        }
        // 找不到对应的分期付款，原则上不可能出现这种情况，这里的判断只是增加代码健壮性
        if (!$installment = Installment::query()->where('order_id', $this->order->id)->first()) {
            return;
        }
        // 遍历对应分期付款的所有还款计划
        foreach ($installment->items as $item) {
            // 如果还款计划未支付，或者退款状态为退款成功或退款中，则跳过
            if (!$item->paid_at || in_array($item->refund_status, [
                    InstallmentItem::REFUND_STATUS_SUCCESS,
                    InstallmentItem::REFUND_STATUS_PROCESSING,
                ])) {
                continue;
            }
            // 调用具体的退款逻辑，
            try {
                $this->refundInstallmentItem($item);
            } catch (\Exception $e) {
                \Log::warning('分期退款失败：'.$e->getMessage(), [
                    'installment_item_id' => $item->id,
                ]);
                // 假如某个还款计划退款报错了，则暂时跳过，继续处理下一个还款计划的退款
                continue;
            }
        }
        // 设定一个全部退款成功的标志位
        $allSuccess = true;
        // 再次遍历所有还款计划
        foreach ($installment->items as $item) {
            // 如果该还款计划已经还款，但退款状态不是成功
            if ($item->paid_at &&
                $item->refund_status !== InstallmentItem::REFUND_STATUS_SUCCESS) {
                // 则将标志位记为 false
                $allSuccess = false;
                break;
            }
        }
        // 如果所有退款都成功，则将对应商品订单的退款状态修改为退款成功
        if ($allSuccess) {
            $this->order->update([
                'refund_status' => Order::REFUND_STATUS_SUCCESS,
            ]);
        }
    }

    protected function refundInstallmentItem(InstallmentItem $item)
    {
        $refundNo = $this->order->refund_no.'_'.$item->sequence;
        switch ($item->payment_method) {
            case 'alipay':
                $ret = app('alipay')->refund([
                   'trade_no'       => $item->payment_no,
                   'refund_amount'  => $item->base,
                   'out_request_no' => $refundNo,
                ]);

            if ($ret->sub_code) {
                $item->update([
                   'refund_status' => InstallmentItem::REFUND_STATUS_FAILED,
                ]);
            }else{
                $item->update([
                   'refund_status' => InstallmentItem::REFUND_STATUS_SUCCESS,
                ]);
            }
            break;
        default:
               throw new InternalException('未知订单支付方式:' . $item->payment_method);
               break;
        }
    }
}
