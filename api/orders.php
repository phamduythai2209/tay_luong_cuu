<?php
// ══════════════════════════════════════
// api/orders.php
// GET    → danh sách đơn / 1 đơn
// POST   → tạo đơn hàng mới (khách hàng + admin)
// PUT    → cập nhật trạng thái        (cần đăng nhập)
// DELETE → xóa đơn                    (cần đăng nhập)
// ══════════════════════════════════════
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ═══════════════════════════════════════
// GET — Lấy đơn hàng
// ═══════════════════════════════════════
if ($method === 'GET') {
    requireAdmin();
    $conn = getDB();

    if ($id > 0) {
        // Lấy 1 đơn + items
        $stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$order) { mysqli_close($conn); jsonResponse(['error' => 'Không tìm thấy đơn'], 404); }

        // Lấy items
        $stmt2 = mysqli_prepare($conn,
            "SELECT oi.*, p.name, p.short_name, p.image
             FROM order_items oi
             LEFT JOIN products p ON oi.product_id = p.id
             WHERE oi.order_id = ?");
        mysqli_stmt_bind_param($stmt2, 'i', $id);
        mysqli_stmt_execute($stmt2);
        $result = mysqli_stmt_get_result($stmt2);
        $items  = [];
        while ($row = mysqli_fetch_assoc($result)) $items[] = $row;

        $order['items'] = $items;
        mysqli_close($conn);
        jsonResponse($order);
    }

    // Lấy tất cả đơn hàng (có filter)
    $where  = [];
    $status = $_GET['status'] ?? '';
    if ($status) {
        $status_esc = mysqli_real_escape_string($conn, $status);
        $where[]    = "status = '$status_esc'";
    }
    $search = $_GET['search'] ?? '';
    if ($search) {
        $s = mysqli_real_escape_string($conn, $search);
        $where[] = "(order_code LIKE '%$s%' OR customer_name LIKE '%$s%' OR phone LIKE '%$s%')";
    }

    $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $result    = mysqli_query($conn,
        "SELECT o.*, GROUP_CONCAT(CONCAT(p.short_name,'×',oi.qty) SEPARATOR ', ') as product_summary
         FROM orders o
         LEFT JOIN order_items oi ON o.id = oi.order_id
         LEFT JOIN products p ON oi.product_id = p.id
         $where_sql
         GROUP BY o.id
         ORDER BY o.created_at DESC");

    $orders = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = [
            'id'             => (int)$row['id'],
            'order_code'     => $row['order_code'],
            'customer_name'  => $row['customer_name'],
            'phone'          => $row['phone'],
            'address'        => $row['address'],
            'note'           => $row['note'],
            'total'          => (int)$row['total'],
            'status'         => $row['status'],
            'payment_method' => $row['payment_method'],
            'product_summary'=> $row['product_summary'] ?? '',
            'created_at'     => $row['created_at'],
        ];
    }
    mysqli_close($conn);
    jsonResponse($orders);
}

