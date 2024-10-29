<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InitialFillingBakerreports extends Model
{
    use HasFactory;

    protected $fillable = [
        'initial_bakerreports_id',
        'bread_id',
        'filling_production'
    ];

    public function initialBakerReports()
    {
        return $this->belongsTo(InitialBakerreports::class);
    }

    public function bread()
    {
        return $this->belongsTo(Product::class);
    }
}
