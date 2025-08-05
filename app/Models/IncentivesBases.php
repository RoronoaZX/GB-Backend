<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncentivesBases extends Model
{
    use HasFactory;

    protected $fillable = [
        'number_of_employees',
        'target',
        'baker_multiplier',
        'lamesador_multiplier',
        'hornero_incentives',
    ];
}
