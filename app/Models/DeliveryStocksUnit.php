<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryStocksUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'rm_delivery_id',
        'raw_materials_id',
        'unit_type',
        'category',
        'quantity',
        'price_per_unit',
        'price_per_gram',
        'gram',
        'pcs',
        'kilo'
    ];


       public function delivery()
    {
        return $this->belongsTo(RawMaterialsDelivery::class, 'rm_delivery_id');
    }
}
