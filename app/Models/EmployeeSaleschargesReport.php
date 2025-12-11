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
}
