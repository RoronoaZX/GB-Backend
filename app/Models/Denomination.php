<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Denomination extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_report_id',
        'oneThousandBills',
        'fiveHundredBills',
        'twoHundredBills',
        'oneHundredBills',
        'fiftyBills',
        'twentyBills',
        'twentyCoins',
        'tenCoins',
        'fiveCoins',
        'oneCoins',
        'twentyFiveCents',
    ];

    public function salesReports()
    {
        return $this->belongsTo(SalesReports::class, 'sales_report_id');
    }
}
