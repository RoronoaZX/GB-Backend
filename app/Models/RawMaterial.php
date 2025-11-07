<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RawMaterial extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'code',
        'category',
        'unit',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting((function ($rawMaterial) {
                $rawMaterial->rawMaterialGroups()
                            ->delete();
            }
        ));
    }

    public function rawMaterialGroups()
    {
        return $this->hasMany(IngredientGroup::class, 'ingredient_id');
    }

    public function scopeRawMaterials($query)
    {
        return $query->where('category', 'ingredients');
    }

    public function scopeSearch($query, $keyword)
    {
        return $query->where('name', 'LIKE', "%{$keyword}%")
                     ->orWhere('code', 'LIKE', "%{$keyword}%")
                     ->orWhere('category', 'LIKE', "%{$keyword}%");
    }

    public function branch_rawMaterials()
    {
        return $this->hasMany(BranchRawMaterialsReport::class, 'branch_id');
    }

    public function warehouse_rawMaterials()
    {
        return $this->hasMany(WarehouseRawMaterialsReport::class, 'warehouse_id');
    }
}
