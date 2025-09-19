<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecipeRmCost extends Model
{
    use HasFactory;

    protected $table = [
        'recipe_cost_id',
        'raw_materials_cost_id',
        'price_per_unit',
        'unit',
        'quantity',

    ];
}
