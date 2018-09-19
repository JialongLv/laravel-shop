<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
      'title','description', 'image', 'on_sale',
      'rating','aold_count', 'review_count', 'price'
    ];

    protected $casts = [
      'on_sale' => 'boolean',
    ];

    //与商品sku关联
    public function skus()
    {
        return $this->hasMany(ProductSku::class);
    }

    public function getImageUrlAttribute()
    {
        if (Str::startsWith($this->attributes['image'],['http://', 'https://'])){
            return $this->attributes['image'];
        }

        return \Storage::disk('public')->url($this->attributes['image']);
    }
}
