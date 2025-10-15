<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecipeCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_rm_stock_id',
        'branch_id',
        'recipe_id',
        'raw_material_id',
        'initial_bakerreport_id',
        'branch_recipe_id',
        'quantity_used',
        'price_per_gram',
        'total_cost',
        'status',
        'kilo'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function branchRecipe()
    {
        return $this->belongsTo(BranchRecipe::class);
    }

    public function initialBakerreport()
    {
        return $this->belongsTo(InitialBakerreports::class);
    }

    public function branchRmStock()
    {
        return $this->belongsTo(BranchRmStocks::class, 'branch_rm_stock_id');
    }
}
