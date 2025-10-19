<?php
declare(strict_types=1);

/** @var PDO $pdo */
$pdo = $GLOBALS['pdo'];

header('Content-Type: application/json; charset=utf-8');

//helpers 
function as_int($v, int $default, int $min, int $max): int {
    if (!isset($v) || $v === '' || !is_numeric($v)) return $default;
    $v = (int)$v;
    if ($v < $min) $v = $min;
    if ($v > $max) $v = $max;
    return $v;
}
function bad_request(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}


$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($userId <= 0) bad_request('user_id is required (> 0)');

$page   = as_int($_GET['page']     ?? null, 1, 1, 1000000);
$per    = as_int($_GET['per_page'] ?? null, 20, 1, 100);
$offset = ($page - 1) * $per;


$start  = $_GET['start'] ?? null; 
$end    = $_GET['end']   ?? null; 

//where builder 
$where  = ['o.user_id = :uid'];
$params = [':uid' => $userId];

if ($start !== null && $start !== '') {
    $where[] = 'o.created_at >= :start';
    $params[':start'] = (string)$start;
}
if ($end !== null && $end !== '') {    
    $where[] = 'o.created_at < :end'; 
    $params[':end'] = (string)$end;
}
$whereSql = 'WHERE ' . implode(' AND ', $where);

// ---- total count for pagination ----
$sqlCount = "SELECT COUNT(*) AS total FROM orders o $whereSql";
$stCount = $pdo->prepare($sqlCount);
foreach ($params as $k => $v) {
    $stCount->bindValue($k, $k === ':uid' ? (int)$v : (string)$v, $k === ':uid' ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stCount->execute();
$total = (int)$stCount->fetchColumn();


$sql = "
SELECT
  o.id,
  o.user_id,
  o.total,
  o.created_at,
  p.method    AS payment_method,
  p.status    AS payment_status,
  COALESCE(oi.c, 0) AS items_count
FROM orders o
LEFT JOIN payments p ON p.order_id = o.id
LEFT JOIN (
  SELECT order_id, COUNT(*) AS c
  FROM order_items
  GROUP BY order_id
) oi ON oi.order_id = o.id
$whereSql
-- ترتیب سازگار با ایندکس (بدون تابع):
ORDER BY o.created_at DESC, o.id DESC
LIMIT :limit OFFSET :offset
";

$st = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $st->bindValue($k, $k === ':uid' ? (int)$v : (string)$v, $k === ':uid' ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$st->bindValue(':limit',  $per,    PDO::PARAM_INT);
$st->bindValue(':offset', $offset, PDO::PARAM_INT);
$st->execute();
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

// pagination
$out = json_encode([
    'token_hint' => '{{CAND-Q2WN}}',
    'page'       => $page,
    'per_page'   => $per,
    'total'      => $total,
    'total_pages'=> $totalPages,
    'count'      => count($rows),
    'has_next'   => $hasNext,
    'has_prev'   => $hasPrev,
    'next_page'  => $nextPage,
    'prev_page'  => $prevPage,
    'data'       => array_map(function(array $r) {
        $r['payment'] = [
            'method' => $r['payment_method'] ?? null,
            'status' => $r['payment_status'] ?? null,
        ];
        unset($r['payment_method'], $r['payment_status']);
        return $r;
    }, $rows),
], JSON_UNESCAPED_UNICODE);

// set the cache
$cacheKey = "orders:u{$userId}:p{$page}:per{$per}:s{$start}:e{$end}";
cache_set($cacheKey, $out);
echo $out;