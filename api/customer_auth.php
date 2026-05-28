<?php
// Session riêng cho khách hàng
session_name('tlc_customer');
session_start();

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── ĐĂNG KÝ ──────────────────────────────────────────────────
if ($method === 'POST' && $action === 'register') {
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $name  = trim($body['name']     ?? '');
    $phone = trim($body['phone']    ?? '');
    $email = strtolower(trim($body['email'] ?? ''));
    $pass  = $body['password']      ?? '';

    if (!$name || !$email || !$pass) jsonResponse(['error' => 'Vui lòng điền đầy đủ thông tin'], 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(['error' => 'Email không hợp lệ'], 400);
    if (strlen($pass) < 6) jsonResponse(['error' => 'Mật khẩu tối thiểu 6 ký tự'], 400);

    $conn = getDB();
    $chk  = mysqli_prepare($conn, "SELECT id FROM customers WHERE email = ?");
    mysqli_stmt_bind_param($chk, 's', $email);
    mysqli_stmt_execute($chk);
    if (mysqli_fetch_assoc(mysqli_stmt_get_result($chk))) {
        mysqli_close($conn);
        jsonResponse(['error' => 'Email đã được đăng ký'], 400);
    }

    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $ins  = mysqli_prepare($conn, "INSERT INTO customers (name, phone, email, password) VALUES (?,?,?,?)");
    mysqli_stmt_bind_param($ins, 'ssss', $name, $phone, $email, $hash);
    mysqli_stmt_execute($ins);
    $id = mysqli_insert_id($conn);
    mysqli_close($conn);

    $_SESSION['customer_id']    = $id;
    $_SESSION['customer_name']  = $name;
    $_SESSION['customer_email'] = $email;

    $conn2 = getDB();
    $tr    = mysqli_prepare($conn2, "SELECT id, email, password FROM customers WHERE id=?");
    mysqli_stmt_bind_param($tr, 'i', $id);
    mysqli_stmt_execute($tr);
    $nr    = mysqli_fetch_assoc(mysqli_stmt_get_result($tr));
    mysqli_close($conn2);
    $token = md5($nr['id'] . $nr['email'] . $nr['password']);
    jsonResponse(['success' => true, 'name' => $name, 'email' => $email, 'id' => $id, 'token' => $token]);
}

// ── ĐĂNG NHẬP ────────────────────────────────────────────────
if ($method === 'POST' && $action === 'login') {
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $email = strtolower(trim($body['email'] ?? ''));
    $pass  = $body['password'] ?? '';

    if (!$email || !$pass) jsonResponse(['error' => 'Vui lòng điền email và mật khẩu'], 400);

    $conn = getDB();
    $stmt = mysqli_prepare($conn, "SELECT id, name, email, password, is_active FROM customers WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $row  = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_close($conn);

    if (!$row || !password_verify($pass, $row['password'])) {
        jsonResponse(['error' => 'Email hoặc mật khẩu không đúng'], 401);
    }
    if (!$row['is_active']) jsonResponse(['error' => 'Tài khoản đã bị khóa'], 403);

    $_SESSION['customer_id']    = $row['id'];
    $_SESSION['customer_name']  = $row['name'];
    $_SESSION['customer_email'] = $row['email'];
    $token = md5($row['id'] . $row['email'] . $row['password']);
    jsonResponse(['success' => true, 'name' => $row['name'], 'email' => $row['email'], 'id' => $row['id'], 'token' => $token]);
}

// ── ĐĂNG XUẤT ────────────────────────────────────────────────
if ($method === 'POST' && $action === 'logout') {
    unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['customer_email']);
    jsonResponse(['success' => true]);
}

// ── CHECK SESSION ─────────────────────────────────────────────
if ($method === 'GET' && $action === 'me') {
    if (!empty($_SESSION['customer_id'])) {
        jsonResponse([
            'logged_in' => true,
            'name'      => $_SESSION['customer_name'],
            'email'     => $_SESSION['customer_email'],
            'id'        => $_SESSION['customer_id'],
        ]);
    }
    jsonResponse(['logged_in' => false]);
}

// ── QUÊN MẬT KHẨU - GỬI MÃ ──────────────────────────────────
if ($method === 'POST' && $action === 'forgot') {
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $email = strtolower(trim($body['email'] ?? ''));
    if (!$email) jsonResponse(['error' => 'Vui lòng nhập email'], 400);

    $conn = getDB();
    $stmt = mysqli_prepare($conn, "SELECT id, name FROM customers WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $row  = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$row) { mysqli_close($conn); jsonResponse(['error' => 'Email không tồn tại'], 404); }

    $token   = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $del = mysqli_prepare($conn, "DELETE FROM password_resets WHERE email = ? AND used = 0");
    mysqli_stmt_bind_param($del, 's', $email);
    mysqli_stmt_execute($del);

    $ins = mysqli_prepare($conn, "INSERT INTO password_resets (email, token, expires_at) VALUES (?,?,?)");
    mysqli_stmt_bind_param($ins, 'sss', $email, $token, $expires);
    mysqli_stmt_execute($ins);
    mysqli_close($conn);

    $subject = 'Mã đặt lại mật khẩu - Tây Lương Cửu';
    $message = "Xin chào {$row['name']},\n\nMã đặt lại mật khẩu của bạn là:\n\n    $token\n\nMã này có hiệu lực trong 24 giờ.\nNếu bạn không yêu cầu, hãy bỏ qua email này.\n\n-- Tây Lương Cửu";
    $headers = "From: noreply@tayluongcuu.com\r\nContent-Type: text/plain; charset=UTF-8";

    $sent = mail($email, $subject, $message, $headers);
    if (!$sent) {
        jsonResponse(['success' => true, 'dev_token' => $token, 'note' => 'Email chưa cấu hình SMTP, dùng dev_token để test']);
    }
    jsonResponse(['success' => true]);
}

// ── ĐẶT LẠI MẬT KHẨU ────────────────────────────────────────
if ($method === 'POST' && $action === 'reset') {
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $email = strtolower(trim($body['email']    ?? ''));
    $token = trim($body['token']               ?? '');
    $pass  = $body['password']                 ?? '';

    if (!$email || !$token || !$pass) jsonResponse(['error' => 'Thiếu thông tin'], 400);
    if (strlen($pass) < 6) jsonResponse(['error' => 'Mật khẩu tối thiểu 6 ký tự'], 400);

    $conn = getDB();
    $stmt = mysqli_prepare($conn, "SELECT id FROM password_resets WHERE email=? AND token=? AND used=0 AND expires_at > NOW()");
    mysqli_stmt_bind_param($stmt, 'ss', $email, $token);
    mysqli_stmt_execute($stmt);
    $row  = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$row) { mysqli_close($conn); jsonResponse(['error' => 'Mã không hợp lệ hoặc đã hết hạn'], 400); }

    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $upd  = mysqli_prepare($conn, "UPDATE customers SET password=? WHERE email=?");
    mysqli_stmt_bind_param($upd, 'ss', $hash, $email);
    mysqli_stmt_execute($upd);

    $mark = mysqli_prepare($conn, "UPDATE password_resets SET used=1 WHERE email=? AND token=?");
    mysqli_stmt_bind_param($mark, 'ss', $email, $token);
    mysqli_stmt_execute($mark);
    mysqli_close($conn);

    jsonResponse(['success' => true]);
}

// ── ĐỔI MẬT KHẨU (khi đã login) ─────────────────────────────
if ($method === 'POST' && $action === 'change_password') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $cid  = (int)($body['cid'] ?? 0);
    $tok  = $body['tok']       ?? '';
    $old  = $body['old_password'] ?? '';
    $new  = $body['new_password'] ?? '';

    if (!$cid || !$tok || !$old || !$new) jsonResponse(['error' => 'Thiếu thông tin'], 400);
    if (strlen($new) < 6) jsonResponse(['error' => 'Mật khẩu mới tối thiểu 6 ký tự'], 400);

    $conn = getDB();
    $stmt = mysqli_prepare($conn, "SELECT password FROM customers WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $cid);
    mysqli_stmt_execute($stmt);
    $row  = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$row || !password_verify($old, $row['password'])) {
        mysqli_close($conn);
        jsonResponse(['error' => 'Mật khẩu cũ không đúng'], 400);
    }
    $hash = password_hash($new, PASSWORD_DEFAULT);
    $upd  = mysqli_prepare($conn, "UPDATE customers SET password=? WHERE id=?");
    mysqli_stmt_bind_param($upd, 'si', $hash, $cid);
    mysqli_stmt_execute($upd);
    mysqli_close($conn);
    jsonResponse(['success' => true]);
}

