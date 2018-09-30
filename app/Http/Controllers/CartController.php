<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CartService;
use App\Http\Requests\AddCartRequest;
use App\Models\ProductSku;

class CartController extends Controller
{
    protected $cartServices;
    public function  __construct(CartService $cartService)
    {
        $this->cartServices = $cartService;
    }

    public function add(AddCartRequest $request)
    {
        $this->cartServices->add($request->input('sku_id'), $request->input('amount'));
        return [];
    }

    public function index(Request $request)
    {
        $cartItems = $this->cartServices->get();
        $addresses = $request->user()->addresses()->orderBy('last_used_at', 'desc')->get();

        return view('cart.index', ['cartItems' => $cartItems, 'addresses' => $addresses]);
    }

    public function remove(ProductSku $sku, Request $request)
    {
       $this->cartServices->remove($sku->id);

        return [];
    }
}
