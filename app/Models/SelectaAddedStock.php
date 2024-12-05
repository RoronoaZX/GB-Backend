<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SelectaAddedStock extends Model
{
    use HasFactory;
    protected $fillable = [
        'selecta_stocks_report_id',
        'product_id',
        'price',
        'added_stocks',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function selectaStocksReport()
    {
        return $this->belongsTo(SelectaStocksReport::class, 'selecta_stocks_report_id');
    }

}
