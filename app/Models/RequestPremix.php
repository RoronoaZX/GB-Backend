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
        return $this->belongsTo(BranchPremix::class, 'branch_premix_id')
                    ->with('branch_recipe.branch', 'branch_recipe.ingredientGroups.ingredient');
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
    public function history()
    {
        return $this->hasMany(RequestPremixesHistory::class, 'request_premixes_id');
    }
    public function confirmedHistory()
    {
        return $this->hasMany(RequestPremixesHistory::class, 'request_premixes_id')
                    ->where('status', 'confirmed');
    }
    public function declinedHistory()
    {
        return $this->hasMany(RequestPremixesHistory::class, 'request_premixes_id')
                    ->where('status', 'declined');
    }
    public function historyWithEmployee()
    {
        return $this->hasMany(RequestPremixesHistory::class, 'request_premixes_id')
                    ->with('employee');
    }

}
