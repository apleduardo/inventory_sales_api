<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Sale extends Model
{
    use HasFactory;

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
