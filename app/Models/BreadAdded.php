<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreadAdded extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'product_id',
        'from_branch_id',
        'to_branch_id',
        'price',
        'bread_added',
        'status',
        'remark',
    ];

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
