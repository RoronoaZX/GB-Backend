<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BirReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'receipt_no',
        'tin_no',
        'description',
        'address',
        'amount',
        'category',
        'created_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
