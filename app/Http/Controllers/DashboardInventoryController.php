<?php

namespace App\Http\Controllers;

use App\Models\BranchRawMaterialsReport;
use App\Models\WarehouseRawMaterialsReport;
use App\Models\RawMaterial;
use App\Models\DeliveryStocksUnit;
use App\Models\RecipeCost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardInventoryController extends Controller
{
    public function getInventoryMetrics(Request $request)
    {
        try {
            $branchId = $request->query('branch_id', 'global');

            // 1. Current Balances (The Stock Ledger)
            $rawMaterials = RawMaterial::all();
            $currentBalances = [];

            // Pre-fetch reports for speed
            $branchReportsQuery = BranchRawMaterialsReport::query();
            if ($branchId !== 'global') {
                $branchReportsQuery->where('branch_id', $branchId);
            }
            $branchReports = $branchReportsQuery->get()->groupBy('ingredients_id');

            $warehouseReports = collect();
            if ($branchId === 'global') {
                $warehouseReports = WarehouseRawMaterialsReport::all()->groupBy('raw_material_id');
            }

            foreach ($rawMaterials as $rm) {
                $totalQty = 0;
                
                // Sum from branch
                if (isset($branchReports[$rm->id])) {
                    $totalQty += $branchReports[$rm->id]->sum('total_quantity');
                }

                // Sum from warehouse if global
                if ($branchId === 'global' && isset($warehouseReports[$rm->id])) {
                    $totalQty += $warehouseReports[$rm->id]->sum('total_quantity');
                }

                if ($totalQty > 0) {
                    $currentBalances[] = [
                        'raw_material_id' => $rm->id,
                        'name' => $rm->name,
                        'code' => $rm->code,
                        'category' => $rm->category,
                        'unit' => $rm->unit,
                        'total_quantity' => round($totalQty, 2)
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

            if ($branchId !== 'global') {
                $deliveriesQuery->where('raw_materials_deliveries.to_id', $branchId)
                                ->where('raw_materials_deliveries.to_designation', 'Branch');
            }

            $inMovementsRaw = $deliveriesQuery->select(
                    DB::raw('DATE(raw_materials_deliveries.updated_at) as date'),
                    DB::raw('SUM(delivery_stocks_units.quantity) as total_qty')
                )
                ->groupBy(DB::raw('DATE(raw_materials_deliveries.updated_at)'))
                ->get();

            // 3. OUT Movements (Recipe Cost / Usage)
            $usageQuery = RecipeCost::whereIn('status', ['confirmed', 'missing_stock'])
                ->where('created_at', '>=', now()->subDays(365));
                
            if ($branchId !== 'global') {
                $usageQuery->where('branch_id', $branchId);
            }

            $outMovementsRaw = $usageQuery->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(quantity_used) as total_qty')
                )
                ->groupBy(DB::raw('DATE(created_at)'))
                ->get();
                
            return response()->json([
                'success' => true,
                'currentBalances' => $currentBalances,
                'inMovements' => $inMovementsRaw,
                'outMovements' => $outMovementsRaw
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load inventory metrics',
                'error' => $e->getMessage() // Good for debugging
            ], 500);
        }
    }
}
