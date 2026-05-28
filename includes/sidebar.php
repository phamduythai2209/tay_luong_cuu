<?php
// ============================================================
// FILE: includes/sidebar.php
// ============================================================
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <div class="brand-name">Tây Lương <span>Cửu</span></div>
        <div class="brand-sub">Admin Panel</div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-group">Tổng Quan</div>
        <a class="sidebar-item <?= $current==='index.php'?'active':'' ?>" href="index.php">
            <span class="si-icon">📊</span> <span>Dashboard</span>
        </a>

        <div class="nav-group">Quản Lý</div>
        <a class="sidebar-item <?= $current==='products.php'?'active':'' ?>" href="products.php">
            <span class="si-icon">🍶</span> <span>Sản Phẩm</span>
        </a>
        <a class="sidebar-item <?= $current==='orders.php'?'active':'' ?>" href="orders.php">
            <span class="si-icon">📦</span> <span>Đơn Hàng</span>
            <span class="si-badge" id="pendingBadge"></span>
        </a>
        <a class="sidebar-item <?= $current==='customers.php'?'active':'' ?>" href="customers.php">
            <span class="si-icon">👥</span> <span>Khách Hàng</span>
        </a>
        <a class="sidebar-item <?= $current==='recipes.php'?'active':'' ?>" href="recipes.php">
    <span class="si-icon">🍹</span> <span>Công Thức Pha Chế</span>
</a>

        <div class="nav-group">Hệ Thống</div>
        <a class="sidebar-item" href="../index.php" target="_blank">
            <span class="si-icon">🌐</span> <span>Xem Trang Chủ</span>
        </a>
        <a class="sidebar-item" href="#" onclick="doLogout(event)">
            <span class="si-icon">🚪</span> <span>Đăng Xuất</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        Phiên bản 1.0.0<br>
        © <?= date('Y') ?> Tây Lương Cửu
    </div>
</aside>
<script>
// Hiện badge đơn chờ duyệt
(async function(){
    try {
        const r = await fetch('../api/stats.php');
        const d = await r.json();
        if (d.pending_orders > 0) {
            const b = document.getElementById('pendingBadge');
            if (b) { b.textContent = d.pending_orders; b.classList.add('show'); }
        }
    } catch(e){}
})();
</script>