<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherAddedStocks extends Model
{
    use HasFactory;

    protected $fillable = [
        'other_stocks_report_id',
        'product_id',
        'price',
        'added_stocks',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function otherStocksReport()
    {
        return $this->belongsTo(OtherStocksReport::class, 'other_stocks_report_id');
    }
}
