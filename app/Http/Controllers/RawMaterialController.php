<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use Illuminate\Http\Request;

class RawMaterialController extends Controller
{
    public function index()
    {

        $raw_materials = RawMaterial::orderBy('created_at', 'desc')->get();
        return  $raw_materials;
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
        $raw_material = RawMaterial::create($request->all());

        return response()->json($raw_material);
    }

    public function update(Request $request, $id)
    {
        $raw_material = RawMaterial::find($id);

        if (!$raw_material) {
            return response()->json([
                'message' => 'Raw material not found'
            ], 404);
        }
        $raw_material->update($request->all());
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
