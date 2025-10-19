<?php
declare(strict_types=1);

date_default_timezone_set('UTC');

$dbFile = __DIR__ . '/../db/orders.sqlite';
$initSql = __DIR__ . '/../db/migrations/001_init.sql';

// --- create/connect PDO ---
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// برای bind شدن LIMIT/OFFSET در SQLite (در صورت نیاز)
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
// خواندن پیش‌فرض به صورت associative
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// --- SQLite PRAGMAs for perf & stability ---
$pdo->exec("PRAGMA foreign_keys = ON");
$pdo->exec("PRAGMA journal_mode = WAL");     // بهتر برای هم‌زمانی و خواندن‌های متعدد
$pdo->exec("PRAGMA synchronous = NORMAL");   // توازن سرعت/ایمنی
$pdo->exec("PRAGMA busy_timeout = 5000");    // جلوگیری از database is locked
$pdo->exec("PRAGMA temp_store = MEMORY");
$pdo->exec("PRAGMA cache_size = -20000");    // ~20MB page cache (منفی یعنی کیلوبایت-)

if (!file_exists($dbFile)) {
    // initialize schema from 001_init.sql
    $sql = file_get_contents($initSql);
    $pdo->exec($sql);
}

// --- run additional migrations if any (002_*, 003_* ...) ---
$migrationsDir = __DIR__ . '/../db/migrations';
foreach (glob($migrationsDir . '/*.sql') as $mig) {
    // 001_init.sql قبلاً اجرا شده؛ بقیه را هم اعمال کن (ایندکس‌ها و ...)
    if (basename($mig) === '001_init.sql') continue;
    $pdo->exec(file_get_contents($mig));
}
// به SQLite کمک کن پلن‌ها را به‌روز کند
$pdo->exec("ANALYZE"); 
$pdo->exec("PRAGMA optimize");

// --- cache: prefer SQLite-backed cache; fallback if missing ---
$GLOBALS['pdo'] = $pdo; // لازم برای فایل‌های کش
$cacheLib = __DIR__ . '/lib/cache.php';
if (file_exists($cacheLib)) {
    require_once $cacheLib;
    if (function_exists('cache_bootstrap')) {
        cache_bootstrap($pdo); // جدول cache ایجاد/بهینه شود
    }
} else {
    // Fallback VERY-naive file cache (اگر lib/cache.php هنوز ساخته نشده)
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
}

// expose PDO globally (existing code relies on it)
$GLOBALS['pdo'] = $pdo;