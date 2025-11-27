<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InventoryLevel extends Model
{
    use HasFactory;

    protected $primaryKey = 'product_id';

    public $incrementing = false;
    
    protected $fillable = [
        'product_id',
        'quantity',
        'archived',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
