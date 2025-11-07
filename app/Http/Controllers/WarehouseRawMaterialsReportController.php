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
        $warehouseRawMaterials = WarehouseRawMaterialsReport::orderBy('created_at', 'desc')
                                    ->with('rawMaterials')
                                    ->get();

        return $warehouseRawMaterials;
    }

    public function getRawMaterials($warehouseId)
    {
        $warehouseRawMaterials = WarehouseRawMaterialsReport::where('warehouse_id', $warehouseId)
                                    ->with(['rawMaterials'])
                                    ->get();

        return response()->json($warehouseRawMaterials, 200);
    }

    public function searchWarehouseRawMaterials(Request $request)
    {
        $keyword         = $request->input('keyword');
        $warehouseId     = $request->input('warehouse_id');

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
            'warehouse_id'       => 'required|exists:warehouses,id',
            'raw_material_id'    => 'required',
            'total_quantity'     => 'required|numeric'
        ]);

        $existingWarehouseRawMaterials = WarehouseRawMaterialsReport::where('warehouse_id', $validatedData['warehouse_id'])
                                            ->where('raw_material_id', $validatedData['raw_material_id'])
                                            ->first();

        if ($existingWarehouseRawMaterials) {
            return response()->json([
                'message' => 'The RawMaterials already exists in this branch.'
            ]);
        }

        $warehouseRawMaterials = WarehouseRawMaterialsReport::create([
            'warehouse_id'       => $validatedData['warehouse_id'],
            'raw_material_id'    =>  $validatedData['raw_material_id'],
            'total_quantity'     =>  $validatedData['total_quantity'],
        ]);

        return response()->json([
            'message'    => "Warehouse Raw Materials saved successfully",
            'data'       => $warehouseRawMaterials
        ], 201);
    }

    public function bulkStore(Request $request)
    {
        $data = $request->input('materials');

        if (!is_array($data) || empty($data)) {
            return response()->json(['message' => 'No raw materials provided'], 400);
        }

        // Extract raw_material_id and warehouse_id from input data
        $rawMaterialWarehousePairs = collect($data)->map(function ($material) {
            return [
                'raw_material_id'    => $material['raw_material_id'],
                'warehouse_id'       => $material['warehouse_id']];
        });

        // Fetch existing records that match both raw_material_id and warehouse_id
        $existingRecords = WarehouseRawMaterialsReport::whereIn('raw_material_id', $rawMaterialWarehousePairs->pluck('raw_material_id'))
                            ->whereIn('warehouse_id', $rawMaterialWarehousePairs->pluck('warehouse_id'))
                            ->get(['raw_material_id', 'warehouse_id'])
                            ->toArray();

        // Convert to an associative array for easier lookup
        $existingPairs = [];

        foreach ($existingRecords as $record) {
            $existingPairs[$record['raw_material_id'] . '_' . $record['warehouse_id']] = true;
        }

        // Filter out existing materials
        $newMaterials = array_filter($data, function ($material) use ($existingPairs) {
            return !isset($existingPairs[$material['raw_material_id'] . '_' . $material['warehouse_id']]);
        });

        if (empty($newMaterials)) {
            return response()->json(['message' => 'All raw materials already exist in the warehouse'], 200);
        }

        // Add timestamps
        $now = now();
        foreach ($newMaterials as &$material) {
            $material['created_at'] = $now;
            $material['updated_at'] = $now;
        }

        // Insert only new materials
        WarehouseRawMaterialsReport::insert($newMaterials);

        $insertedRawMaterials = WarehouseRawMaterialsReport::with(['rawMaterials', 'warehouse'])
                                    ->whereIn('raw_material_id', collect($newMaterials)->pluck('raw_material_id'))
                                    ->whereIn('warehouse_id', collect($newMaterials)->pluck('warehouse_id'))
                                    ->orderByDesc('id')
                                    ->get();

        return response()->json([
            'message'    => 'Raw materials added successfully!',
            'data'       => $insertedRawMaterials
        ]);
    }


    public function updateStocks(Request $request, $id)
    {
        $validateData = $request->validate([
            'total_quantity' => 'required|integer'
        ]);
        $warehouseRawMaterials                   = WarehouseRawMaterialsReport::findorFail($id);
        $warehouseRawMaterials->total_quantity   = $validateData['total_quantity'];
        $warehouseRawMaterials->save();

        return response()->json([
            'message' => 'Stocks updated successfully',
            'total_quantity' => $warehouseRawMaterials
        ]);
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
