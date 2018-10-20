<?php

namespace App\Admin\Controllers;

use App\Exceptions\InternalException;
use App\Exceptions\InvalidRequestException;
use App\Http\Requests\Admin\HandleRedundRequest;
use App\Http\Requests\Request;
use App\Models\Order;
use App\Http\Controllers\Controller;
use Encore\Admin\Admin;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;
use App\Models\CrowdfundingProduct;

class OrdersController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('订单列表')
            ->body($this->grid());
    }


    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Order);
        // 只展示已支付的订单，并且默认按支付时间倒序排序
        $grid->model()->whereNotNull('paid_at')->orderBy('paid_at','desc');
        $grid->no('订单流水号');
        // 展示关联关系的字段时，使用 column 方法
        $grid->column('user.name','卖家');

        $grid->total_amount('总金额')->sortable();

        $grid->paid_at('支付时间')->sortable();
        $grid->ship_status('物流')->display(function ($value){
            return Order::$shipStatusMap[$value];
        });
        $grid->refund_status('退款状态')->display(function ($value){
            return Order::$refundStatusMap[$value];
        });
        // 禁用创建按钮，后台不需要创建订单
        $grid->disableCreateButton();
        $grid->actions(function ($action){
           $action->disableDelete();
           $action->disableEdit();
        });

        $grid->tools(function ($tools){
            //禁用批量删除按钮
           $tools->batch(function ($batch){
              $batch->disableDelete();
           });
        });
        return $grid;
    }

    public function show(Order $order,Content $content)
    {
        return $content->header('查看订单')
            ->body(view('admin.orders.show', ['order' => $order]));

    }

    public function ship(Order $order, Request $request)
    {
        if (!$order->paid_at){
            throw new InvalidRequestException('该订单未付款');
        }

        if ($order->ship_status !== Order::SHIP_STATUS_PENDING){
            throw new InvalidRequestException('该订单已发货');
        }

        // 众筹订单只有在众筹成功之后发货
        if ($order->type === Order::TYPE_CROWDFUNDING &&
            $order->items[0]->product->crowdfunding->status !== CrowdfundingProduct::STATUS_SUCCESS) {
            throw new InvalidRequestException('众筹订单只能在众筹成功之后发货');
        }

        $data = $this->validate($request, [
           'express_company' => ['required'],
            'express_no' => ['required'],
        ],[],[
            'express_company' => '物流公司',
            'express_no' => '物流单号'
        ]);

        $order->update([
           'ship_status' => Order::SHIP_STATUS_DELIVERED,
           'ship_data'   => $data,
        ]);

        return redirect()->back();
    }

    public function handleRefund(Order $order, HandleRedundRequest $request)
    {
        if ($order->refund_status !== Order::REFUND_STATUS_APPLIED){
            throw new InvalidRequestException('订单状态不正确');
        }

        if ($request->input('agree')){
            $this->_refundOrder($order);
        }else{
            $extra = $order->extra ?: [];
            $extra['refund_disagree_reason'] = $request->input('reason');

            $order->update([
               'refund_status' =>Order::REFUND_STATUS_PENDING,
               'extra' => $extra,
            ]);
        }

        return $order;
    }

    protected function _refundOrder(Order $order)
    {
        switch ($order->payment_method){
            case 'wechat':

                break;
            case 'alipay':

                $refundNo = Order::getAvailableRefundNo();

                $ret = app('alipay')->refund([
                    'out_trade_no' => $order->no,
                   'refund_amount' => $order->total_amount,
                   'out_request_no' => $refundNo,
                ]);

            if ($ret->sub_code){
                $extra = $order->exists;
                $extra['refund_failed_code'] = $ret->sub_code;

                $order->update([
                   'refund_no' => $refundNo,
                   'refund_status' => Order::REFUND_STATUS_FAILED,
                   'extra' => $extra
                ]);
            }else{
                $order->update([
                   'refund_no' => $refundNo,
                   'refund_status' => Order::REFUND_STATUS_SUCCESS,
                ]);
            }
            break;

            default:
                throw new InternalException('未知订单支付方式:'.$order->payment_method);
                break;
        }
    }

}
