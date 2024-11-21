<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReports extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'user_id',
        'products_total_sales',
        'expenses_total',
        'denomination_total',
        'charges_amount',
        'over_total',
        'credit_total'
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->with('employee');
    }

    public function breadReports()
    {
        return $this->hasMany(BreadSalesReport::class, 'sales_report_id')->with('bread');
    }

    public function selectaReports()
    {
        return $this->hasMany(SelectaSalesReport::class, 'sales_report_id')->with('selecta');
    }

    public function softdrinksReports()
    {
        return $this->hasMany(SoftdrinksSalesReport::class, 'sales_report_id')->with('softdrinks');
    }
    public function otherProductsReports()
    {
        return $this->hasMany(OtherProducts::class, 'sales_report_id')->with('otherProducts');
    }

    public function cakeSalesReports()
    {
        return $this->hasMany(CakeSalesReport::class, 'sales_report_id')->with('cakeReport');
    }

    public function expensesReports()
    {
        return $this->hasMany(ExpencesReport::class, 'sales_report_id');
    }

    public function denominationReports()
    {
        return $this->hasMany(Denomination::class, 'sales_report_id');
    }

    public function creditReports()
    {
        return $this->hasMany(EmployeeCredits::class, 'sales_report_id')->with('creditProducts', 'creditUserId');
    }

}
