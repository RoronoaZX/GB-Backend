<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CakeIngredientReports extends Model
{
    use HasFactory;

    protected $fillable = [
        'cake_reports_id',
        'branch_raw_materials_reports_id',
        'quantity',
        'unit',
    ];

    public function cakeReports()
    {
        return $this->belongsTo(CakeReport::class);
        // , 'cake_reports_id'
    }

    public function branchRawMaterialsReports()
    {
        return $this->belongsTo(BranchRawMaterialsReport::class)
                    ->with('ingredients');
    }

}
