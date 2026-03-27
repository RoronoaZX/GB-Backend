<?php

namespace App\Models;

use Illuminate\Cache\Events\RetrievingKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeOnLeave extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'duration_value',
        'duration_type',
        'leave_type',
        'handled_by',
        'status',
        'reason'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function handledBy()
    {
        return $this->belongsTo(Employee::class, 'handled_by', 'id');
    }
}
