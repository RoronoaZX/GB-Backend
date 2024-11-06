<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'category',
    ];

    // public static function boot()
    // {
    //     parent::boot();

    //     static::deleting(function ($recipe) {
    //         $recipe->breadGroups()->delete();
    //         $recipe->ingredientGroups()->delete();
    //     });
    // }

    public function breadGroups()
    {
        return $this->hasMany(BreadGroup::class);
    }

    public function ingredientGroups()
    {
        return $this->hasMany(IngredientGroup::class);
    }

    public function scopeSearch($query, $keyword)
    {
        return $query->where('name','LIKE', "%{$keyword}%");
    }
}
