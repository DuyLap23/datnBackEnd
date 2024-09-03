<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSize extends Model
{
    use HasFactory;

    const TYPE_CLOTHES = 1;
    const TYPE_SHOE = 2;

    protected $fillable = [
            'name',
            'type',
        ];

}
