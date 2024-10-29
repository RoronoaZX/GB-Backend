<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BakerReports extends Model
{
    use HasFactory;
    protected $fillable = [
        'branch_id',
        'user_id',
        'recipe_id',
        'recipe_category',
        'status',
        'kilo',
        'short',
        'over',
        'actual_target'
    ];

}
