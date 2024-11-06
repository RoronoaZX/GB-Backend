<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'location',
        'employee_id',
        'phone',
        'status',
    ];

    public function employee()
    {
        return $this->hasMany(Employee::class);
    }
    public function employees()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function branches() {
        return $this->hasMany(Branch::class);
    }

    public function warehouseEmployee()
    {
        return $this->hasMany(WarehouseEmployee::class);
    }

    // public function scopeSearch($query, $keyword)
    // {
    //     return $query->where('name', 'LIKE', "%{$keyword}%")->orWhere('person_incharge','LIKE', "%{$keyword}%");
    // }

}
