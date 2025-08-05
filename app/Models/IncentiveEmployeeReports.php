<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncentiveEmployeeReports extends Model
{
    use HasFactory;

    protected $fillable = [
        'incentive_reports_id',
        'employee_id',
        'designation',
        'shift_status',
    ];

    public function incentiveReports()
    {
        return $this->belongsTo(IncentivesReports::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
