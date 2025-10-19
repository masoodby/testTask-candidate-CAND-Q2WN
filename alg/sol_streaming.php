<?php
// Streaming-ish: assume input is partitioned by user (or we bucket by user key).
// Maintain a small deque per user with timestamps in ascending order.
function streamProcess(string $user, int $ts, array &$state): void {
    if (!isset($state[$user])) $state[$user] = [];
    $dq =& $state[$user];
    $dq[] = $ts;
    // pop from left while window > 300s
    while ($dq[0] < $ts - 300) array_shift($dq);
}
