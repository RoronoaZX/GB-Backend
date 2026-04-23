<?php

namespace App\Http\Controllers;

use App\Models\BranchRawMaterialsReport;
use App\Models\WarehouseRawMaterialsReport;
use App\Models\RawMaterial;
use App\Models\DeliveryStocksUnit;
use App\Models\RecipeCost;
use App\Models\RecipeCostChangeLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardInventoryController extends Controller
{
    public function getInventoryMetrics(Request $request)
    {
        try {
            $branchId = $request->query('branch_id');
            $warehouseId = $request->query('warehouse_id');
            $mode = 'global';
            if ($branchId) $mode = 'branch';
            if ($warehouseId) $mode = 'warehouse';

            // 1. Current Balances (The Stock Ledger)
            $rawMaterials    = RawMaterial::all();
            $currentBalances = [];

            // Pre-fetch reports for speed
            $branchReports = collect();
            if ($mode === 'global' || $mode === 'branch') {
                $branchReportsQuery = BranchRawMaterialsReport::query();
                if ($mode === 'branch') {
                    $branchReportsQuery->where('branch_id', $branchId);
                }
                $branchReports = $branchReportsQuery->get()->groupBy('ingredients_id');
            }

            $warehouseReports = collect();
            if ($mode === 'global' || $mode === 'warehouse') {
                $warehouseReportsQuery = WarehouseRawMaterialsReport::query();
                if ($mode === 'warehouse') {
                    $warehouseReportsQuery->where('warehouse_id', $warehouseId);
                }
                $warehouseReports = $warehouseReportsQuery->get()->groupBy('raw_material_id');
            }

            foreach ($rawMaterials as $rm) {
                $totalQty = 0;

                // Sum from branch
                if (isset($branchReports[$rm->id])) {
                    $totalQty += $branchReports[$rm->id]->sum('total_quantity');
                }

                // Sum from warehouse
                if (isset($warehouseReports[$rm->id])) {
                    $totalQty += $warehouseReports[$rm->id]->sum('total_quantity');
                }

                if ($totalQty > 0) {
                    $currentBalances[] = [
                        'raw_material_id'   => $rm->id,
                        'name'              => $rm->name,
                        'code'              => $rm->code,
                        'category'          => $rm->category,
                        'unit'              => $rm->unit,
                        'total_quantity'    => round($totalQty, 2)
                    ];
                }
            }

            usort($currentBalances, function ($a, $b) {
                return $b['total_quantity'] <=> $a['total_quantity']; // Descending
            });

            // 2. IN Movements (Deliveries) Over Last 365 Days
            $deliveriesQuery = DeliveryStocksUnit::join('raw_materials_deliveries', 'delivery_stocks_units.rm_delivery_id', '=', 'raw_materials_deliveries.id')
                ->where('raw_materials_deliveries.status', 'confirmed')
                ->where('raw_materials_deliveries.created_at', '>=', now()->subDays(365));

            if ($mode === 'branch') {
                $deliveriesQuery->where('raw_materials_deliveries.to_id', $branchId)
                                ->where('raw_materials_deliveries.to_designation', 'Branch');
            } elseif ($mode === 'warehouse') {
                $deliveriesQuery->where('raw_materials_deliveries.to_id', $warehouseId)
                                ->where('raw_materials_deliveries.to_designation', 'Warehouse');
            }

            $inMovementsRaw = $deliveriesQuery->select(
                    DB::raw('DATE(raw_materials_deliveries.updated_at) as date'),
                    DB::raw('SUM(delivery_stocks_units.quantity) as total_qty')
                )
                ->groupBy(DB::raw('DATE(raw_materials_deliveries.updated_at)'))
                ->get();

            // 3. OUT Movements (Recipe Cost / Usage / Deliveries OUT)
            // For branches, OUT is usage. For warehouses, OUT is delivery to branches.
            $outMovementsRaw = collect();

            if ($mode === 'branch' || $mode === 'global') {
                $usageQuery = RecipeCost::whereIn('status', ['confirmed', 'missing_stock'])
                    ->where('created_at', '>=', now()->subDays(365));

                if ($mode === 'branch') {
                    $usageQuery->where('branch_id', $branchId);
                }

                $outMovementsRaw = $usageQuery->select(
                        DB::raw('DATE(created_at) as date'),
                        DB::raw('SUM(quantity_used) as total_qty')
                    )
                    ->groupBy(DB::raw('DATE(created_at)'))
                    ->get();
            }

            if ($mode === 'warehouse') {
                // For warehouse, OUT movements are deliveries to branches
                $outMovementsRaw = DeliveryStocksUnit::join('raw_materials_deliveries', 'delivery_stocks_units.rm_delivery_id', '=', 'raw_materials_deliveries.id')
                    ->where('raw_materials_deliveries.status', 'confirmed')
                    ->where('raw_materials_deliveries.created_at', '>=', now()->subDays(365))
                    ->where('raw_materials_deliveries.from_id', $warehouseId)
                    ->where('raw_materials_deliveries.from_designation', 'Warehouse')
                    ->select(
                        DB::raw('DATE(raw_materials_deliveries.updated_at) as date'),
                        DB::raw('SUM(delivery_stocks_units.quantity) as total_qty')
                    )
                    ->groupBy(DB::raw('DATE(raw_materials_deliveries.updated_at)'))
                    ->get();
            }

            return response()->json([
                'success'            => true,
                'currentBalances'    => $currentBalances,
                'inMovements'        => $inMovementsRaw,
                'outMovements'       => $outMovementsRaw
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success'    => false,
                'message'    => 'Failed to load inventory metrics',
                'error'      => $e->getMessage() // Good for debugging
            ], 500);
        }
    }

    public function getPredictiveStocking(Request $request)
    {
        try {
            $branchId = $request->query('branch_id');
            $warehouseId = $request->query('warehouse_id');
            $daysToAnalyze = 30; 
            $predictionDays = 14; 

            $usageData = collect();

            if ($warehouseId) {
                // For Warehouse, "Usage" is Deliveries OUT to Branches
                $usageData = DeliveryStocksUnit::join('raw_materials_deliveries', 'delivery_stocks_units.rm_delivery_id', '=', 'raw_materials_deliveries.id')
                    ->where('raw_materials_deliveries.status', 'confirmed')
                    ->where('raw_materials_deliveries.created_at', '>=', now()->subDays($daysToAnalyze))
                    ->where('raw_materials_deliveries.from_id', $warehouseId)
                    ->where('raw_materials_deliveries.from_designation', 'Warehouse')
                    ->select(
                        'delivery_stocks_units.raw_material_id',
                        DB::raw('SUM(delivery_stocks_units.quantity) as total_usage')
                    )
                    ->groupBy('delivery_stocks_units.raw_material_id')
                    ->get()
                    ->keyBy('raw_material_id');
            } elseif ($branchId) {
                // For a specific Branch, "Usage" is Recipe Consumption
                $usageData = RecipeCost::where('created_at', '>=', now()->subDays($daysToAnalyze))
                    ->whereIn('status', ['confirmed', 'missing_stock'])
                    ->where('branch_id', $branchId)
                    ->select(
                        'raw_material_id',
                        DB::raw('SUM(quantity_used) as total_usage')
                    )
                    ->groupBy('raw_material_id')
                    ->get()
                    ->keyBy('raw_material_id');
            } else {
                // GLOBAL VIEW: Aggregate usage across all branches
                $usageData = RecipeCost::where('created_at', '>=', now()->subDays($daysToAnalyze))
                    ->whereIn('status', ['confirmed', 'missing_stock'])
                    ->select(
                        'raw_material_id',
                        DB::raw('SUM(quantity_used) as total_usage')
                    )
                    ->groupBy('raw_material_id')
                    ->get()
                    ->keyBy('raw_material_id');
            }

            // 2. Get Current Stock Levels
            $allStocks = collect();

            if ($warehouseId) {
                $allStocks = WarehouseRawMaterialsReport::with('rawMaterials')
                    ->where('warehouse_id', $warehouseId)
                    ->get()
                    ->map(function($s) {
                        return [
                            'ingredients_id' => $s->raw_material_id,
                            'total_quantity' => $s->total_quantity,
                            'name' => $s->rawMaterials->name ?? 'Unknown',
                            'unit' => $s->rawMaterials->unit ?? 'pcs'
                        ];
                    });
            } elseif ($branchId) {
                $allStocks = BranchRawMaterialsReport::with('ingredients')
                    ->where('branch_id', $branchId)
                    ->get()
                    ->map(function($s) {
                        return [
                            'ingredients_id' => $s->ingredients_id,
                            'total_quantity' => $s->total_quantity,
                            'name' => $s->ingredients->name ?? 'Unknown',
                            'unit' => $s->ingredients->unit ?? 'pcs'
                        ];
                    });
            } else {
                // GLOBAL VIEW: Sum all Branch + all Warehouse stocks
                $branchStocks = BranchRawMaterialsReport::with('ingredients')
                    ->select('ingredients_id', DB::raw('SUM(total_quantity) as total_quantity'))
                    ->groupBy('ingredients_id')
                    ->get();

                $warehouseStocks = WarehouseRawMaterialsReport::with('rawMaterials')
                    ->select('raw_material_id', DB::raw('SUM(total_quantity) as total_quantity'))
                    ->groupBy('raw_material_id')
                    ->get();

                // Merge and Sum
                $merged = [];
                foreach ($branchStocks as $bs) {
                    $merged[$bs->ingredients_id] = [
                        'ingredients_id' => $bs->ingredients_id,
                        'total_quantity' => (float)$bs->total_quantity,
                        'name' => $bs->ingredients->name ?? 'Unknown',
                        'unit' => $bs->ingredients->unit ?? 'pcs'
                    ];
                }
                foreach ($warehouseStocks as $ws) {
                    if (isset($merged[$ws->raw_material_id])) {
                        $merged[$ws->raw_material_id]['total_quantity'] += (float)$ws->total_quantity;
                    } else {
                        $merged[$ws->raw_material_id] = [
                            'ingredients_id' => $ws->raw_material_id,
                            'total_quantity' => (float)$ws->total_quantity,
                            'name' => $ws->rawMaterials->name ?? 'Unknown',
                            'unit' => $ws->rawMaterials->unit ?? 'pcs'
                        ];
                    }
                }
                $allStocks = collect(array_values($merged));
            }

            $predictions = [];

            foreach ($allStocks as $stock) {
                $stock = (object)$stock;
                $usage = $usageData->get($stock->ingredients_id);
                $totalUsed = $usage ? $usage->total_usage : 0;
                $dailyUsage = $totalUsed / $daysToAnalyze;

                if ($dailyUsage > 0) {
                    $daysRemaining = $stock->total_quantity / $dailyUsage;
                    
                    $status = 'healthy';
                    if ($daysRemaining <= 3) {
                        $status = 'critical';
                    } elseif ($daysRemaining <= $predictionDays) {
                        $status = 'warning';
                    }

                    $predictions[] = [
                        'id' => $stock->ingredients_id,
                        'name' => $stock->name,
                        'current_stock' => round($stock->total_quantity, 2),
                        'daily_usage' => round($dailyUsage, 2),
                        'days_remaining' => round($daysRemaining, 1),
                        'unit' => $stock->unit,
                        'status' => $status
                    ];
                }
            }

            // Sort by days remaining (most critical first)
            usort($predictions, function ($a, $b) {
                return $a['days_remaining'] <=> $b['days_remaining'];
            });

            return response()->json([
                'success' => true,
                'data' => $predictions
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load predictive stocking data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getRecipeCostMetrics()
    {
        try {
            // 1. Average Cost per Recipe Production
            $recipeRuns = RecipeCost::select('initial_bakerreport_id', DB::raw('SUM(total_cost) as run_cost'))
                ->groupBy('initial_bakerreport_id')
                ->get();

            $avgCost = $recipeRuns->avg('run_cost') ?: 0;

            // 2. Top 5 Most Expensive Recipes (Global Average)
            // Join with recipes table to get the name
            $topRecipes = RecipeCost::join('recipes', 'recipe_costs.recipe_id', '=', 'recipes.id')
                ->select('recipes.name as recipe_name', 'recipe_costs.initial_bakerreport_id', DB::raw('SUM(recipe_costs.total_cost) as total_run_cost'))
                ->groupBy('recipes.name', 'recipe_costs.initial_bakerreport_id')
                ->get()
                ->groupBy('recipe_name')
                ->map(function ($group) {
                    return [
                        'recipe_name' => $group->first()->recipe_name,
                        'avg_cost'    => $group->avg('total_run_cost')
                    ];
                })
                ->sortByDesc('avg_cost')
                ->take(5)
                ->values();

            // 3. Recent Cost Changes (The Audit Log)
            $recentChanges = RecipeCostChangeLog::with(['user.employee', 'recipeCost.recipe'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($log) {
                    $emp = $log->user?->employee;
                    // Safely get recipe name through nested relationship
                    $recipeName = $log->recipeCost?->recipe?->name ?? 'Unknown Recipe';
                    
                    return [
                        'id'            => $log->id,
                        'recipe_name'   => $recipeName,
                        'changed_field' => $log->changed_field,
                        'old_value'     => $log->old_value,
                        'new_value'     => $log->new_value,
                        'changed_by'    => $emp ? trim($emp->firstname . ' ' . $emp->lastname) : 'Administrator',
                        'date'          => $log->created_at,
                    ];
                });

            return response()->json([
                'success'       => true,
                'averageCost'   => round($avgCost, 2),
                'topRecipes'    => $topRecipes,
                'recentChanges' => $recentChanges,
            ]);
        } catch (\Exception $e) {
             return response()->json([
                'success' => false,
                'message' => 'Failed to load recipe cost metrics',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
