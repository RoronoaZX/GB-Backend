<?php

namespace App\Http\Controllers;

use App\Models\SoftdrinksAddedStocks;
use Illuminate\Http\Request;

class SoftdrinksAddedStocksController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function fetchPendingReports($branchId)
    {
        $query = SoftdrinksAddedStocks::with(['branch', 'product'])
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'branches_id'    => 'required|exists:branches,id',
            'product_id'     => 'required|exists:products,id',
            'price'          => 'required|numeric',
            'added_stocks'   => 'required|numeric',
            'status'         => 'required|string'
        ]);

        $stock = SoftdrinksAddedStocks::create([
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
