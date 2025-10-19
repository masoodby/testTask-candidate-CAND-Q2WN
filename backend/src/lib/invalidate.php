<?php
declare(strict_types=1);
require_once __DIR__ . '/cache.php';

function invalidate_user_orders_cache(int $userId): void {
    if (!function_exists('cache_del_prefix')) return;
    cache_del_prefix("orders:u{$userId}:");
}