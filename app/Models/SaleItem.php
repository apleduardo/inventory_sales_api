<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'cost_price',
        'profit',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
