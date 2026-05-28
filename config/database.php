<?php
mysqli_report(MYSQLI_REPORT_OFF);
// ============================================================
// FILE: config/database.php
// ============================================================

define('DB_HOST',    'localhost');
define('DB_NAME',    'tay_luong_cuu');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

function getDB() {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn) {
        http_response_code(500);
        die(json_encode(['error' => 'Kết nối database thất bại: ' . mysqli_connect_error()], JSON_UNESCAPED_UNICODE));
    }
    mysqli_set_charset($conn, DB_CHARSET);
    return $conn;
}

function jsonResponse($data, $code = 200) {
    if (ob_get_level()) ob_clean();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requireAdmin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin_id'])) {
        jsonResponse(['error' => 'Chưa đăng nhập hoặc phiên đã hết hạn'], 401);
    }
}

// ── Sanitize: escape SQL + strip HTML tags ───────────────────
function clean($conn, $str) {
    // strip_tags loại bỏ <br>, <b>... trước khi escape để tránh lỗi JSON
    return mysqli_real_escape_string($conn, trim(strip_tags((string)$str)));
}

// ── Format sản phẩm cho JS ──────────────────────────────────
function formatProduct($row) {
    return [
        'id'             => (int)$row['id'],
        'name'           => $row['name']          ?? '',
        'short'          => $row['short_name']     ?: ($row['name'] ?? ''),
        'cat'            => $row['category']       ?? '',
        'alc'            => (float)($row['alc']    ?? 0),
        'vol'            => (int)($row['volume']   ?? 750),
        'price'          => (int)($row['price']    ?? 0),
        'priceOld'       => isset($row['price_old']) && $row['price_old'] !== null ? (int)$row['price_old'] : null,
        'origin'         => $row['origin']         ?? '',
        'flavor'         => $row['flavor']         ?? '',
        'desc'           => $row['description']    ?? '',
        'img'            => ($row['image'] ?? null) ?: null,
        'badge'          => $row['badge']           ?? 'new',
        'badgeText'      => $row['badge_text']      ?? 'Mới',
        'is_active'      => (int)($row['is_active'] ?? 1),
        'statusActive'   => (bool)($row['is_active'] ?? 1),
        'stock'          => (int)($row['stock']            ?? 0),
        'totalImported'  => (int)($row['total_imported']   ?? 0),
        'totalExported'  => (int)($row['total_exported']   ?? 0),
        'avgImportPrice' => (int)($row['avg_import_price'] ?? 0),
        'sort_order'     => (int)($row['sort_order']       ?? 0),
        'gift_note'      => $row['gift_note']              ?? '',
        'occasion'       => $row['occasion']               ?? '',
        'is_gift'        => (int)($row['is_gift']          ?? 0),
        'show_taste'     => (int)($row['show_taste']       ?? 0),
        'taste_body'     => isset($row['taste_body'])  && $row['taste_body']  !== null ? (int)$row['taste_body']  : null,
        'taste_sweet'    => isset($row['taste_sweet']) && $row['taste_sweet'] !== null ? (int)$row['taste_sweet'] : null,
        'created_at'     => $row['created_at'] ?? '',
    ];
}