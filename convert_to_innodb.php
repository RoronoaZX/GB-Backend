<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = ['branches', 'products', 'warehouses', 'employees', 'users', 'branch_recipes', 'recipes'];

foreach ($tables as $table) {
    try {
        echo "Converting $table to InnoDB...\n";
        DB::statement("ALTER TABLE $table ENGINE=InnoDB");
        echo "Success!\n";
    } catch (\Exception $e) {
        echo "Failed for $table: " . $e->getMessage() . "\n";
    }
}
