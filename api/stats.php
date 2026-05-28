<?php
// ============================================================
// FILE: api/stats.php
// GET → Thống kê dashboard: doanh thu, đơn hàng, tồn kho
//       Doanh thu 6 tháng gần nhất, tồn kho theo sản phẩm
// ============================================================
session_start();
require_once __DIR__ . '/../config/database.php';
requireAdmin();

$conn = getDB();

// ── Thống kê tổng quan ──────────────────────────────────────
$total_products  = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM products WHERE is_active = 1"))['c'];
$total_orders    = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders"))['c'];
$pending_orders  = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE status = 'Chờ duyệt'"))['c'];
$shipping_orders = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM orders WHERE status = 'Đang giao'"))['c'];
$total_stock     = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(stock),0) AS s FROM products"))['s'];
$total_revenue   = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(total),0) AS s FROM orders WHERE status = 'Đã giao'"))['s'];

// ── Doanh thu 6 tháng gần nhất ──────────────────────────────
$monthly = [];
for ($i = 5; $i >= 0; $i--) {
    $row = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COALESCE(SUM(total),0) AS rev,
                DATE_FORMAT(DATE_SUB(NOW(), INTERVAL $i MONTH), '%m/%Y') AS label
         FROM orders
         WHERE status = 'Đã giao'
           AND YEAR(created_at)  = YEAR(DATE_SUB(NOW(),  INTERVAL $i MONTH))
           AND MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL $i MONTH))"));
    $monthly[] = [
        'label' => 'T' . ltrim(explode('/', $row['label'])[0], '0'),
        'value' => (int)$row['rev'],
    ];
}

// ── Tồn kho theo sản phẩm ───────────────────────────────────
$stock_res = mysqli_query($conn,
    "SELECT id, name, short_name, stock, total_imported, total_exported, avg_import_price
     FROM products WHERE is_active = 1 ORDER BY id ASC");
$stock_data = [];
while ($row = mysqli_fetch_assoc($stock_res)) {
    $stock_data[] = [
        'id'             => (int)$row['id'],
        'name'           => $row['name'],
        'short'          => $row['short_name'] ?: $row['name'],
        'stock'          => (int)$row['stock'],
        'total_imported' => (int)$row['total_imported'],
        'total_exported' => (int)$row['total_exported'],
        'avg_price'      => (int)$row['avg_import_price'],
    ];
}

// ── Top 5 sản phẩm bán chạy ─────────────────────────────────
$top_res = mysqli_query($conn,
    "SELECT p.name, p.short_name, SUM(oi.qty) AS total_sold, SUM(oi.qty * oi.price) AS revenue
     FROM order_items oi
     JOIN products p ON oi.product_id = p.id
     GROUP BY oi.product_id
     ORDER BY total_sold DESC
     LIMIT 5");
$top_products = [];
while ($row = mysqli_fetch_assoc($top_res)) {
    $top_products[] = [
        'name'       => $row['short_name'] ?: $row['name'],
        'total_sold' => (int)$row['total_sold'],
        'revenue'    => (int)$row['revenue'],
    ];
}

// ── Đơn hàng gần nhất ───────────────────────────────────────
$recent_res = mysqli_query($conn,
    "SELECT order_code, customer_name, total, status, created_at
     FROM orders ORDER BY created_at DESC LIMIT 5");
$recent_orders = [];
while ($row = mysqli_fetch_assoc($recent_res)) $recent_orders[] = $row;

mysqli_close($conn);

jsonResponse([
    'total_products'  => $total_products,
    'total_orders'    => $total_orders,
    'pending_orders'  => $pending_orders,
    'shipping_orders' => $shipping_orders,
    'total_stock'     => $total_stock,
    'total_revenue'   => $total_revenue,
    'monthly_revenue' => $monthly,
    'stock_by_product'=> $stock_data,
    'top_products'    => $top_products,
    'recent_orders'   => $recent_orders,
]);
