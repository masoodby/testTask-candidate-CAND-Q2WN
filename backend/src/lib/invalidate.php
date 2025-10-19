<?php
declare(strict_types=1);

require_once __DIR__ . '/cache.php';

function invalidate_user_orders_cache(int $userId): void {
    if (!function_exists('cache_del_prefix')) {
        error_log('invalidate_user_orders_cache: cache_del_prefix() is not available. Did you include cache.php?');
        return;
    }

    if (function_exists('cache_bootstrap') && isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof PDO) {
        try {
            cache_bootstrap($GLOBALS['pdo']); 
        } catch (Throwable $e) {
            error_log('cache_bootstrap failed: ' . $e->getMessage());
        }
    }

    $prefix = "orders:u{$userId}:";
    cache_del_prefix($prefix);
}