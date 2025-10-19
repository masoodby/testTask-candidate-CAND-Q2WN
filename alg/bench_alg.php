<?php
require __DIR__ . '/gen.php';
require __DIR__ . '/sol_sort_sliding.php';
require __DIR__ . '/sol_streaming.php';

$TOKEN = getenv('BENCH_TOKEN') ?: 'CAND-Q2WN';


$scenarios = [
    ['users' => 100, 'events' => 5_000],
    ['users' => 200, 'events' => 20_000],
    ['users' => 500, 'events' => 50_000],
];

function runOnce(callable $fn, array $data): array {
    $t0 = hrtime(true);
    $res = $fn($data);
    $t1 = hrtime(true);
    $ms = ($t1 - $t0) / 1e6;
    sort($res);
    return [$ms, $res];
}

echo "token,{$TOKEN}\n";
echo "scenario,users,events,algo,time_ms,found_users\n";

foreach ($scenarios as $i => $sc) {
    $data = genData($sc['users'], $sc['events'], 42 + $i);

    
    [$msBatch, $foundBatch] = runOnce('findUsers3in5', $data);

    
    [$msStream, $foundStream] = runOnce('streamUsers3in5', $data);

    
    if ($foundBatch !== $foundStream) {
        
    }

    echo "S".($i+1).",{$sc['users']},{$sc['events']},batch,".number_format($msBatch, 2).",\"".implode('|',$foundBatch)."\"\n";
    echo "S".($i+1).",{$sc['users']},{$sc['events']},stream,".number_format($msStream, 2).",\"".implode('|',$foundStream)."\"\n";
}