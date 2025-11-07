<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherStocksReport extends Model
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

    public function otherAddedStock()
    {
        return $this->hasMany(OtherAddedStocks::class, 'other_stocks_report_id')
                    ->with('product');
    }

}
