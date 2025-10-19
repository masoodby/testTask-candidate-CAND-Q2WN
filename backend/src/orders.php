<?php
declare(strict_types=1);

$pdo = $GLOBALS['pdo'];

// --- Deliberate issues below (for the candidate to find & fix) ---
// 1) No proper WHERE index on created_at (SQLite allows indexes but we didn't add).
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per    = isset($_GET['per_page']) ? max(1, min(100, (int)$_GET['per_page'])) : 20;

$start  = $_GET['start'] ?? null;
$end    = $_GET['end'] ?? null;

// Very naive cache key (candidates should redesign invalidation)
$cacheKey = "orders:u{$userId}:p{$page}:per{$per}:s{$start}:e{$end}";
if ($cached = cache_get($cacheKey)) {
    echo $cached;
    return;
}

// Build base SQL (inefficient on purpose)
$sql = "SELECT id, user_id, total, created_at FROM orders WHERE user_id = :uid";
$params = [':uid' => $userId];

if ($start) {
    $sql .= " AND created_at >= :start"; // no index -> slow
    $params[':start'] = $start;
}
if ($end) {
    $sql .= " AND created_at <= :end";   // no index -> slow
    $params[':end'] = $end;
}

// BAD: sorting on computed expression (just to be nasty)
// We'll simply sort by created_at ASC, but candidate can consider different sort orders.
$sql .= " ORDER BY datetime(created_at) ASC";

// BAD pagination: OFFSET pagination without a stable index
$offset = ($page - 1) * $per;
$sql .= " LIMIT {$per} OFFSET {$offset}";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// N+1 style enrichment (simulate joins badly)
foreach ($rows as &$r) {
    // BAD: separate queries per row (simulate N+1)
    $s2 = $pdo->prepare("SELECT method, status FROM payments WHERE order_id = :oid");
    $s2->execute([':oid' => $r['id']]);
    $pay = $s2->fetch(PDO::FETCH_ASSOC);
    $r['payment'] = $pay ?: ['method' => null, 'status' => null];

    // Another N+1 for item count
    $s3 = $pdo->prepare("SELECT COUNT(*) as c FROM order_items WHERE order_id = :oid");
    $s3->execute([':oid' => $r['id']]);
    $cnt = $s3->fetch(PDO::FETCH_ASSOC);
    $r['items_count'] = $cnt ? (int)$cnt['c'] : 0;
}
unset($r);

// Fake delay to exaggerate slowness
usleep(50000); // 50ms

$out = json_encode([
    'token_hint' => '{{CAND-Q2WN}}',
    'page' => $page,
    'per_page' => $per,
    'count' => count($rows),
    'data' => $rows
], JSON_UNESCAPED_UNICODE);

cache_set($cacheKey, $out);
echo $out;
