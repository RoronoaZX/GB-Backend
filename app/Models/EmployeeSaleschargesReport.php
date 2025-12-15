<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSaleschargesReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_report_id',
        'employee_id',
        'charge_amount'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function salesReport()
    {
        return $this->belongsTo(SalesReports::class, 'sales_report_id');
    }
}
