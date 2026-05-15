<?php

namespace App\Http\Controllers;

use App\Models\BranchRecipe;
use App\Models\BranchRmStocks;
use Illuminate\Http\Request;
use App\Services\HistoryLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $branchRecipe = BranchRecipe::orderBy('created_at', 'desc')
                                ->where('branch_id', $branchId)
                                ->with([
                                    'branch',
                                    'recipe',
                                    'breadGroups.bread',
                                    'ingredientGroups.ingredient'
                                ])
                                ->get();

        $formattedBranchRecipes = $branchRecipe->map(function ($branchRecipe) use ($branchId) {
            return [
                'id'                 => $branchRecipe->id,
                'name'               => $branchRecipe->recipe->name,
                'category'           => $branchRecipe->recipe->category,
                'target'             => $branchRecipe->target,
                'status'             => $branchRecipe->status,
                'bread_groups'       => $branchRecipe->breadGroups->pluck('bread.name'),
                'ingredient_groups'  => $branchRecipe->ingredientGroups->map(function ($ingredientGroup) use ($branchId) {
                    //                 // ✅ Fetch the oldest stock for this ingredient in this branch
                    $stock = BranchRmStocks::where('branch_id',  $branchId)
                                        ->where('raw_material_id', $ingredientGroup->ingredient_id)
                                        ->where('quantity', '>', 0)
                                        ->orderBy('created_at', 'asc')
                                        ->first();

                    return [
                        'ingredient_name'    => $ingredientGroup->ingredient->name,
                        'code'               => $ingredientGroup->ingredient->code,
                        'quantity'           => $ingredientGroup->quantity,
                        'unit'               => $ingredientGroup->ingredient->unit,
                        'price_per_gram'     => $stock ? $stock->price_per_gram : null
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
            'branch_id'                      => 'required|integer',
            'recipe_id'                      => 'required|integer',
            'target'                         => 'required|numeric',
            'status'                         => 'required|string|max:30',
            'breads'                         => 'required|array',
            'breads.*.bread_id'              => 'required|integer|exists:products,id',
            'ingredients'                    => 'required|array',
            'ingredients.*.ingredient_id'    => 'required|integer|exists:raw_materials,id',
            'ingredients.*.quantity'         => 'required',
        ]);

        $existingBranchRecipe = BranchRecipe::where('branch_id', $validatedData['branch_id'])
                                    ->where('recipe_id', $validatedData['recipe_id'])
                                    ->where('status', 'active')
                                    ->first();

        if ($existingBranchRecipe) {
            return response()->json([
                'message' => 'The recipe already exists in this branch.'
            ]);
        }
        DB::beginTransaction();
        try {
            $branchRecipe = BranchRecipe::create($validatedData);

            $branchRecipe->ingredientGroups()->createMany($validatedData['ingredients']);
            $branchRecipe->breadGroups()->createMany($validatedData['breads']);

            // LOG-30 — Recipe: Branch Assign
            HistoryLogService::log([
                'user_id'          => Auth::id(),
                'report_id'        => $branchRecipe->id,
                'type_of_report'   => 'Recipe',
                'name'             => "Recipe assigned to branch",
                'action'           => 'created',
                'updated_data'     => $branchRecipe->load(['ingredientGroups', 'breadGroups'])->toArray(),
                'designation'      => $validatedData['branch_id'],
                'designation_type' => 'branch',
            ]);

            DB::commit();

            return response()->json([
                'message'    => 'Branch recipe saved successfully',
                'data'       => $branchRecipe
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to save branch recipe', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateTarget(Request $request, $id)
    {
        $validatedData = $request->validate([
            'target' => 'required|numeric',
        ]);

        $recipe          = BranchRecipe::findOrFail($id);
        $oldTarget       = $recipe->target;
        $recipe->target  = $validatedData['target'];
        $recipe->save();

        // LOG-30 — Recipe: Target Updated
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'report_id'        => $recipe->id,
            'type_of_report'   => 'Recipe',
            'name'             => "Recipe target updated",
            'action'           => 'updated',
            'updated_field'    => 'target',
            'original_data'    => $oldTarget,
            'updated_data'     => $recipe->target,
            'designation'      => $recipe->branch_id,
            'designation_type' => 'branch',
        ]);

        return response()->json(['message' => 'Target updated successfully', 'recipe' => $recipe]);
    }
    public function branchUpdateStatus(Request $request, $id)
    {
        $validatedData = $request->validate([
            'status' => 'required|string',
        ]);

        $recipe          = BranchRecipe::findOrFail($id);
        $oldStatus       = $recipe->status;
        $recipe->status  = $validatedData['status'];
        $recipe->save();

        // LOG-30 — Recipe: Status Updated
        HistoryLogService::log([
            'user_id'          => Auth::id(),
            'report_id'        => $recipe->id,
            'type_of_report'   => 'Recipe',
            'name'             => "Recipe status updated",
            'action'           => 'updated',
            'updated_field'    => 'status',
            'original_data'    => $oldStatus,
            'updated_data'     => $recipe->status,
            'designation'      => $recipe->branch_id,
            'designation_type' => 'branch',
        ]);

        return response()->json(['message' => 'Status updated successfully', 'recipe' => $recipe]);
    }

    public function branchSearchRecipe(Request $request)
    {
        $searchBranchRecipe      = $request->input('keyword');
        $searchBranchRecipeId    = $request->input('branch_id');
        $perPage                 = $request->input('per_page', 12); // default 12 per page

        // Base query
        $query = BranchRecipe::with('recipe')
                    ->where('status', 'active')
                    ->when($searchBranchRecipe !== null, function ($query) use ($searchBranchRecipe) {
                        $query->whereHas('recipe', function ($recipeQuery) use ($searchBranchRecipe) {
                            $recipeQuery->where('name', 'like', "%{$searchBranchRecipe}%");
                        });
                    })
                    ->when($searchBranchRecipeId !== null, function ($query) use ($searchBranchRecipeId) {
                        $query->where('branch_id', $searchBranchRecipeId);
                    })
                    ->with(['breadGroups.bread', 'ingredientGroups.ingredient'])
                    ->orderBy('created_at', 'desc')
                    ->get();

        // Make recipes unique by recipe name (to avoid duplicates in result)
        $uniqueRecipes = $query->unique(function ($item) {
            return $item->recipe->name;
        });

        // Paginate manually
        $page        = $request->input('page', 1);
        $paginated   = $uniqueRecipes->forPage($page, $perPage)->values();

        // Format the response
        $formattedRecipes = $paginated->map(function ($recipe) {
            return [
                'id'             => $recipe->id,
                'recipe_id'      => $recipe->recipe->id,
                'name'           => $recipe->recipe->name,
                'category'       => $recipe->recipe->category,
                'target'         => $recipe->target,
                'bread_groups'   => $recipe->breadGroups->map(function ($breadGroup) {
                    return [
                        'product_id' => $breadGroup->bread->id,
                        'bread_name' => $breadGroup->bread->name,
                    ];
                }),
                'ingredients'    => $recipe->ingredientGroups->map(function ($ingredientGroup) {
                    return [
                        'raw_materials_id'   => $ingredientGroup->ingredient->id,
                        'code'               => $ingredientGroup->ingredient->code,
                        'ingredient_name'    => $ingredientGroup->ingredient->name,
                        'quantity'           => $ingredientGroup->quantity,
                        'unit'               => $ingredientGroup->ingredient->unit,
                    ];
                }),
            ];
        });

        return response()->json($formattedRecipes);
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

        DB::beginTransaction();
        try {
            $oldData = $branchRecipe->toArray();
            $branchRecipe->delete();

            // LOG-30 — Recipe: Deleted
            HistoryLogService::log([
                'user_id'          => Auth::id(),
                'report_id'        => $id,
                'type_of_report'   => 'Recipe',
                'name'             => "Recipe removed from branch",
                'action'           => 'deleted',
                'original_data'    => $oldData,
                'designation'      => $oldData['branch_id'],
                'designation_type' => 'branch',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Recipe deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete branch recipe', 'error' => $e->getMessage()], 500);
        }
    }

}
