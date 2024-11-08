<?php

namespace App\Http\Controllers;

use App\Models\BranchEmployee;
use App\Models\BranchProduct;
use App\Models\SalesReports;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SalesReportsController extends Controller
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

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|integer|exists:branches,id',
            'user_id' => 'required|integer|exists:users,id',
            'denomination_total' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'expenses_total' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'products_total_sales' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'charges_amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'over_total' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'credit_total' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'breadReports' => 'required|array',
            'selectaReports' => 'nullable|array',
            'softdrinksReports' => 'nullable|array',
            'expensesReports' => 'nullable|array',
            'denominationReports' => 'required|array',
            'creditReports' => 'nullable|array',
            'creditReports.*.credits' => 'nullable|array',

        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $salesReport = SalesReports::create([
            'branch_id' => $request->branch_id,
            'user_id' => $request->user_id,
            'denomination_total' => $request->denomination_total,
            'expenses_total' => $request->expenses_total,
            'products_total_sales' => $request->products_total_sales,
            'charges_amount' => $request->charges_amount,
            'over_total' => $request->over_total,
            'credit_total' => $request->credit_total,
        ]);

        foreach ($request->breadReports as $breadReport) {
            $salesReport->breadReports()->create($breadReport);

            $branchProduct = BranchProduct::where('branches_id', $request->branch_id)
            ->where('product_id', $breadReport['product_id'])
            ->first();

            if ($branchProduct) {
                $branchProduct->beginnings = $breadReport['remaining'];
                $branchProduct->total_quantity = $breadReport['remaining'];
                $branchProduct->save();
            }

        }

        // Store Selecta Reports
        foreach ($request->selectaReports as $selectaReport) {
            $salesReport->selectaReports()->create($selectaReport);

            $branchProduct = BranchProduct::where('branches_id', $request->branch_id)
            ->where('product_id', $selectaReport['product_id'])
            ->first();

        if ($branchProduct) {
            $branchProduct->beginnings = $selectaReport['remaining'];
            $branchProduct->total_quantity = $selectaReport['remaining'];
            $branchProduct->save();
        }
        }

        // Store Softdrinks Reports
        foreach ($request->softdrinksReports as $softdrinksReport) {
            $salesReport->softdrinksReports()->create($softdrinksReport);

            $branchProduct = BranchProduct::where('branches_id', $request->branch_id)
            ->where('product_id', $softdrinksReport['product_id'])
            ->first();

        if ($branchProduct) {
            $branchProduct->beginnings = $softdrinksReport['remaining'];
            $branchProduct->total_quantity = $softdrinksReport['remaining'];
            $branchProduct->save();
        }
        }

        // Store Expenses Reports
        foreach ($request->expensesReports as $expensesReport) {
            $salesReport->expensesReports()->create($expensesReport);
        }

        // Store Denomination Reports
        foreach ($request->denominationReports as $denominationReport) {
            foreach ($denominationReport as $key => $value) {
                if (is_string($value)) {
                    $denominationReport[$key] = (int)str_replace(',', '', $value);
                }
            }
            $salesReport->denominationReports()->create($denominationReport);
        }

            // Loop through each creditReport entry
        foreach ($request->creditReports as $creditReportData) {
            // Store each Credit Report
            $creditReports = $salesReport->creditReports()->create([
                'credit_user_id' => $creditReportData['credit_user_id'],
                'total_amount' => $creditReportData['total_amount'],
                'branch_id' => $creditReportData['branch_id'],
                'user_id' => $creditReportData['user_id'],
            ]);

            // Store each Credit within the Credit Report
            foreach ($creditReportData['credits'] as $credit) {
                $credit['credit_user_id'] = $creditReportData['credit_user_id'];
                $creditReports->creditProducts()->create($credit);
            }
        }
    }


    public function show(SalesReports $salesReports)
    {
        //
    }


    public function edit(SalesReports $salesReports)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SalesReports  $salesReports
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SalesReports $salesReports)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SalesReports  $salesReports
     * @return \Illuminate\Http\Response
     */
    public function destroy(SalesReports $salesReports)
    {
        //
    }
}
