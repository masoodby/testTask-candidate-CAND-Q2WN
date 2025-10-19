<?php
declare(strict_types=1);

$pdo = new PDO('sqlite:' . __DIR__ . '/orders.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("DELETE FROM orders; DELETE FROM order_items; DELETE FROM payments; VACUUM;");

$rows = (int)($argv[1] ?? 5000);
$users = max(5, (int)($argv[2] ?? 20));

$pdo->beginTransaction();
$insOrder = $pdo->prepare("INSERT INTO orders (user_id, total, created_at) VALUES (?, ?, ?)");
$insItem  = $pdo->prepare("INSERT INTO order_items (order_id, sku, qty) VALUES (?, ?, ?)");
$insPay   = $pdo->prepare("INSERT INTO payments (order_id, method, status) VALUES (?, ?, ?)");

$startTs = strtotime('2025-01-01 00:00:00');
for ($i=0; $i<$rows; $i++) {
    $uid = rand(1, $users);
    $total = rand(10, 500) + (rand(0, 99)/100);
    $ts = $startTs + rand(0, 60*60*24*60); // within ~60 days
    $dt = gmdate('Y-m-d H:i:s', $ts);

    $insOrder->execute([$uid, $total, $dt]);
    $oid = (int)$pdo->lastInsertId();

    $items = rand(1, 5);
    for ($k=0; $k<$items; $k++) {
        $sku = 'SKU-' . strtoupper(substr(md5($oid . '-' . $k), 0, 6));
        $qty = rand(1,3);
        $insItem->execute([$oid, $sku, $qty]);
    }
    $method = ['card','paypal','cod'][rand(0,2)];
    $status = ['paid','pending','failed'][rand(0,2)];
    $insPay->execute([$oid, $method, $status]);
}
$pdo->commit();

echo "Seeded $rows orders for $users users.\n";
