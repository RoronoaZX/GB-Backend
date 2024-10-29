<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseStocksReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'raw_materials_id',
        'available_stocks'
    ];
}
