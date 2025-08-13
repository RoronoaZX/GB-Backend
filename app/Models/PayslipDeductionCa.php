<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayslipDeductionCa extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_deduction_id',
        'cash_advance_id',
        'employee_id',
        'date',
        'amount',
        'number_of_payment',
        'payment_per_payroll',
        'remaining_payments',
        'reason',
    ];
}
