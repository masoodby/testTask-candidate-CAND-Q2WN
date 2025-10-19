<?php
// Generate random orders for users A..Z
$rows = (int)($argv[1] ?? 100000);
$users = (int)($argv[2] ?? 26);
$names = array_map(fn($i)=>chr(65+$i), range(0, $users-1));
$start = strtotime('2025-01-01 00:00:00');

$out = fopen(__DIR__.'/orders.csv', 'w');
fputcsv($out, ['user','time']);
for ($i=0; $i<$rows; $i++) {
    $u = $names[array_rand($names)];
    $ts = $start + rand(0, 86400*30);
    fputcsv($out, [$u, date('Y-m-d H:i:s', $ts)]);
}
fclose($out);
echo "Generated orders.csv with $rows rows.\n";
