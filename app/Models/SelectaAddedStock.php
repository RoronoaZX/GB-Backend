<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SelectaAddedStock extends Model
{
    use HasFactory;
    protected $fillable = [
        'branches_id',
        'product_id',
        'price',
        'added_stocks',
        'status',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branches_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
