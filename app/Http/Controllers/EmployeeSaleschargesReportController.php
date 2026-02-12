<?php

namespace App\Http\Controllers;

use App\Models\EmployeeSaleschargesReport;
use Carbon\Carbon;
use Database\Seeders\EmployeeTableSeeder;
use Illuminate\Http\Request;

class EmployeeSaleschargesReportController extends Controller
{

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
                'message'        => 'Employee sales charges report fetch successfully.',
                'sales_charge'   => $employeeSalesCharges
            ], 201);
        }catch (\Exception $e) {
            return response()->json([
                'error'      => 'Failed to fetch employee charges',
                'message'    => $e-> getMessage()
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

}
