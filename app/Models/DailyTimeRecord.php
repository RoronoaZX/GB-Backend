<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyTimeRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_uuid_in',
        'device_uuid_out',
        'employee_id',
        'employee_allowance',
        'time_in',
        'time_out',
        'lunch_break_start',
        'lunch_break_end',
        'break_start',
        'break_end',
        'overtime_start',
        'overtime_end',
        'overtime_reason',
        'ot_status',
        'approved_by',
        'declined_reason',
        'half_day_reason',
        'shift_status',
        'schedule_in',
        'schedule_out'
    ];


    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function deviceIN()
    {
        return $this->belongsTo(Device::class, 'device_uuid_in', 'uuid');
    }

    public function deviceOUT()
    {
        return $this->belongsTo(Device::class, 'device_uuid_out', 'uuid');
    }

    public function approvedBy()
    {
        return $this->belongsTo(Employee::class, 'approved_by', 'id');
    }
}
