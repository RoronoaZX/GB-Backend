<?php

namespace App\Http\Controllers;

use App\Models\BranchProduct;
use App\Models\OtherAddedStocks;
use App\Models\OtherStocksReport;
use Illuminate\Http\Request;

class OtherStocksReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function getBranchOtherReports(Request $request, $branchId)
    {
        try {
            // Get the per_page parameter from the request or use a default value (e.g., 10)
            $perPage = $request->get('per_page', 5);

              // Fetch reports for the specific branch, eager-loading necessary relationships
              $otherStockReport = OtherStocksReport::with(['employee', 'branch', 'otherAddedStock'])
              ->where('branches_id', $branchId) // Filter by branch ID
              ->orderBy('created_at', 'desc') // Order by the creation date
              ->paginate($perPage);

              // Return a successful response
            return response()->json($otherStockReport);
        } catch (\Exception $e) {
            // Handle and return an error response
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reports. ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getPendingReports($branchId, Request $request)
    {
        // Validate the category parameter, if provided
        $request->validate([
            'status' => 'nullable|string'
        ]);

        // Set category to 'pending' by default, if not provided
        $status = $request->query('status', 'pending');

        // Fetch the SelectaStocksReport with the related SelectaAddedStock and filter by category and branch_id
        $otherStockReports = OtherStocksReport::where('branches_id', $branchId)
        ->where('status', $status) // Assuming 'status' is the column representing 'pending' or other states
        ->with(['branch','employee',
        'otherAddedStock' => function ($query) {
            $query->where('added_stocks', '>', 0); // Optional: Only fetch added stocks greater than 0
        }
        ])
        ->get();
         // Return the response with the filtered reports and their associated added stocks
         return response()->json($otherStockReports);
    }

    public function getConfirmedReport($branchId, Request $request)
    {
        $request->validate([
            'status' => 'nullable|string'
        ]);

        // Set category to 'pending' by default, if not provided
        $status = $request->query('status', 'confirmed');

        // Fetch the SelectaStocksReport with the related SelectaAddedStock and filter by category and branch_id
        $otherStockReport = OtherStocksReport::where('branches_id', $branchId)
            ->where('status', $status) // Assuming 'status' is the column representing 'pending' or other states
            ->with(['branch','employee',
                'otherAddedStock' => function ($query) {
                    $query->where('added_stocks', '>', 0); // Optional: Only fetch added stocks greater than 0
                }
            ])
            ->get();

        // Return the response with the filtered reports and their associated added stocks
        return response()->json($otherStockReport);
    }

    public function getDeclinedReport($branchId, Request $request)
    {
        $request->validate([
            'status' => 'nullable|string'
        ]);

        // Set category to 'pending' by default, if not provided
        $status = $request->query('status', 'declined');

         // Fetch the SelectaStocksReport with the related SelectaAddedStock and filter by category and branch_id
         $otherStockReport = OtherStocksReport::where('branches_id', $branchId)
         ->where('status', $status) // Assuming 'status' is the column representing 'pending' or other states
         ->with(['branch','employee',
             'otherAddedStock' => function ($query) {
                 $query->where('added_stocks', '>', 0); // Optional: Only fetch added stocks greater than 0
             }
         ])
         ->get();

     // Return the response with the filtered reports and their associated added stocks
     return response()->json($otherStockReport);
    }

    public function confirmReport($id)
    {
        try {
            // Fetch the SelectaStocksReport with related added stocks
            $otherStocksReport = OtherStocksReport::with('otherAddedStock')->findOrFail($id);

             // Ensure the report is in "pending" status before confirming
             if (strtolower($otherStocksReport->status) === 'pending') {

                // Loop through each added stock entry
                foreach ($otherStocksReport->otherAddedStock as $addedStock) {
                    // Update the BranchProduct table for the branch and product
                    $branchProduct = BranchProduct::where('branches_id', $otherStocksReport->branches_id)
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
                            'message' => "BranchProduct not found for branch_id: {$otherStocksReport->branches_id} and product_id: {$addedStock->product_id}."
                        ], 404);
                    }
                }

                // Update the report status to "confirmed"
                $otherStocksReport->status = 'confirmed';
                $otherStocksReport->save();

                return response()->json(['message' => 'Report confirmed and inventory updated successfully'], 200);
            }

            return response()->json(['message' => 'Invalid report status or already confirmed'], 400);
        }  catch (\Exception $e) {
            // Catch any errors and return a detailed response
            return response()->json([
                'message' => 'An error occurred while confirming the report.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function declineReport(Request $request, $id)
    {
        // Validate the input
        $request->validate([
            'remark' => 'required|string|max:255',
        ]);

        try {
            // Find the report by ID
            $otherStockReport = OtherStocksReport::findOrFail($id);

            // Update the report's status and save the remark
            $otherStockReport->status = 'declined';
            $otherStockReport->remark = $request->input('remark');
            $otherStockReport->save();
            // $selectaStocksReports->declined_at = now(); // Optional: Add a timestamp for when it was declined

            // Return success response
            return response()->json([
                'message' => 'Report declined successfully.',
                'report' => $otherStockReport,
            ], 200);
        } catch (\Exception $e) {
            // Handle errors
            return response()->json([
                'message' => 'Failed to decline report.',
                'error' => $e->getMessage(),
            ], 500);
        }
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

        $otherStockReport = OtherStocksReport::create([
            'branches_id' => $validateData['branches_id'],
            'employee_id' => $validateData['employee_id'],
            'status' => $validateData['status'],
        ]);

        foreach ($validateData['products'] as $product) {
            OtherAddedStocks::create([
                'other_stocks_report_id' => $otherStockReport->id,
                'product_id' => $product['product_id'],
                'price' => $product['price'],
                'added_stocks' => $product['added_stocks'],
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(OtherStocksReport $otherStocksReport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OtherStocksReport $otherStocksReport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OtherStocksReport $otherStocksReport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OtherStocksReport $otherStocksReport)
    {
        //
    }
}
