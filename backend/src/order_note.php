<?php
declare(strict_types=1);

/** @var PDO $pdo */
$pdo = $GLOBALS['pdo'];

header('Content-Type: application/json; charset=utf-8');

function end_json(int $code, array $payload): never {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($orderId <= 0) end_json(400, ['error' => 'invalid order id']);

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || !array_key_exists('note', $data)) {
    end_json(400, ['error' => 'note required']);
}
$note = trim((string)$data['note']);
if (mb_strlen($note) > 500) end_json(400, ['error' => 'note too long (max 500 chars)']);

// وجود سفارش + user_id
$st = $pdo->prepare("SELECT id, user_id FROM orders WHERE id = :id LIMIT 1");
$st->execute([':id' => $orderId]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row) end_json(404, ['error' => 'order not found']);
$userId = (int)$row['user_id'];

// UPDATE
$st2 = $pdo->prepare("UPDATE orders SET note = :note WHERE id = :id");
$st2->execute([':note' => $note, ':id' => $orderId]);
$affected = $st2->rowCount();

// ابطال کش
$inv = __DIR__ . '/lib/invalidate.php';
if (file_exists($inv)) {
    require_once $inv;
    if (function_exists('invalidate_user_orders_cache')) {
        invalidate_user_orders_cache($userId);
    }
}

// پاسخ
end_json(200, ['ok' => true, 'affected' => $affected, 'order_id' => $orderId, 'note' => $note]);