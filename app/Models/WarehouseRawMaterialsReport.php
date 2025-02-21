<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseRawMaterialsReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'raw_material_id',
        'total_quantity',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function rawMaterials()
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }
}
