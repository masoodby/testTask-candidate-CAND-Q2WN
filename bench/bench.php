<?php
// Usage: php bench.php 50 > bench.csv
// Set env BENCH_TOKEN={{TOKEN}} to write header row with token.
$N = (int)($argv[1] ?? 20);
$TARGET_URL = getenv('BENCH_URL') ?: 'http://127.0.0.1:8080/api/orders?user_id=1&page=1&per_page=20';

$token = getenv('BENCH_TOKEN') ?: '{{CAND-Q2WN}}';
$out = fopen('php://stdout', 'w');
fputcsv($out, ['token',$token]);
fputcsv($out, ['i','ms']);
for ($i=1; $i<=$N; $i++) {
    $t0 = microtime(true);
    $ctx = stream_context_create(['http'=>['timeout'=>30]]);
    $body = @file_get_contents($TARGET_URL, false, $ctx);
    $dt = (microtime(true) - $t0) * 1000;
    fputcsv($out, [$i, round($dt,2)]);
    usleep(10000); // 10ms between requests
}
