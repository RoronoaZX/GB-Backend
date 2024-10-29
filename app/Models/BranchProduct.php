<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchProduct extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'branches_id',
        'category',
        'price',
        'beginnings',
        'new_production',
        'total_quantity',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branches_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function branch_products()
    {
        return $this->belongsToMany(Product::class, 'branches_products', 'branches_id', 'product_id')->withPivot('price');
    }
}
