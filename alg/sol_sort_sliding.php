<?php

function findUsers3in5(array $events): array {
    $byUser = [];
    foreach ($events as $ev) {
        // guard minimal shape
        if (!isset($ev['user'], $ev['time'])) continue;
        $byUser[$ev['user']][] = strtotime($ev['time']);
    }

    $result = [];
    foreach ($byUser as $u => $ts) {
        sort($ts);               
        $l = 0;
        for ($r = 0; $r < count($ts); $r++) {
            while ($ts[$r] - $ts[$l] > 300) $l++;    
            if ($r - $l + 1 >= 3) {                  
                $result[$u] = true;
                break;
            }
        }
    }
    return array_keys($result);
}

// CLI quick-test (optional)
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $sample = [
        ['user'=>'A','time'=>'2025-10-18 14:00:00'],
        ['user'=>'A','time'=>'2025-10-18 14:03:00'],
        ['user'=>'A','time'=>'2025-10-18 14:04:30'],
        ['user'=>'B','time'=>'2025-10-18 14:10:00'],
    ];
    print_r(findUsers3in5($sample)); // expects: ["A"]
}