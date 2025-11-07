<?php

namespace App\Http\Controllers;

use App\Models\BranchRawMaterialsReport;
use App\Models\RawMaterial;
use App\Models\WarehouseRawMaterialsReport;
use Illuminate\Http\Request;

class RawMaterialController extends Controller
{
    public function index()
    {

        $raw_materials = RawMaterial::orderBy('created_at', 'desc')->get();
        return  $raw_materials;
    }

    public function fetchrawMaterialsBranchWarehouse(Request $request, $id)
    {
        $designation = strtolower($request->input('designation'));

        if ($designation === 'branch') {
                $rawMaterials = BranchRawMaterialsReport::where('branch_id', $id)
                ->with('ingredients')
                ->get()
                ->pluck('ingredients');

        } elseif ($designation === 'warehouse') {
                $rawMaterials = WarehouseRawMaterialsReport::where('warehouse_id', $id)
                ->with('rawMaterials')
                ->get()
                ->pluck('rawMaterials');

        } else {
                return response()->json([
                    'message' => 'Invalid designation'
                ], 400);
        }

        if ($rawMaterials->isEmpty()) {
            return response()->json([
                'message'    => "No raw materials found for this {$designation}",
                'data'       => [],
            ]);
        }

        return response()->json([
            'message'    => 'Raw materials fetched successfully',
            'data'       => $rawMaterials,
        ]);
    }

    public function searchRawMaterials(Request $request)
    {
        $keyword = $request->input('keyword');

        $request->validate([
            'keyword' => 'required|string|max:255'
        ]);

        $results = RawMaterial::search($keyword)->get();

        return response()->json($results);

    }

    public function store(Request $request)
    {
        $validateData = $request->validate([
            'name'       => 'required|unique:raw_materials,name',
            'code'       => 'required|unique:raw_materials,code',
            'category'   => 'required',
            'unit'       => 'required',
        ]);

        $rawMaterials = RawMaterial::create($validateData);

        return response()->json([
            'message'        => 'Raw Materials saved successfully',
            'rawMaterials'   => $rawMaterials
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $raw_material = RawMaterial::find($id);

        if (!$raw_material) {
            return response()->json([
                'message' => 'Raw material not found'
            ], 404);
        }

        $validateData = $request->validate([
            'name'       => 'required|unique:raw_materials,name',
            'code'       => 'required|unique:raw_materials,code',
            'category'   => 'required',
            'unit'       => 'required',
        ]);

        $raw_material->update($validateData);
        $updated_raw_material = $raw_material->fresh();
        return response()->json($updated_raw_material);
    }

    public function destroy($id)
    {
        $raw_materials = RawMaterial::find($id);
        if (!$raw_materials) {
            return response()->json([
                'message' => 'Raw materials not found'
            ], 404);
        }

        $raw_materials->delete();
        return response()->json([
            'message' => 'raw material deleted successfully'
        ], 200);
    }

    public function fetchRawMaterialsIngredients()
    {
        $ingredients = RawMaterial::where('category','ingredients')->get();
        return response()->json($ingredients);
    }

}
