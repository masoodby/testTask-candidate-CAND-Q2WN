<?php
$db = new PDO('sqlite:' . __DIR__ . '/../db/database.sqlite');

$userId = 1; $limit = 20; $offset = 0;

$sql = "EXPLAIN QUERY PLAN
SELECT o.id, o.user_id, o.created_at
FROM orders o
WHERE o.user_id = :uid
ORDER BY o.created_at DESC
LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
$stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));