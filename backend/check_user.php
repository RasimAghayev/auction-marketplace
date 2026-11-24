<?php
$file = file_get_contents('/var/www/html/app/Models/User.php');
$lines = explode("\n", $file);
for ($i = 145; $i < 200; $i++) {
    if (isset($lines[$i])) {
        echo ($i+1) . ": " . $lines[$i] . "\n";
    }
}