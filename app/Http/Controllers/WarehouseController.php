<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\BranchRawMaterialsReport;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouse = Warehouse::orderBy('created_at', 'desc')->with('employees')->get();
        return  $warehouse;
    }

    public function getWarehouse($warehouseId)
    {
        $warehouse = Warehouse::find($warehouseId);

        if (!$warehouse) {
            return response()->json([
                'message' => 'Warehouse not found'
            ], 404);
        }

        return response()->json($warehouse, 200);
    }

    // public function getWarehouseBranchReport($warehouseId)
    // {
    //     // Fetch all reports for branches within the given warehouse
    //     $reports = BranchRawMaterialsReport::query()
    //         ->whereHas('branch', function ($query) use ($warehouseId) {
    //             $query->where('warehouse_id', $warehouseId);
    //         })
    //         ->with('branch', 'ingredients') // Eager load branch details
    //         ->get();

    //     // Group the reports by branch_id
    //     $groupedReports = $reports->groupBy(function ($report) {
    //         return $report->branch_id;
    //     })->map(function ($reports, $branchId) {
    //         return [
    //             'branch_id' => $branchId,
    //             'branch_name' => $reports->first()->branch->name, // Assuming `name` exists
    //             'reports' => $reports->map(function ($report) {
    //                 return [
    //                     'id' => $report->id,
    //                     'report_date' => $report->created_at,
    //                     'raw_material' =>  [
    //                         'id' => $report->ingredients_id,
    //                         'name' => $report->ingredients->name, // Assuming `name` exists in the ingredient table
    //                         'code' => $report->ingredients->code,
    //                         'unit' => $report->ingredients->unit,
    //                         'category' => $report->ingredients->category
    //                     ],
    //                     'quantity' => $report->total_quantity,
    //                     // Add other fields as needed
    //                 ];
    //             }),
    //         ];
    //     })->values();

    //     return response()->json($groupedReports);
    // }
    public function getWarehouseBranchReport($warehouseId)
{
    // Fetch all branches under the warehouse
    $branches = Branch::where('warehouse_id', $warehouseId)->get();

    // Fetch all reports for branches within the warehouse
    $reports = BranchRawMaterialsReport::query()
        ->whereHas('branch', function ($query) use ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        })
        ->with('branch', 'ingredients') // Eager load branch and ingredient details
        ->get();

    // Group reports by branch_id and ensure it's a collection
    $groupedReports = $reports->groupBy('branch_id')->map(function ($reports, $branchId) {
        return collect([
            'branch_id' => $branchId,
            'branch_name' => $reports->first()->branch->name, // Assuming `name` exists
            'reports' => $reports->map(function ($report) {
                return [
                    'id' => $report->id,
                    'report_date' => $report->created_at,
                    'raw_material' => [
                        'id' => $report->ingredients_id,
                        'name' => $report->ingredients->name,
                        'code' => $report->ingredients->code,
                        'unit' => $report->ingredients->unit,
                        'category' => $report->ingredients->category,
                    ],
                    'quantity' => $report->total_quantity,
                ];
            })->values(), // Ensure it's a collection
        ]);
    });

    // Ensure all branches are included, even those without reports
    $finalResult = $branches->map(function ($branch) use ($groupedReports) {
        return [
            'branch_id' => $branch->id,
            'branch_name' => $branch->name,
            'reports' => $groupedReports->get($branch->id, collect(['reports' => collect()]))['reports'], // Always return a collection
        ];
    });

    return response()->json($finalResult);
}



    // public function getWarehouseBranchReport($warehouseId)
    // {
    //     $report = BranchRawMaterialsReport::query()
    //             ->whereHas('branch', function ($query) use ($warehouseId) {
    //                 $query->where('warehouse_id', $warehouseId);
    //             })
    //             ->with('branch')
    //             ->get();

    // return response()->json(['data',$report]);

    // }

//     public function getWarehouseBranchReport($warehouseId)
// {
//     // Fetch all branches and their reports for the given warehouse
//     $branchesWithReports = Branch::where('warehouse_id', $warehouseId)
//         ->with('branchRawMaterialsReport') // Ensure the relationship is loaded
//         ->get();

//     // Format the data to group reports by branch
//     $groupedData = $branchesWithReports->map(function ($branch) {
//         return [
//             'branch_id' => $branch->id,
//             'branch_name' => $branch->name,
//             'reports' => $branch->branchRawMaterialsReports, // Reports for this branch
//         ];
//     });

//     // Return the grouped data
//     return response()->json([
//         'success' => true,
//         'data' => $groupedData,
//     ]);
// }

    public function searchWarehouse(Request $request)
    {
        $keyword = $request->input('keyword');

        $warehouse = Warehouse::where('name', 'like', "%$keyword%")
                        ->orderBy('created_at', 'desc')
                        ->take(7)
                        ->get();

        if ($warehouse->isEmpty()) {
            $warehouse = Warehouse::orderBy('created_at', 'desc')->take(7)->get();
        }

        return response()->json($warehouse, 200);
    }



    public function fetchWarehouseWithEmployee()
    {
        $warehouseWithEmployee = Warehouse::with('warehouseEmployee')->orderBy('name', 'asc')->get();
        return response()->json($warehouseWithEmployee,200);
    }

    public function store(Request $request)
    {
        $validateData = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'name' => 'required|unique:warehouses',
            'location' => 'required',
            'phone' => 'required',
            'status' => 'required',
        ]);
        // ||unique:warehouses

        $existingWarehouse = Warehouse::where('name', $validateData['name'])
                                    ->where('location', $validateData['location'])
                                    ->first();
        if ($existingWarehouse) {
            return response()->json([
                'message' => 'Warehouse already exist'
            ]);
        }

        $warehouse = Warehouse::create([
            'employee_id' => $validateData['employee_id'],
            'name' => $validateData['name'],
            'location' => $validateData['location'],
            'phone' => $validateData['phone'],
            'status' => $validateData['status'],

        ]);

        $warehouseResponseData = $warehouse->fresh()->load('employees');

        return response()->json([
            'message' => 'Warehouse saved successfully',
            'warehouse' => $warehouseResponseData
        ], 201);
    }


    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::find($id);
        if (!$warehouse) {
            return response()->json([
                'message' => 'Raw material not found'
            ], 404);
        }
        $validatedData = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'name' => 'required|unique:warehouses',
            'location' => 'required',
            'phone' => 'required',
            'status' => 'required',
        ]);
        $warehouse->update($validatedData);
        $updated_warehouse = $warehouse->fresh()->load('employees');
        return response()->json($updated_warehouse);
    }

    public function destroy($id)
    {
       $warehouse = Warehouse::find($id);
       if (!$warehouse) {
        return response()->json([
            'message' => 'Warehouse not found'
        ]);
       }
       $warehouse->delete();
       return response()->json([
        'message' => 'Warehouse deleted successfully'
       ]);
    }

}
