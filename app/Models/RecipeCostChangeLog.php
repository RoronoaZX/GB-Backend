<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecipeCostChangeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_cost_id',
        'branch_id',
        'user_id',
        'changed_field',
        'old_value',
        'new_value',
        'reason',
    ];

    public function recipeCost()
    {
        return $this->belongsTo(RecipeCost::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->with('employee');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
