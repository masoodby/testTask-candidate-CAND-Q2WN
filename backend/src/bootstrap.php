<?php
declare(strict_types=1);

date_default_timezone_set('UTC');

$dbFile = __DIR__ . '/../db/orders.sqlite';
$initSql = __DIR__ . '/../db/migrations/001_init.sql';

if (!file_exists($dbFile)) {
    // Initialize DB
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = file_get_contents($initSql);
    $pdo->exec($sql);
} else {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

// Very naive cache stub (to be replaced/improved by candidate)
function cache_get(string $key): ?string {
    $f = sys_get_temp_dir() . '/cache_' . md5($key) . '.txt';
    if (file_exists($f) && (time() - filemtime($f) < 10)) {
        return file_get_contents($f);
    }
    return null;
}
function cache_set(string $key, string $value): void {
    $f = sys_get_temp_dir() . '/cache_' . md5($key) . '.txt';
    file_put_contents($f, $value);
}

$GLOBALS['pdo'] = $pdo;
