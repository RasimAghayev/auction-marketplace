<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$roles = DB::connection('mongodb_testing')->table('roles')->get();
echo 'Roles in testing DB:' . PHP_EOL;
foreach ($roles as $role) {
    echo '  - ' . $role->name . ' (id: ' . $role->id . ')' . PHP_EOL;
}