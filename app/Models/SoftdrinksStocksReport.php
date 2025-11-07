<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoftdrinksStocksReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'branches_id',
        'employee_id',
        'status',
        'remark',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branches_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function softdrinksAddedStocks()
    {
        return $this->hasMany(SoftdrinksAddedStocks::class, 'softdrinks_stocks_report_id')
                    ->with('product');
    }
}
