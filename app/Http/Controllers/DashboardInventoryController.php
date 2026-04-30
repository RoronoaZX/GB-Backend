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

            // Optimize: Get usage history for ALL raw materials in one query (7-day trend)
            $usageHistoryAll = RecipeCost::where('created_at', '>=', now()->subDays(7))
                ->select('raw_material_id', DB::raw('DATE(created_at) as date'), DB::raw('SUM(quantity_used) as total_used'))
                ->groupBy('raw_material_id', 'date')
                ->get()
                ->groupBy('raw_material_id');

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
                    $displayQty = $totalQty;
                    $displayUnit = $rm->unit;

                    if (strtolower($displayUnit) === 'grams') {
                        $displayQty = $displayQty / 1000;
                        $displayUnit = 'kg';
                    }

                    // Use optimized usage history
                    $rmUsage = $usageHistoryAll->get($rm->id) ?? collect();
                    $trend = [];
                    for ($i = 6; $i >= 0; $i--) {
                        $date = now()->subDays($i)->format('Y-m-d');
                        $usageOnDate = $rmUsage->firstWhere('date', $date);
                        $trend[] = (float)($usageOnDate ? $usageOnDate->total_used : 0);
                    }

                    $currentBalances[] = [
                        'raw_material_id'   => $rm->id,
                        'name'              => $rm->name,
                        'code'              => $rm->code,
                        'category'          => $rm->category,
                        'unit'              => $displayUnit,
                        'total_quantity'    => round($displayQty, 2),
                        'usage_trend'       => $trend
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
            $alpha = 2 / ($daysToAnalyze + 1);

            // 1. Fetch Daily Usage Logs for EMA calculation
            $usageQuery = null;
            if ($warehouseId) {
                $usageQuery = DeliveryStocksUnit::join('raw_materials_deliveries', 'delivery_stocks_units.rm_delivery_id', '=', 'raw_materials_deliveries.id')
                    ->where('raw_materials_deliveries.status', 'confirmed')
                    ->where('raw_materials_deliveries.from_id', $warehouseId)
                    ->where('raw_materials_deliveries.from_designation', 'Warehouse');
            } elseif ($branchId) {
                $usageQuery = RecipeCost::where('branch_id', $branchId)
                    ->whereIn('status', ['confirmed', 'missing_stock']);
            } else {
                // Global view: Combined usage
                $usageQuery = RecipeCost::whereIn('status', ['confirmed', 'missing_stock']);
            }

            $tablePrefix = $warehouseId ? 'raw_materials_deliveries.' : '';
            $usageLogs = $usageQuery->where($tablePrefix . 'created_at', '>=', now()->subDays($daysToAnalyze))
                ->select(
                    $warehouseId ? 'delivery_stocks_units.raw_material_id as raw_material_id' : 'raw_material_id',
                    DB::raw('DATE(' . $tablePrefix . 'created_at) as date'),
                    DB::raw('SUM(' . ($warehouseId ? 'quantity' : 'quantity_used') . ') as daily_usage')
                )
                ->groupBy($warehouseId ? 'delivery_stocks_units.raw_material_id' : 'raw_material_id', DB::raw('DATE(' . $tablePrefix . 'created_at)'))
                ->orderBy('date', 'asc')
                ->get()
                ->groupBy('raw_material_id');

            $emaUsageMap = [];
            foreach ($usageLogs as $rmId => $logs) {
                $ema = 0;
                foreach ($logs as $log) {
                    $ema = ($log->daily_usage * $alpha) + ($ema * (1 - $alpha));
                }
                $emaUsageMap[$rmId] = $ema;
            }

            // 2. Get Current Stock Levels + Raw Material Lead Times
            $allStocks = collect();
            if ($warehouseId) {
                $allStocks = WarehouseRawMaterialsReport::with('rawMaterials')
                    ->where('warehouse_id', $warehouseId)
                    ->get()
                    ->map(fn($s) => [
                        'id' => $s->raw_material_id,
                        'qty' => $s->total_quantity,
                        'name' => $s->rawMaterials->name ?? 'Unknown',
                        'unit' => $s->rawMaterials->unit ?? 'pcs',
                        'lead_time' => $s->rawMaterials->supplier_lead_time ?? 3
                    ]);
            } elseif ($branchId) {
                $allStocks = BranchRawMaterialsReport::with('ingredients')
                    ->where('branch_id', $branchId)
                    ->get()
                    ->map(fn($s) => [
                        'id' => $s->ingredients_id,
                        'qty' => $s->total_quantity,
                        'name' => $s->ingredients->name ?? 'Unknown',
                        'unit' => $s->ingredients->unit ?? 'pcs',
                        'lead_time' => $s->ingredients->supplier_lead_time ?? 3
                    ]);
            } else {
                // Global Sum
                $rmData = RawMaterial::all()->keyBy('id');
                $branchStocks = BranchRawMaterialsReport::select('ingredients_id', DB::raw('SUM(total_quantity) as total_quantity'))->groupBy('ingredients_id')->get();
                $warehouseStocks = WarehouseRawMaterialsReport::select('raw_material_id', DB::raw('SUM(total_quantity) as total_quantity'))->groupBy('raw_material_id')->get();
                
                $merged = [];
                foreach ($branchStocks as $bs) {
                    $rm = $rmData->get($bs->ingredients_id);
                    $merged[$bs->ingredients_id] = [
                        'id' => $bs->ingredients_id,
                        'qty' => (float)$bs->total_quantity,
                        'name' => $rm->name ?? 'Unknown',
                        'unit' => $rm->unit ?? 'pcs',
                        'lead_time' => $rm->supplier_lead_time ?? 3
                    ];
                }
                foreach ($warehouseStocks as $ws) {
                    $rm = $rmData->get($ws->raw_material_id);
                    if (isset($merged[$ws->raw_material_id])) {
                        $merged[$ws->raw_material_id]['qty'] += (float)$ws->total_quantity;
                    } else {
                        $merged[$ws->raw_material_id] = [
                            'id' => $ws->raw_material_id,
                            'qty' => (float)$ws->total_quantity,
                            'name' => $rm->name ?? 'Unknown',
                            'unit' => $rm->unit ?? 'pcs',
                            'lead_time' => $rm->supplier_lead_time ?? 3
                        ];
                    }
                }
                $allStocks = collect(array_values($merged));
            }

            $predictions = [];
            foreach ($allStocks as $stock) {
                $dailyUsage = $emaUsageMap[$stock['id']] ?? 0;

                if ($dailyUsage > 0) {
                    $daysRemaining = $stock['qty'] / $dailyUsage;
                    $leadTime = $stock['lead_time'];
                    
                    // Danger Threshold: Lead Time (e.g. 3 days)
                    // Warning Threshold: Lead Time + 2 days (e.g. 5 days)
                    $status = 'healthy';
                    if ($daysRemaining <= $leadTime) {
                        $status = 'critical';
                    } elseif ($daysRemaining <= ($leadTime + 2)) {
                        $status = 'warning';
                    }

                    $displayStock = $stock['qty'];
                    $displayUsage = $dailyUsage;
                    $displayUnit = $stock['unit'];

                    if (strtolower($displayUnit) === 'grams') {
                        $displayStock /= 1000;
                        $displayUsage /= 1000;
                        $displayUnit = 'kg';
                    }

                    $predictions[] = [
                        'id' => $stock['id'],
                        'name' => $stock['name'],
                        'current_stock' => round($displayStock, 2),
                        'daily_usage' => round($displayUsage, 2),
                        'days_remaining' => round($daysRemaining, 1),
                        'unit' => $displayUnit,
                        'status' => $status,
                        'lead_time' => $leadTime
                    ];
                }
            }

            usort($predictions, fn($a, $b) => $a['days_remaining'] <=> $b['days_remaining']);

            return response()->json(['success' => true, 'data' => $predictions]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to load predictive stocking', 'error' => $e->getMessage()], 500);
        }
    }

    public function getRecipeCostMetrics()
    {
        try {
            $cachedData = \Illuminate\Support\Facades\Cache::remember('recipe_cost_metrics_v5', 3600, function () {
                // 1. Average Cost per Recipe Production
                $recipeRuns = RecipeCost::select('initial_bakerreport_id', DB::raw('SUM(total_cost) as run_cost'))
                    ->groupBy('initial_bakerreport_id')
                    ->get();

                $avgCost = $recipeRuns->avg('run_cost') ?: 0;

                // 2. Top 5 Most Expensive Recipes (Global Average)
                $topRecipes = DB::table('recipe_costs')
                    ->join('recipes', 'recipe_costs.recipe_id', '=', 'recipes.id')
                    ->select('recipes.name as recipe_name', DB::raw('SUM(recipe_costs.total_cost) / COUNT(DISTINCT recipe_costs.initial_bakerreport_id) as avg_cost'))
                    ->groupBy('recipes.id', 'recipes.name')
                    ->orderByDesc('avg_cost')
                    ->take(5)
                    ->get();

                // 3. Recent Cost Changes (The Audit Log)
                $recentChanges = RecipeCostChangeLog::with(['user.employee', 'recipeCost.recipe', 'recipeCost.rawMaterial'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($log) {
                        $emp = $log->user?->employee;
                        $recipeName = $log->recipeCost?->recipe?->name ?? 'Unknown Recipe';
                        $unit = $log->recipeCost?->rawMaterial?->unit ?? 'Gram';
                        
                        return [
                            'id'            => $log->id,
                            'recipe_name'   => $recipeName,
                            'unit'          => $unit,
                            'changed_field' => $log->changed_field,
                            'old_value'     => $log->old_value,
                            'new_value'     => $log->new_value,
                            'changed_by'    => $emp ? trim($emp->firstname . ' ' . $emp->lastname) : 'Administrator',
                            'date'          => $log->created_at,
                        ];
                    });

                return [
                    'averageCost'   => round($avgCost, 2),
                    'topRecipes'    => $topRecipes,
                    'recentChanges' => $recentChanges,
                ];
            });

            return response()->json(array_merge(['success' => true], $cachedData));
        } catch (\Exception $e) {
             return response()->json([
                'success' => false,
                'message' => 'Failed to load recipe cost metrics',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
