<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$columns = DB::select("DESCRIBE cake_reports");
echo "=== cake_reports columns ===\n";
foreach ($columns as $column) {
    echo "Field: {$column->Field} | Type: {$column->Type} | Null: {$column->Null} | Key: {$column->Key} | Default: {$column->Default}\n";
}

$columnsDtr = DB::select("DESCRIBE daily_time_records");
echo "\n=== daily_time_records columns ===\n";
foreach ($columnsDtr as $column) {
    echo "Field: {$column->Field} | Type: {$column->Type}\n";
}
