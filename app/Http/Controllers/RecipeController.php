<?php

namespace App\Http\Controllers;

use App\Models\BreadGroup;
use App\Models\IngredientGroups;
use App\Models\Recipe;
use Illuminate\Http\Request;
use App\Services\HistoryLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        DB::beginTransaction();
        try {
            $recipe = Recipe::create([
                'name'       => $validatedData['name'],
                'category'   => $validatedData['category'],
            ]);

            $recipeResponseData = $recipe->fresh();

            // LOG-30 — Recipe: Created
            HistoryLogService::log([
                'user_id'          => Auth::id(),
                'report_id'        => $recipe->id,
                'type_of_report'   => 'Recipe',
                'name'             => $recipe->name,
                'action'           => 'created',
                'updated_data'     => $recipeResponseData->toArray(),
                'designation'      => 0, // Master data
                'designation_type' => 'warehouse',
            ]);

            DB::commit();

            return response()->json([
                'message'    => 'Recipe saved successfully',
                'recipe'     => $recipeResponseData
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create recipe', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $recipe = Recipe::find($id);

        if (!$recipe) {
            return response()->json([
                'success'    => false,
                'message'    => 'Recipe not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $oldData = $recipe->toArray();
            $recipe->delete();

            // LOG-30 — Recipe: Deleted
            HistoryLogService::log([
                'user_id'          => Auth::id(),
                'report_id'        => $id,
                'type_of_report'   => 'Recipe',
                'name'             => $oldData['name'],
                'action'           => 'deleted',
                'original_data'    => $oldData,
                'designation'      => 0,
                'designation_type' => 'warehouse',
            ]);

            DB::commit();

            return response()->json([
                'success'        => true,
                'message'        => 'Recipe deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete recipe', 'error' => $e->getMessage()], 500);
        }
    }


    public function updateTarget(Request $request, $id)
    {
        $validatedData = $request->validate([
            'target' => 'required|integer',
        ]);

        $recipe          = Recipe::findOrFail($id);
        $oldTarget       = $recipe->target;
        $recipe->target  = $validatedData['target'];
        $recipe->save();

        // LOG-30 — Recipe: Target Updated
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'report_id'        => $recipe->id,
            'type_of_report'   => 'Recipe',
            'name'             => $recipe->name,
            'action'           => 'updated',
            'updated_field'    => 'target',
            'original_data'    => $oldTarget,
            'updated_data'     => $recipe->target,
            'designation'      => 0,
            'designation_type' => 'warehouse',
        ]);

        return response()->json(['message' => 'Target updated successfully', 'recipe' => $recipe]);
    }

    public function updateName(Request $request, $id)
    {
        $recipe = Recipe::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:recipes',
        ]);

        $oldName = $recipe->name;
        $recipe->name = $validatedData['name'];
        $recipe->save();

        // LOG-30 — Recipe: Name Updated
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'report_id'        => $recipe->id,
            'type_of_report'   => 'Recipe',
            'name'             => $recipe->name,
            'action'           => 'updated',
            'updated_field'    => 'name',
            'original_data'    => $oldName,
            'updated_data'     => $recipe->name,
            'designation'      => 0,
            'designation_type' => 'warehouse',
        ]);

        return response()->json($recipe);
    }
    public function updateStatus(Request $request, $id)
    {
        $validatedData = $request->validate([
            'status' => 'required|string|max:255',
        ]);

        $recipe          = Recipe::findOrFail($id);
        $oldStatus       = $recipe->status;
        $recipe->status  = $validatedData['status'];
        $recipe->save();

        // LOG-30 — Recipe: Status Updated
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'report_id'        => $recipe->id,
            'type_of_report'   => 'Recipe',
            'name'             => $recipe->name,
            'action'           => 'updated',
            'updated_field'    => 'status',
            'original_data'    => $oldStatus,
            'updated_data'     => $recipe->status,
            'designation'      => 0,
            'designation_type' => 'warehouse',
        ]);

        return response()->json([
            'message' => 'Status updated successfully',
            'recipe' => $recipe
        ]);
    }
}
