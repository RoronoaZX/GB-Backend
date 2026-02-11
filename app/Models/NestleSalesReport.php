<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NestleSalesReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'user_id',
        'product_id',
        'sales_report_id',
        'beginnings',
        'remaining',
        'price',
        'sold',
        'out',
        'sales',
        'added_stocks',
        'status',
        'handled_by',
        'reason',
        'handled_at'
    ];

    public function salesReports()
    {
        return $this->belongsTo(SalesReports::class, 'sales_report_id');
    }

    public function nestle()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function handledBy()
    {
        return $this->belongsTo(Employee::class, 'handled_by', 'id');
    }
}
