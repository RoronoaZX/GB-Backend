<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BakerChargesReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'user_id',
        'recipe_id',
        'baker_report_id',
        'name',
        'amount'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recipe()
    {
        return $this->belongsTo(BakerReports::class);
    }

    public function baker_report()
    {
        return $this->belongsTo(BakerReports::class);
    }
}
