<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InitialBakerreports extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'branch_recipe_id',
        'user_id',
        'recipe_category',
        'status',
        'kilo',
        'short',
        'over',
        'target',
        'actual_target',
        'remark',
        'created_at', // allow optional overriding

    ];

    protected $append = ['combined_bakers_reports'];

        public function branch()
        {
            return $this->belongsTo(Branch::class);
        }

        public function user()
        {
            return $this->belongsTo(User::class)->with('employee');
        }

        // public function recipe()
        // {
        //     return $this->belongsTo(Recipe::class);
        // }

        public function branchRecipe()
        {
            return $this->belongsTo(BranchRecipe::class)->with('recipe');
        }
        // public function branchRecipe()
        // {
        //     return $this->belongsTo(BranchRecipe::class, 'branch_recipe_id', 'id')->with('recipe');
        // }

        public function breadBakersReports()
        {
            return $this->hasMany(InitialBreadBakerreports::class, 'initial_bakerreports_id', 'id')->with('bread');
        }

        public function ingredientBakersReports()
        {
            return $this->hasMany(InitialIngredientsBakerreports::class, 'initial_bakerreports_id', 'id')->with('ingredients');
        }
        public function fillingBakersReports()
        {
            return $this->hasMany(InitialFillingBakerreports::class, 'initial_bakerreports_id', 'id')->with('bread');
        }
        public function scopePendingDoughReports($query)
        {
            return $query->where('recipe_category', 'dough')->where('status', 'pending');
        }
        public function breadBakerReports()
        {
            return $this->belongsTo(Product::class, 'bread_id', 'id');
        }
        public function breadProductionReports()
        {
            return $this->hasMany(BreadProductionReport::class, 'initial_bakerreports_id')->with('bread');
        }
        public function getCombinedBakersReportsAttribute()
        {
            // Merge breadBakersReports and fillingBakersReports
            return $this->breadBakersReports->merge($this->fillingBakersReports);
        }

}
