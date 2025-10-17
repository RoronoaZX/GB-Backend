<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'rm_delivery_id',
        'supplier_name',
        'status'
    ];


    public function supplierIngredients()
    {
        return $this->hasMany(SupplierIngredient::class, 'supplier_record_id');
    }
}
