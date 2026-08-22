<?php
require __DIR__ . '/vendor/autoload.php';
$path = __DIR__ . '/storage/app/trends_output_v2/trends_combined.csv';
if (!file_exists($path)) { echo "no file\n"; exit(1); }
$rows = array_map('str_getcsv', file($path));
print_r($rows);
