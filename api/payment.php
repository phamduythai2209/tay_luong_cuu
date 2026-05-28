<?php
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

defined('SEPAY_ACCOUNT_NO')   || define('SEPAY_ACCOUNT_NO',   '7800122092004');
defined('SEPAY_BANK_CODE')    || define('SEPAY_BANK_CODE',    'MB');
defined('SEPAY_ACCOUNT_NAME') || define('SEPAY_ACCOUNT_NAME', 'CONG TY TAY LUONG CUU');

// ═══════════════════════════════════════
// GET — Kiểm tra trạng thái thanh toán
// ═══════════════════════════════════════
if ($method === 'GET' && $action === 'check') {
    $order_code = $_GET['order_code'] ?? '';
    if (!$order_code) jsonResponse(['error' => 'Thiếu order_code'], 400);

    if (strpos($order_code, '#') !== 0) $order_code = '#' . $order_code;

    $conn = getDB();
    $stmt = mysqli_prepare($conn, "SELECT status, payment_status, total FROM orders WHERE order_code = ?");
    mysqli_stmt_bind_param($stmt, 's', $order_code);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$row) { mysqli_close($conn); jsonResponse(['error' => 'Không tìm thấy'], 404); }

    // Nếu đã paid trong DB → trả về luôn
    if ($row['payment_status'] === 'paid') {
        mysqli_close($conn);
        jsonResponse(['paid' => true, 'status' => $row['status']]);
    }

    // Poll SePay API
    $sepayUrl = 'https://my.sepay.vn/userapi/transactions/list?account_number=' . SEPAY_ACCOUNT_NO . '&limit=20';
    $ch = curl_init($sepayUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ZQZJR8H7YHCB054WRBG60VKGWRAMHIEOT8KLGT3KXFYOFPZIL2TXU9UB1N369PMF',
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res  = curl_exec($ch);
    curl_close($ch);

    $paid = false;
    if ($res) {
        $data = json_decode($res, true);
        $txns = $data['transactions'] ?? [];
        $code_clean = str_replace('#', '', $order_code);
        foreach ($txns as $t) {
            $tc = strtoupper($t['transaction_content'] ?? '');
            $ai = (float)($t['amount_in'] ?? 0);
            if (strpos($tc, strtoupper($code_clean)) !== false && $ai >= (float)$row['total']) {
                $paid = true;
                break;
            }
        }
    }

    if ($paid) {
        $upd = mysqli_prepare($conn, "UPDATE orders SET payment_status='paid', status='Đang giao' WHERE order_code=?");
        mysqli_stmt_bind_param($upd, 's', $order_code);
        mysqli_stmt_execute($upd);
    }
    mysqli_close($conn);
    jsonResponse(['paid' => $paid, 'status' => $paid ? 'Đang giao' : $row['status']]);
}

// ═══════════════════════════════════════
// Webhook SePay
// ═══════════════════════════════════════
if ($action === 'webhook') {
    $data    = json_decode(file_get_contents('php://input'), true) ?? [];
    $content = strtoupper($data['transferContent'] ?? '');
    preg_match('/TLC\d+/', $content, $m);
    if ($m) {
        $code = '#' . $m[0];
        $conn = getDB();
        $stmt = mysqli_prepare($conn, "UPDATE orders SET status='Đang giao', payment_status='paid' WHERE order_code=?");
        mysqli_stmt_bind_param($stmt, 's', $code);
        mysqli_stmt_execute($stmt);
        mysqli_close($conn);
    }
    jsonResponse(['success' => true]);
}

