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
        'reason'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
