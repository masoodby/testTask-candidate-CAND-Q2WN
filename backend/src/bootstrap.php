<?php
declare(strict_types=1);

date_default_timezone_set('UTC');

$dbFile  = __DIR__ . '/../db/orders.sqlite';
$initSql = __DIR__ . '/../db/migrations/001_init.sql';


 
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);


if (!file_exists($dbFile) || filesize($dbFile) === 0) {
    if (is_file($initSql)) {
        $sql = file_get_contents($initSql);
        if ($sql !== false) {
            $pdo->exec($sql);
        }
    }
}


$GLOBALS['pdo'] = $pdo;


require_once __DIR__ . '/lib/cache.php';
cache_bootstrap($pdo);