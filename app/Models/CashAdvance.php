<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashAdvance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'amount',
        'number_of_payments',
        'payment_per_payroll',
        'remaining_payments',
        'reason'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
