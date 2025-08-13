<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayslipDeductionCharges extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_deduction_id',
        'sales_report_id',
        'user_id',
        'branch_id',
        'charges_amount',
    ];
}
