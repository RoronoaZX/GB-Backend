<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchRmStocks extends Model
{
    use HasFactory;

    protected $fillable = [
        'raw_material_id',
        'branch_id',
        'price_per_price',
        'quantity',
        'gram',
        'kilo',
        'pcs',
        'total_grams'
    ];
}
