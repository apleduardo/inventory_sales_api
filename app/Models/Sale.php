<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [
        'customer_name',
        'transaction_hash',
        'total_amount',
        'total_profit',
        'status',
    ];
}
