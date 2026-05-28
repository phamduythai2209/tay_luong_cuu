<?php
// ============================================================
// FILE: api/suggestions.php
// GET ?product_id=X → Gợi ý rượu cùng loại / giá tương tự
//   Không dùng AI — thuần SQL:
//   1. Cùng category, giá chênh ±200k (ưu tiên)
//   2. Nếu không đủ → bổ sung từ tất cả sản phẩm khác
//   Trả về tối đa 4 sản phẩm
// ============================================================
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') jsonResponse(['error' => 'Chỉ hỗ trợ GET'], 405);

$pid  = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$limit = min((int)($_GET['limit'] ?? 4), 8);

if ($pid <= 0) jsonResponse(['error' => 'Thiếu product_id'], 400);

$conn = getDB();

// Lấy thông tin sản phẩm gốc
$stmt = mysqli_prepare($conn, "SELECT id, category, price FROM products WHERE id = ? AND is_active = 1");
mysqli_stmt_bind_param($stmt, 'i', $pid);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) {
    mysqli_close($conn);
    jsonResponse(['error' => 'Không tìm thấy sản phẩm'], 404);
}

$price_min = $product['price'] - 200000;
$price_max = $product['price'] + 200000;
$category  = clean($conn, $product['category']);

// ── Bước 1: Cùng category + giá tương tự ────────────────────
$stmt2 = mysqli_prepare($conn,
    "SELECT id, name, short_name, category, price, price_old, image, badge, badge_text, alc, volume, origin, stock
     FROM products
     WHERE is_active = 1
       AND id != ?
       AND category = ?
       AND price BETWEEN ? AND ?
     ORDER BY ABS(price - ?) ASC
     LIMIT ?");
mysqli_stmt_bind_param($stmt2, 'isiiis', $pid, $category, $price_min, $price_max, $product['price'], $limit);
mysqli_stmt_execute($stmt2);
$res1    = mysqli_stmt_get_result($stmt2);
$results = [];
$ids     = [$pid];

while ($row = mysqli_fetch_assoc($res1)) {
    $results[] = formatSuggestion($row);
    $ids[]     = $row['id'];
}

// ── Bước 2: Bổ sung nếu chưa đủ ────────────────────────────
if (count($results) < $limit) {
    $need      = $limit - count($results);
    $exclude   = implode(',', $ids);
    $stmt3     = mysqli_prepare($conn,
        "SELECT id, name, short_name, category, price, price_old, image, badge, badge_text, alc, volume, origin, stock
         FROM products
         WHERE is_active = 1 AND id NOT IN ($exclude)
         ORDER BY ABS(price - ?) ASC
         LIMIT ?");
    mysqli_stmt_bind_param($stmt3, 'ii', $product['price'], $need);
    mysqli_stmt_execute($stmt3);
    $res2 = mysqli_stmt_get_result($stmt3);
    while ($row = mysqli_fetch_assoc($res2)) {
        $results[] = formatSuggestion($row);
    }
}

mysqli_close($conn);
jsonResponse($results);

// ── Helper format ────────────────────────────────────────────
function formatSuggestion($row) {
    return [
        'id'        => (int)$row['id'],
        'name'      => $row['name'],
        'short'     => $row['short_name'] ?: $row['name'],
        'category'  => $row['category'],
        'price'     => (int)$row['price'],
        'priceOld'  => $row['price_old'] ? (int)$row['price_old'] : null,
        'img'       => $row['image'] ?: null,
        'badge'     => $row['badge'],
        'badgeText' => $row['badge_text'],
        'alc'       => (float)$row['alc'],
        'vol'       => (int)$row['volume'],
        'origin'    => $row['origin'] ?? '',
        'stock'     => (int)$row['stock'],
    ];
}
