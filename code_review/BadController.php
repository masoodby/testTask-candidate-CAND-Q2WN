<?php
// Intentionally bad controller for code review (no need to run).
class OrderController {
    public function list() {
        $db = new PDO('sqlite:/tmp/production.sqlite'); // hardcoded path

        // No auth, no validation
        $userId = $_GET['user'] ?? 1;
        $per = $_GET['per'] ?? 1000; // insane default
        $page = $_GET['page'] ?? 1;

        $sql = "SELECT * FROM orders WHERE user_id = $userId ORDER BY created_at DESC"; // SQL injection risk
        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        // manual pagination in PHP (inefficient)
        $offset = ($page-1)*$per;
        $slice = array_slice($rows, $offset, $per);

        // join in PHP
        foreach ($slice as &$r) {
            $items = $db->query("SELECT * FROM order_items WHERE order_id = ".$r['id'])->fetchAll(PDO::FETCH_ASSOC);
            $r['items'] = $items;
        }

        header('Cache-Control: no-store');
        return json_encode($slice); // no error handling
    }
}
