<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseScalingReport extends Model
{
    use HasFactory;

    public $fillable = [
        'warehouse_id',
        'employee_id',
        'recipe_id',
        'branch_id',
        'status',
        'remarks'
    ];

}
