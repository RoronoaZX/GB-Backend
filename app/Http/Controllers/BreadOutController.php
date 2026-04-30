<?php

namespace App\Http\Controllers;

use App\Models\BreadOut;
use App\Models\BranchProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BreadOutController extends Controller
{
    public function index(Request $request)
    {
        $query = BreadOut::with(['branch', 'product']);

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id'      => 'required|exists:branches,id',
            'product_id'     => 'required|exists:products,id',
            'quantity'       => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $branchProduct = BranchProduct::where('branches_id', $request->branch_id)
                ->where('product_id', $request->product_id)
                ->first();

            if (!$branchProduct) {
                return response()->json(['message' => 'Product not found in branch'], 400);
            }

            $breadOut = BreadOut::create([
                'branch_id'      => $request->branch_id,
                'product_id'     => $request->product_id,
                'quantity'       => $request->quantity,
                'status'         => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'message'    => 'Bread out logged successfully',
                'data'       => $breadOut
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message'    => 'Error logging bread out',
                'error'      => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:received,repurposed,transferred,spoiled'
        ]);

        $breadOut = BreadOut::findOrFail($id);
        $breadOut->status = $request->status;
        $breadOut->save();

        return response()->json([
            'message'   => 'Status updated successfully',
            'data'      => $breadOut
        ]);
    }
}
