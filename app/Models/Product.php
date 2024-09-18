<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory,softDeletes;

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'img_thumbnail',
        'price_regular',
        'price_sale',
        'description',
        'content',
        'user_manual',
        'view',
        'is_active',
        'is_new',
        'is_show_home',
        'category_id',
        'brand_id',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'is_new' => 'boolean',
        'is_show_home' => 'boolean',
    ];
    public $date = ['deleted_at'];

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
        return $this->belongsToMany(Tag::class);
    }

    public function productImages()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}

