<?php
// ============================================================
// FILE: admin/login.php — Trang đăng nhập Admin
// ============================================================
session_start();
if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập — Tây Lương Cửu Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        body        { display:flex; align-items:center; justify-content:center; min-height:100vh; background:#080505; }
        .login-wrap { width:420px; }
        .login-logo { text-align:center; margin-bottom:40px; }
        .login-logo h1 { font-family:'Playfair Display',serif; font-size:2rem; color:#F8F0E0; }
        .login-logo h1 span { color:#C9973A; }
        .login-logo p  { font-size:.65rem; letter-spacing:.3em; text-transform:uppercase; color:#6B4030; margin-top:6px; }
        .login-box  { background:#140A08; border:1px solid rgba(201,151,58,.22); padding:44px 40px; }
        .login-title { font-family:'Playfair Display',serif; font-size:1.3rem; font-weight:400; color:#F8F0E0; margin-bottom:6px; }
        .login-sub   { font-size:.78rem; color:#6B4030; margin-bottom:32px; }
        .field       { margin-bottom:18px; }
        .field label { display:block; font-size:.62rem; letter-spacing:.18em; text-transform:uppercase; color:#9A7060; margin-bottom:7px; }
        .field input { width:100%; background:rgba(255,255,255,.04); border:1px solid rgba(201,151,58,.22); color:#F8F0E0;
                       padding:11px 14px; font-size:.92rem; font-family:inherit; outline:none; transition:border-color .3s; }
        .field input:focus { border-color:#C9973A; }
        .field input::placeholder { color:#4A2810; }
        .btn-login   { width:100%; padding:13px; background:#C9973A; color:#000; border:none; cursor:pointer;
                       font-size:.72rem; font-weight:700; letter-spacing:.2em; text-transform:uppercase;
                       transition:all .3s; margin-top:8px; }
        .btn-login:hover   { background:#E2B865; }
        .btn-login:disabled{ opacity:.6; cursor:not-allowed; }
        .login-error { display:none; background:rgba(139,32,32,.15); border:1px solid rgba(139,32,32,.3);
                       color:#D06060; padding:10px 14px; font-size:.82rem; margin-bottom:16px; }
        .login-foot  { text-align:center; margin-top:20px; font-size:.68rem; color:#4A2810; }
    </style>
</head>
<body>
    <div class="login-wrap">
        <div class="login-logo">
            <h1>Tây Lương <span>Cửu</span></h1>
            <p>Hệ thống quản trị</p>
        </div>
        <div class="login-box">
            <div class="login-title">Đăng nhập</div>
            <div class="login-sub">Nhập thông tin quản trị viên để tiếp tục</div>

            <div id="loginError" class="login-error"></div>

            <div class="field">
                <label>Tên đăng nhập</label>
                <input type="text" id="username" placeholder="admin" autocomplete="username">
            </div>
            <div class="field">
                <label>Mật khẩu</label>
                <input type="password" id="password" placeholder="••••••••"
                       autocomplete="current-password"
                       onkeydown="if(event.key==='Enter') doLogin()">
            </div>
            <button class="btn-login" id="btnLogin" onclick="doLogin()">
                Đăng Nhập →
            </button>
            <div class="login-foot">© <?php echo date('Y'); ?> Tây Lương Cửu</div>
        </div>
    </div>

    <script>
    async function doLogin() {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value;
        const errEl    = document.getElementById('loginError');
        const btn      = document.getElementById('btnLogin');

        errEl.style.display = 'none';

        if (!username || !password) {
            errEl.textContent   = '⚠ Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu!';
            errEl.style.display = 'block';
            return;
        }

        btn.textContent = 'Đang xử lý...';
        btn.disabled    = true;

        try {
            const res  = await fetch('../api/auth.php', {
                method:      'POST',
                credentials: 'same-origin',
                headers:     { 'Content-Type': 'application/json' },
                body:        JSON.stringify({ action: 'login', username, password }),
            });
            const data = await res.json();

            if (data.success) {
                window.location.href = 'index.php';
            } else {
                throw new Error(data.error || 'Đăng nhập thất bại');
            }
        } catch (err) {
            errEl.textContent   = '❌ ' + err.message;
            errEl.style.display = 'block';
            document.getElementById('password').value = '';
            document.getElementById('password').focus();
        } finally {
            btn.textContent = 'Đăng Nhập →';
            btn.disabled    = false;
        }
    }
    </script>
</body>
</html>
