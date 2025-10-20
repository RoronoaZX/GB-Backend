<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RawMaterialsDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'approved_by',
        'from_id',
        'from_designation',
        'from_name',
        'to_id',
        'to_designation',
        'remarks',
        'status'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function items()
    {
        return $this->hasMany(DeliveryStocksUnit::class, 'rm_delivery_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'to_id');
    }

    // Dynamic accessor
    public function getToDataAttribute()
    {
        if ($this->to_designation === 'Warehouse') {
            return $this->warehouse;
        }

        if ($this->to_designation === 'Branch') {
            return $this->branch;
        }

        return null;
    }

}
