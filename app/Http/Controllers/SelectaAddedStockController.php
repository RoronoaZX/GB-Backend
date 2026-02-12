<?php

namespace App\Http\Controllers;

use App\Models\SelectaAddedStock;
use Illuminate\Http\Request;

class SelectaAddedStockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function fetchPendingReports($branchId)
    {

        // Retrieve pending stock reports, filtered by branch if branchId is provided
        $query = SelectaAddedStock::with(['branch', 'product'])
                    ->where('status', 'pending');

        if ($branchId) {
            $query->where('branches_id', $branchId);
        }

        $pendingReports = $query->get();

        return response()->json([
            'message'    => 'Pending reports retrieved successfully.',
            'data'       => $pendingReports,
        ], 200);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'branches_id'    => 'required|exists:branches,id',
            'product_id'     => 'required|exists:products,id',
            'price'          => 'required|numeric',
            'added_stocks'   => 'required|numeric',
            'status'         => 'required|string'
        ]);

        // Save the stock record with a pending status
        $stock = SelectaAddedStock::create([
            'branches_id'    => $validatedData['branches_id'],
            'product_id'     => $validatedData['product_id'],
            'price'          => $validatedData['price'],
            'added_stocks'   => $validatedData['added_stocks'],
            'status'         => $validatedData['status'], // Likely 'pending' at this stage
        ]);

        return response()->json([
            'message'    => 'Stock saved successfully. Awaiting admin confirmation.',
            'data'       => $stock,
        ], 201);


    }

}
