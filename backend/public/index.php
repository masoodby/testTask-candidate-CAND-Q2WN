<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');
if ($method === 'OPTIONS') { http_response_code(204); exit; }

// Routes
if ($method === 'GET' && $path === '/api/orders') {
    require __DIR__ . '/../src/orders.php';
    exit;
}
if ($method === 'PATCH' && preg_match('#^/api/orders/(\d+)/note$#', $path, $m)) {
    $_GET['order_id'] = (int)$m[1];
    require __DIR__ . '/../src/order_note.php';
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not Found', 'path' => $path]);