// ═══════════════════════════════════════
// POST — Tạo đơn hàng mới
// ═══════════════════════════════════════
if ($method === 'POST') {
    $conn = getDB();
    $body = json_decode(file_get_contents('php://input'), true);

    // Validate bắt buộc
    if (empty($body['customer_name']) || empty($body['phone']) || empty($body['address'])) {
        jsonResponse(['error' => 'Thiếu thông tin: họ tên, điện thoại, địa chỉ'], 400);
    }
    if (empty($body['items']) || !is_array($body['items'])) {
        jsonResponse(['error' => 'Giỏ hàng trống'], 400);
    }

    // Tạo mã đơn hàng
    $last  = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT order_code FROM orders ORDER BY id DESC LIMIT 1"));
    $last_num = $last ? (int)preg_replace('/\D/', '', $last['order_code']) : 247;
    $order_code = '#TLC' . str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);

    // Tính tổng + kiểm tra tồn kho
    $total    = 0;
    $items    = [];
    foreach ($body['items'] as $item) {
        $pid = (int)($item['product_id'] ?? $item['id'] ?? 0);
        $qty = (int)($item['qty'] ?? $item['quantity'] ?? 1);
        if ($pid <= 0 || $qty <= 0) continue;

        $stmt = mysqli_prepare($conn, "SELECT id, price, stock, short_name FROM products WHERE id = ? AND is_active = 1");
        mysqli_stmt_bind_param($stmt, 'i', $pid);
        mysqli_stmt_execute($stmt);
        $product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$product) { jsonResponse(['error' => "Sản phẩm #$pid không tồn tại"], 400); }
        if ($product['stock'] < $qty) {
            jsonResponse(['error' => "Sản phẩm '{$product['short_name']}' không đủ tồn kho (còn {$product['stock']} chai)"], 400);
        }

        $items[]  = ['id' => $pid, 'qty' => $qty, 'price' => (int)$product['price']];
        $total   += $product['price'] * $qty;
    }

    if (empty($items)) jsonResponse(['error' => 'Không có sản phẩm hợp lệ'], 400);

    // Lưu đơn hàng
    $name    = mysqli_real_escape_string($conn, trim($body['customer_name']));
    $phone   = mysqli_real_escape_string($conn, trim($body['phone']));
    $address = mysqli_real_escape_string($conn, trim($body['address']));
    $note    = mysqli_real_escape_string($conn, trim($body['note'] ?? ''));
    $method_pay = mysqli_real_escape_string($conn, $body['payment_method'] ?? 'COD');

    // Gắn customer_id nếu có (truyền từ JS)
    $customer_id = (int)($body['customer_id'] ?? 0);
    if ($customer_id > 0) {
        $sql = "INSERT INTO orders (order_code, customer_name, phone, address, note, total, payment_method, customer_id)
                VALUES ('$order_code','$name','$phone','$address','$note',$total,'$method_pay',$customer_id)";
    } else {
        $sql = "INSERT INTO orders (order_code, customer_name, phone, address, note, total, payment_method)
                VALUES ('$order_code','$name','$phone','$address','$note',$total,'$method_pay')";
    }

    if (!mysqli_query($conn, $sql)) {
        jsonResponse(['error' => 'Lỗi tạo đơn: ' . mysqli_error($conn)], 500);
    }
    $order_id = mysqli_insert_id($conn);

    // Lưu order_items + trừ kho
    foreach ($items as $item) {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?,?,?,?)");
        mysqli_stmt_bind_param($stmt, 'iiii', $order_id, $item['id'], $item['qty'], $item['price']);
        mysqli_stmt_execute($stmt);

        // Trừ tồn kho
        $stmt2 = mysqli_prepare($conn,
            "UPDATE products SET stock = stock - ?, total_exported = total_exported + ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt2, 'iii', $item['qty'], $item['qty'], $item['id']);
        mysqli_stmt_execute($stmt2);

        // Ghi log xuất kho
        $stmt3 = mysqli_prepare($conn,
            "INSERT INTO inventory_log (type, product_id, qty, note) VALUES ('out', ?, ?, ?)");
        $log_note = "Đơn $order_code";
        mysqli_stmt_bind_param($stmt3, 'iis', $item['id'], $item['qty'], $log_note);
        mysqli_stmt_execute($stmt3);
    }

    mysqli_close($conn);
    jsonResponse([
        'success'    => true,
        'order_id'   => $order_id,
        'order_code' => $order_code,
        'total'      => $total
    ], 201);
}

// ═══════════════════════════════════════
// PUT — Cập nhật đơn hàng (status, v.v.)
// ═══════════════════════════════════════
if ($method === 'PUT') {
    requireAdmin();
    if (!$id) jsonResponse(['error' => 'Thiếu id đơn hàng'], 400);

    $conn = getDB();
    $body = json_decode(file_get_contents('php://input'), true);

    $allowed_statuses = ['Chờ duyệt', 'Đang giao', 'Đã giao', 'Đã hủy'];
    $fields = [];

    if (isset($body['status'])) {
        if (!in_array($body['status'], $allowed_statuses)) {
            jsonResponse(['error' => 'Trạng thái không hợp lệ'], 400);
        }
        $status    = mysqli_real_escape_string($conn, $body['status']);
        $fields[]  = "status = '$status'";
    }
    if (isset($body['note'])) {
        $note     = mysqli_real_escape_string($conn, $body['note']);
        $fields[] = "note = '$note'";
    }

    if (empty($fields)) jsonResponse(['error' => 'Không có gì để cập nhật'], 400);

    mysqli_query($conn, "UPDATE orders SET " . implode(', ', $fields) . " WHERE id = $id");
    mysqli_close($conn);
    jsonResponse(['success' => true, 'message' => 'Đã cập nhật đơn hàng']);
}

// ═══════════════════════════════════════
// DELETE — Xóa đơn hàng
// ═══════════════════════════════════════
if ($method === 'DELETE') {
    requireAdmin();
    if (!$id) jsonResponse(['error' => 'Thiếu id'], 400);

    $conn = getDB();
    // order_items sẽ tự xóa theo CASCADE
    mysqli_query($conn, "DELETE FROM orders WHERE id = $id");
    mysqli_close($conn);
    jsonResponse(['success' => true, 'message' => 'Đã xóa đơn hàng']);
}