<?php

namespace App\Http\Controllers;

use App\Models\BranchProduct;
use App\Models\SoftdrinksAddedStocks;
use App\Models\SoftdrinksStocksReport;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use PhpParser\Node\Stmt\TryCatch;

class SoftdrinksStocksReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sofdrinksStockReports = SoftdrinksStocksReport::with(['branch', 'employee'])->get();

        return response()->json($sofdrinksStockReports);
    }

    public function getBranchSoftdrinksReports(Request $request, $branchId)
    {
        try {
              // Get the per_page parameter from the request or use a default value (e.g., 10)
            $perPage = $request->get('per_page', 5);

            // Fetch reports for the specific branch, eager-loading necessary relationships
            $softdrinksStocksReport = SoftdrinksStocksReport::with(['employee', 'branch', 'softdrinksAddedStocks'])
                ->where('branches_id', $branchId) // Filter by branch ID
                ->orderBy('created_at', 'desc') // Order by the creation date
                ->paginate($perPage);

            // Return a successful response
            return response()->json($softdrinksStocksReport);
        } catch (\Exception $e) {
            // Handle and return an error response
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reports. ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getpendingReports($branchId, Request $request)
    {
        // Validate the category parameter, if provided
        $validatedData = $request->validate([
            'status' => 'nullable|string'
        ]);

         // Set category to 'pending' by default, if not provided
         $status = $request->query('status', 'pending');
         $page = $request->get('page', 1);
         $perPage = $request->get('per_page', 5);

         // Fetch the SoftdrinksStocksReport with the related SoftdrinksAddedStock and filter by category and branch_id
        $softdrinksStockReports = SoftdrinksStocksReport::where('branches_id', $branchId)
        ->where('status', $status) // Assuming 'status' is the column representing 'pending' or other states
        ->with(['branch','employee',
            'softdrinksAddedStocks' => function ($query) {
                $query->where('added_stocks', '>', 0); // Optional: Only fetch added stocks greater than 0
            }
        ])
        ->orderBy('created_at', 'desc')
        ->get();

        // Paginate manually
        $paginated = new LengthAwarePaginator(
            $softdrinksStockReports->forPage($page, $perPage)->values(),
            $softdrinksStockReports->count(),
            $perPage,
            $page,
            ['page' => url()->current()]
        );

        return response()->json($paginated);
    }

    public function declineReport(Request $request, $id)
    {
        $request->validate([
            'remark' => 'required|string|max:255',
        ]);

        try {
            // Find the report by ID
            $softdrinksStockReports = SoftdrinksStocksReport::findOrFail($id);

            // Update the report's status and save the remark
            $softdrinksStockReports->status = 'declined';
            $softdrinksStockReports->remark = $request->input('remark');
            $softdrinksStockReports->save();

            // Return success response
            return response()->json([
                'message'    => 'Report declined successfully.',
                'report'     => $softdrinksStockReports,
            ], 200);
        }catch (\Exception $e) {
            // Handle errors
            return response()->json([
                'message'    => 'Failed to decline report.',
                'error'      => $e->getMessage(),
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
        $page = $request->get('page',1);
        $perPage = $request->get('per_page', 5);

         // Fetch the SoftdrinksStocksReport with the related SoftdrinksAddedStock and filter by category and branch_id
         $softdrinksStockReport = SoftdrinksStocksReport::where('branches_id', $branchId)
         ->where('status', $status) // Assuming 'status' is the column representing 'pending' or other states
         ->with(['branch','employee',
             'softdrinksAddedStocks' => function ($query) {
                 $query->where('added_stocks', '>', 0); // Optional: Only fetch added stocks greater than 0
             }
         ])
         ->orderBy('created_at', 'desc')
         ->get();

        // Paginate manually
        $paginated = new LengthAwarePaginator(
            $softdrinksStockReport->forPage($page, $perPage)->values(),
            $softdrinksStockReport->count(),
            $perPage,
            $page,
            ['path' => url()->current()]
        );

        return response()->json($paginated);
    }

    public function confirmReport($id)
    {
        // Fetch the SoftdrinksStocksReport with related added stocks
        try{
            $softdrinksStockReport = SoftdrinksStocksReport::with('softdrinksAddedStocks')->findOrFail($id);

            // Ensure the report is in "pending" status before confirming
            if(strtolower($softdrinksStockReport->status) === 'pending') {
                // Loop through each added stock entry
                foreach($softdrinksStockReport->softdrinksAddedStocks as $addedStock) {
                    // Update the BranchProduct table for the branch and product
                    $branchProduct = BranchProduct::where('branches_id', $softdrinksStockReport->branches_id)
                    ->where('product_id', $addedStock->product_id)
                    ->first();

                    if ($branchProduct) {
                        // Update total_quantity with added stock quantity
                        $existingTotalQuantity = $branchProduct->total_quantity;
                        $branchProduct->new_production = $addedStock->added_stocks;
                        $branchProduct->total_quantity = $existingTotalQuantity + $branchProduct->new_production;
                        $branchProduct->save();
                    } else {
                        // Optionally handle products not found in BranchProduct table
                        return response()->json([
                            'message' => "BranchProduct not found for branch_id: {$softdrinksStockReport->branches_id} and product_id: {$addedStock->product_id}."
                        ], 404);
                    }
                }
                $softdrinksStockReport->status = 'confirmed';
                $softdrinksStockReport->save();

                return response()->json(['message' => 'Report confirmed and inventory updated successfully'], 200);
            }

            return response()->json(['message' => 'Invalid report status or already confirmed'], 400);
        } catch (\Exception $e) {
            // Catch any errors and return a detailed response
            return response()->json([
                'message'    => 'An error occurred while confirming the report.',
                'error'      => $e->getMessage()
            ], 500);
        }
    }

    public function getDeclinedReport($branchId, Request $request)
    {
        $validateData = $request->validate([
            'status' => 'nullable|string'
        ]);

        // Set category to 'pending' by default, if not provided
        $status = $request->query('status', 'declined');
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 5);

        // Fetch the SelectaStocksReport with the related SelectaAddedStock and filter by category and branch_id
        $softdrinksStockReport = SoftdrinksStocksReport::where('branches_id', $branchId)
            ->where('status', $status)
            ->with(['branch', 'employee', 'softdrinksAddedStocks' => function($query){
                $query->where('added_stocks', '>', 0);
            }])
            ->get();

            // Paginate manually
            $paginated= new LengthAwarePaginator(
                $softdrinksStockReport->forPage($page, $perPage)->values(),
                $softdrinksStockReport->count(),
                $perPage,
                $page,
                ['path' => url()->current()]
            );

            return response()->json($paginated);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'branches_id'                => 'required|exists:branches,id', // Ensure branch exists
            'employee_id'                => 'required|exists:employees,id', // Ensure employee exists
            'status'                     => 'required|string',
            'products'                   => 'required|array',
            'products.*.product_id'      => 'required|exists:products,id', // Ensure product exists
            'products.*.price'           => 'required|numeric', // Must be a positive number
            'products.*.added_stocks'    => 'required|numeric|min:1', // Must be a positive number
        ]);

        $softdrinksStockReport = SoftdrinksStocksReport::create([
            'branches_id'    => $validateData['branches_id'],
            'employee_id'    => $validateData['employee_id'],
            'status'         => $validateData['status'],
        ]);

        foreach ($validateData['products'] as $product) {
            SoftdrinksAddedStocks::create([
                'softdrinks_stocks_report_id'    => $softdrinksStockReport->id,
                'product_id'                     => $product['product_id'],
                'price'                          => $product['price'],
                'added_stocks'                   => $product['added_stocks'],
            ]);
        }
    }

}
