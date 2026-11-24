<?php
require 'vendor/autoload.php';
$faker = \Faker\Factory::create();
print_r(get_class_methods($faker));