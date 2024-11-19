<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductVariant extends Model
{
    use HasFactory , SoftDeletes;

    protected $fillable = [
        'product_id',
        'product_color_id',
        'product_size_id',
        'quantity',
        'image'
    ];

    protected $casts = [
        'price' => 'float',
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productColor()
    {
        return $this->belongsTo(ProductColor::class);
    }

    public function productSize()
    {
        return $this->belongsTo(ProductSize::class);
    }

    public function productImages()
    {
        return $this->hasMany(ProductImage::class);
    }
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function productDelete()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}
