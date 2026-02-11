<?php

namespace App\Http\Controllers;

use App\Models\EmployeeSaleschargesReport;
use App\Models\HistoryLog;
use App\Models\NestleSalesReport;
use App\Models\SalesReports;
use GuzzleHttp\Promise\FulfilledPromise;
use Illuminate\Http\Request;

class NestleSalesReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    private function updateNestleField(
        Request $request,
        int $id,
        string $field,
        string $successMessage
    ) {
        $validated = $request->validate([
            $field => 'required|integer',
            'sales_report_id' => 'required|integer',
            'charges_amount' => 'required|numeric',
            'over_amount' => 'required|numeric',
        ]);

        // Update nestle field
        $nestle = NestleSalesReport::findOrFail($id);
        $nestle->$field = $validated[$field];
        $nestle->save();

        // Update sales report totals
        $this->updateSalesReportAmounts(
            $validated['sales_report_id'],
            $validated['charges_amount'],
            $validated['over_amount']
        );

        // Recalculate employee charges
        $this->recalculateEmployeeCharges(
            $validated['sales_report_id'],
            $validated['charges_amount']
        );

        // Log history
        $this->createHistoryLog($request);

        return response()->json([
            'message' => $successMessage,
            $field => $validated[$field]
        ]);
    }

    /**
     * ================================
     * HELPER METHODS
     * ================================
     */

    private function updateSalesReportAmounts($salesReportId, $charges, $over)
    {
        SalesReports::where('id', $salesReportId)->update([
            'charges_amount' => $charges,
            'over_total' => $over
        ]);
    }

    private function recalculateEmployeeCharges($salesReportId, $chargesAmount)
    {
        $employees = EmployeeSaleschargesReport::where(
            'sales_report_id',
            $salesReportId
        )->get();

        if ($employees->isEmpty()) return;

        $perEmployee = round($chargesAmount / $employees->count(), 2);

        foreach ($employees as $employee) {
            $employee->update([
                'charge_amount' => $perEmployee
            ]);
        }
    }

    private function createHistoryLog(Request $request)
    {
        HistoryLog::create($request->only([
            'report_id',
            'name',
            'original_data',
            'updated_data',
            'updated_field',
            'designation',
            'designation_type',
            'action',
            'type_of_report',
            'user_id',
        ]));
    }

    /**
     * ===============================
     * UPDATE ENDPOINTS
     * ===============================
     */

    public function updatePrice(Request $request, $id)
    {
        return $this->updateNestleField(
            $request,
            $id,
            'price',
            'Price updated successfully'
        );
    }

    public function updatedBeginnings(Request $request, $id)
    {
        return $this->updateNestleField(
            $request,
            $id,
            'beginnings',
            'Beginnings updated successfully'
        );
    }

    public function updatedRemaining(Request $request, $id)
    {
        return $this->updateNestleField(
            $request,
            $id,
            'remaining',
            'Remaining updated successfully'
        );
    }

    public function updatedNestleOut(Request $request, $id)
    {
        return $this->updateNestleField(
            $request,
            $id,
            'out',
            'Out updated successfully'
        );
    }

    public function udpatedAddedStocks(Request $request, $id)
    {
        return $this->updateNestleField(
            $request,
            $id,
            'added_stocks',
            'Added stocks updated successfully'
        );
    }

    /**
     * ===============================
     * CREATE PRODUCTION
     * ===============================
     */

    public function addingNestleProduction(Request $request)
    {
        $validated = $request->validate([
            'user_id'            => 'required|exists:users,id',
            'branch_id'          => 'required|exists:branches,id',
            'sales_report_id'    => 'required|exists:sales_reports,id',
            'product_id'         => 'required|exists:products,id',
            'handled_by'         => 'required|exists:employees,id',
            'product_name'       => 'required|string',
            'price'              => 'required|numeric',
            'beginnings'         => 'nullable|numeric',
            'remaining'          => 'nullable|numeric',
            'added_stocks'       => 'nullable|numeric',
            'out'                => 'nullable|numeric',
            'sold'               => 'nullable|numeric',
            'reason'             => 'nullable|string',
            'status'             => 'nullable|string',
            'total'              => 'nullable|numeric',
            'sales'              => 'nullable|numeric',
        ]);

        $nestle = NestleSalesReport::create($validated);

        // IMPORTANT: Load relationships
        $nestle->load('nestle');

        return response()->json([
            'message' => 'Nestle Production added successfully',
            'data'    => $nestle
        ]);
    }
}
