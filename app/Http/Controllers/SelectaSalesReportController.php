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

    //     $selectaSalesReport          = SelectaSalesReport::findorFail($id);
    //     $selectaSalesReport->price   = $validatedData['price'];
    //     $selectaSalesReport->save();

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
    //         'user_id'            => $request->input('user_id')
    //     ]);

    //     return response()->json([
    //         'message' => 'Price updated successfully',
    //         'price' => $selectaSalesReport
    //     ]);
    // }
    // public function updatedBeginnings(Request $request, $id)
    // {
    //     $validatedData = $request->validate([
    //         'beginnings'         => 'required|integer',
    //         'sales_report_id'    => 'required|integer',
    //         'charges_amount'     => 'required|numeric',
    //         'over_amount'        => 'required|numeric'
    //     ]);

    //     $selectaSalesReport              = SelectaSalesReport::findorFail($id);
    //     $selectaSalesReport->beginnings  = $validatedData['beginnings'];
    //     $selectaSalesReport->save();

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

    //      HistoryLog::create([
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
    //         'message'        => 'beginnings updated successfully',
    //         'beginnings'     => $selectaSalesReport
    //     ]);
    // }
    // public function updatedRemaining(Request $request, $id)
    // {
    //     $validatedData = $request->validate([
    //         'remaining'          => 'required|integer',
    //         'sales_report_id'    => 'required|integer',
    //         'charges_amount'     => 'required|numeric',
    //         'over_amount'        => 'required|numeric'
    //     ]);

    //     $selectaSalesReport              = SelectaSalesReport::findorFail($id);
    //     $selectaSalesReport->remaining   = $validatedData['remaining'];
    //     $selectaSalesReport->save();

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
    //         'message'    => 'remaining updated successfully',
    //         'remaining'  => $selectaSalesReport
    //     ]);
    // }
    // public function updatedSelectaOut(Request $request, $id)
    // {
    //     $validatedData = $request->validate([
    //         'out'                => 'required|integer',
    //         'sales_report_id'    => 'required|integer',
    //         'charges_amount'     => 'required|numeric',
    //         'over_amount'        => 'required|numeric'
    //     ]);

    //     $selectaSalesReport          = SelectaSalesReport::findorFail($id);
    //     $selectaSalesReport->out     = $validatedData['out'];
    //     $selectaSalesReport->save();

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
    //         'message'    => 'out updated successfully',
    //         'out'        => $selectaSalesReport
    //     ]);
    // }
    // public function updatedAddedStocks(Request $request, $id)
    // {
    //     $validatedData = $request->validate([
    //         'added_stocks'       => 'required|integer',
    //         'sales_report_id'    => 'required|integer',
    //         'charges_amount'     => 'required|numeric',
    //         'over_amount'        => 'required|numeric'
    //     ]);

    //     $selectaSalesReport                  = SelectaSalesReport::findorFail($id);
    //     $selectaSalesReport->added_stocks    = $validatedData['added_stocks'];
    //     $selectaSalesReport->save();

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
    //         'message' => 'added_stocks updated successfully',
    //         'added_stocks' => $selectaSalesReport
    //     ]);
    // }

    // public function addingSelectaProduction(Request $request)
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
    //         'added_stocks'       => 'numeric',
    //         'out'                => 'numeric',
    //         'sold'               => 'numeric',
    //         'total'              => 'numeric',
    //         'sales'              => 'numeric',
    //     ]);

    //     $selectaSalesReport = SelectaSalesReport::create($validated);

    //     return response()->json([
    //         'message' => 'Selecta Production added successfully',
    //         'selectaSalesReport' => $selectaSalesReport
    //     ]);
    // }

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
             'user_id'           => 'required|exists:users,id',
            'branch_id'          => 'required|exists:branches,id',
            'sales_report_id'    => 'required|exists:sales_reports,id',
            'product_id'         => 'required|exists:products,id',
            'product_name'       => 'required|string',
            'price'              => 'required|numeric',
            'beginnings'         => 'nullable|numeric',
            'remaining'          => 'nullable|numeric',
            'added_stocks'       => 'nullable|numeric',
            'out'                => 'nullable|numeric',
            'sold'               => 'nullable|numeric',
            'total'              => 'nullable|numeric',
            'sales'              => 'nullable|numeric',
        ]);

        $selecta = SelectaSalesReport::create($validated);

        // IMPORTANT: Load relationships
        $selecta->load('selecta');

        return response()->json([
            'message' => 'Selecta Production added successfully',
            'data'    => $selecta
        ]);
    }
}
