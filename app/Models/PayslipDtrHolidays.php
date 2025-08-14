<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayslipDtrHolidays extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_dtr_id',
        'date',
        'name',
        'type',
    ];
}
