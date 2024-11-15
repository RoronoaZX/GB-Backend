<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchRecipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'recipe_id',
        'status',
        'target',
        'branch_id',
    ];

    public function breadGroups()
    {
        return $this->hasMany(BreadGroup::class);
    }

    public function ingredientGroups()
    {
        return $this->hasMany(IngredientGroup::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class,'id', 'recipe_id');
    }
}
