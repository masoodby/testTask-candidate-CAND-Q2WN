<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/lib/cache.php';


$dbFile = __DIR__ . '/../db/test_cache.sqlite';
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$GLOBALS['pdo'] = $pdo; 


cache_bootstrap($pdo);

echo "✅ cache_bootstrap ok\n";


$key = 'demo:test';
$value = json_encode(['foo' => 'bar', 'time' => time()]);

cache_set($key, $value, 5); 
echo "✅ cache_set ok\n";

$got = cache_get($key);
echo "cache_get returned: " . var_export($got, true) . "\n";


cache_del_prefix('demo:');
$afterDel = cache_get($key);
echo "after delete: ";
var_export($afterDel);
echo "\n";