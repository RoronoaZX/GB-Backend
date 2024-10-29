<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeCreditProducts extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_credits_id',
        'credit_user_id',
        'product_id',
        'price',
        'pieces'
    ];

    public function creditUserId()
    {
        return $this->belongsTo(Employee::class, 'credit_user_id', 'id');
    }

    public function employeeCredits()
    {
        return $this->belongsTo(EmployeeCredits::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
