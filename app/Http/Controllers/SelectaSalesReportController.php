<?php

namespace App\Http\Controllers;

use App\Models\EmployeeSaleschargesReport;
use App\Models\HistoryLog;
use App\Models\SalesReports;
use App\Models\SelectaSalesReport;
use Illuminate\Http\Request;

class SelectaSalesReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    private function updateSelectaField(
        Request $request,
        int $id,
        string $field,
        string $successMessage
    ) {
        $validated = $request->validate([
            $field               => 'required|integer',
            'sales_report_id'    => 'required|integer',
            'charges_amount'     => 'required|numeric',
            'over_amount'        => 'required|numeric',
        ]);

        // Update selecta field
        $selecta = SelectaSalesReport::findOrFail($id);
        $selecta->$field = $validated[$field];
        $selecta->save();

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
            'message'    => $successMessage,
            $field       => $selecta
        ]);
    }

    /**
     * ==============================
     * HELPER METHODS
     * ==============================
     */
    private function updateSalesReportAmounts($salesReportId, $charges, $over)
    {
        SalesReports::where('id', $salesReportId)->update([
            'charges_amount'     => $charges,
            'over_total'         => $over
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
     * ==============================
     * UPDATE ENDPOINTS
     * ==============================
     */
    public function updatePrice(Request $request, $id)
    {
        return $this->updateSelectaField(
            $request,
            $id,
            'price',
            'Price updated successfully'
        );
    }

    public function updatedBeginnings(Request $request, $id)
    {
        return $this->updateSelectaField(
            $request,
            $id,
            'beginnings',
            'Beginnings updated successfully'
        );
    }

    public function updatedRemaining(Request $request, $id)
    {
        return $this->updateSelectaField(
            $request,
            $id,
            'remaining',
            'Remaining updated successfully'
        );
    }

    public function updatedSelectaOut(Request $request, $id)
    {
        return $this->updateSelectaField(
            $request,
            $id,
            'out',
            'Out updated successfully'
        );
    }

    public function updatedAddedStocks(Request $request, $id)
    {
        return $this->updateSelectaField(
            $request,
            $id,
            'added_stocks',
            'Added stocks updated successfully'
        );
    }

    /**
     * ==============================
     * CREATE PRODUCTION
     * ==============================
     */
    public function addingSelectaProduction(Request $request)
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

        $selecta = SelectaSalesReport::create($validated);

        // IMPORTANT: Load relationships
        $selecta->load('selecta', 'handledBy');

        return response()->json([
            'message' => 'Selecta Production added successfully',
            'data'    => $selecta
        ]);
    }
}
