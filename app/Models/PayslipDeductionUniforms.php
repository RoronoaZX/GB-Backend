<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayslipDeductionUniforms extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_deduction_id',
        'uniform_id',
        'employee_id',
        'number_of_payments',
        'payments_per_payroll',
        'remaining_payments',
        'total_amount',
    ];
}
