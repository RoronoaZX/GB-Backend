<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BranchRawMaterialsReport;
use App\Models\BranchRmStocks;
use App\Models\WarehouseRawMaterialsReport;
use App\Models\WarehouseRmStocks;

echo "=========================================================\n";
echo "       STOCKS AND DELIVERY DISCREPANCY ANALYSIS          \n";
echo "=========================================================\n\n";

echo "1. Checking Branch Stocks (Aggregates vs Batches)...\n";
$branchReports = BranchRawMaterialsReport::all();
$branchDiscrepancies = 0;

foreach ($branchReports as $report) {
    // In branch, we match 'ingredients_id' with 'raw_material_id'
    $totalBatchQuantity = BranchRmStocks::where('branch_id', $report->branch_id)
        ->where('raw_material_id', $report->ingredients_id)
        ->sum('quantity');

    if (abs($report->total_quantity - $totalBatchQuantity) > 0.01) {
        echo "⚠️ DISCREPANCY [Branch ID: {$report->branch_id}, Ingredient ID: {$report->ingredients_id}]\n";
        echo "   - Aggregate Report (total_quantity): {$report->total_quantity}\n";
        echo "   - Extracted RM Stocks Sum (quantity from branches): {$totalBatchQuantity}\n";
        echo "   - Difference: " . abs($report->total_quantity - $totalBatchQuantity) . "\n\n";
        $branchDiscrepancies++;
    }
}
if ($branchDiscrepancies === 0) {
    echo "✔️ All active Branch Stocks perfectly coincide with their aggregates.\n\n";
} else {
    echo "Total Branch Discrepancies Found: $branchDiscrepancies\n\n";
}

echo "2. Checking Warehouse Stocks (Aggregates vs Batches)...\n";
$warehouseReports = WarehouseRawMaterialsReport::all();
$whDiscrepancies = 0;

foreach ($warehouseReports as $report) {
    $totalBatchQuantity = WarehouseRmStocks::where('warehouse_id', $report->warehouse_id)
        ->where('raw_material_id', $report->raw_material_id)
        ->sum('total_grams');

    if (abs($report->total_quantity - $totalBatchQuantity) > 0.01) {
        echo "⚠️ DISCREPANCY [Warehouse ID: {$report->warehouse_id}, Ingredient ID: {$report->raw_material_id}]\n";
        echo "   - Aggregate Report (total_quantity): {$report->total_quantity}\n";
        echo "   - Extracted RM Stocks Sum (total_grams from warehouse batches): {$totalBatchQuantity}\n";
        echo "   - Difference: " . abs($report->total_quantity - $totalBatchQuantity) . "\n\n";
        $whDiscrepancies++;
    }
}
if ($whDiscrepancies === 0) {
    echo "✔️ All active Warehouse Stocks perfectly coincide with their aggregates.\n\n";
} else {
    echo "Total Warehouse Discrepancies Found: $whDiscrepancies\n\n";
}

echo "✔️ Analysis complete.\n";
