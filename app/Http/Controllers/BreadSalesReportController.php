<?php

namespace App\Http\Controllers;

use App\Models\BreadSalesReport;
use App\Models\EmployeeSaleschargesReport;
use App\Models\SalesReports;
use Illuminate\Http\Request;
use App\Services\HistoryLogService;
use PhpParser\Builder\Function_;
use PHPUnit\Framework\MockObject\ReturnValueNotConfiguredException;

class BreadSalesReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    private function updateBreadField(
        Request $request,
        int $id,
        string $field,
        string $successMessage,
        ) {
            $validated = $request->validate([
                $field               => 'required|integer',
                'sales_report_id'    => 'required|integer',
                'charges_amount'     => 'required|numeric',
                'over_amount'        => 'required|numeric',
            ]);

            // Update bread field
            $bread = BreadSalesReport::findOrFail($id);
            $bread->$field = $validated[$field];
            $bread->save();

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
                $field => $bread
            ]);

        }

        /**
         * =============================
         * HELPER METHODS
         * =============================
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
            HistoryLogService::log($request->only([
                'report_id', 'name', 'original_data', 'updated_data',
                'updated_field', 'designation', 'designation_type',
                'action', 'type_of_report', 'user_id'
            ]));
        }

        /**
         * =============================
         * UPDATE METHODS
         * =============================
         */

        public function updatePrice(Request $request, $id)
        {
            return $this->updateBreadField(
                $request,
                $id,
                'price',
                'Price updated successfully'
            );
        }

        public function updateBeginnings(Request $request, $id)
        {
            return $this->updateBreadField(
                $request,
                $id,
                'beginnings',
                'Beginnings updated successfully'
            );
        }

        public function updatedNewProduction(Request $request, $id)
        {
            return $this->updateBreadField(
                $request,
                $id,
                'new_production',
                'New production updated successfully'
            );
        }

        public function updateRemaining(Request $request, $id)
        {
            return $this->updateBreadField(
                $request,
                $id,
                'remaining',
                'Remaining updated successfully'
            );
        }

        public function updateBreadOut(Request $request, $id)
        {
            $response = $this->updateBreadField(
                $request,
                $id,
                'bread_out',
                'Bread out updated successfully'
            );

            $bread = BreadSalesReport::findOrFail($id);
            if ($bread->bread_out > 0) {
                \App\Models\BreadOut::updateOrCreate(
                    ['bread_sales_report_id' => $bread->id],
                    [
                        'branch_id' => $bread->branch_id,
                        'product_id' => $bread->product_id,
                        'quantity' => $bread->bread_out,
                        'status' => 'pending'
                    ]
                );
            }

            return $response;
        }

        public function addingBreadProduction(Request $request)
        {
            $validated = $request->validate([
                'user_id'           => 'required|exists:users,id',
                'branch_id'         => 'required|exists:branches,id',
                'sales_report_id'   => 'required|exists:sales_reports,id',
                'product_id'        => 'required|exists:products,id',
                'handled_by'        => 'required|exists:employees,id',
                'product_name'      => 'required|string',
                'price'             => 'required|numeric',
                'beginnings'        => 'nullable|numeric',
                'remaining'         => 'nullable|numeric',
                'new_production'    => 'nullable|numeric',
                'bread_out'         => 'nullable|numeric',
                'bread_sold'        => 'nullable|numeric',
                'reason'            => 'nullable|string',
                'status'            => 'nullable|string',
                'total'             => 'nullable|numeric',
                'sales'             => 'nullable|numeric',
            ]);

            $breadProduction = BreadSalesReport::create($validated);

            // AUTOMATION: Create BreadOut entry for repurposing if bread_out > 0
            if ($breadProduction->bread_out > 0) {
                \App\Models\BreadOut::create([
                    'bread_sales_report_id' => $breadProduction->id,
                    'branch_id' => $breadProduction->branch_id,
                    'product_id' => $breadProduction->product_id,
                    'quantity' => $breadProduction->bread_out,
                    'status' => 'pending'
                ]);
            }

            // IMPORTANT: Load realtionships
            $breadProduction->load('bread', 'handledBy');

            return response()->json([
                'message' => 'Bread Production added successfully',
                'data'    => $breadProduction
            ]);
        }

}
