<?php
// ============================================================
// FILE: admin/index.php — Dashboard tổng quan
// ============================================================
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <div class="topbar-title">Dashboard Tổng Quan</div>
                <div class="topbar-date" id="todayDate"></div>
            </div>
            <div class="topbar-right">
                <span class="topbar-user">👤 <?php echo htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username']); ?></span>
                <a href="login.php?logout=1" class="btn-logout" onclick="doLogout(event)">Đăng xuất</a>
            </div>
        </div>

        <div class="admin-content">

            <!-- STAT CARDS -->
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card loading"><div class="stat-icon"></div><div class="stat-val" id="sProducts">—</div><div class="stat-lbl">Sản Phẩm</div></div>
                <div class="stat-card loading"><div class="stat-icon"></div><div class="stat-val" id="sOrders">—</div><div class="stat-lbl">Đơn Hàng</div></div>
                <div class="stat-card loading"><div class="stat-icon"></div><div class="stat-val" id="sPending">—</div><div class="stat-lbl">Chờ Duyệt</div></div>
                <div class="stat-card loading"><div class="stat-icon"></div><div class="stat-val" id="sRevenue">—</div><div class="stat-lbl">Doanh Thu (đã giao)</div></div>
            </div>

            <!-- CHARTS ROW -->
            <div class="charts-row">
                <div class="chart-card">
                    <div class="chart-title">Doanh Thu 6 Tháng (Triệu VNĐ)</div>
                    <div class="bar-chart" id="revenueChart"></div>
                </div>
                <div class="chart-card">
                    <div class="chart-title">Tồn Kho Hiện Tại</div>
                    <div id="stockSummary"></div>
                </div>
            </div>

            <!-- TABLES ROW -->
            <div class="tables-row">
                <!-- Đơn hàng gần nhất -->
                <div class="table-card">
                    <div class="table-card-head">
                        <span>Đơn Hàng Gần Nhất</span>
                        <a href="orders.php" class="link-more">Xem tất cả →</a>
                    </div>
                    <table>
                        <thead><tr><th>Mã Đơn</th><th>Khách</th><th>Tổng</th><th>Trạng Thái</th></tr></thead>
                        <tbody id="recentOrders"></tbody>
                    </table>
                </div>

                <!-- Top sản phẩm -->
                <div class="table-card">
                    <div class="table-card-head">
                        <span>Top Sản Phẩm Bán Chạy</span>
                        <a href="products.php" class="link-more">Xem tất cả →</a>
                    </div>
                    <table>
                        <thead><tr><th>Sản Phẩm</th><th>Đã Bán</th><th>Doanh Thu</th></tr></thead>
                        <tbody id="topProducts"></tbody>
                    </table>
                </div>
            </div>

        </div><!-- admin-content -->
    </main>
</div>

<div id="toast" class="toast"></div>

<script src="../assets/js/admin.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('todayDate').textContent =
        new Date().toLocaleDateString('vi-VN', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
    loadDashboard();
});

async function loadDashboard() {
    try {
        const stats = await adminFetch('../api/stats.php');

        // Stat cards
        document.getElementById('sProducts').textContent = stats.total_products;
        document.getElementById('sOrders').textContent   = stats.total_orders;
        document.getElementById('sPending').textContent  = stats.pending_orders;
        document.getElementById('sRevenue').textContent  = '₫' + Number(stats.total_revenue).toLocaleString('vi-VN');

        // Remove loading class
        document.querySelectorAll('.stat-card').forEach(c => c.classList.remove('loading'));

        // Biểu đồ doanh thu
        buildBarChart('revenueChart',
            stats.monthly_revenue.map(m => Math.round(m.value / 1000000)),
            stats.monthly_revenue.map(m => m.label)
        );

        // Tồn kho
        const stock = document.getElementById('stockSummary');
        const maxSt = Math.max(...stats.stock_by_product.map(p => p.stock), 1);
        stock.innerHTML = stats.stock_by_product.map(p => {
            const pct = (p.stock / maxSt * 100).toFixed(0);
            const lvl = p.stock > 200 ? 'high' : p.stock > 80 ? 'mid' : 'low';
            return `<div class="stock-row">
                <div class="stock-row-head"><span>${p.short}</span><span class="stock-qty">${p.stock} chai</span></div>
                <div class="stock-bar-bg"><div class="stock-bar ${lvl}" style="width:${pct}%"></div></div>
            </div>`;
        }).join('');

        // Đơn gần nhất
        const statusClass = { 'Chờ duyệt':'warning','Đang giao':'info','Đã giao':'success','Đã hủy':'danger' };
        document.getElementById('recentOrders').innerHTML = stats.recent_orders.map(o => `
            <tr>
                <td style="color:var(--primary);font-weight:600">${o.order_code}</td>
                <td>${o.customer_name}</td>
                <td>₫${Number(o.total).toLocaleString('vi-VN')}</td>
                <td><span class="badge badge-${statusClass[o.status]||'info'}">${o.status}</span></td>
            </tr>`).join('') || '<tr><td colspan="4" class="text-center text-muted">Chưa có đơn hàng</td></tr>';

        // Top sản phẩm
        document.getElementById('topProducts').innerHTML = stats.top_products.map(p => `
            <tr>
                <td>${p.name}</td>
                <td style="color:var(--primary);font-weight:600">${p.total_sold} chai</td>
                <td>₫${Number(p.revenue).toLocaleString('vi-VN')}</td>
            </tr>`).join('') || '<tr><td colspan="3" class="text-center text-muted">Chưa có dữ liệu</td></tr>';

    } catch (err) {
        showToast('❌ Lỗi tải dữ liệu: ' + err.message, 'error');
    }
}

async function doLogout(e) {
    e.preventDefault();
    await adminFetch('../api/auth.php', { method:'POST', body: JSON.stringify({ action:'logout' }) });
    window.location.href = 'login.php';
}
</script>
</body>
</html>