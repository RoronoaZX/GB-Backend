<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseRmStocks extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'raw_material_id',
        'price_per_gram',
        'quantity',
        'gram',
        'kilo',
        'pcs',
        'total_grams'
    ];
}
