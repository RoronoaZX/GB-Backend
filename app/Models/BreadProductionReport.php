<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreadProductionReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'user_id',
        'branch_recipe_id',
        'initial_bakerreports_id',
        'bread_id',
        'bread_new_production',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branchRecipe()
    {
        return $this->belongsTo(BranchRecipe::class)->with('recipe');
    }

    public function initialBakerReport()
    {
        return $this->belongsTo(InitialBakerreports::class, 'initial_bakerreports_id');
    }

    public function bread()
    {
        return $this->belongsTo(Product::class, 'bread_id', 'id');
    }
}
