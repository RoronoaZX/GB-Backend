<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoftdrinksSalesReport extends Model
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
    ];

    public function salesReports()
    {
        return $this->belongsTo(SalesReports::class, 'sales_report_id');
    }

    public function softdrinks()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
