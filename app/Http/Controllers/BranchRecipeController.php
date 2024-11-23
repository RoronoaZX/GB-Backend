<?php

namespace App\Http\Controllers;

use App\Models\BranchRecipe;
use Illuminate\Contracts\Support\ValidatedData;
use Illuminate\Http\Request;

class BranchRecipeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function getBranchRecipe($branchId)
    {
        $branchRecipe = BranchRecipe::orderBy('created_at', 'desc')->where('branch_id', $branchId)->with(['branch', 'recipe', 'breadGroups.bread', 'ingredientGroups.ingredient'])->get();

        $formattedBranchRecipes = $branchRecipe->map(function($branchRecipe) {
            return [
                'id' => $branchRecipe->id,
                'name' => $branchRecipe->recipe->name,
                'category' => $branchRecipe->recipe->category,
                'target' => $branchRecipe->target,
                'status' => $branchRecipe->status,
                'bread_groups' => $branchRecipe->breadGroups->pluck('bread.name'),
                'ingredient_groups' => $branchRecipe->ingredientGroups->map(function ($ingredientGroup) {
                    return [
                        'ingredient_name' => $ingredientGroup->ingredient->name,
                        'code' => $ingredientGroup->ingredient->code,
                        'quantity' => $ingredientGroup->quantity,
                        'unit' => $ingredientGroup->ingredient->unit
                    ];
                }),
            ];
        });
        return response()->json($formattedBranchRecipes, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'branch_id' => 'required|integer',
            'recipe_id' => 'required|integer',
            'target' => 'required|integer',
            'status' => 'required|string|max:30',
            'breads' => 'required|array',
            'breads.*.bread_id' => 'required|integer|exists:products,id',
            'ingredients' => 'required|array',
            'ingredients.*.ingredient_id' => 'required|integer|exists:raw_materials,id',
            'ingredients.*.quantity' => 'required',
        ]);

        $existingBranchRecipe = BranchRecipe::where('branch_id', $validatedData['branch_id'])->where('recipe_id', $validatedData['recipe_id'])->first();

        if ($existingBranchRecipe) {
            return response()->json([
                'message' => 'The recipe already exists in this branch.'
            ]);
        }
        $branchRecipe = BranchRecipe::create($validatedData);

        $branchRecipe->ingredientGroups()->createMany($validatedData['ingredients']);
        $branchRecipe->breadGroups()->createMany($validatedData['breads']);

        return response()->json([
            'message' => 'Branch recipe saved successfully',
            'data' => $branchRecipe
        ]);
    }

    public function updateTarget(Request $request, $id)
    {
        $validatedData = $request->validate([
            'target' => 'required|integer',
        ]);

        $recipe = BranchRecipe::findOrFail($id);
        $recipe->target = $validatedData['target'];
        $recipe->save();

        return response()->json(['message' => 'Target updated successfully', 'recipe' => $recipe]);
    }
    public function branchUpdateStatus(Request $request, $id)
    {
        $validatedData = $request->validate([
            'status' => 'required|string',
        ]);

        $recipe = BranchRecipe::findOrFail($id);
        $recipe->status = $validatedData['status'];
        $recipe->save();

        return response()->json(['message' => 'Status updated successfully', 'recipe' => $recipe]);
    }

    public function branchSearchRecipe(Request $request)
    {
        $searchBranchRecipe = $request->input('keyword');
        $searchBranchRecipeId = $request->input('branch_id');

        $branchRecipe = BranchRecipe::with('recipe')
            ->when($searchBranchRecipe !== null, function ($query) use ($searchBranchRecipe) {
                $query->whereHas('recipe', function ($recipeQuery) use ($searchBranchRecipe) {
                    $recipeQuery->where('name', 'like', "%{$searchBranchRecipe}%");
                });
            })
            ->when($searchBranchRecipeId !== null, function ($query) use ($searchBranchRecipeId) {
                $query->where('branch_id', $searchBranchRecipeId); // Filter by branch ID if provided
            })
            ->with(['breadGroups.bread', 'ingredientGroups.ingredient'])
            ->orderBy('created_at', 'desc')
            ->take(12)
            ->get();

        $formattedRecipes = $branchRecipe->map(function ($recipe) {
            return [
                'id' => $recipe->id,
                'name' => $recipe->recipe->name,
                'category' => $recipe->recipe->category,
                'target' => $recipe->target,
                'bread_groups' => $recipe->breadGroups->map(function ($breadGroup) {
                    return [
                        'product_id' => $breadGroup->bread->id,
                        'bread_name' => $breadGroup->bread->name,
                    ];
                }),
                'ingredients' => $recipe->ingredientGroups->map(function ($ingredientGroup) {
                    return [
                        'raw_materials_id' => $ingredientGroup->ingredient->id,
                        'code' => $ingredientGroup->ingredient->code,
                        'ingredient_name' => $ingredientGroup->ingredient->name,
                        'quantity' => $ingredientGroup->quantity,
                        'unit' => $ingredientGroup->ingredient->unit,
                    ];
                })
            ];
        });

        return response()->json($formattedRecipes);
    }

    // public function branchSearchRecipe(Request $request)
    // {
    //     $searchBranchRecipe = $request->input('keyword');

    //     $branchRecipe = BranchRecipe::with('recipe')
    //                 ->when($searchBranchRecipe !== null, function ($query) use ($searchBranchRecipe) {
    //                     $query->whereHas('recipe', function ($recipeQuery) use ($searchBranchRecipe) {
    //                         $recipeQuery->where('name', 'like', "%{$searchBranchRecipe}%");
    //                     });
    //                 })
    //                 ->with(['breadGroups.bread', 'ingredientGroups.ingredient'])
    //                 ->orderBy('created_at', 'desc')
    //                 ->take(12)
    //                 ->get();
    //     return response()->json($branchRecipe);
    // }

    /**
     * Display the specified resource.
     */
    public function show(BranchRecipe $branchRecipe)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BranchRecipe $branchRecipe)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BranchRecipe $branchRecipe)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $branchRecipe = BranchRecipe::find($id);

        if (!$branchRecipe) {
            return response()->json([
                'success' => false,
                'message' => 'Recipe not found'
            ], 404);
        }

        $branchRecipe->delete();

        return response()->json([
            'success' => true,
            'message' => 'Recipe deleted successfully'
        ], 200);
    }

}
