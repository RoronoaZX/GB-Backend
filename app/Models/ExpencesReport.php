<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpencesReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_report_id',
        'user_id',
        'branch_id',
        'name',
        'amount',
        'description',
        'category',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function salesReports()
    {
        return $this->belongsTo(SalesReports::class, 'sales_report_id');
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
