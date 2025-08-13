<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayslipDeductions extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_id',
        'benefits_total',
        'cash_advance_total',
        'credit_total',
        'employee_charge_total',
        'total_deduction',
        'uniform_total',
    ];
}
