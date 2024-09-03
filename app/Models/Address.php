<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone_number',
        'city',
        'district',
        'additional_address',
        'detail_address',
        'is_default',
    ];
    public function user()
    {
       return  $this->belongsTo(User::class);
    }
}
