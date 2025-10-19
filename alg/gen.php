<?php


function genData(int $users = 100, int $events = 5000, int $seed = 42): array {
    mt_srand($seed);
    $out = [];

    
    $base = strtotime('2025-03-01 00:00:00');

    for ($i = 0; $i < $events; $i++) {
        
        $uIndex = mt_rand(1, $users);
        $user   = "U{$uIndex}";

        
        $offset = mt_rand(0, 172800); 
        $ts     = $base + $offset;

        $out[] = ['user' => $user, 'time' => date('Y-m-d H:i:s', $ts)];
    }

    
    for ($b = 0; $b < max(1, intdiv($users, 10)); $b++) {
        $uIndex = mt_rand(1, $users);
        $user   = "U{$uIndex}";
        $t0     = $base + mt_rand(0, 172000);
        $out[] = ['user'=>$user, 'time'=>date('Y-m-d H:i:s', $t0 + 0)];
        $out[] = ['user'=>$user, 'time'=>date('Y-m-d H:i:s', $t0 + 60)];
        $out[] = ['user'=>$user, 'time'=>date('Y-m-d H:i:s', $t0 + 120)];
    }

    return $out;
}


if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $data = genData(5, 30, 7);
    echo "Sample:\n";
    print_r(array_slice($data, 0, 5));
}