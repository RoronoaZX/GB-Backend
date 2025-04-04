<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchEmployee;
use App\Models\BranchProduct;
use App\Models\CakeReport;
use App\Models\CakeSalesReport;
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

    public function adminStoreSalesReport(Request $request)
{
    $validator = Validator::make($request->all(), [
        'branch_id' => 'required|integer|exists:branches,id',
        'user_id' => 'required|integer|exists:users,id',
        'created_at' => 'nullable|date',
        'denomination_total' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
        'expenses_total' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
        'products_total_sales' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
        'charges_amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
        'over_total' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
        'credit_total' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
        'breadReports' => 'required|array',
        'selectaReports' => 'nullable|array',
        'softdrinksReports' => 'nullable|array',
        'otherProductsReports' => 'nullable|array',
        'cakeReports' => 'nullable|array',
        'withOutReceiptExpensesReport' => 'nullable|array',
        'denominationReports' => 'required|array',
        'creditReports' => 'nullable|array',
        'creditReports.*.credits' => 'nullable|array',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }

    $salesReport = new SalesReports([
        'branch_id' => $request->branch_id,
        'user_id' => $request->user_id,
        'denomination_total' => $request->denomination_total,
        'expenses_total' => $request->expenses_total,
        'products_total_sales' => $request->products_total_sales,
        'charges_amount' => $request->charges_amount,
        'over_total' => $request->over_total,
        'credit_total' => $request->credit_total,
        'created_at' => $request->created_at,
    ]);

    $salesReport->timestamps = false; // ✅ Allow custom created_at
    $salesReport->save();

    foreach ($request->breadReports as $breadReport) {
        $salesReport->breadReports()->create($breadReport);

        $branchProduct = BranchProduct::where('branches_id', $request->branch_id)
            ->where('product_id', $breadReport['product_id'])
            ->first();

        if ($branchProduct) {
            $branchProduct->beginnings = $breadReport['remaining'];
            $branchProduct->new_production = $breadReport['branch_new_production'];
            $branchProduct->total_quantity = $breadReport['remaining'];
            $branchProduct->save();
        }
    }

    foreach ($request->selectaReports ?? [] as $selectaReport) {
        $salesReport->selectaReports()->create($selectaReport);

        $branchProduct = BranchProduct::where('branches_id', $request->branch_id)
            ->where('product_id', $selectaReport['product_id'])
            ->first();

        if ($branchProduct) {
            $branchProduct->beginnings = $selectaReport['remaining'];
            $branchProduct->new_production = $selectaReport['new_production'];
            $branchProduct->total_quantity = $selectaReport['remaining'];
            $branchProduct->save();
        }
    }

    foreach ($request->cakeReports ?? [] as $cakeReport) {
        $existingCake = CakeReport::find($cakeReport['cake_report_id']);

        if ($existingCake) {
            $existingCake->sales_status = $cakeReport['sales_status'];
            $existingCake->save();

            $salesReport->cakeSalesReports()->create([
                'sales_report_id' => $salesReport->id,
                'cake_report_id' => $cakeReport['cake_report_id'],
            ]);
        } else {
            return response()->json([
                'error' => "Cake report with ID {$cakeReport['cake_report_id']} not found."
            ], 404);
        }
    }

    foreach ($request->softdrinksReports ?? [] as $softdrinksReport) {
        $salesReport->softdrinksReports()->create($softdrinksReport);

        $branchProduct = BranchProduct::where('branches_id', $request->branch_id)
            ->where('product_id', $softdrinksReport['product_id'])
            ->first();

        if ($branchProduct) {
            $branchProduct->beginnings = $softdrinksReport['remaining'];
            $branchProduct->new_production = $softdrinksReport['new_production'];
            $branchProduct->total_quantity = $softdrinksReport['remaining'];
            $branchProduct->save();
        }
    }

    foreach ($request->otherProductsReports ?? [] as $otherProductsReport) {
        $salesReport->otherProductsReports()->create($otherProductsReport);

        $branchProduct = BranchProduct::where('branches_id', $request->branch_id)
            ->where('product_id', $otherProductsReport['product_id'])
            ->first();

        if ($branchProduct) {
            $branchProduct->beginnings = $otherProductsReport['remaining'];
            $branchProduct->new_production = $otherProductsReport['new_production'];
            $branchProduct->total_quantity = $otherProductsReport['remaining'];
            $branchProduct->save();
        }
    }

    foreach ($request->withOutReceiptExpensesReport ?? [] as $withOutReceiptExpensesReport) {
        $salesReport->expensesReports()->create($withOutReceiptExpensesReport);
    }

    foreach ($request->denominationReports as $denominationReport) {
        foreach ($denominationReport as $key => $value) {
            if (is_string($value)) {
                $denominationReport[$key] = (int)str_replace(',', '', $value);
            }
        }
        $salesReport->denominationReports()->create($denominationReport);
    }

    foreach ($request->creditReports ?? [] as $creditReportData) {
        $creditReports = $salesReport->creditReports()->create([
            'credit_user_id' => $creditReportData['credit_user_id'],
            'total_amount' => $creditReportData['total_amount'],
            'branch_id' => $creditReportData['branch_id'],
            'user_id' => $creditReportData['user_id'],
        ]);

        foreach ($creditReportData['credits'] ?? [] as $credit) {
            $credit['credit_user_id'] = $creditReportData['credit_user_id'];
            $creditReports->creditProducts()->create($credit);
        }
    }

    return response()->json(['message' => 'Sales report saved successfully.'], 200);
}


    // public function adminStoreSalesReport(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'branch_id' => 'required|integer|exists:branches,id',
    //         'user_id' => 'required|integer|exists:users,id',
    //         'created_at' => 'nullable|date',
    //         'denomination_total' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
    //         'expenses_total' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
    //         'products_total_sales' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
    //         'charges_amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
    //         'over_total' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
    //         'credit_total' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
    //         'breadReports' => 'required|array',
    //         'selectaReports' => 'nullable|array',
    //         'softdrinksReports' => 'nullable|array',
    //         'otherProductsReports' => 'nullable|array',
    //         'cakeReports' => 'nullable|array',
    //         'expensesReports' => 'nullable|array',
    //         'denominationReports' => 'required|array',
    //         'creditReports' => 'nullable|array',
    //         'creditReports.*.credits' => 'nullable|array',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 400);
    //     }

    //     $salesReport = SalesReports::create([
    //         'branch_id' => $request->branch_id,
    //         'user_id' => $request->user_id,
    //         'denomination_total' => $request->denomination_total,
    //         'expenses_total' => $request->expenses_total,
    //         'products_total_sales' => $request->products_total_sales,
    //         'charges_amount' => $request->charges_amount,
    //         'over_total' => $request->over_total,
    //         'credit_total' => $request->credit_total,
    //         'created_at' => $request->created_at
    //     ]);

    //     foreach ($request->breadReports as $breadReport) {
    //         $salesReport->breadReports()->create($breadReport);

    //         $branchProduct = BranchProduct::where('branches_id', $request->branch_id)
    //         ->where('product_id', $breadReport['product_id'])
    //         ->first();

    //         if ($branchProduct) {
    //             $branchProduct->beginnings = $breadReport['remaining'];
    //             //this code is to set 0 the new production record of the branch product
    //             //in every submiting of report so that the new production will not be added
    //             // to the new production of the branch product
    //             $branchProduct->new_production = $breadReport['branch_new_production'];
    //             $branchProduct->total_quantity = $breadReport['remaining'];
    //             $branchProduct->save();
    //         }

    //     }

    //     // Store Selecta Reports
    //     foreach ($request->selectaReports as $selectaReport) {
    //         $salesReport->selectaReports()->create($selectaReport);

    //         $branchProduct = BranchProduct::where('branches_id', $request->branch_id)
    //         ->where('product_id', $selectaReport['product_id'])
    //         ->first();

    //     if ($branchProduct) {
    //         $branchProduct->beginnings = $selectaReport['remaining'];
    //         //this code is to set 0 the new production record of the branch product
    //         //in every submiting of report so that the new production will not be added
    //         // to the new production of the branch product
    //         $branchProduct->new_production = $selectaReport['new_production'];
    //         $branchProduct->total_quantity = $selectaReport['remaining'];
    //         $branchProduct->save();
    //     }
    //     }

    //     // Store Cake Reports
    //     foreach ($request->cakeReports as $cakeReport) {
    //         // Create Cake Report record
    //         // $salesReport->cakeSalesReports()->create($cakeReport);

    //         // Find the Cake entry using its ID and update its sales_status
    //         $existingCake = CakeReport::find($cakeReport['cake_report_id']); // Assuming Cake is an Eloquent model for the Cake table

    //         if ($existingCake) {
    //             // Update the sales_status
    //             $existingCake->sales_status = $cakeReport['sales_status'];
    //             $existingCake->save();

    //             // Save data to the Cake Sales Report table
    //             $salesReport->cakeSalesReports()->create([
    //                 'sales_report_id' => $salesReport->id, // This is the ID of the newly created SalesReports batch
    //                 'cake_report_id' => $cakeReport['cake_report_id'], // Provided by frontend
    //             ]);
    //         } else {
    //             // Handle case where Cake report ID is not found
    //             return response()->json([
    //                 'error' => "Cake report with ID {$cakeReport['cake_report_id']} not found."
    //             ], 404);
    //         }
    //     }

    //     // Store Softdrinks Reports
    //     foreach ($request->softdrinksReports as $softdrinksReport) {
    //         $salesReport->softdrinksReports()->create($softdrinksReport);

    //         $branchProduct = BranchProduct::where('branches_id', $request->branch_id)
    //         ->where('product_id', $softdrinksReport['product_id'])
    //         ->first();

    //     if ($branchProduct) {
    //         $branchProduct->beginnings = $softdrinksReport['remaining'];
    //         //this code is to set 0 the new production record of the branch product
    //         //in every submiting of report so that the new production will not be added
    //         // to the new production of the branch product
    //         $branchProduct->new_production = $softdrinksReport['new_production'];
    //         $branchProduct->total_quantity = $softdrinksReport['remaining'];
    //         $branchProduct->save();
    //     }
    //     }

    //     // Store  Other Products
    //     foreach ($request->otherProductsReports as $otherProductsReports) {
    //         $salesReport->otherProductsReports()->create($otherProductsReports);

    //         $branchProduct = BranchProduct::where('branches_id', $request->branch_id)
    //                 ->where('product_id', $otherProductsReports['product_id'])
    //                 ->first();

    //         if ($branchProduct) {
    //             $branchProduct->beginnings = $otherProductsReports['remaining'];
    //             //this code is to set 0 the new production record of the branch product
    //             //in every submiting of report so that the new production will not be added
    //             // to the new production of the branch product
    //             $branchProduct->new_production = $otherProductsReports['new_production'];
    //             $branchProduct->total_quantity = $otherProductsReports['remaining'];
    //             $branchProduct->save();
    //         }
    //     }

    //     // Store Expenses Reports
    //     foreach ($request->expensesReports as $expensesReport) {
    //         $salesReport->expensesReports()->create($expensesReport);
    //     }

    //     // Store Denomination Reports
    //     foreach ($request->denominationReports as $denominationReport) {
    //         foreach ($denominationReport as $key => $value) {
    //             if (is_string($value)) {
    //                 $denominationReport[$key] = (int)str_replace(',', '', $value);
    //             }
    //         }
    //         $salesReport->denominationReports()->create($denominationReport);
    //     }

    //         // Loop through each creditReport entry
    //     foreach ($request->creditReports as $creditReportData) {
    //         // Store each Credit Report
    //         $creditReports = $salesReport->creditReports()->create([
    //             'credit_user_id' => $creditReportData['credit_user_id'],
    //             'total_amount' => $creditReportData['total_amount'],
    //             'branch_id' => $creditReportData['branch_id'],
    //             'user_id' => $creditReportData['user_id'],
    //         ]);

    //         // Store each Credit within the Credit Report
    //         foreach ($creditReportData['credits'] as $credit) {
    //             $credit['credit_user_id'] = $creditReportData['credit_user_id'];
    //             $creditReports->creditProducts()->create($credit);
    //         }
    //     }
    // }

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
            'otherProductsReports' => 'nullable|array',
            'cakeReports' => 'nullable|array',
            'withOutReceiptExpensesReport' => 'nullable|array',
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
                //this code is to set 0 the new production record of the branch product
                //in every submiting of report so that the new production will not be added
                // to the new production of the branch product
                $branchProduct->new_production = $breadReport['branch_new_production'];
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
            //this code is to set 0 the new production record of the branch product
            //in every submiting of report so that the new production will not be added
            // to the new production of the branch product
            $branchProduct->new_production = $selectaReport['new_production'];
            $branchProduct->total_quantity = $selectaReport['remaining'];
            $branchProduct->save();
        }
        }

        // Store Cake Reports
        foreach ($request->cakeReports as $cakeReport) {
            // Create Cake Report record
            // $salesReport->cakeSalesReports()->create($cakeReport);

            // Find the Cake entry using its ID and update its sales_status
            $existingCake = CakeReport::find($cakeReport['cake_report_id']); // Assuming Cake is an Eloquent model for the Cake table

            if ($existingCake) {
                // Update the sales_status
                $existingCake->sales_status = $cakeReport['sales_status'];
                $existingCake->save();

                // Save data to the Cake Sales Report table
                $salesReport->cakeSalesReports()->create([
                    'sales_report_id' => $salesReport->id, // This is the ID of the newly created SalesReports batch
                    'cake_report_id' => $cakeReport['cake_report_id'], // Provided by frontend
                ]);
            } else {
                // Handle case where Cake report ID is not found
                return response()->json([
                    'error' => "Cake report with ID {$cakeReport['cake_report_id']} not found."
                ], 404);
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
            //this code is to set 0 the new production record of the branch product
            //in every submiting of report so that the new production will not be added
            // to the new production of the branch product
            $branchProduct->new_production = $softdrinksReport['new_production'];
            $branchProduct->total_quantity = $softdrinksReport['remaining'];
            $branchProduct->save();
        }
        }

        // Store  Other Products
        foreach ($request->otherProductsReports as $otherProductsReports) {
            $salesReport->otherProductsReports()->create($otherProductsReports);

            $branchProduct = BranchProduct::where('branches_id', $request->branch_id)
                    ->where('product_id', $otherProductsReports['product_id'])
                    ->first();

            if ($branchProduct) {
                $branchProduct->beginnings = $otherProductsReports['remaining'];
                //this code is to set 0 the new production record of the branch product
                //in every submiting of report so that the new production will not be added
                // to the new production of the branch product
                $branchProduct->new_production = $otherProductsReports['new_production'];
                $branchProduct->total_quantity = $otherProductsReports['remaining'];
                $branchProduct->save();
            }
        }

        // Store Expenses Reports
        foreach ($request->withOutReceiptExpensesReport as $withOutReceiptExpensesReport) {
            $salesReport->expensesReports()->create($withOutReceiptExpensesReport);
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

    public function fetchBranchSalesReport($branchId)
    {
        $reports = SalesReports::where('branch_id', $branchId)
                ->with(['branch', 'user', 'breadReports', 'selectaReports', 'softdrinksReports', 'expensesReports', 'denominationReports', 'creditReports'])
                ->orderBy('created_at', 'desc')
                ->get();

        return response()->json($reports);
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
