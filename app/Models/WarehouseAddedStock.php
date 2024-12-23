<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseAddedStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_stock_report_id',
        'raw_material_id',
        'quantity',
    ];

    public function warehouseStockReport()
    {
        return $this->belongsTo(WarehouseStockReports::class, 'warehouse_stock_report_id');
    }

    public function rawMaterials()
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }

}
