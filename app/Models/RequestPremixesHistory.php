<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestPremixesHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_premixes_id',
        'branch_premix_id',
        'warehouse_id',
        'status',
        'quantity', // Log the user who made the request
        'changed_by', // Log the user who made the request
        // 'changed_at' => now(),
        'notes',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'changed_by');
    }
}
