<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierIngredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_record_id',
        'raw_material_id',
        'quantity',
        'price_per_gram',
        'price_per_unit',
        'pcs',
        'kilo',
        'gram',
        'category'
    ];

    public function supplierRecord()
    {
        return $this->belongsTo(SupplierRecord::class, 'supplier_record_id');
    }

    public function rawMaterials()
    {
        return $this->belongsTo(RawMaterial::class, 'raw_material_id');
    }
}
