<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CakeSalesReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_report_id',
        'cake_report_id',
    ];


    /**
     * Relationship: Get the related Sales Report
     */
    public function salesReport()
    {
        return $this->belongsTo(SalesReports::class, 'sales_report_id');
    }

    /**
     * Relationship: Get the related Cake
     */
    public function cakeReport()
    {
        return $this->belongsTo(CakeReport::class, 'cake_report_id')->with('user');
    }
}
