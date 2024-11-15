<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VouCher extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'name',
        'minimum_order_value',
        'discount_type',
        'discount_value',
        'start_date',
        'end_date',
        'usage_limit',
        'voucher_active',
        'used_count',
        'applicable_type',
        'applicable_ids',
        'code'
    ];
    protected $table = 'vouchers';

    protected $dates = ['deleted_at'];



}
