<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepurposeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'bread_out_id',
        'action_type',
        'outputable_id',
        'outputable_type',
        'destination_branch_id',
        'output_quantity',
    ];

    public function breadOut()
    {
        return $this->belongsTo(BreadOut::class);
    }

    public function outputable()
    {
        return $this->morphTo();
    }

    public function destinationBranch()
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }
}
