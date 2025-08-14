<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayslipBakerReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_incentive_id',
        'branch_id',
        'user_id',
        'initial_bakerreport_id',
        'branch_recipe_id',
        'recipe_category',
        'status',
        'kilo',
        'short',
        'over',
        'target',
        'actual_target',
        'created_at'
    ];


}
