<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngredientGroup extends Model
{
    use HasFactory;

    protected $fillable = ['branch_recipe_id', 'ingredient_id','quantity'];

    public function recipe()
    {
        // return $this->belongsTo(Recipe::class);
        return $this->belongsTo(BranchRecipe::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(RawMaterial::class);
    }
}
