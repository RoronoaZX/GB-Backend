<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreadSalesReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'user_id',
        'product_id',
        'sales_report_id',
        'beginnings',
        'new_production',
        'remaining',
        'price',
        'bread_sold',
        'bread_out',
        'total',
        'bread_over',
        'sales',

    ];

    public function salesReports()
    {
        return $this->belongsTo(SalesReports::class, 'sales_report_id');
    }

    public function bread()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
