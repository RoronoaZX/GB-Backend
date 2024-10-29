<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchRawMaterialsReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'ingredients_id',
        'total_quantity'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function bakerReport()
    {
        return $this->belongsTo(InitialBakerreports::class, 'baker_report_id');
    }

    public function ingredients()
    {
        return $this->belongsTo(RawMaterial::class, 'ingredients_id');
    }
}
