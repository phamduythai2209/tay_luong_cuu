<?php
// ============================================================
// FILE: api/auth.php
// GET            → Kiểm tra đã đăng nhập chưa
// POST login     → Đăng nhập admin
// POST logout    → Đăng xuất
// POST change_pw → Đổi mật khẩu
// ============================================================
session_start();
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

// ── GET: Kiểm tra session ────────────────────────────────────
if ($method === 'GET') {
    jsonResponse([
        'logged_in' => !empty($_SESSION['admin_id']),
        'username'  => $_SESSION['admin_username'] ?? null,
        'name'      => $_SESSION['admin_name']     ?? null,
    ]);
}

// ── POST ────────────────────────────────────────────────────
if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? 'login';

    // ĐĂNG XUẤT
    if ($action === 'logout') {
        $_SESSION = [];
        session_destroy();
        jsonResponse(['success' => true, 'message' => 'Đã đăng xuất']);
    }

    // ĐĂNG NHẬP
    if ($action === 'login') {
        $username = trim($body['username'] ?? '');
        $password = trim($body['password'] ?? '');

        if (!$username || !$password) {
            jsonResponse(['error' => 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu'], 400);
        }

        $conn = getDB();
        $stmt = mysqli_prepare($conn, "SELECT id, username, password, full_name FROM admins WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_close($conn);

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']       = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name']     = $admin['full_name'];
            $_SESSION['login_time']     = time();
            jsonResponse([
                'success'  => true,
                'username' => $admin['username'],
                'name'     => $admin['full_name'],
            ]);
        } else {
            jsonResponse(['error' => 'Sai tên đăng nhập hoặc mật khẩu!'], 401);
        }
    }

    // ĐỔI MẬT KHẨU
    if ($action === 'change_password') {
        requireAdmin();
        $old = $body['old_password'] ?? '';
        $new = $body['new_password'] ?? '';

        if (strlen($new) < 6) {
            jsonResponse(['error' => 'Mật khẩu mới phải ít nhất 6 ký tự'], 400);
        }

        $conn = getDB();
        $stmt = mysqli_prepare($conn, "SELECT password FROM admins WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $_SESSION['admin_id']);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if (!$row || !password_verify($old, $row['password'])) {
            mysqli_close($conn);
            jsonResponse(['error' => 'Mật khẩu cũ không đúng'], 400);
        }

        $hash = password_hash($new, PASSWORD_DEFAULT);
        $upd  = mysqli_prepare($conn, "UPDATE admins SET password = ? WHERE id = ?");
        mysqli_stmt_bind_param($upd, 'si', $hash, $_SESSION['admin_id']);
        mysqli_stmt_execute($upd);
        mysqli_close($conn);

        jsonResponse(['success' => true, 'message' => 'Đổi mật khẩu thành công!']);
    }
}

jsonResponse(['error' => 'Method không hợp lệ'], 405);
