<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncentiveEmployeeReports extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'employee_id',
        'number_of_employees',
        'designation',
        'shift_status',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
