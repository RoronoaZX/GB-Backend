<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeBenefit extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'sss_number',
        'sss',
        'hdmf_number',
        'hdmf',
        'phic_number',
        'phic',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
