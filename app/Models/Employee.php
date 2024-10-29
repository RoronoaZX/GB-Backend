<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employment_type_id',
        'firstname',
        'middlename',
        'lastname',
        'birthdate',
        'phone',
        'address',
        'sex',
        'position',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'employee_id', 'id');
    }

    public function branchEmployee()
    {
        return $this->hasOne(BranchEmployee::class, 'employee_id','id');
    }

    public function salesReports()
    {
        return $this->hasMany(SalesReports::class);
    }
    public function employmentType()
    {
        return $this->belongsTo(EmploymentType::class, 'employment_type_id', 'id');
    }
    public function branch()
    {
        return $this->hasOne(Branch::class, 'employee_id', 'id');
    }
}
