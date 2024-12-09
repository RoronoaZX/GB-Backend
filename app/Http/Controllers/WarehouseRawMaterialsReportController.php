<?php

namespace App\Http\Controllers;

use App\Models\WarehouseRawMaterialsReport;
use Illuminate\Http\Request;

class WarehouseRawMaterialsReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $warehouseRawMaterials = WarehouseRawMaterialsReport::orderBy('created_at', 'desc')->with('rawMaterials')->get();

        return $warehouseRawMaterials;
    }

    public function getRawMaterials($warehouseId)
    {
        $warehouseRawMaterials = WarehouseRawMaterialsReport::where('warehouse_id', $warehouseId)->with(['warehouse', 'rawMaterials'])->get()
                            ->map(function ($warehouseRawMaterials) {
                                $rawMaterials = $warehouseRawMaterials->rawMaterials;

                                return $rawMaterials;
                            });

        return response()->json($warehouseRawMaterials, 200);
    }

    public function searchWarehouseRawMaterials(Request $request)
    {
        $keyword = $request->input('keyword');
        $warehouseId = $request->input('warehouse_id');

        $results = WarehouseRawMaterialsReport::with('rawMaterials')
                ->where('rawMaterials', $warehouseId)
                ->whereHas('rawMaterials', function ($query) use ($keyword){
                    $query->where('name', 'LIKE', '%' . $keyword . '%');
                })
                ->get();
        return response()->json($results);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'raw_material_id' => 'required',
            'total_quantity' => 'required|numeric'
        ]);

        $existingWarehouseRawMaterials = WarehouseRawMaterialsReport::where('warehouse_id', $validatedData['warehouse_id'])->where('raw_material_id', $validatedData['raw_material_id'])->first();

        if ($existingWarehouseRawMaterials) {
            return response()->json([
                'message' => 'The RawMaterials already exists in this branch.'
            ]);
        }

        $warehouseRawMaterials = WarehouseRawMaterialsReport::create([
            'warehouse_id' => $validatedData['warehouse_id'],
            'raw_material_id' =>  $validatedData['raw_material_id'],
            'total_quantity' =>  $validatedData['total_quantity'],
        ]);

        return response()->json([
            'message' => "Warehouse Raw Materials saved successfully",
            'data' => $warehouseRawMaterials
        ], 201);
    }

    public function updateStocks(Request $request, $id)
    {
        $validateData = $request->validate([
            'total_quantity' => 'required|integer'
        ]);
        $warehouseRawMaterials = WarehouseRawMaterialsReport::findorFail($id);
        $warehouseRawMaterials->total_quantity = $validateData['total_quantity'];
        $warehouseRawMaterials->save();

        return response()->json(['message' => 'Stocks updated successfully', 'total_quantity' => $warehouseRawMaterials]);
    }

    public function destroy($id)
    {
        $warehouseRawMaterials = WarehouseRawMaterialsReport::find($id);

        if (!$warehouseRawMaterials) {
            return response()->json([
                'message' => 'Raw materials not found'
            ], 404);
        }

        $warehouseRawMaterials->delete();
        return response()->json([
            'message' => 'Raw materials deleted successfully'
        ]);

    }
}