// ═══════════════════════════════════════
// POST — Tạo đơn SePay mới
// ═══════════════════════════════════════
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    if (empty($body['customer_name'])) jsonResponse(['error' => 'Thiếu họ tên'], 400);
    if (empty($body['phone']))         jsonResponse(['error' => 'Thiếu SĐT'], 400);
    if (empty($body['address']))       jsonResponse(['error' => 'Thiếu địa chỉ'], 400);
    if (empty($body['items']))         jsonResponse(['error' => 'Giỏ hàng trống'], 400);

    $conn = getDB();

    // Sinh mã đơn không trùng
    do {
        $last     = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT MAX(CAST(REGEXP_REPLACE(order_code,'[^0-9]','') AS UNSIGNED)) as maxnum FROM orders"));
        $last_num   = $last['maxnum'] ? (int)$last['maxnum'] : 247;
        $order_code = '#TLC' . str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
        $check      = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id FROM orders WHERE order_code='$order_code'"));
    } while ($check);

    // Validate items
    $total       = 0;
    $valid_items = [];
    foreach ($body['items'] as $item) {
        $pid = (int)($item['product_id'] ?? $item['id'] ?? 0);
        $qty = (int)($item['qty'] ?? 1);
        if (!$pid || !$qty) continue;

        $stmt = mysqli_prepare($conn, "SELECT id, short_name, price, stock FROM products WHERE id=? AND is_active=1");
        mysqli_stmt_bind_param($stmt, 'i', $pid);
        mysqli_stmt_execute($stmt);
        $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$p) jsonResponse(['error' => "Sản phẩm #$pid không tồn tại"], 400);
        if ($p['stock'] < $qty) jsonResponse(['error' => "'{$p['short_name']}' chỉ còn {$p['stock']} chai"], 400);

        $valid_items[] = ['id' => $pid, 'qty' => $qty, 'price' => (int)$p['price']];
        $total        += $p['price'] * $qty;
    }
    if (!$valid_items) jsonResponse(['error' => 'Không có sản phẩm hợp lệ'], 400);

    $name        = clean($conn, $body['customer_name']);
    $phone       = clean($conn, $body['phone']);
    $address     = clean($conn, $body['address']);
    $note        = clean($conn, $body['note'] ?? '');
    $customer_id = (int)($body['customer_id'] ?? 0); // ← FIX: nhận customer_id

    // Insert đơn hàng — có hoặc không có customer_id
    if ($customer_id > 0) {
        $ok = mysqli_query($conn,
            "INSERT INTO orders (order_code, customer_name, phone, address, note, total, payment_method, payment_status, customer_id)
             VALUES ('$order_code','$name','$phone','$address','$note',$total,'SePay','pending',$customer_id)");
    } else {
        $ok = mysqli_query($conn,
            "INSERT INTO orders (order_code, customer_name, phone, address, note, total, payment_method, payment_status)
             VALUES ('$order_code','$name','$phone','$address','$note',$total,'SePay','pending')");
    }

    if (!$ok) jsonResponse(['error' => 'Lỗi DB: ' . mysqli_error($conn)], 500);

    $order_id = mysqli_insert_id($conn);

    // Insert order_items + trừ kho
    foreach ($valid_items as $item) {
        $s = mysqli_prepare($conn, "INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?,?,?,?)");
        mysqli_stmt_bind_param($s, 'iiii', $order_id, $item['id'], $item['qty'], $item['price']);
        mysqli_stmt_execute($s);

        $s2 = mysqli_prepare($conn, "UPDATE products SET stock=stock-?, total_exported=total_exported+? WHERE id=?");
        mysqli_stmt_bind_param($s2, 'iii', $item['qty'], $item['qty'], $item['id']);
        mysqli_stmt_execute($s2);
    }
    mysqli_close($conn);

    // Tạo QR SePay
    $content = str_replace('#', '', $order_code);
    $qr_url  = "https://qr.sepay.vn/img?acc=" . SEPAY_ACCOUNT_NO
             . "&bank=" . SEPAY_BANK_CODE
             . "&amount=" . $total
             . "&des=" . urlencode($content)
             . "&template=compact";

    jsonResponse([
        'success'      => true,
        'order_id'     => $order_id,
        'order_code'   => $order_code,
        'total'        => $total,
        'qr_url'       => $qr_url,
        'account_no'   => SEPAY_ACCOUNT_NO,
        'account_name' => SEPAY_ACCOUNT_NAME,
        'bank'         => SEPAY_BANK_CODE,
        'content'      => $content,
    ], 201);
}

jsonResponse(['error' => 'Method không hợp lệ'], 405);