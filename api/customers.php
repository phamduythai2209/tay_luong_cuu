<?php
// ============================================================
// FILE: api/customers.php — API quản lý khách hàng (admin)
// ============================================================
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── DANH SÁCH KHÁCH HÀNG ─────────────────────────────────────
if ($method === 'GET' && !$action) {
    $conn = getDB();
    $res  = mysqli_query($conn, "
        SELECT c.*, 
               COUNT(o.id) as order_count,
               COALESCE(SUM(o.total),0) as total_spent
        FROM customers c
        LEFT JOIN orders o ON o.customer_id = c.id
        GROUP BY c.id
        ORDER BY c.id DESC
    ");
    $customers = [];
    while ($row = mysqli_fetch_assoc($res)) {
        unset($row['password']); // Không trả về mật khẩu
        $row['order_count']  = (int)$row['order_count'];
        $row['total_spent']  = (int)$row['total_spent'];
        $row['is_active']    = (int)$row['is_active'];
        $customers[] = $row;
    }
    mysqli_close($conn);
    jsonResponse(['customers' => $customers]);
}

// ── ĐƠN HÀNG CỦA KHÁCH ───────────────────────────────────────
if ($method === 'GET' && $action === 'orders') {
    $id   = (int)($_GET['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'Thiếu id'], 400);
    $conn = getDB();
    $stmt = mysqli_prepare($conn, "SELECT order_code, total, status, payment_method, payment_status, created_at FROM orders WHERE customer_id=? ORDER BY id DESC");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $res    = mysqli_stmt_get_result($stmt);
    $orders = [];
    while ($row = mysqli_fetch_assoc($res)) $orders[] = $row;
    mysqli_close($conn);
    jsonResponse(['orders' => $orders]);
}

// ── KHÓA TÀI KHOẢN ───────────────────────────────────────────
if ($method === 'POST' && $action === 'lock') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int)($body['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'Thiếu id'], 400);
    $conn = getDB();
    $stmt = mysqli_prepare($conn, "UPDATE customers SET is_active=0 WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_close($conn);
    jsonResponse(['success' => true]);
}

// ── MỞ KHÓA TÀI KHOẢN ────────────────────────────────────────
if ($method === 'POST' && $action === 'unlock') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int)($body['id'] ?? 0);
    if (!$id) jsonResponse(['error' => 'Thiếu id'], 400);
    $conn = getDB();
    $stmt = mysqli_prepare($conn, "UPDATE customers SET is_active=1 WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_close($conn);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Action không hợp lệ'], 400);