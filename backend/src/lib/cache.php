<?php
declare(strict_types=1);


/** @var PDO $pdo */
$pdo = $GLOBALS['pdo'] ?? null;

// create cache table & cleanup 
function cache_bootstrap(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cache (
            cache_key   TEXT PRIMARY KEY,
            value       TEXT NOT NULL,
            expires_at  INTEGER NOT NULL
        );
    ");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_cache_expires ON cache(expires_at)");
    
    $pdo->exec("DELETE FROM cache WHERE expires_at < strftime('%s','now')");
}


const CACHE_TTL = 60; // seconds


function cache_get(string $key): ?string {
    global $pdo;
    if (!$pdo instanceof PDO) return null;

    $st = $pdo->prepare("SELECT value FROM cache WHERE cache_key = :k AND expires_at > strftime('%s','now')");
    $st->execute([':k' => $key]);
    $val = $st->fetchColumn();
    return $val === false ? null : (string)$val;
}

//cache_set 
function cache_set(string $key, string $value, int $ttl = CACHE_TTL): void {
    global $pdo;
    if (!$pdo instanceof PDO) return;

    $st = $pdo->prepare("
        INSERT INTO cache (cache_key, value, expires_at)
        VALUES (:k, :v, strftime('%s','now') + :ttl)
        ON CONFLICT(cache_key) 
        DO UPDATE SET value = excluded.value, expires_at = excluded.expires_at
    ");
    $st->execute([':k' => $key, ':v' => $value, ':ttl' => $ttl]);
}

// cache_del_prefix 
function cache_del_prefix(string $prefix): void {
    global $pdo;
    if (!$pdo instanceof PDO) return;

    $st = $pdo->prepare("DELETE FROM cache WHERE cache_key LIKE :pfx");
    $st->execute([':pfx' => $prefix . '%']);
}