<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecipeCost extends Model
{
    use HasFactory;

    protected $table = [
        'initial_bakerreport_id',
        'branch_recipe_id',
        'total_cost',
        'status',
        'kilo'
    ];
}
