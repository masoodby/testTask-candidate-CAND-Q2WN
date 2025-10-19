<?php
declare(strict_types=1);

require_once __DIR__ . '/cache.php';

function invalidate_user_orders_cache(int $userId): void {
    $prefix = "orders:u{$userId}:";
    cache_del_prefix($prefix);
}