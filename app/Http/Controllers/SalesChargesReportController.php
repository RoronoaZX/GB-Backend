<?php

namespace App\Http\Controllers;

use App\Models\SalesChargesReport;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class SalesChargesReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Validation incoming DATA
        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id'
        ]);

        // Fetch the employee using validated ID
        $employee_charges = SalesChargesReport::find($validated['employee_id']);

        return response()->json([
            'success'            => true,
            'employee_charges'   => $employee_charges
        ]);
    }

    public function fetchEmployeeChargesPerCutOff($from, $to, $employee_id)
    {
        try {
            // Parse incoming date strings like "May 26, 2025"
            $fromDate = Carbon::parse($from)->startOfDay();
            $toDate   = Carbon::parse($to)->endOfDay();

            // Get employee credits with related products and product info
            $employeeSalesCharges = SalesChargesReport::with(['employee'])
                                    ->where('employee_id', $employee_id)
                                    ->whereBetween('created_at', [$fromDate, $toDate])
                                    ->get();
            return response()->json([
                'message'        => 'Employee sales charges report fetlch successfully.',
                'sales_charge'   => $employeeSalesCharges
            ], 201);
        }catch (\Exception $e) {
            return  response()->json([
                'error'      => 'Failed to fetch employee charges',
                'message'    => $e->getMessage()
            ], 500);
        }
    }

}
