<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseScalingMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_scaling_report_id',
        'raw_material_id',
        'quantity'
    ];
}
