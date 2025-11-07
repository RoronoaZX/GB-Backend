<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Warehouse;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Branch extends Model
{
    use HasFactory;
    protected $fillable = [
        'warehouse_id',
        'employee_id',
        'name',
        'location',
        'phone',
        'status',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'branches_products', 'branches_id', 'product_id')
                    ->withPivot('price');
    }

    public function branch_products()
    {
        return $this->hasMany(BranchProduct::class,'branches_id','id');
    }

    public function salesReports()
    {
        return $this->hasMany(SalesReports::class);
    }

    public function branchEmployee()
    {
        return $this->hasMany(BranchEmployee::class);
    }

    public function employee()
    {
        return $this->hasMany(Employee::class, 'employee_id', 'id');
    }

    public function employees()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function branchRawMaterialsReport()
    {
        return $this->hasMany(BranchRawMaterialsReport::class);
    }
}
