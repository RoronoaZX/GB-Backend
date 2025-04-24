<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'user_id',
        'type_of_report',
        'name',
        'updated_field',
        'designation',
        'designation_type',
        'action',
        'original_data',
        'updated_data'
    ];

    public function userId()
    {
        return $this->belongsTo(User::class, 'user_id')->with('employee');
    }

    // Define separate relationships for Branch and Warehouse
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'designation');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'designation');
    }

    public function getDesignation()
    {
        return $this->designation_type === 'branch' ? $this->branch : $this->warehouse;
    }
}
