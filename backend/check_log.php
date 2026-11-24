<?php
$log = file_get_contents('storage/logs/laravel.log');
$lines = explode("\n", $log);
// Find the last ERROR entry and show more context
for ($i = count($lines) - 1; $i >= 0; $i--) {
    if (strpos($lines[$i], 'production.ERROR') !== false) {
        echo $lines[$i] . "\n";
        for ($j = $i + 1; $j < min($i + 30, count($lines)); $j++) {
            echo $lines[$j] . "\n";
        }
        break;
    }
}