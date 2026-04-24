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
            $days = $request->query('days', 30);
            $startDate = now()->subDays($days);

            // 1. Get Sales Revenue per Product
            $salesQuery = BreadSalesReport::where('created_at', '>=', $startDate)
                ->select('product_id', DB::raw('SUM(sales) as total_revenue'))
                ->groupBy('product_id');

            if ($branchId) {
                // Filter by branch if requested
                $salesQuery->whereHas('salesReports', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            }

            $salesData = $salesQuery->get()->keyBy('product_id');

            // 2. Get Production Cost per Product
            // Join RecipeCost -> Recipe -> BranchRecipe -> BreadGroup
            $costQuery = RecipeCost::join('recipes', 'recipe_costs.recipe_id', '=', 'recipes.id')
                ->join('branch_recipes', 'recipes.id', '=', 'branch_recipes.recipe_id')
                ->join('bread_groups', 'branch_recipes.id', '=', 'bread_groups.branch_recipe_id')
                ->where('recipe_costs.created_at', '>=', $startDate)
                ->select(
                    'bread_groups.bread_id as product_id',
                    DB::raw('SUM(recipe_costs.total_cost) as total_production_cost')
                )
                ->groupBy('bread_groups.bread_id');

            if ($branchId) {
                $costQuery->where('recipe_costs.branch_id', $branchId)
                          ->where('branch_recipes.branch_id', $branchId);
            }

            $costData = $costQuery->get()->keyBy('product_id');

            // 3. Aggregate and Calculate Margins
            $allProductIds = $salesData->keys()->merge($costData->keys())->unique();
            $products = Product::whereIn('id', $allProductIds)->get()->keyBy('id');

            $results = [];
            foreach ($allProductIds as $id) {
                $product = $products->get($id);
                if (!$product) continue;

                $revenue = (float)($salesData->get($id)->total_revenue ?? 0);
                $cost = (float)($costData->get($id)->total_production_cost ?? 0);
                $profit = $revenue - $cost;
                $margin = $revenue > 0 ? ($profit / $revenue) * 100 : 0;

                $results[] = [
                    'id' => $id,
                    'name' => $product->name,
                    'revenue' => round($revenue, 2),
                    'cost' => round($cost, 2),
                    'profit' => round($profit, 2),
                    'margin' => round($margin, 2),
                    'status' => $margin < 20 ? 'low' : ($margin < 40 ? 'medium' : 'high')
                ];
            }

            // Sort by lowest margin first to highlight problematic items
            usort($results, fn($a, $b) => $a['margin'] <=> $b['margin']);

            return response()->json([
                'success' => true,
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate profit margins',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
