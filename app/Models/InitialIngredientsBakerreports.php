<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InitialIngredientsBakerreports extends Model
{
    use HasFactory;

    protected $fillable = [
        'initial_bakerreports_id',
        'ingredients_id',
        'quantity',
        'unit',
    ];

    public function initialBakerReports()
    {
        return $this->belongsTo(InitialBakerreports::class);
    }

    public function ingredients()
    {
        return $this->belongsTo(RawMaterial::class);
    }
}
