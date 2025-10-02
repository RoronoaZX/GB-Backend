<?php

namespace App\Http\Controllers;

use App\Models\BreadGroup;
use App\Models\IngredientGroups;
use App\Models\Recipe;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function index()
    {
        $recipes = Recipe::orderBy('created_at', 'desc')->get();
        return response()->json($recipes);
    }

    public function searchRecipe(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string|max:255'
        ]);

        $keyword = $request->input('keyword');

       $recipes = Recipe::where('name', 'LIKE', "%{$keyword}%")->get();
        return response()->json($recipes);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name'       => 'required|string|max:255|unique:recipes',
            'category'   => 'required|string|max:30',
        ]);

        $recipe = Recipe::create([
            'name'       => $validatedData['name'],
            'category'   => $validatedData['category'],
        ]);

        $recipeResponseData = $recipe->fresh();

        return response()->json([
            'message'    => 'Recipe saved successfully',
            'recipe'     => $recipeResponseData
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $recipe = Recipe::find($id);

        if (!$recipe) {
            return response()->json([
                'success' => false,
                'message' => 'Recipe not found'
            ], 404);
        }

        $recipe->delete();

        return response()->json([
            'success' => true,
            'message' => 'Recipe deleted successfully'
        ], 200);
    }


    public function updateTarget(Request $request, $id)
    {
        $validatedData = $request->validate([
            'target' => 'required|integer',
        ]);

        $recipe = Recipe::findOrFail($id);
        $recipe->target = $validatedData['target'];
        $recipe->save();

        return response()->json(['message' => 'Target updated successfully', 'recipe' => $recipe]);
    }

    public function updateName(Request $request, $id)
    {
        $recipe = Recipe::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:recipes',
        ]);

        $recipe->name = $validatedData['name'];
        $recipe->save();

        return response()->json($recipe);
    }
    public function updateStatus(Request $request, $id)
    {
        $validatedData = $request->validate([
            'status' => 'required|string|max:255',
        ]);

        $recipe = Recipe::findOrFail($id);
        $recipe->status = $validatedData['status'];
        $recipe->save();

        return response()->json(['message' => 'Status updated successfully', 'recipe' => $recipe]);
    }
}