// ── XEM ĐƠN HÀNG CỦA MÌNH ───────────────────────────────────
if ($method === 'GET' && $action === 'my_orders') {
    $cid = (int)($_GET['cid'] ?? 0);
    $tok = $_GET['tok']       ?? '';
    if (!$cid || !$tok) jsonResponse(['error' => 'Chưa đăng nhập'], 401);

    // Verify token
    $conn = getDB();
    $tv   = mysqli_prepare($conn, "SELECT id, email, password FROM customers WHERE id=? AND is_active=1");
    mysqli_stmt_bind_param($tv, 'i', $cid);
    mysqli_stmt_execute($tv);
    $cu   = mysqli_fetch_assoc(mysqli_stmt_get_result($tv));
    if (!$cu || md5($cu['id'] . $cu['email'] . $cu['password']) !== $tok) {
        mysqli_close($conn);
        jsonResponse(['error' => 'Token không hợp lệ'], 401);
    }

    // Lấy danh sách đơn hàng (có id để fetch items)
    $stmt = mysqli_prepare($conn,
        "SELECT id, order_code, customer_name, phone, address, note,
                total, status, payment_method, payment_status, created_at
         FROM orders
         WHERE customer_id = ?
         ORDER BY id DESC
         LIMIT 100");
    mysqli_stmt_bind_param($stmt, 'i', $cid);
    mysqli_stmt_execute($stmt);
    $res    = mysqli_stmt_get_result($stmt);
    $orders = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $orders[] = $row;
    }

    // Lấy items cho từng đơn
    foreach ($orders as &$order) {
        $oid   = (int)$order['id'];
        $istmt = mysqli_prepare($conn,
            "SELECT oi.product_id, oi.qty, oi.price,
                    p.name  AS product_name,
                    p.short_name AS product_short,
                    p.alc, p.volume AS vol
             FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?");
        mysqli_stmt_bind_param($istmt, 'i', $oid);
        mysqli_stmt_execute($istmt);
        $ires           = mysqli_stmt_get_result($istmt);
        $order['items'] = [];
        while ($irow = mysqli_fetch_assoc($ires)) {
            $order['items'][] = $irow;
        }
    }
    unset($order); // break reference

    mysqli_close($conn);
    jsonResponse(['orders' => $orders]);
}

jsonResponse(['error' => 'Action không hợp lệ'], 400);