<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchPremix extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'branch_recipe_id',
        'name',
        'category',
        'status',
        'available_stocks',
    ];

    public function branch_recipe()
    {
        return $this->belongsTo(BranchRecipe::class);
    }


}
