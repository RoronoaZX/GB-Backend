<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngredientGroup extends Model
{
    use HasFactory;

    protected $fillable = ['recipe_id', 'ingredient_id','quantity'];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(RawMaterial::class);
    }
}
