<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'user_id',
        'baker_report_id',
        'sales_report_id',
        'branch_raw_materials_report_id',
        'expences_report_id'
    ];
}
