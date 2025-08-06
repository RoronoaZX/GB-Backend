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
        'branch_id',
        'branch_recipe_id',
        'kilo',
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
