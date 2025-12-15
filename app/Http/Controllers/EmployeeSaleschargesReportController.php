<?php

namespace App\Http\Controllers;

use App\Models\EmployeeSaleschargesReport;
use Carbon\Carbon;
use Database\Seeders\EmployeeTableSeeder;
use Illuminate\Http\Request;

class EmployeeSaleschargesReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }


    public function fetchEmployeeChargesPerCutOff($from, $to, $employee_id)
    {
        try {
            // parse incoming date strings like "May 26, 2025"
            $fromDate = Carbon::parse($from)->startOfDay();
            $toDate = Carbon::parse($to)->endOfDay();

            // Get employee charges with related products and product info
            $employeeSalesCharges = EmployeeSaleschargesReport::with(['employee', 'salesReport.branch'])
                ->where('employee_id', $employee_id)
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->get();

            return response()->json([
                'message' => 'Employee sales charges report fetch successfully.',
                'sales_charge' => $employeeSalesCharges
            ], 201);
        }catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch employee charges',
                'message' => $e-> getMessage()
            ], 500);
        }
    }

    public function updateCharges(Request $request, $id)
    {
        $salesReports = EmployeeSaleschargesReport::find($id);
        $salesReports->charge_amount = $request->charge_amount;
        $salesReports->save();

        return response()->json(['message' => 'Employee charges updated successfully.'], 200 );
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(EmployeeSaleschargesReport $employeeSaleschargesReport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EmployeeSaleschargesReport $employeeSaleschargesReport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EmployeeSaleschargesReport $employeeSaleschargesReport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EmployeeSaleschargesReport $employeeSaleschargesReport)
    {
        //
    }
}
