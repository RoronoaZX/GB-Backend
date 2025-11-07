<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseStockReports extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'employee_id',
        'supplier_company_name',
        'supplier_name'
    ];

    public function warehouseAddedStocks()
    {
        return $this->hasMany(WarehouseAddedStock::class, 'warehouse_stock_report_id')
                    ->with('rawMaterials');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
