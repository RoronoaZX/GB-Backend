<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'from',
        'to',
        'payroll_release_date',
        'rate_per_day',
        'total_days',
        'uniform_balance',
        'credit_balance',
        'cash_advance_balance',
        'total_earnings',
        'total_deductions',
        'net_income',
    ];
}
