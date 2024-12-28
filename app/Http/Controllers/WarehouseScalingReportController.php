<?php

namespace App\Http\Controllers;

use App\Models\WarehouseScalingMaterial;
use App\Models\WarehouseScalingReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WarehouseScalingReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    // Validate the main reports array
    $validator = Validator::make($request->all(), [
        'reports' => 'required|array',
        'reports.*.branch_id' => 'required|integer|exists:branches,id',
        'reports.*.warehouse_id' => 'required|integer|exists:warehouses,id',
        'reports.*.employee_id' => 'required|integer|exists:employees,id',
        'reports.*.recipe_id' => 'required|integer|exists:recipes,id',
        'reports.*.status' => 'required|string',
        'reports.*.ingredients' => 'required|array',
        'reports.*.ingredients.*.raw_materials_id' => 'required|integer|exists:raw_materials,id',
        'reports.*.ingredients.*.quantity' => 'required|numeric',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed for reports',
            'errors' => $validator->errors(),
        ], 422);
    }

    // Process and save each report
    foreach ($request->reports as $report) {
        $warehouseScalingReport = WarehouseScalingReport::create([
            'branch_id' => $report['branch_id'],
            'warehouse_id' => $report['warehouse_id'],
            'employee_id' => $report['employee_id'],
            'recipe_id' => $report['recipe_id'],
            'status' => $report['status'],
        ]);

        // Save ingredients associated with the WarehouseScalingReport
        foreach ($report['ingredients'] as $ingredient) {
            WarehouseScalingMaterial::create([
                'warehouse_scaling_report_id' => $warehouseScalingReport->id,
                'raw_material_id' => $ingredient['raw_materials_id'],
                'quantity' => $ingredient['quantity'],
            ]);
        }
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Reports and ingredients saved successfully',
    ], 201);
}

    /**
     * Display the specified resource.
     */
    public function show(WarehouseScalingReport $warehouseScalingReport)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WarehouseScalingReport $warehouseScalingReport)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WarehouseScalingReport $warehouseScalingReport)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WarehouseScalingReport $warehouseScalingReport)
    {
        //
    }
}
