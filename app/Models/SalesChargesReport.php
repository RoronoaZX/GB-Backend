<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesChargesReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_report_id',
        'branch_id',
        'user_id',
        'name',
        'amount',

    ];
}
