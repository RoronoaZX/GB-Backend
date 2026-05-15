<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

try {
    $branchesStatus = DB::select('SHOW TABLE STATUS WHERE Name = "branches"')[0];
    echo "Branches Engine: " . $branchesStatus->Engine . "\n";
    echo "Branches Collation: " . $branchesStatus->Collation . "\n";

    $breadOutsStatus = DB::select('SHOW TABLE STATUS WHERE Name = "bread_outs"')[0];
    echo "BreadOuts Engine: " . $breadOutsStatus->Engine . "\n";
    echo "BreadOuts Collation: " . $breadOutsStatus->Collation . "\n";

    echo "Attempting to add branch_id foreign key...\n";
    Schema::table('bread_outs', function (Blueprint $table) {
        $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
    });
    echo "Success for branch_id!\n";

    echo "Attempting to add product_id foreign key...\n";
    Schema::table('bread_outs', function (Blueprint $table) {
        $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
    });
    echo "Success for product_id!\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
