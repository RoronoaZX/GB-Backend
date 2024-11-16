<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CakeReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'user_id',
        'name',
        'layers',
        'price',
        'unit',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->with('employee');
    }

    public function cakeIngredientReports()
    {
        return $this->hasMany(CakeIngredientReports::class, 'cake_reports_id')->with('branchRawMaterialsReports');
    }

}
