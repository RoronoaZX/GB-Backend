<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_id',
        'uuid',
        'name',
        'model',
        'os_version',
        'designation',
    ];

     // Define separate relationships for Branch and Warehouse
     public function branch()
     {
         return $this->belongsTo(Branch::class, 'reference_id');
     }

     public function warehouse()
     {
         return $this->belongsTo(Warehouse::class, 'reference_id');
     }

     // Create an accessor method to get the correct reference dynamically
     public function getReferenceAttribute()
     {
         return $this->designation === 'branch' ? $this->branch : $this->warehouse;
     }
}
