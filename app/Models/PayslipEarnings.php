<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayslipEarnings extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_id',
        'allowances_pay',
        'holidays_pay',
        'incentives_pay',
        'night_diff_pay',
        'overtime_pay',
        'undertime_pay',
        'working_hours_pay',
    ];
}
