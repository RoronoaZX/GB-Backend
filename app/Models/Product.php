<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Branch;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'category',
    ];


    protected static function boot()
    {
        parent::boot();

        static::deleting((function ($product){
            $product->breadGroups()
                    ->delete();
        }));
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branches_products', 'product_id', 'branch_id')
                    ->withPivot('price');
    }

    public function breadGroups()
    {
        return $this->hasMany(BreadGroup::class, 'bread_id');
    }

    public function scopeBread($query)
    {
        return $query->where('category', 'bread');
    }

    public function scopeSearch($query, $keyword)
    {
        return $query->where('name','LIKE', "%{$keyword}%")
                     ->orWhere('category', 'LIKE', "%{$keyword}%");
    }

    public function branch_products()
    {
        return $this->hasMany(BranchProduct::class, 'branches_id', 'id');
    }
}
