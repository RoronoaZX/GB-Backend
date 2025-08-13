<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayslipDeductionUniformPants extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_deduction_uniform_id',
        'uniform_pant_id',
        'date',
        'pcs',
        'price',
        'size',
    ];
}
