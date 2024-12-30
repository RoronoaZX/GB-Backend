<?php

namespace App\Http\Controllers;

use App\Models\BranchRawMaterialsReport;
use Illuminate\Http\Request;

class BranchRawMaterialsReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $branchRawMaterials = BranchRawMaterialsReport::orderBy('created_at', 'desc')->with('ingredients')->get();
        return $branchRawMaterials;
    }

    public function getRawMaterials($branchId)
    {
        $branchRawMaterials = BranchRawMaterialsReport::where('branch_id', $branchId)->with(['branch', 'ingredients'])->get();

        return response()->json($branchRawMaterials, 200);
    }

    public function searchBranchRawMaterials(Request $request)
    {
        $keyword = $request->input('keyword');
        $branchId = $request->input('branch_id');

        $results = BranchRawMaterialsReport::with('ingredients')
                ->where('branch_id', $branchId)
                ->whereHas('ingredients', function ($query) use ($keyword){
                    $query->where('name', 'LIKE', '%' . $keyword . '%');
                })
                ->get();
        return response()->json($results);
    }

    public function fetchRawMaterialsIngredients(Request $request, $branchId)
    {
        // Validate the incoming request data
        $validateData = $request->validate([
            'category' => 'required|string',
        ]);

        // Fetch raw materials with ingredients for the specified branch and category
        $branchRawMaterials = BranchRawMaterialsReport::where('branch_id', $branchId)
            ->whereHas('ingredients', function ($query) use ($validateData) {
                // Check category in the RawMaterial table
                $query->where('category', $validateData['category']);
            })
            ->with('ingredients') // Eager load the related ingredient
            ->get();

        // Flatten the data to include raw material and ingredient details in each row
        $flattenedData = $branchRawMaterials->map(function ($rawMaterial) {
            return [
                'raw_material_report_id' => $rawMaterial->id,
                'branch_id' => $rawMaterial->branch_id,
                'raw_material_id' => $rawMaterial->ingredients->id, // Access the ingredient's ID
                'raw_material_name' => $rawMaterial->ingredients->name, // Access the ingredient's name
                'ingredient_category' => $rawMaterial->ingredients->category, // Access the ingredient's category
                'ingredient_quantity' => $rawMaterial->total_quantity, // If the quantity is in the report
                'ingredient_unit' => $rawMaterial->ingredients->unit, // Access the ingredient's unit
            ];
        });

        return response()->json($flattenedData);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'ingredients_id' => 'required',
            'total_quantity' => 'required|numeric',
        ]);

        $existingBranchRawMaterials = BranchRawMaterialsReport::where('branch_id', $validatedData['branch_id'])->where('ingredients_id', $validatedData['ingredients_id'])->first();

        if ($existingBranchRawMaterials) {
            return response()->json([
                'message' => 'The RawMaterials already exists in this branch.'
            ]);
        }

        $branchRawMaterials = BranchRawMaterialsReport::create([
            'branch_id' => $validatedData['branch_id'],
            'ingredients_id' => $validatedData['ingredients_id'],
            'total_quantity' => $validatedData['total_quantity'],
        ]);

        return response()->json([
            'message' => "Branch Raw Materials saved successfully",
            'data' => $branchRawMaterials
        ], 201);
    }

    public function updateStocks(Request $request, $id)
    {
        $validateData = $request->validate([
            'total_quantity' => 'required|integer'
        ]);
        $branchRawMaterials = BranchRawMaterialsReport::findorFail($id);
        $branchRawMaterials->total_quantity = $validateData['total_quantity'];
        $branchRawMaterials->save();

        return response()->json(['message' => 'Stocks updated successfully', 'total_quantity' => $branchRawMaterials]);
    }

    public function destroy($id)
    {
        $branchRawMaterials = BranchRawMaterialsReport::find($id);

        if (!$branchRawMaterials) {
            return response()->json([
                'message' => 'Raw materials not found'
            ], 404);
        }

        $branchRawMaterials->delete();
        return response()->json([
            'message' => 'Raw materials deleted successfully'
        ]);

    }
}
