<?php

namespace App\Http\Controllers;

use App\Models\BranchPremix;
use Illuminate\Http\Request;

class BranchPremixController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function searchBranchPremix(Request $request)
    {
        $searchBranchPremix = $request->input('keyword');
        $searchBranchPremixId = $request->input('branch_id');

        $branchPremix = BranchPremix::with('branch_recipe')
            ->where('status', 'active')
            ->when($searchBranchPremix, function ($query, $searchBranchPremix) {
                return $query->where('name', 'LIKE', "%{$searchBranchPremix}%");
            })
            ->when($searchBranchPremixId, function ($query, $searchBranchPremixId) {
                return $query->where('branch_id', $searchBranchPremixId);
            })
            ->get();

        return response()->json($branchPremix);
    }

    public function getBranchPremix($branchId)
    {
        $branchPremix = BranchPremix::orderBy('created_at', 'desc')->where('branch_id', $branchId)->with(['branch_recipe.recipe'])->get();

        return response()->json($branchPremix);
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'branch_recipe_id' => 'required|exists:branch_recipes,id',
            'name' => 'required|string|max:50',
            'category' => 'required|string|max:50',
            'status' => 'required|string|max:50',
            'available_stocks' => 'required|numeric',
        ]);

        // Check if the branch_recipe_id already exists
        $exists = BranchPremix::where('branch_recipe_id', $request->branch_recipe_id)->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Branch recipe already exists.'
            ], 422);
        }

        // Create new BranchPremix
        $branchPremix = BranchPremix::create([
            'branch_id' => $request->branch_id,
            'branch_recipe_id' => $request->branch_recipe_id,
            'name' => $request->name,
            'category' => $request->category,
            'status' => $request->status,
            'available_stocks' => $request->available_stocks,
        ]);

        return response()->json([
            'message' => 'Branch premix created successfully.',
            'branchPremix' => $branchPremix
        ], 201);
    }

    public function updateRequestPremixStatus(Request $request, $id)
    {
        $validatedData = $request->validate(['status' => 'required|string']);
        $recipe = BranchPremix::findOrFail($id);
        $recipe->status = $validatedData['status'];
        $recipe->save();

        return response()->json(['message' => 'Status updated successfully', 'recipe' => $recipe]);
    }


    /**
     * Display the specified resource.
     */
    public function show(BranchPremix $branchPremix)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BranchPremix $branchPremix)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BranchPremix $branchPremix)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BranchPremix $branchPremix)
    {
        //
    }
}
