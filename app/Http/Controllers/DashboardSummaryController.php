<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\RawMaterial;
use App\Models\Recipe;
use App\Models\SupplierRecord;
use App\Models\InitialBakerreports;
use App\Models\SalesReports;
use App\Models\Warehouse;
use App\Models\BranchRawMaterialsReport;
use App\Models\RecipeCost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardSummaryController extends Controller
{
    /**
     * Get a unified summary of all dashboard metrics in a single request.
     * This reduces the number of parallel API calls from 11 to 1.
     */
    public function getSummary(Request $request)
    {
        try {
            $branchId = $request->query('branch_id');
            $warehouseId = $request->query('warehouse_id');
            $days = $request->query('days', 30);
            $startDate = Carbon::now()->subDays($days);

            // 1. Basic Counts
            $bakerReportsQuery = InitialBakerreports::query();
            $employeeQuery = Employee::where('status', '!=', 'inactive');

            if ($branchId && $branchId !== 'global') {
                $bakerReportsQuery->where('branch_id', $branchId);
                $employeeQuery->whereHas('branchEmployee', function($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            } elseif ($warehouseId) {
                $employeeQuery->whereHas('warehouseEmployee', function($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                });
            }

            $counts = [
                'total_branches'    => Branch::count(),
                'total_warehouses'  => Warehouse::count(),
                'total_employees'   => $employeeQuery->count(),
                'total_recipes'     => Recipe::count(),
                'total_suppliers'   => SupplierRecord::count(),
                'total_baker_reports' => $bakerReportsQuery->count(),
            ];

            // 2. Low Stock Count
            $lowStockThreshold = 50;
            $lowStockCount = 0;
            if ($branchId && $branchId !== 'global') {
                $lowStockCount = BranchRawMaterialsReport::where('branch_id', $branchId)
                    ->where('total_quantity', '<', $lowStockThreshold)
                    ->count();
            } else {
                // Global count across all branches/warehouses
                $lowStockCount = BranchRawMaterialsReport::where('total_quantity', '<', $lowStockThreshold)->count();
                // Add warehouse low stocks if needed
            }

            // 3. Financial Summary (Aggregated in DB)
            $salesSummaryQuery = SalesReports::where('created_at', '>=', $startDate);
            if ($branchId && $branchId !== 'global') {
                $salesSummaryQuery->where('branch_id', $branchId);
            }
            
            $salesSummary = $salesSummaryQuery->select(
                    DB::raw('SUM(products_total_sales) as total_gross_revenue'),
                    DB::raw('SUM(expenses_total) as total_expenses'),
                    DB::raw('SUM(products_total_sales - expenses_total) as total_net_revenue')
                )
                ->first();

            // 4. Recipe Cost Summary
            $avgRecipeCostQuery = RecipeCost::where('created_at', '>=', $startDate);
            if ($branchId && $branchId !== 'global') {
                $avgRecipeCostQuery->where('branch_id', $branchId);
            }
            $avgRecipeCost = $avgRecipeCostQuery->avg('total_cost') ?: 0;

            // 5. Recent Activity (Latest 5 logs)
            $recentActivityQuery = DB::table('history_logs');
            if ($branchId && $branchId !== 'global') {
                $recentActivityQuery->where('designation', $branchId)->where('designation_type', 'branch');
            } elseif ($warehouseId) {
                $recentActivityQuery->where('designation', $warehouseId)->where('designation_type', 'warehouse');
            }
            
            $recentActivity = $recentActivityQuery->select('id', 'name as action', 'type_of_report as details', 'created_at as time')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'counts' => $counts,
                'low_stock_count' => $lowStockCount,
                'financials' => [
                    'gross' => round($salesSummary->total_gross_revenue ?? 0, 2),
                    'expenses' => round($salesSummary->total_expenses ?? 0, 2),
                    'net' => round($salesSummary->total_net_revenue ?? 0, 2),
                ],
                'average_recipe_cost' => round($avgRecipeCost, 2),
                'recent_activity' => $recentActivity,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
