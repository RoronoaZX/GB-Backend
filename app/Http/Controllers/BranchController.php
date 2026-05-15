<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Services\HistoryLogService;


class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::orderBy('created_at', 'desc')
                            ->with('warehouse', 'employees')
                            ->get();

        return response()->json($branches, 200);
    }

    public function fetchBranchUnderWarehouse($warehouseId)
    {
        $branches = Branch::where('warehouse_id', $warehouseId)
                        ->orderBy('name', 'asc')
                        ->get();

        return response()->json($branches, 200);
    }


    public function show($id)
    {
        $branch = Branch::where('id', $id)
                        ->with('branch_products')
                        ->first();

        return response()->json($branch );
    }

    public function searchBranch(Request $request)
    {
        $keyword = $request->input('keyword');

        $branch = Branch::where('name', 'like', "%$keyword%")
                        ->orderBy('created_at', 'desc')
                        ->take(7)
                        ->get();

        if ($branch->isEmpty()) {
            $branch = Branch::orderBy('created_at', 'desc')
                            ->take(7)
                            ->get();
        }

        return response()->json($branch, 200);
    }

    public function fetchBranchWithEmployee()
    {
        $brancWithEmployee = Branch::with('branchEmployee')
                                    ->orderBy('name', 'asc')
                                    ->get();

        return response()->json($brancWithEmployee, 200);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'warehouse_id'  => 'required|exists:warehouses,id',
            'employee_id'   => 'required|exists:employees,id',
            'name'          => 'required|unique:branches',
            'location'      => 'nullable',
            'phone'         => 'nullable',
            'status'        => 'nullable',
        ]);

        $existingBranch = Branch::where('name', $validatedData['name'])
                                ->where('location', $validatedData['location'])
                                ->first();

        if ($existingBranch) {
            return response()->json([
                'message' => 'Branch already exist'
            ]);
        }

        $branch = Branch:: create([
            'warehouse_id'  => $validatedData['warehouse_id'],
            'employee_id'   => $validatedData['employee_id'],
            'name'          => $validatedData['name'],
            'location'      => $validatedData['location'],
            'phone'         => $validatedData['phone'],
            'status'        => $validatedData['status'],
        ]);

        // LOG-18 — Branch: Create
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'type_of_report'   => 'Branch',
            'name'             => $branch->name,
            'action'           => 'created',
            'updated_data'     => $branch->toArray(),
            'designation'      => $branch->id,
            'designation_type' => 'branch',
        ]);

        $branchResponseData = $branch->fresh()->load('employees', 'warehouse');
        return response()->json([
            'message'    => 'Branch saved successfully',
            'branch'     => $branchResponseData
        ], 201);
    }

    public function update(Request $request, $id)
    {
        Log::info("Received ID: $id");

        $branch = Branch::find($id);
        if (!$branch) {
            return response()->json([
                'message' => 'Branch not found'
            ], 404);
        }

        $validatedData = $request->validate([
            'warehouse_id'  => 'sometimes|required|exists:warehouses,id',
            'employee_id'   => 'sometimes|required',
            'name'          => 'sometimes|required|unique:branches,name,' . $id,
            'location'      => 'nullable',
            'phone'         => 'nullable',
            'status'        => 'nullable',
        ]);

        $oldData = $branch->toArray();

        $branch->update($validatedData);

        // LOG-18 — Branch: Update
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'type_of_report'   => 'Branch',
            'name'             => $branch->name,
            'action'           => 'updated',
            'original_data'    => $oldData,
            'updated_data'     => $branch->toArray(),
            'designation'      => $branch->id,
            'designation_type' => 'branch',
        ]);
        $updated_branch = $branch->fresh()->load('warehouse', 'employees');
        return response()->json($updated_branch);
    }

    public function destroy($id)
    {
        $branch = Branch::find($id);
        if (!$branch) {
            return response()->json([
                'message' => 'Branch not found'
            ], 404);
        }

        $oldData = $branch->toArray();
        $branch->delete();

        // LOG — Branch: Delete
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'type_of_report'   => 'Branch',
            'name'             => $branch->name,
            'action'           => 'deleted',
            'original_data'    => $oldData,
            'designation'      => $branch->id,
            'designation_type' => 'branch',
        ]);

        return response()->json([
            'message' => 'Branch deleted successfully'
        ]);
    }
}
