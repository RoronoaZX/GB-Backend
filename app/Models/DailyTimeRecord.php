<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyTimeRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'time_in',
        'time_out',
        'lunch_break_start',
        'lunch_break_end',
        'break_start',
        'break_end',
        'overtime_start',
        'overtime_end',
    ];


    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
