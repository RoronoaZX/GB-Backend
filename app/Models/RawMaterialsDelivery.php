<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterialsDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_id',
        'from_designation',
        'from_name',
        'to_id',
        'to_designation',
        'remarks',
        'status'
    ];

        public function items()
    {
        return $this->hasMany(DeliveryStocksUnit::class, 'rm_delivery_id');
    }

}
