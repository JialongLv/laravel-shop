<?php

namespace App\Services;

use App\Exceptions\CouponCodeUnavailableException;
use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
use App\Models\CouponCode;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\User;
use App\Models\UserAddress;
use Carbon\Carbon;
use function foo\func;

class OrderService
{
    public function store(User $user, UserAddress $address, $remark, $items, CouponCode $coupon = null)
    {
        if ($coupon){
            $coupon->checkAvailable($user);
        }

        $order = \DB::transaction(function () use ($user, $address, $remark, $items, $coupon){
           $address->update(['last_used_at' => Carbon::now()]);

           $order = new Order([
              'address' => [
                  'address' => $address->full_address,
                  'zip' => $address->zip,
                  'contact_name' => $address->contact_name,
                  'contact_phone' => $address->contact_phone,
              ],
               'remark' => $remark,
               'total_amount' => 0,
           ]);

           $order->user()->associate($user);

           $order->save();

           $totalAmount = 0;
           foreach ($items as $data){
               $sku = ProductSku::find($data['sku_id']);

               $item = $order->items()->make([
                   'amount' => $data['amount'],
                   'price'  => $sku->price,
               ]);

               $item->product()->associate($sku->product_id);
               $item->productSku()->associate($sku);
               $item->save();
               $totalAmount += $sku->price * $data['amount'];
               if ($sku->decreaseStock($data['amount']) <= 0){
                   throw new InvalidRequestException('该商品库存不足');
               }

           }

           if ($coupon){
               $coupon->checkAvailable($user,$totalAmount);

               $totalAmount = $coupon->getAdjustedPrice($totalAmount);

               $order->couponCode()->associate($coupon);

               if ($coupon->changUsed() <= 0){
                   throw new CouponCodeUnavailableException('该优惠券已被兑完');
               }
           }

           $order->update(['total_amount' => $totalAmount]);
           $skuIds = collect($items)->pluck('sku_id')->all();
           app(CartService::class)->remove($skuIds);
           return $order;
        });

        dispatch(new CloseOrder($order, config('app.order_ttl')));

        return $order;
    }

    public function crowdfunding(User $user, UserAddress $address, ProductSku $sku, $amount)
    {
        $order = \DB::transaction(function () use ($amount, $sku, $user, $address){
           $address->update(['last_used_at' => Carbon::now()]);

           $order = new Order([
              'address' => [$address->full_address,
              'zip' => $address->zip,
              'contact_name' => $address->contact_name,
              'contact_phone' => $address->contact_phone,
                  ],
               'remark'       => '',
               'total_amount' => $sku->price * $amount,
           ]);

           $order->user()->associate($user);

           $order->save();

           $item = $order->items()->make([
              'amount' => $amount,
              'price' => $sku->price,
           ]);

           $item->product()->associate($sku->product_id);
           $item->productSku()->associate($sku);
           $item->save();

           if ($sku->decreaseStock($amount) <= 0){
               throw new InvalidRequestException('该商品库存不足');
           }

           return $order;
        });

        $crowdfundingTtl  = $sku->product->crowdfunding->end_at->getTimestamp() - time();
        dispatch(new CloseOrder($order, min(config('app.order_ttl'),$crowdfundingTtl)));

        return $order;
    }
}