<?php

namespace App\Http\Controllers;

use App\Models\BranchRawMaterialsReport;
use App\Models\HistoryLog;
use App\Models\RawMaterial;
use Illuminate\Http\Client\ResponseSequence;
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
        $branchRawMaterials = BranchRawMaterialsReport::orderBy('created_at', 'desc')
                                                        ->with('ingredients')
                                                        ->get();

        return $branchRawMaterials;
    }

    public function getRawMaterials($branchId)
    {
        $branchRawMaterials = BranchRawMaterialsReport::where('branch_id', $branchId)
                                ->with([
                                    'branch',
                                    'ingredients',
                                    'oldestNonZeroStock' => function ($query) use  ($branchId) {
                                        $query->where('branch_id', $branchId)
                                                ->where('quantity', '>', 0) // ✅ strictly greater then 0
                                                ->orderBy('created_at', 'asc') // ✅ oldest first
                                                ->select(
                                                    'id', 'raw_material_id', 'branch_id',
                                                    'price_per_gram', 'quantity', 'created_at','updated_at'
                                                    )
                                                ->limit(1);
                                        }
                                    ])
                                ->get();

        return response()->json($branchRawMaterials, 200);
    }

    public function searchBranchRawMaterials(Request $request)
    {
        $keyword  = $request->input('keyword');
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
                'branch_id'              => $rawMaterial->branch_id,
                'raw_material_id'        => $rawMaterial->ingredients->id, // Access the ingredient's ID
                'raw_material_name'      => $rawMaterial->ingredients->name, // Access the ingredient's name
                'ingredient_category'    => $rawMaterial->ingredients->category, // Access the ingredient's category
                'ingredient_quantity'    => $rawMaterial->total_quantity, // If the quantity is in the report
                'ingredient_unit'        => $rawMaterial->ingredients->unit, // Access the ingredient's unit
            ];
        });

        return response()->json($flattenedData);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function bulkStore(Request $request)
    {
        $data = $request->input('materials');

        if (!is_array($data) || empty($data)) {
            return response()->json([
                'message' => 'No raw materials provided'
            ], 400);
        }

        // Extract ingredient and branch pairs
        $rawMaterialBranchPairs = collect($data)->map(function ($material) {
            return [
                'ingredients_id' => $material['ingredients_id'],
                'branch_id'      => $material['branch_id']
            ];
        });

        // Fetch existing pairs
        $existingRecords = BranchRawMaterialsReport::whereIn('ingredients_id', $rawMaterialBranchPairs->pluck('ingredients_id'))
                            ->whereIn('branch_id',
                                        $rawMaterialBranchPairs->pluck('branch_id')
                                    )
                            ->get(['ingredients_id', 'branch_id'])
                            ->toArray();

        // Map existing pairs to a lookup array
        $existingPairs = [];
        foreach ($existingRecords as $record) {
            $existingPairs[$record['ingredients_id'] . '_' . $record['branch_id']] = true;
        }

        // Filter out new materials that aren't already in DB
        $newMaterials = array_filter($data, function ($material) use ($existingPairs) {
                            $key = $material['ingredients_id'] . '_' . $material['branch_id'];
                            return !isset($existingPairs[$key]);
        });

        if (empty($newMaterials)) {
            return response()->json([
                'message' => 'All raw materials already exist in the warehouse'
            ], 200);
        }

        // Add timestamps to new records
        $now = now();
        foreach ($newMaterials as &$material) {
            $material['created_at'] = $now;
            $material['updated_at'] = $now;
        }

        // Insert into DB
        BranchRawMaterialsReport::insert($newMaterials);

        // Fetch inserted records with relationships
        $insertedReports = BranchRawMaterialsReport::with(['ingredients', 'branch'])
                            ->whereIn('ingredients_id',
                                        collect($newMaterials)
                                        ->pluck('ingredients_id')
                                    )
                            ->whereIn('branch_id',
                                        collect($newMaterials)
                                        ->pluck('branch_id')
                                        )
                            ->orderByDesc('id') // optional: ensure latest ones first
                            ->get();

        return response()->json([
            'message'    => 'Raw materials added successfully!',
            'data'       => $insertedReports
        ]);
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
            'branch_id'          => 'required|exists:branches,id',
            'ingredients_id'     => 'required',
            'total_quantity'     => 'required|numeric',
        ]);

        $existingBranchRawMaterials = BranchRawMaterialsReport::where('branch_id', $validatedData['branch_id'])
                                                                ->where('ingredients_id', $validatedData['ingredients_id'])
                                                                ->first();

        if ($existingBranchRawMaterials) {
            return response()->json([
                'message' => 'The RawMaterials already exists in this branch.'
            ]);
        }

        $branchRawMaterials = BranchRawMaterialsReport::create([
            'branch_id'          => $validatedData['branch_id'],
            'ingredients_id'     => $validatedData['ingredients_id'],
            'total_quantity'     => $validatedData['total_quantity'],
        ]);

        return response()->json([
            'message'    => "Branch Raw Materials saved successfully",
            'data'       => $branchRawMaterials
        ], 201);
    }

    public function updateStocks(Request $request, $id)
    {
        $validateData = $request->validate([
            'total_quantity' => 'required|integer'
        ]);
        $branchRawMaterials                  = BranchRawMaterialsReport::findorFail($id);
        $branchRawMaterials->total_quantity  = $validateData['total_quantity'];
        $branchRawMaterials->save();

        HistoryLog::create([
            'report_id'          => $request->input('report_id'),
            'name'               => $request->input('name'),
            'original_data'      => $request->input('original_data'),
            'updated_data'       => $request->input('updated_data'),
            'updated_field'      => $request->input('updated_field'),
            'designation'        => $request->input('designation'),
            'designation_type'   => $request->input('designation_type'),
            'action'             => $request->input('action'),
            'type_of_report'     => $request->input('type_of_report'),
            'user_id'            => $request->input('user_id'),
        ]);

        return response()->json([
            'message' => 'Stocks updated successfully',
            'total_quantity' => $branchRawMaterials
        ]);
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
