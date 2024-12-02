<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'img_thumbnail',
        'price_regular',
        'price_sale',
        'description',
        'content',
        'user_manual',
        'view',
        'is_active',
        'category_id',
        'brand_id',
        'tags',
        'productImages',
        'productVariants',

    ];
    protected $casts = [
        'is_active' => 'boolean',
        'price_regular' => 'float',
        'price_sale' => 'float',
    ];
    public $dates = ['deleted_at'];

    public function favoredByUsers()
    {
        return $this->belongsToMany(User::class, 'favourite_lists');
    }public function commentedByUsers()
    {
        return $this->belongsToMany(User::class, 'comments');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tags');
    }

    public function comments() {
        return $this->hasMany(Comment::class);
    }
    
    public function productImages()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }


}

