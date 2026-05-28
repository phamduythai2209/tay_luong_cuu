<?php
// ============================================================
// FILE: setup_admin.php
// Chạy 1 lần để tạo tài khoản admin với mật khẩu bcrypt đúng
// ⚠ XÓA FILE NÀY NGAY SAU KHI DÙNG XONG!
// ============================================================
require_once __DIR__ . '/config/database.php';

$username  = 'admin';
$password  = '123456';     // ← Đổi mật khẩu theo ý muốn tại đây
$full_name = 'Quản Trị Viên';

$hashed = password_hash($password, PASSWORD_DEFAULT);
$conn   = getDB();

mysqli_query($conn, "DELETE FROM admins WHERE username = '" . clean($conn, $username) . "'");

$stmt = mysqli_prepare($conn, "INSERT INTO admins (username, password, full_name) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, 'sss', $username, $hashed, $full_name);
$ok = mysqli_stmt_execute($stmt);
mysqli_close($conn);

if (php_sapi_name() === 'cli') {
    echo $ok
        ? "✅ Tạo admin thành công!\n   Username: $username\n   Password: $password\n\n⚠  Xóa file này ngay!\n"
        : "❌ Lỗi tạo admin!\n";
} else {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body style='font-family:monospace;background:#080505;color:#F8F0E0;padding:48px;'>";
    if ($ok) {
        echo "<h2 style='color:#C9973A;'>✅ Tạo admin thành công!</h2>
              <p>Username: <strong>$username</strong></p>
              <p>Password: <strong>$password</strong></p>
              <p style='color:#D06060;margin-top:24px;'>⚠ Hãy xóa file <code>setup_admin.php</code> ngay bây giờ!</p>
              <br><a href='admin/login.php' style='color:#C9973A;'>→ Đến trang đăng nhập</a>";
    } else {
        echo "<h2 style='color:#D06060;'>❌ Lỗi tạo admin!</h2>";
    }
    echo "</body></html>";
}
