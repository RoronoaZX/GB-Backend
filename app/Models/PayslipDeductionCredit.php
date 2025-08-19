<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayslipDeductionCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_deduction_id',
        'branch_id',
        'sales_report_id',
        'employee_credit_id',
        'employee_credit_product_id',
        'employee_id',
        'product_id',
        'date',
        'pieces',
        'price',
        'product_name',
        'total_price',
        'total_amount'
    ];
}
