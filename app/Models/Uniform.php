<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Uniform extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'number_of_payments',
        'total_amount',
        'payments_per_payroll',
        'remaining_payments'

    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function tShirt()
    {
        return $this->hasMany(UniformTshirt::class);
    }

    public function pants()
    {
        return $this->hasMany(UniformPants::class);
    }
}
