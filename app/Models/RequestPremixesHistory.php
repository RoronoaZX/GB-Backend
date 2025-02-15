<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestPremixesHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_premixes_id',
        'status',
        'changed_by', // Log the user who made the request
        // 'changed_at' => now(),
        'notes',
    ];
}
