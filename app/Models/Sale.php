<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    // Status da venda
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_FAILED = 'FAILED';

    protected $fillable = [
        'customer_name',
        'transaction_hash',
        'total_amount',
        'total_profit',
        'status',
    ];

    public function items()
    {
        return $this->hasMany(SaleItem::class, 'sale_id');
    }
}
