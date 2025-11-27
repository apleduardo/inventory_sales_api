<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'product_id',
        'movement_type', // 'IN' ou 'OUT'
        'quantity',
        'cost_price',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
