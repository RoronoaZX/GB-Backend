<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayslipHolidaySummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_id',
        'label',
        'additional_pay',
        'date',
        'holiday_rate',
        'holiday_type',
        'type',
        'worked_hours',
    ];
}
