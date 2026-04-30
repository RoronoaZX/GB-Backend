<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpoilageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'bread_out_id',
        'quantity',
        'reason',
    ];

    public function breadOut()
    {
        return $this->belongsTo(BreadOut::class);
    }
}
