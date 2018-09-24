<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Exceptions\InvalidRequestException;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        $builder = Product::where('on_sale', true);
        if ($search = $request->input('search', '')){
            $like = '%'.$search.'%';
            $builder->where(function ($query) use ($like)  {
                $query->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('skus', function ($query) use ($like) {
                        $query->where('title', 'like', $like)
                            ->orWhere('description', 'like', $like);
                    });
            });
        }


        if ($order = $request->input('order', '')) {
            if (preg_match('/^(.+)_(asc|desc)$/', $order, $m)) {
                if (in_array($m[1], ['price', 'sold_count', 'rating'])) {
                    // 根据传入的排序值来构造排序参数
                    $builder->orderBy($m[1], $m[2]);
                }
            }
        }
        $products = $builder->paginate(16);

        return view('products.index', ['products' => $products,
            'filters' => [
                'search' => $search,
                'order' => $order
            ]
            ]);
    }

    public function show(Product $product, Request $request)
    {
        //判断商品是否已经上架,如果没上架跑出异常
        if (!$product->on_sale){
            throw new InvalidRequestException('商品未上架');
        }

        return view('products.show',['product'=> $product]);
    }
}
