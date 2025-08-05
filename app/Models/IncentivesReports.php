<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncentivesReports extends Model
{
    use HasFactory;

    protected $fillable = [
        'initial_bakerreports_id',
        'user_employee_id',
        'over_kilo',
        'total_employees',
        'status'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'user_employee_id', 'id');
    }

    public function initialBakerreports()
    {
        return $this->belongsTo(InitialBakerreports::class);
    }
}
