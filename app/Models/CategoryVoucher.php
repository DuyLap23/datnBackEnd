<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryVoucher extends Model
{
    use HasFactory;
    protected $fillable = ['category_id', 'voucher_id'];

}
