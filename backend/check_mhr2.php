<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check model_has_roles data
$connection = \DB::connection('mongodb');

echo "=== Running query with MQL ===\n";
$pivotTable = 'model_has_roles';

$query = $connection->table('roles')
    ->join($pivotTable, 'roles.id', '=', $pivotTable . '.role_id')
    ->where($pivotTable . '.model_id', '6b032a992af07a9d000e19f2')
    ->where($pivotTable . '.model_type', 'App\Models\User');

$mql = $query->toMql();
echo "MQL type: " . gettype($mql) . "\n";
print_r($mql);

$results = $query->get();
echo "Results count: " . count($results) . "\n";
foreach ($results as $r) {
    var_dump($r);
}