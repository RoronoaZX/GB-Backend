<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniformPants extends Model
{
    use HasFactory;

    protected $fillable = [
        'uniform_id',
        'size',
        'pcs',
        'price'
    ];

    public function uniform()
    {
        return $this->belongsTo(Uniform::class);
    }
}
