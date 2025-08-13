<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayslipDeductionCredit extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_deduction_id',
        'product_id',
        'pieces',
        'price',
        'product_name',
        'total_price',
    ];
}
