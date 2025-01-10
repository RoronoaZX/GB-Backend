<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchEmployee extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'employee_id',
        'time_shift',
    ];

    public function user()
    {
        return $this->belongsTo(User::class,'employee_id', 'id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class,'employee_id', 'id');
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

}
