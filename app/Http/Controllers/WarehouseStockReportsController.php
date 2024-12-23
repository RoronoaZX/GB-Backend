<?php

namespace App\Http\Controllers;

use App\Models\WarehouseAddedStock;
use App\Models\WarehouseRawMaterialsReport;
use App\Models\WarehouseStockReports;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class WarehouseStockReportsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'employee_id' => 'required|integer|exists:employees,id',
            'supplier_company_name' => 'required|string|max:255',
            'supplier_name' => 'required|string|max:255',
            'raw_materials' => 'required|array',
            'raw_materials.*.raw_material_id' => 'required|integer|exists:raw_materials,id',
            'raw_materials.*.quantity' => 'required|integer|min:1',
        ]);

        // Create a warehouse stock report
        $warehouseStockReport = WarehouseStockReports::create([
            'warehouse_id' => $validatedData['warehouse_id'],
            'employee_id' => $validatedData['employee_id'],
            'supplier_company_name' => $validatedData['supplier_company_name'],
            'supplier_name' => $validatedData['supplier_name'],
        ]);

        // Add raw materials to the stock report
        foreach ($validatedData['raw_materials'] as $rawMaterial) {
            $warehouseStockReport->warehouseAddedStocks()->create([
                'raw_material_id' => $rawMaterial['raw_material_id'],
                'quantity' => $rawMaterial['quantity'],
            ]);

            $warehouseAddedStock = WarehouseRawMaterialsReport::where('warehouse_id', $validatedData['warehouse_id'])
                                ->where('raw_material_id', $rawMaterial['raw_material_id'])
                                ->first();
            if ($warehouseAddedStock) {
                $warehouseAddedStock->total_quantity += $rawMaterial['quantity'];
                $warehouseAddedStock->save();
            }
        }

        // Return a success response
        return response()->json([
            'message' => 'Warehouse stock report created successfully.',
            'data' => $warehouseStockReport->load('warehouseAddedStocks'),
        ], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(WarehouseStockReports $warehouseStockReports)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(WarehouseStockReports $warehouseStockReports)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WarehouseStockReports $warehouseStockReports)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WarehouseStockReports $warehouseStockReports)
    {
        //
    }
}
