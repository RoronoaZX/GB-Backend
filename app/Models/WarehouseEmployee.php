<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class WarehouseEmployee extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'employee_id',
        'time_in',
        'time_out'
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }
    public function employee()
    {
        return $this->belongsTp(Employee::class, 'employee_id','id');
    }
    public function scopeByBranch($query, $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'employee_id', 'id');
    }
}
