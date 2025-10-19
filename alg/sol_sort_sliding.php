<?php
// O(n log n) approach: sort by user, then sliding window for each user.
function findUsers3in5(array $orders): array {
    // orders: [['user'=>'A','time'=>'2025-10-18 14:00:00'], ...]
    $byUser = [];
    foreach ($orders as $o) {
        $byUser[$o['user']][] = strtotime($o['time']);
    }
    $result = [];
    foreach ($byUser as $u => $ts) {
        sort($ts);
        $l = 0;
        for ($r=0; $r<count($ts); $r++) {
            while ($ts[$r] - $ts[$l] > 300) $l++;
            if ($r - $l + 1 >= 3) { $result[$u] = true; break; }
        }
    }
    return array_keys($result);
}
