<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VnpayTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'transaction_id',
        'amount',
        'response_code',
        'response_message',
        'secure_hash',
    ];
}
