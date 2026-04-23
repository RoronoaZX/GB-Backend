<?php

namespace App\Http\Controllers;

use App\Models\RecipeCost;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use ReturnTypeWillChange;

class RecipeCostController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function fetchRecipeCosts(Request $request, $branchId)
    {
        $page        = (int) $request->get('page', 1);
        $perPage     = (int) $request->get('per_page', 5);
        $search      = $request->query('search', '');

        // ✅ Step 1: Base Query
        $query = RecipeCost::where('branch_id', $branchId)
                   ->with(['recipe', 'branchRmStock', 'user', 'initialBakerreport', 'rawMaterial'])
                   ->orderBy('created_at', 'desc');

        // ✅ Step 2: Apply search filter (by recipe name or raw material name)
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('recipe', function ($r) use ($search) {
                    $r->where('name', 'like', "%{$search}%");
                });
            });
        }

        // ✅ Step 3: Paginate RAW rows first
        // $paginated = $query->paginate($perPage, ['*'], 'page', $page);
        $allRows = $query->get();

        // ✅ Step 4: Group ONLY current page data
        $grouped = collect($allRows)
        ->groupBy(fn ($item) =>
            $item->recipe_id . '_' . $item->initial_bakerreport_id
        )
        ->map(function ($group) {
            $first = $group->first();

            return [
                'recipe_id'         => $first->recipe_id,
                'recipe_name'       => $first->recipe?->name,
                'recipe_total_cost' => $group->sum('total_cost'),
                'user'              => $first->user,
                'created_at'        => $first->created_at,
                'kilo'              => $first->initialBakerreport?->kilo,

                'items' => $group->map(function ($item) {
                    return [
                        'raw_material_name' => $item->rawMaterial?->name,
                        'quantity_used'     => $item->quantity_used,
                        'price_per_gram'    => $item->price_per_gram,
                        'total_cost'        => $item->total_cost,
                    ];
                })->values(),
            ];
        })
        ->sortByDesc('created_at')
        ->values();

        $total = $grouped->count();

        $pagedData = $grouped
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();


        // ✅ Step 5: Return pagination meta from RAW paginator
        return response()->json([
            'data'         => $pagedData,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => ceil($total / $perPage),
        ]);

    }

    public function fetchGlobalRecipeCosts(Request $request, $recipeId)
    {
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 10);

        $query = RecipeCost::where('recipe_id', $recipeId)
            ->with(['recipe', 'branch', 'user.employee', 'rawMaterial'])
            ->orderBy('created_at', 'desc');

        $allRows = $query->get();
        $grouped = collect($allRows)
            ->groupBy('initial_bakerreport_id')
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'branch_name'       => $first->branch?->name,
                    'baker_name'        => $first->user?->employee ? ($first->user->employee->firstname . ' ' . $first->user->employee->lastname) : 'N/A',
                    'created_at'        => $first->created_at,
                    'kilo'              => $first->kilo,
                    'recipe_total_cost' => $group->sum('total_cost'),
                    'items'             => $group->map(function ($item) {
                        return [
                            'raw_material_name' => $item->rawMaterial?->name,
                            'quantity_used'     => $item->quantity_used,
                            'price_per_gram'    => $item->price_per_gram,
                            'total_cost'        => $item->total_cost,
                        ];
                    })->values(),
                ];
            })
            ->values()
            ->sortByDesc('created_at')
            ->values();

        $total = $grouped->count();
        $pagedData = $grouped->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'data'         => $pagedData,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => (int)$page,
            'last_page'    => (int)ceil($total / $perPage),
        ]);
    }
}
