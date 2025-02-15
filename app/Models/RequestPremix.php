<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestPremix extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_premix_id',
        'warehouse_id',
        'employee_id',
        'name',
        'category',
        'status',
        'quantity',
    ];

    public function branchPremix()
    {
        return $this->belongsTo(BranchPremix::class, 'branch_premix_id');
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
