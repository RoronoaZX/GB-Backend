<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchEmployee;
use App\Models\BranchProduct;
use App\Models\CakeReport;
use App\Models\CakeSalesReport;
use App\Models\EmployeeSaleschargesReport;
use App\Models\SalesReports;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SalesReportsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function fetchEmployeeCharges($from, $to, $employee_id)
    {
        $fromDate    = Carbon::parse($from)->startOfDay();
        $toDate      = Carbon::parse($to)->endOfDay();

        $user        = User::where('employee_id', $employee_id)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found for this employee'], 404);
        }

        // Step 2: Use the user_id to get the sales reports with charges
        $charges     = SalesReports::where('user_id', $user->id)
                        ->with('branch')
                        ->whereBetween('created_at', [$fromDate, $toDate])
                        ->get();

        return response()->json($charges);
    }

    public function adminStoreSalesReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id'                      => 'required|integer|exists:branches,id',
            'user_id'                        => 'required|integer|exists:users,id',
            'created_at'                     => 'nullable|date',
            'employee_in_shift'              => 'required|array',
            'denomination_total'             => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'expenses_total'                 => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'products_total_sales'           => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'charges_amount'                 => 'required|numeric|min:0',
            'over_total'                     => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'credit_total'                   => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'breadReports'                   => 'required|array',
            'selectaReports'                 => 'nullable|array',
            'nestleReports'                  => 'nullable|array',
            'softdrinksReports'              => 'nullable|array',
            'otherProductsReports'           => 'nullable|array',
            'cakeReports'                    => 'nullable|array',
            'withOutReceiptExpensesReport'   => 'nullable|array',
            'denominationReports'            => 'required|array',
            'creditReports'                  => 'nullable|array',
            'creditReports.*.credits'        => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $salesReport = new SalesReports([
            'branch_id'              => $request->branch_id,
            'user_id'                => $request->user_id,
            'denomination_total'     => $request->denomination_total,
            'expenses_total'         => $request->expenses_total,
            'products_total_sales'   => $request->products_total_sales,
            'charges_amount'         => $request->charges_amount,
            'over_total'             => $request->over_total,
            'credit_total'           => $request->credit_total,
            'created_at'             => $request->created_at,
        ]);

        $salesReport->timestamps = false; // ✅ Allow custom created_at
        $salesReport->save();

        foreach ($request->breadReports as $breadReport) {
            $breadReport['status'] = 'confirmed';

            $salesReport->breadReports()->create($breadReport);

            $branchProduct = BranchProduct::where('branches_id', $request->branch_id)
                ->where('product_id', $breadReport['product_id'])
                ->first();

            if ($branchProduct) {
                $branchProduct->beginnings       = $breadReport['remaining'];
                $branchProduct->new_production   = $breadReport['branch_new_production'];
                $branchProduct->total_quantity   = $breadReport['remaining'];
                $branchProduct->save();
            }
        }

        foreach ($request->selectaReports ?? [] as $selectaReport) {
            $selectaReport['status'] = 'confirmed';

            $salesReport->selectaReports()->create($selectaReport);

            $branchProduct = BranchProduct::where('branches_id', $request->branch_id)
                ->where('product_id', $selectaReport['product_id'])
                ->first();

            if ($branchProduct) {
                $branchProduct->beginnings       = $selectaReport['remaining'];
                $branchProduct->new_production   = $selectaReport['new_production'];
                $branchProduct->total_quantity   = $selectaReport['remaining'];
                $branchProduct->save();
            }
        }

        foreach ($request->nestleReports as $nestleReport) {
            $nestleReport['status'] = 'confirmed';

            $salesReport->nestleReports()->create($nestleReport);
        }

        foreach ($request->cakeReports ?? [] as $cakeReport) {


            $existingCake = CakeReport::find($cakeReport['cake_report_id']);

            if ($existingCake) {
                $existingCake->sales_status = $cakeReport['sales_status'];
                $existingCake->save();

                $salesReport->cakeSalesReports()->create([
                    'sales_report_id'    => $salesReport->id,
                    'cake_report_id'     => $cakeReport['cake_report_id'],
                ]);
            } else {
                return response()->json([
                    'error' => "Cake report with ID {$cakeReport['cake_report_id']} not found."
                ], 404);
            }
        }

        foreach ($request->softdrinksReports ?? [] as $softdrinksReport) {
            $softdrinksReport['status'] = 'confirmed';

            $salesReport->softdrinksReports()->create($softdrinksReport);

            $branchProduct = BranchProduct::where('branches_id', $request->branch_id)
                ->where('product_id', $softdrinksReport['product_id'])
                ->first();

            if ($branchProduct) {
                $branchProduct->beginnings       = $softdrinksReport['remaining'];
                $branchProduct->new_production   = $softdrinksReport['new_production'];
                $branchProduct->total_quantity   = $softdrinksReport['remaining'];
                $branchProduct->save();
            }
        }

        foreach ($request->otherProductsReports ?? [] as $otherProductsReport) {
            $otherProductsReport['status'] = 'confirmed';


            $salesReport->otherProductsReports()->create($otherProductsReport);

            $branchProduct = BranchProduct::where('branches_id', $request->branch_id)
                ->where('product_id', $otherProductsReport['product_id'])
                ->first();

            if ($branchProduct) {
                $branchProduct->beginnings       = $otherProductsReport['remaining'];
                $branchProduct->new_production   = $otherProductsReport['new_production'];
                $branchProduct->total_quantity   = $otherProductsReport['remaining'];
                $branchProduct->save();
            }
        }

        foreach ($request->withOutReceiptExpensesReport ?? [] as $withOutReceiptExpensesReport) {
            $salesReport->expensesReports()->create($withOutReceiptExpensesReport);
        }

        $denominationReport = $request->denominationReports;

        foreach ($denominationReport as $key => $value) {
            if (is_string($value)) {
                $denominationReport[$key] = (int) str_replace(',', '', $value);
            }
        }

        $salesReport->denominationReports()->create($denominationReport);

        foreach ($request->creditReports ?? [] as $creditReportData) {
            $creditReports = $salesReport->creditReports()->create([
                'credit_user_id'     => $creditReportData['credit_user_id'],
                'total_amount'       => $creditReportData['total_amount'],
                'branch_id'          => $creditReportData['branch_id'],
                'user_id'            => $creditReportData['user_id'],
            ]);

            foreach ($creditReportData['credits'] ?? [] as $credit) {
                $credit['credit_user_id'] = $creditReportData['credit_user_id'];
                $creditReports->creditProducts()->create($credit);
            }
        }

        // --- Compute Charges Distribution ---
        $employees = $request->employee_in_shift;

        // make sure not empty (already validated, but safe check)
        if (count($employees) > 0) {

            // Divide charges_amount equally
            $totalCharges = floatval($request->charges_amount);
            $employeeCount = count($employees);
            $sharePerEmployee = $employeeCount > 0 ? round($totalCharges / $employeeCount, 2) : 0;

            // Save record far each employee
            foreach ($employees as $shiftEmployee) {
                EmployeeSaleschargesReport::create([
                    'sales_report_id'    => $salesReport->id,
                    'employee_id'        => $shiftEmployee['employee_id'],
                    'charge_amount'      => $sharePerEmployee
                ]);
            }
        }


        return response()->json(['message' => 'Sales report saved successfully.'], 200);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id'                      => 'required|integer|exists:branches,id',
            'user_id'                        => 'required|integer|exists:users,id',
            'employee_in_shift'              => 'required|array',
            'denomination_total'             => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'expenses_total'                 => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'products_total_sales'           => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'charges_amount'                 => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'over_total'                     => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'credit_total'                   => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'breadReports'                   => 'required|array',
            'selectaReports'                 => 'nullable|array',
            'nestleReports'                  => 'nullable|array',
            'softdrinksReports'              => 'nullable|array',
            'otherProductsReports'           => 'nullable|array',
            'cakeReports'                    => 'nullable|array',
            'withOutReceiptExpensesReport'   => 'nullable|array',
            'denominationReports'            => 'required|array',
            'creditReports'                  => 'nullable|array',
            'creditReports.*.credits'        => 'nullable|array',

        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $salesReport = SalesReports::create([
            'branch_id'              => $request->branch_id,
            'user_id'                => $request->user_id,
            'denomination_total'     => $request->denomination_total,
            'expenses_total'         => $request->expenses_total,
            'products_total_sales'   => $request->products_total_sales,
            'charges_amount'         => $request->charges_amount,
            'over_total'             => $request->over_total,
            'credit_total'           => $request->credit_total,
        ]);

        foreach ($request->breadReports as $breadReport) {
            $breadReport['status'] = 'pending';

            $salesReport->breadReports()->create($breadReport);

        }

        // Store Selecta Reports
        foreach ($request->selectaReports as $selectaReport) {
            $selectaReport['status'] = 'pending';

            $salesReport->selectaReports()->create($selectaReport);
        }

        foreach ($request->nestleReports as $nestleReport) {
            $nestleReport['status'] = 'pending';

            $salesReport->nestleReports()->create($nestleReport);
        }

        // Store Cake Reports
        foreach ($request->cakeReports as $cakeReport) {

            // Find the Cake entry using its ID and update its sales_status
            $existingCake = CakeReport::find($cakeReport['cake_report_id']); // Assuming Cake is an Eloquent model for the Cake table
        }

        // Store Softdrinks Reports
        foreach ($request->softdrinksReports as $softdrinksReport) {
            $softdrinksReport['status'] = 'pending';

            $salesReport->softdrinksReports()->create($softdrinksReport);
        }

        // Store  Other Products
        foreach ($request->otherProductsReports as $otherProductsReports) {
            $otherProductsReports['status'] = 'pending';

            $salesReport->otherProductsReports()->create($otherProductsReports);
        }

        // Store Expenses Reports
        foreach ($request->withOutReceiptExpensesReport as $withOutReceiptExpensesReport) {
            $salesReport->expensesReports()->create($withOutReceiptExpensesReport);
        }

        // Store denomination Reports
        $denominationReport = $request->denominationReports;

        // sanitize values
        foreach ($denominationReport as $key => $value) {
            if (is_string($value)) {
                $denominationReport[$key] = (int)str_replace(',', '', $value);
            }
        }

        $salesReport->denominationReports()->create($denominationReport);

            // Loop through each creditReport entry
        foreach ($request->creditReports as $creditReportData) {
            // Store each Credit Report
            $creditReports = $salesReport->creditReports()->create([
                'credit_user_id'     => $creditReportData['credit_user_id'],
                'total_amount'       => $creditReportData['total_amount'],
                'branch_id'          => $creditReportData['branch_id'],
                'user_id'            => $creditReportData['user_id'],
            ]);

            // Store each Credit within the Credit Report
            foreach ($creditReportData['credits'] as $credit) {
                $credit['credit_user_id'] = $creditReportData['credit_user_id'];
                $creditReports->creditProducts()->create($credit);
            }
        }

        // --- Compute Charges Distribution ---
        $employees = $request->employee_in_shift;

        // make sure not empty (already validated, but safe check)
        if (count($employees) > 0) {

            // Divide charges_amount equally
            $totalCharges = floatval($request->charges_amount);
            $employeeCount = count($employees);
            $sharePerEmployee = $employeeCount > 0 ? round($totalCharges / $employeeCount, 2) : 0;

            // Save record for each employee
            foreach ($employees as $shiftEmployee) {
                EmployeeSaleschargesReport::create([
                    'sales_report_id' => $salesReport->id,
                    'employee_id'     => $shiftEmployee['employee_id'],
                    'charge_amount'   => $sharePerEmployee,
                ]);
            }
        }
    }

    public function fetchBranchSalesReport($branchId)
    {
        $reports = SalesReports::where('branch_id', $branchId)
                    ->with([
                        'branch', 'user', 'breadReports', 'selectaReports', 'nestleReports',
                        'softdrinksReports', 'expensesReports', 'denominationReports', 'creditReports'
                        ])
                    ->orderBy('created_at', 'desc')
                    ->get();

        return response()->json($reports);
    }

    public function updateEmployeeCharges(Request $request, $id)
    {
        $salesReports = SalesReports::find($id);
        $salesReports->charges_amount = $request->charges_amount;
        $salesReports->save();

        return response()->json(['message' => 'Employee charges updated successfully.'], 200);
    }
}
