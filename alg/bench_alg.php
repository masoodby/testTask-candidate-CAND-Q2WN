<?php
require __DIR__.'/sol_sort_sliding.php';
$csv = array_map('str_getcsv', file(__DIR__.'/orders.csv'));
$hdr = array_shift($csv);
$orders = [];
foreach ($csv as $row) {
  $orders[] = ['user'=>$row[0], 'time'=>$row[1]];
}
$start = microtime(true);
$r = findUsers3in5($orders);
$dur = microtime(true) - $start;
echo "users=" . count($r) . ", time=" . $dur . "s\n";
