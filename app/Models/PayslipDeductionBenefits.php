<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayslipDeductionBenefits extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_deduction_id',
        'employee_id',
        'hdmf',
        'phic',
        'sss',
    ];
}
