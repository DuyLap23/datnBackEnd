<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasFactory, softDeletes;

    protected $fillable = [
        'name',
        'image',
        'description',
    ];
    public $date = ['deleted_at'];
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
