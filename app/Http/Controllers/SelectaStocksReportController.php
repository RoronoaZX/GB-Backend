<?php

namespace App\Http\Controllers;

use App\Models\BranchProduct;
use App\Models\SelectaAddedStock;
use App\Models\SelectaStocksReport;
use Exception;
use Illuminate\Http\Request;

class SelectaStocksReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }
    public function getPendingReports($branchId, Request $request)
    {
        // Validate the category parameter, if provided
        $validatedData = $request->validate([
            'status' => 'nullable|string'
        ]);

        // Set category to 'pending' by default, if not provided
        $status = $request->query('status', 'pending');

        // Fetch the SelectaStocksReport with the related SelectaAddedStock and filter by category and branch_id
        $selectaStocksReports = SelectaStocksReport::where('branches_id', $branchId)
            ->where('status', $status) // Assuming 'status' is the column representing 'pending' or other states
            ->with(['branch','employee',
                'selectaAddedStocks' => function ($query) {
                    $query->where('added_stocks', '>', 0); // Optional: Only fetch added stocks greater than 0
                }
            ])
            ->get();

        // Return the response with the filtered reports and their associated added stocks
        return response()->json($selectaStocksReports);
    }

    public function confirmReport($id)
    {
        try {
            // Fetch the SelectaStocksReport with related added stocks
            $selectaStocksReport = SelectaStocksReport::with('selectaAddedStocks')->findOrFail($id);

            // Ensure the report is in "pending" status before confirming
            if (strtolower($selectaStocksReport->status) === 'pending') {

                // Loop through each added stock entry
                foreach ($selectaStocksReport->selectaAddedStocks as $addedStock) {
                    // Update the BranchProduct table for the branch and product
                    $branchProduct = BranchProduct::where('branches_id', $selectaStocksReport->branches_id)
                        ->where('product_id', $addedStock->product_id)
                        ->first();

                    if ($branchProduct) {
                        // Update total_quantity with added stock quantity
                        $existingTotalQuantity = $branchProduct->total_quantity;
                        $branchProduct->new_production = $addedStock->added_stocks; // Store new stock addition
                        $branchProduct->total_quantity = $existingTotalQuantity + $branchProduct->new_production;
                        $branchProduct->save();
                    } else {
                        // Optionally handle products not found in BranchProduct table
                        return response()->json([
                            'message' => "BranchProduct not found for branch_id: {$selectaStocksReport->branches_id} and product_id: {$addedStock->product_id}."
                        ], 404);
                    }
                }

                // Update the report status to "confirmed"
                $selectaStocksReport->status = 'confirmed';
                $selectaStocksReport->save();

                return response()->json(['message' => 'Report confirmed and inventory updated successfully'], 200);
            }

            return response()->json(['message' => 'Invalid report status or already confirmed'], 400);

        } catch (\Exception $e) {
            // Catch any errors and return a detailed response
            return response()->json([
                'message' => 'An error occurred while confirming the report.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getConfirmedReport($branchId, Request $request)
    {
        $validateData = $request->validate([
            'status' => 'nullable|string'
        ]);

        // Set category to 'pending' by default, if not provided
        $status = $request->query('status', 'confirmed');

        // Fetch the SelectaStocksReport with the related SelectaAddedStock and filter by category and branch_id
        $selectaStocksReports = SelectaStocksReport::where('branches_id', $branchId)
            ->where('status', $status) // Assuming 'status' is the column representing 'pending' or other states
            ->with(['branch','employee',
                'selectaAddedStocks' => function ($query) {
                    $query->where('added_stocks', '>', 0); // Optional: Only fetch added stocks greater than 0
                }
            ])
            ->get();

        // Return the response with the filtered reports and their associated added stocks
        return response()->json($selectaStocksReports);

    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'branches_id' => 'required|exists:branches,id', // Ensure branch exists
            'employee_id' => 'required|exists:employees,id', // Ensure employee exists
            'status' => 'required|string',
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id', // Ensure product exists
            'products.*.price' => 'required|numeric', // Must be a positive number
            'products.*.added_stocks' => 'required|numeric|min:1', // Must be a positive number
        ]);

        $selectaStocksReport = SelectaStocksReport::create([
            'branches_id' => $validateData['branches_id'],
            'employee_id' => $validateData['employee_id'],
            'status' => $validateData['status'],
        ]);

        foreach ($validateData['products'] as $product) {
            SelectaAddedStock::create([
                'selecta_stocks_report_id' => $selectaStocksReport->id,
                'product_id' => $product['product_id'],
                'price' => $product['price'],
                'added_stocks' => $product['added_stocks'],
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(SelectaStocksReport $selectaStocksReport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SelectaStocksReport $selectaStocksReport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SelectaStocksReport $selectaStocksReport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SelectaStocksReport $selectaStocksReport)
    {
        //
    }
}
