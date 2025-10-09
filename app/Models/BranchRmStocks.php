<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchRmStocks extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'raw_material_id',
        'delivery_su_id',
        'price_per_gram',
        'quantity',
    ];
}
