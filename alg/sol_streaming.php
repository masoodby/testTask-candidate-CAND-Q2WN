<?php

function streamUsers3in5(iterable $events): array {
    $state = [];          
    $hit   = [];          

    foreach ($events as $ev) {
        if (!isset($ev['user'], $ev['time'])) continue;
        $u  = $ev['user'];
        $ts = is_int($ev['time']) ? $ev['time'] : strtotime($ev['time']);

        if (!isset($state[$u])) $state[$u] = [];
        $dq =& $state[$u];

        // append current
        $dq[] = $ts;

        // evict old
        $cut = $ts - 300;
        while (!empty($dq) && $dq[0] < $cut) {
            array_shift($dq);
        }

        // check
        if (count($dq) >= 3) {
            $hit[$u] = true;
        }
    }

    return array_keys($hit);
}


if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $sample = [
        ['user'=>'A','time'=>'2025-10-18 14:00:00'],
        ['user'=>'A','time'=>'2025-10-18 14:03:00'],
        ['user'=>'A','time'=>'2025-10-18 14:04:30'],
        ['user'=>'B','time'=>'2025-10-18 14:10:00'],
    ];
    print_r(streamUsers3in5($sample)); 
}