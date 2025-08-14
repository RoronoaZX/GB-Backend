<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayslipIncentive extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_earning_id',
        'branch_id',
        'employee_id',
        'designation',
        'baker_kilo_total',
        'excess_kilo',
        'incentive_value',
        'multiplier_used',
        'number_of_employees',
        'shift_status',
        'target',

    ];
}
