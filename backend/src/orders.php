<?php
declare(strict_types=1);

/** @var PDO $pdo */
$pdo = $GLOBALS['pdo'];

header('Content-Type: application/json; charset=utf-8');

// ---------- helpers ----------
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

function normalize_date(?string $v, bool $isEnd = false): ?string {
    if ($v === null || $v === '') return null;
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
        if ($isEnd) {
            $dt = new DateTime($v . ' 00:00:00');
            $dt->modify('+1 day');
            return $dt->format('Y-m-d H:i:s');
        }
        return $v . ' 00:00:00';
    }
    return $v;
}

//  inputs
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($userId <= 0) bad_request('user_id is required (> 0)');

$page   = as_int($_GET['page']     ?? null, 1, 1, 1000000);
$per    = as_int($_GET['per_page'] ?? null, 20, 1, 100);
$offset = ($page - 1) * $per;


$startRaw = $_GET['start'] ?? null;
$endRaw   = $_GET['end']   ?? null;
$start    = normalize_date($startRaw, false);
$end      = normalize_date($endRaw,   true);

//  WHERE 
$where  = ['o.user_id = :uid'];
$params = [':uid' => $userId];

if ($start !== null) {
    $where[] = 'o.created_at >= :start';
    $params[':start'] = $start;
}
if ($end !== null) {
    $where[] = 'o.created_at < :end'; 
    $params[':end'] = $end;
}

if ($start !== null && $end !== null && strcmp($start, $end) >= 0) {
    bad_request('invalid range: start must be less than end');
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

//  total count 
$sqlCount = "SELECT COUNT(*) AS total FROM orders o $whereSql";
$stCount = $pdo->prepare($sqlCount);
foreach ($params as $k => $v) {
    $stCount->bindValue($k, $k === ':uid' ? (int)$v : (string)$v, $k === ':uid' ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stCount->execute();
$total = (int)$stCount->fetchColumn();

//  main query (N+1 Removed...) 

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

// ---------- pagination meta ----------
$totalPages = (int)ceil($total / max(1, $per));
$hasNext    = ($offset + $per) < $total;
$hasPrev    = $page > 1;
$nextPage   = $hasNext ? $page + 1 : null;
$prevPage   = $hasPrev ? $page - 1 : null;

// ---------- output ----------
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

// ---------- cache set (اختیاری؛ اگر cache_set دارید) ----------
$cacheKey = "orders:u{$userId}:p{$page}:per{$per}:s{$start}:e{$end}";
if (function_exists('cache_set')) {
    cache_set($cacheKey, $out);
}

echo $out;