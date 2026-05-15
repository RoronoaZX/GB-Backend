<?php

namespace App\Http\Controllers;

use App\Models\BreadSalesReport;
use App\Models\RecipeCost;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfitMarginController extends Controller
{
    /**
     * Get profit margin metrics for all products.
     * Compares "Recipe Production Cost" vs "Sales Revenue".
     */
    public function getProductMargins(Request $request)
    {
        try {
            $branchId = $request->query('branch_id');
            $warehouseId = $request->query('warehouse_id');
            $days = $request->query('days', 30);
            $startDate = now()->subDays($days);

            // If it's a warehouse, return empty data (Warehouses don't have sales/production reports)
            if ($warehouseId) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }

            // 1. Get Product Prices (Selling Prices)
            if (!$branchId || $branchId === 'global') {
                $prices = \App\Models\BranchProduct::select('product_id', DB::raw('MAX(price) as price'))
                    ->groupBy('product_id')
                    ->get()
                    ->keyBy('product_id');
            } else {
                $prices = \App\Models\BranchProduct::where('branches_id', $branchId)
                    ->get()
                    ->keyBy('product_id');
            }

            // 2. Aggregate Bread Production (Dough and Filling)
            $breadQty = [];
            $breadCosts = [];

            $doughQuery = \App\Models\BreadProductionReport::where('created_at', '>=', $startDate);
            if ($branchId && $branchId !== 'global') {
                $doughQuery->where('branch_id', $branchId);
            }
            $doughProductions = $doughQuery->get()->groupBy('initial_bakerreports_id');

            $fillingQuery = \App\Models\InitialFillingBakerreports::where('created_at', '>=', $startDate);
            if ($branchId && $branchId !== 'global') {
                $fillingQuery->whereHas('initialBakerReports', function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                });
            }
            $fillingProductions = $fillingQuery->get();

            // 3. Get Production Costs (Recipe Runs)
            $runCostsQuery = RecipeCost::where('created_at', '>=', $startDate);
            if ($branchId && $branchId !== 'global') {
                $runCostsQuery->where('branch_id', $branchId);
            }
            $runCosts = $runCostsQuery->select('initial_bakerreport_id', DB::raw('SUM(total_cost) as run_total_cost'))
                ->groupBy('initial_bakerreport_id')
                ->get()
                ->keyBy('initial_bakerreport_id');

            // Map production to costs
            foreach ($doughProductions as $runId => $rows) {
                $totalRunCost = (float)($runCosts->get($runId)->run_total_cost ?? 0);
                $totalQty = $rows->sum('bread_new_production');
                
                foreach ($rows as $row) {
                    if ($totalQty > 0) {
                        $share = $row->bread_new_production / $totalQty;
                        $breadCosts[$row->bread_id] = ($breadCosts[$row->bread_id] ?? 0) + ($totalRunCost * $share);
                    }
                    $breadQty[$row->bread_id] = ($breadQty[$row->bread_id] ?? 0) + $row->bread_new_production;
                }
            }

            foreach ($fillingProductions as $row) {
                $breadQty[$row->bread_id] = ($breadQty[$row->bread_id] ?? 0) + $row->filling_production;
            }

            // 4. Aggregate Resold Items Costs and Sales
            $categories = [
                'Selecta' => \App\Models\SelectaAddedStock::class,
                'Softdrinks' => \App\Models\SoftdrinksAddedStocks::class,
                'Nestle' => null, // Nestle doesn't have a separate added stock table
                'Other' => \App\Models\OtherAddedStocks::class
            ];

            $resoldData = [];
            foreach ($categories as $catName => $addedModel) {
                $purchaseCosts = collect();
                if ($addedModel && class_exists($addedModel)) {
                    $purchaseCostsQuery = $addedModel::query();
                    if ($branchId && $branchId !== 'global') {
                        if ($catName === 'Selecta') {
                            $purchaseCostsQuery->whereHas('selectaStocksReport', fn($sq) => $sq->where('branches_id', $branchId));
                        } elseif ($catName === 'Softdrinks') {
                            $purchaseCostsQuery->whereHas('softdrinksStocksReport', fn($sq) => $sq->where('branches_id', $branchId));
                        } elseif ($catName === 'Nestle') {
                            $purchaseCostsQuery->whereHas('nestleStocksReport', fn($sq) => $sq->where('branches_id', $branchId));
                        } elseif ($catName === 'Other') {
                            $purchaseCostsQuery->whereHas('otherStocksReport', fn($sq) => $sq->where('branches_id', $branchId));
                        }
                    }
                    
                    $purchaseCosts = $purchaseCostsQuery->select('product_id', DB::raw('AVG(price) as avg_cost'))
                        ->groupBy('product_id')
                        ->get()
                        ->keyBy('product_id');
                }

                $salesModel = "App\\Models\\{$catName}SalesReport";
                if ($catName === 'Other') $salesModel = "App\\Models\\OtherProducts";
                
                if (class_exists($salesModel)) {
                    $salesQuery = $salesModel::where('created_at', '>=', $startDate);
                    if ($branchId && $branchId !== 'global') {
                        $salesQuery->where('branch_id', $branchId);
                    }
                    $sales = $salesQuery->select('product_id', DB::raw('SUM(sales) as total_sales'), DB::raw('SUM(sold) as total_qty'))
                        ->groupBy('product_id')
                        ->get()
                        ->keyBy('product_id');

                    foreach ($sales as $pId => $data) {
                        $resoldData[$pId] = [
                            'qty' => (float)$data->total_qty,
                            'sales' => (float)$data->total_sales,
                            'avg_cost' => (float)($purchaseCosts[$pId]->avg_cost ?? 0)
                        ];
                    }
                }
            }

            // 5. Final Aggregation
            $productQuery = Product::query();
            if ($branchId && $branchId !== 'global') {
                $productQuery->whereIn('id', \App\Models\BranchProduct::where('branches_id', $branchId)->pluck('product_id'));
            }
            $allProducts = $productQuery->get();
            
            $results = [];

            foreach ($allProducts as $product) {
                $id = $product->id;
                $category = $product->category;
                $price = (float)($prices->get($id)->price ?? 0);
                
                $qty = 0;
                $totalSales = 0;
                $totalCost = 0;

                if (strtolower($category) === 'bread') {
                    $qty = (float)($breadQty[$id] ?? 0);
                    $totalSales = $qty * $price;
                    $totalCost = (float)($breadCosts[$id] ?? 0);
                } else if (isset($resoldData[$id])) {
                    $qty = $resoldData[$id]['qty'];
                    $totalSales = $resoldData[$id]['sales'];
                    $unitCost = $resoldData[$id]['avg_cost'];
                    $totalCost = $unitCost * $qty;
                } else {
                    // Non-bread products with no sales data in timeframe
                    $qty = 0;
                    $totalSales = 0;
                    $totalCost = 0;
                }

                $profit = $totalSales - $totalCost;
                $margin = $totalSales > 0 ? ($profit / $totalSales) * 100 : 0;
                $unitCostDisplay = $qty > 0 ? ($totalCost / $qty) : 0;

                $results[] = [
                    'id' => $id,
                    'name' => $product->name,
                    'category' => $category,
                    'price' => round($price, 2),
                    'production' => $qty,
                    'total_sales_amount' => round($totalSales, 2),
                    'cost' => round($totalCost, 2),
                    'unit_cost' => round($unitCostDisplay, 2),
                    'profit' => round($profit, 2),
                    'margin' => round($margin, 2),
                    'status' => $margin < 20 ? 'low' : ($margin < 40 ? 'medium' : 'high')
                ];
            }

            // Default sorting: lowest margin first
            usort($results, fn($a, $b) => $a['margin'] <=> $b['margin']);

            return response()->json([
                'success' => true,
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate profit margins',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

}
