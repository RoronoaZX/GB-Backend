<?php

namespace App\Http\Controllers;

use App\Models\RecipeCost;
use App\Models\RecipeCostChangeLog;
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
                        'id'                => $item->id,
                        'raw_material_name' => $item->rawMaterial?->name,
                        'unit'              => $item->rawMaterial?->unit,
                        'quantity_used'     => $item->quantity_used,
                        'price_per_gram'    => $item->price_per_gram,
                        'total_cost'        => $item->total_cost,
                        'status'            => $item->status,
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
                            'id'                => $item->id,
                            'raw_material_name' => $item->rawMaterial?->name,
                            'unit'              => $item->rawMaterial?->unit,
                            'quantity_used'     => $item->quantity_used,
                            'price_per_gram'    => $item->price_per_gram,
                            'total_cost'        => $item->total_cost,
                            'status'            => $item->status,
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

    /**
     * Update a recipe cost field and write an audit log entry.
     */
    public function logChange(Request $request)
    {
        try {
            $recipeCost = RecipeCost::findOrFail($request->recipe_cost_id);

            $changedField = $request->changed_field; // 'price_per_gram' or 'quantity_used'
            $newValue     = (float) $request->new_value;
            $oldValue     = (float) $recipeCost->$changedField;

            // Update the recipe_costs record
            $recipeCost->$changedField = $newValue;
            // Recalculate total cost
            $recipeCost->total_cost = $recipeCost->quantity_used * $recipeCost->price_per_gram;
            $recipeCost->save();

            // Write audit log
            RecipeCostChangeLog::create([
                'recipe_cost_id' => $recipeCost->id,
                'branch_id'      => $recipeCost->branch_id,
                'user_id'        => $request->user_id ?? 0,
                'changed_field'  => $changedField,
                'old_value'      => $oldValue,
                'new_value'      => $newValue,
                'reason'         => $request->reason ?? null,
            ]);

            return response()->json([
                'success'        => true,
                'message'        => 'Cost updated and change logged.',
                'new_total_cost' => round($recipeCost->total_cost, 2),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to log change.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return the change history for a specific recipe_cost row.
     */
    public function getChangeLogs($recipeCostId)
    {
        try {
            $logs = RecipeCostChangeLog::where('recipe_cost_id', $recipeCostId)
                ->with('user.employee')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($log) {
                    $emp = $log->user?->employee;
                    return [
                        'id'            => $log->id,
                        'changed_field' => $log->changed_field,
                        'old_value'     => $log->old_value,
                        'new_value'     => $log->new_value,
                        'reason'        => $log->reason,
                        'changed_by'    => $emp
                            ? trim($emp->firstname . ' ' . $emp->lastname)
                            : 'Administrator',
                        'changed_at'    => $log->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data'    => $logs,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch change logs.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkUpdate(Request $request)
    {
        try {
            $ids = $request->ids;
            $changedField = $request->changed_field;
            $newValue = (float) $request->new_value;
            $userId = $request->user_id ?? 0;

            if (!is_array($ids) || empty($ids)) {
                return response()->json(['success' => false, 'message' => 'No IDs provided'], 400);
            }

            $recipeCosts = RecipeCost::whereIn('id', $ids)->get();
            
            \Illuminate\Support\Facades\DB::beginTransaction();

            foreach ($recipeCosts as $recipeCost) {
                $oldValue = (float) $recipeCost->$changedField;
                
                $recipeCost->$changedField = $newValue;
                $recipeCost->total_cost = $recipeCost->quantity_used * $recipeCost->price_per_gram;
                $recipeCost->save();

                RecipeCostChangeLog::create([
                    'recipe_cost_id' => $recipeCost->id,
                    'branch_id'      => $recipeCost->branch_id,
                    'user_id'        => $userId,
                    'changed_field'  => $changedField,
                    'old_value'      => $oldValue,
                    'new_value'      => $newValue,
                    'reason'         => 'Bulk update: ' . ($request->reason ?? 'No reason provided'),
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($recipeCosts) . ' records updated successfully.'
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk update.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
