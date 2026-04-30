<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreadOut extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'product_id',
        'quantity',
        'status',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function repurposeLogs()
    {
        return $this->hasMany(RepurposeLog::class);
    }

    public function spoilageLogs()
    {
        return $this->hasMany(SpoilageLog::class);
    }
}
