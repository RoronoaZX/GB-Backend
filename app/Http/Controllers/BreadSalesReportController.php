<?php

namespace App\Http\Controllers;

use App\Models\BreadSalesReport;
use App\Models\EmployeeSaleschargesReport;
use App\Models\HistoryLog;
use App\Models\SalesReports;
use Illuminate\Http\Request;
use PhpParser\Builder\Function_;
use PHPUnit\Framework\MockObject\ReturnValueNotConfiguredException;

class BreadSalesReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    // public function updatePrice(Request $request, $id)
    // {
    //     $validatedData = $request->validate([
    //         'price'              => 'required|integer',
    //         'sales_report_id'    => 'required|integer',
    //         'charges_amount'     => 'required|numeric',
    //         'over_amount'        => 'required|numeric'
    //     ]);

    //     $breadSalesReport            = BreadSalesReport::findorFail($id);
    //     $breadSalesReport->price     = $validatedData['price'];
    //     $breadSalesReport->save();

    //     $salesReport = SalesReports::find($validatedData['sales_report_id']);
    //     $salesReport->charges_amount = $validatedData['charges_amount'];
    //     $salesReport->over_total = $validatedData['over_amount'];
    //     $salesReport->save();

    //     $employeeCharges = EmployeeSaleschargesReport::where('sales_report_id', $validatedData['sales_report_id'])->get();

    //     $employeeCount = $employeeCharges->count();

    //     if ($employeeCount > 0) {
    //         $chargesPerEmployee = round($validatedData['charges_amount'] / $employeeCount, 2);

    //         foreach ($employeeCharges as $employeeCharge) {
    //             $employeeCharge->charge_amount = $chargesPerEmployee;
    //             $employeeCharge->save();
    //         }
    //     }

    //     HistoryLog::create([
    //         'report_id'          => $request->input('report_id'),
    //         'name'               => $request->input('name'),
    //         'original_data'      => $request->input('original_data'),
    //         'updated_data'       => $request->input('updated_data'),
    //         'updated_field'      => $request->input('updated_field'),
    //         'designation'        => $request->input('designation'),
    //         'designation_type'   => $request->input('designation_type'),
    //         'action'             => $request->input('action'),
    //         'type_of_report'     => $request->input('type_of_report'),
    //         'user_id'            => $request->input('user_id'),
    //     ]);

    //     return response()->json(['message' => 'Price updated successfully', 'price' => $breadSalesReport]);
    // }
    // public function updateBeginnings(Request $request, $id)
    // {
    //     $validatedData = $request->validate([
    //         'beginnings'         => 'required|integer',
    //         'sales_report_id'    => 'required|integer',
    //         'charges_amount'     => 'required|numeric',
    //         'over_amount'        => 'required|numeric'
    //     ]);

    //     $breadSalesReport                = BreadSalesReport::findorFail($id);
    //     $breadSalesReport->beginnings    = $validatedData['beginnings'];
    //     $breadSalesReport->save();

    //     $salesReport                 = SalesReports::find($validatedData['sales_report_id']);
    //     $salesReport->charges_amount = $validatedData['charges_amount'];
    //     $salesReport->over_total     = $validatedData['over_amount'];
    //     $salesReport->save();

    //     $employeeCharges = EmployeeSaleschargesReport::where('sales_report_id', $validatedData['sales_report_id'])->get();

    //     $employeeCount = $employeeCharges->count();

    //     if($employeeCount > 0) {
    //         $chargePerEmployee = round($validatedData['charges_amount'] / $employeeCount, 2);

    //         foreach ($employeeCharges as $employeeCharge) {
    //             $employeeCharge->charge_amount = $chargePerEmployee;
    //             $employeeCharge->save();
    //         }
    //     }

    //     HistoryLog::create([
    //         'report_id'          => $request->input('report_id'),
    //         'name'               => $request->input('name'),
    //         'original_data'      => $request->input('original_data'),
    //         'updated_data'       => $request->input('updated_data'),
    //         'updated_field'      => $request->input('updated_field'),
    //         'designation'        => $request->input('designation'),
    //         'designation_type'   => $request->input('designation_type'),
    //         'action'             => $request->input('action'),
    //         'type_of_report'     => $request->input('type_of_report'),
    //         'user_id'            => $request->input('user_id'),
    //     ]);

    //     return response()->json([
    //         'message' => 'Beginnings updated successfully',
    //         'beginnings' => $breadSalesReport
    //     ]);
    // }
    // public function updatedNewProduction(Request $request, $id)
    // {
    //     $validatedData = $request->validate([
    //         'new_production'     => 'required|integer',
    //         'sales_report_id'    => 'required|integer',
    //         'charges_amount'     => 'required|numeric',
    //         'over_amount'        => 'required|numeric'
    //     ]);

    //     $breadSalesReport                    = BreadSalesReport::findorFail($id);
    //     $breadSalesReport->new_production    = $validatedData['new_production'];
    //     $breadSalesReport->save();

    //     $salesReport = SalesReports::find($validatedData['sales_report_id']);
    //     $salesReport->charges_amount = $validatedData['charges_amount'];
    //     $salesReport->over_total = $validatedData['over_amount'];
    //     $salesReport->save();

    //     $employeeCharges = EmployeeSaleschargesReport::where('sales_report_id', $validatedData['sales_report_id'])->get();

    //     $employeeCount = $employeeCharges->count();

    //     if ($employeeCount > 0) {
    //         $chargePerEmployee = round($validatedData['charges_amount'] / $employeeCount, 2);

    //         foreach ($employeeCharges as $employeeCharge) {
    //             $employeeCharge->charge_amount = $chargePerEmployee;
    //             $employeeCharge->save();
    //         }
    //     }

    //     HistoryLog::create([
    //         'report_id'          => $request->input('report_id'),
    //         'name'               => $request->input('name'),
    //         'original_data'      => $request->input('original_data'),
    //         'updated_data'       => $request->input('updated_data'),
    //         'updated_field'      => $request->input('updated_field'),
    //         'designation'        => $request->input('designation'),
    //         'designation_type'   => $request->input('designation_type'),
    //         'action'             => $request->input('action'),
    //         'type_of_report'     => $request->input('type_of_report'),
    //         'user_id'            => $request->input('user_id'),
    //     ]);

    //     return response()->json([
    //         'message'            => 'Beginnings updated successfully',
    //         'new_production'     => $breadSalesReport
    //     ]);
    // }
    // public function updateRemaining(Request $request, $id)
    // {
    //     $validatedData = $request->validate([
    //         'remaining'          => 'required|integer',
    //         'sales_report_id'    => 'required|integer',
    //         'charges_amount'     => 'required|numeric',
    //         'over_amount'        => 'required|numeric'
    //     ]);

    //     $breadSalesReport                = BreadSalesReport::findorFail($id);
    //     $breadSalesReport->remaining     = $validatedData['remaining'];
    //     $breadSalesReport->save();

    //     $salesReport                 = SalesReports::find($validatedData['sales_report_id']);
    //     $salesReport->charges_amount = $validatedData['charges_amount'];
    //     $salesReport->over_total     = $validatedData['over_amount'];
    //     $salesReport->save();

    //     $employeeCharges = EmployeeSaleschargesReport::where('sales_report_id', $validatedData['sales_report_id'])->get();

    //     $employeeCount = $employeeCharges->count();

    //     if ($employeeCount > 0) {
    //         $chargePerEmployee = round($validatedData['charges_amount'] / $employeeCount, 2);

    //         foreach ($employeeCharges as $employeeCharge) {
    //             $employeeCharge->charge_amount = $chargePerEmployee;
    //             $employeeCharge->save();
    //         }
    //     }

    //     HistoryLog::create([
    //         'report_id'          => $request->input('report_id'),
    //         'name'               => $request->input('name'),
    //         'original_data'      => $request->input('original_data'),
    //         'updated_data'       => $request->input('updated_data'),
    //         'updated_field'      => $request->input('updated_field'),
    //         'designation'        => $request->input('designation'),
    //         'designation_type'   => $request->input('designation_type'),
    //         'action'             => $request->input('action'),
    //         'type_of_report'     => $request->input('type_of_report'),
    //         'user_id'            => $request->input('user_id'),
    //     ]);

    //     return response()->json([
    //         'message'    => 'Remaining updated successfully',
    //         'remaining'  => $breadSalesReport
    //     ]);
    // }
    // public function updateBreadOut(Request $request, $id)
    // {
    //     $validatedData = $request->validate([
    //         'bread_out'          => 'required|integer',
    //         'sales_report_id'    => 'required|integer',
    //         'charges_amount'     => 'required|numeric',
    //         'over_amount'        => 'required|numeric'
    //     ]);

    //     $breadSalesReport                = BreadSalesReport::findorFail($id);
    //     $breadSalesReport->bread_out     = $validatedData['bread_out'];
    //     $breadSalesReport->save();

    //     $salesReport = SalesReports::find($validatedData['sales_report_id']);
    //     $salesReport->charges_amount = $validatedData['charges_amount'];
    //     $salesReport->over_total = $validatedData['over_amount'];
    //     $salesReport->save();

    //     $employeeCharges = EmployeeSaleschargesReport::where('sales_report_id', $validatedData['sales_report_id'])->get();

    //     $employeeCount = $employeeCharges->count();

    //     if ($employeeCount > 0) {
    //         $chargePerEmployee = round($validatedData['charges_amount']  / $employeeCount, 2);

    //         foreach ($employeeCharges as $employeeCharge) {
    //             $employeeCharge->charge_amount = $chargePerEmployee;
    //             $employeeCharge->save();
    //         }
    //     }

    //     HistoryLog::create([
    //         'report_id'          => $request->input('report_id'),
    //         'name'               => $request->input('name'),
    //         'original_data'      => $request->input('original_data'),
    //         'updated_data'       => $request->input('updated_data'),
    //         'updated_field'      => $request->input('updated_field'),
    //         'designation'        => $request->input('designation'),
    //         'designation_type'   => $request->input('designation_type'),
    //         'action'             => $request->input('action'),
    //         'type_of_report'     => $request->input('type_of_report'),
    //         'user_id'            => $request->input('user_id'),
    //     ]);

    //     return response()->json([
    //         'message'    => 'Bread out updated successfully',
    //         'bread_out'  => $breadSalesReport
    //     ]);
    // }

    // public function addingBreadProduction(Request $request)
    // {
    //     $validated = $request->validate([
    //         'user_id'            => 'required|exists:users,id',
    //         'branch_id'          => 'required|exists:branches,id',
    //         'sales_report_id'    => 'required|exists:sales_reports,id',
    //         'product_id'         => 'required|exists:products,id',
    //         'product_name'       => 'required|string',
    //         'price'              => 'required|numeric',
    //         'beginnings'         => 'numeric',
    //         'remaining'          => 'numeric',
    //         'new_production'     => 'numeric',
    //         'bread_out'          => 'numeric',
    //         'bread_sold'         => 'numeric',
    //         'total'              => 'numeric',
    //         'sales'              => 'numeric',
    //     ]);

    //     $breadProduction = BreadSalesReport::create($validated);

    //     return response()->json([
    //         'success'    => true,
    //         'message'    => 'Bread production recorded successfully!',
    //         'data'       => $breadProduction,
    //     ]);
    // }

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
            return $this->updateBreadField(
                $request,
                $id,
                'bread_out',
                'Bread out updated successfully'
            );
        }

        public function addingBreadProduction(Request $request)
        {
            $validated = $request->validate([
                'user_id'           => 'required|exists:users,id',
                'branch_id'         => 'required|exists:branches,id',
                'sales_report_id'   => 'required|exists:sales_reports,id',
                'product_id'        => 'required|exists:products,id',
                'product_name'      => 'required|string',
                'price'             => 'required|numeric',
                'beginnings'        => 'nullable|numeric',
                'remaining'         => 'nullable|numeric',
                'new_production'    => 'nullable|numeric',
                'bread_out'         => 'nullable|numeric',
                'bread_sold'        => 'nullable|numeric',
                'total'             => 'nullable|numeric',
                'sales'             => 'nullable|numeric',
            ]);

            $breadProduction = BreadSalesReport::create($validated);

            return response()->json([
                'message' => 'Bread Production added successfully',
                'data'    => $breadProduction
            ]);
        }

}
