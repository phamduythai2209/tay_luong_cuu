<?php
// ============================================================
// FILE: api/firebase_auth.php
// Sync Firebase Google user vào bảng customers MySQL
// ============================================================
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$body = json_decode(file_get_contents('php://input'), true);
$uid   = clean(getDB(), $body['uid']   ?? '');
$email = clean(getDB(), $body['email'] ?? '');
$name  = clean(getDB(), $body['name']  ?? 'Khách hàng');
$photo = clean(getDB(), $body['photo'] ?? '');

if (!$uid || !$email) {
    jsonResponse(['error' => 'Thiếu thông tin'], 400);
}

$conn = getDB();

// Kiểm tra đã có account chưa (theo email)
$stmt = mysqli_prepare($conn, "SELECT * FROM customers WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$customer = mysqli_fetch_assoc($result);

if ($customer) {
    // Đã có → update firebase_uid nếu chưa có
    if (empty($customer['firebase_uid'])) {
        $upd = mysqli_prepare($conn, "UPDATE customers SET firebase_uid=?, photo=?, is_active=1 WHERE id=?");
        mysqli_stmt_bind_param($upd, 'ssi', $uid, $photo, $customer['id']);
        mysqli_stmt_execute($upd);
    }
} else {
    // Chưa có → tạo mới
    $phone    = '';
    $password = md5(uniqid($uid, true)); // random password
    $ins = mysqli_prepare($conn, 
        "INSERT INTO customers (name, email, phone, password, firebase_uid, photo, is_active, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, 1, NOW())"
    );
    mysqli_stmt_bind_param($ins, 'ssssss', $name, $email, $phone, $password, $uid, $photo);
    mysqli_stmt_execute($ins);
    $newId = mysqli_insert_id($conn);

    $stmt2 = mysqli_prepare($conn, "SELECT * FROM customers WHERE id = ?");
    mysqli_stmt_bind_param($stmt2, 'i', $newId);
    mysqli_stmt_execute($stmt2);
    $result2  = mysqli_stmt_get_result($stmt2);
    $customer = mysqli_fetch_assoc($result2);
}

if (!$customer) {
    jsonResponse(['error' => 'Không thể tạo tài khoản'], 500);
}

// Tạo token
$token = md5($customer['id'] . $customer['email'] . $customer['password']);

jsonResponse([
    'id'       => $customer['id'],
    'name'     => $customer['name'],
    'email'    => $customer['email'],
    'photo'    => $customer['photo'] ?? '',
    'token'    => $token,
    'isGoogle' => true,
]);