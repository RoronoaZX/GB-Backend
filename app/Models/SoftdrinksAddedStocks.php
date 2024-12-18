<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoftdrinksAddedStocks extends Model
{
    use HasFactory;

    protected $fillable = [
        'softdrinks_stocks_report_id',
        'product_id',
        'price',
        'added_stocks',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function softdrinksStocksReport()
    {
        return $this->belongsTo(SoftdrinksStocksReport::class, 'softdrinks_stocks_report_id');
    }
}
