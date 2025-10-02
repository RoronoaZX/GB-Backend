<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreadGroup extends Model
{
    use HasFactory;
    protected $fillable = [
        'branch_recipe_id',
        'bread_id'
    ];


    public function recipe()
    {
        // return $this->belongsTo(Recipe::class);
        return $this->belongsTo(BranchRecipe::class);
    }

    public function bread()
    {
        return $this->belongsTo(Product::class);
    }
}
