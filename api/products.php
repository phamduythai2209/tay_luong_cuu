<?php
// ============================================================
// FILE: api/products.php
// ============================================================
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = $_GET['action'] ?? '';

// ── GET ──────────────────────────────────────────────────────
if ($method === 'GET') {
    $conn     = getDB();
    $is_admin = !empty($_SESSION['admin_id']);
    if ($id > 0) {
        $where = $is_admin ? "id = $id" : "id = $id AND is_active = 1";
        $row   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE $where LIMIT 1"));
        mysqli_close($conn);
        if (!$row) jsonResponse(['error' => 'Không tìm thấy sản phẩm'], 404);
        jsonResponse(formatProduct($row));
    }
    $where  = $is_admin ? '1=1' : 'is_active = 1';
    $search = $_GET['search'] ?? '';
    if ($search) {
        $s      = clean($conn, $search);
        $where .= " AND (name LIKE '%$s%' OR category LIKE '%$s%' OR origin LIKE '%$s%')";
    }
    $result   = mysqli_query($conn, "SELECT * FROM products WHERE $where ORDER BY sort_order ASC, id ASC");
    $products = [];
    while ($row = mysqli_fetch_assoc($result)) $products[] = formatProduct($row);
    mysqli_close($conn);
    jsonResponse($products);
}

// ── POST ─────────────────────────────────────────────────────
if ($method === 'POST') {
    requireAdmin();
    $conn = getDB();
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    // Reorder
    if ($action === 'reorder') {
        $orders = $body['orders'] ?? [];
        if (empty($orders)) { mysqli_close($conn); jsonResponse(['error' => 'Không có dữ liệu'], 400); }
        foreach ($orders as $item) {
            $pid = (int)($item['id'] ?? 0); $order = (int)($item['sort_order'] ?? 0);
            if ($pid > 0) mysqli_query($conn, "UPDATE products SET sort_order = $order WHERE id = $pid");
        }
        mysqli_close($conn);
        jsonResponse(['success' => true, 'message' => 'Đã lưu thứ tự sản phẩm']);
    }

    if (empty($body['name']))     jsonResponse(['error' => 'Tên sản phẩm là bắt buộc'], 400);
    if (empty($body['price']))    jsonResponse(['error' => 'Giá bán là bắt buộc'], 400);
    if (empty($body['category'])) jsonResponse(['error' => 'Danh mục là bắt buộc'], 400);

    // Helper: clean + giữ nguyên HTML (không strip_tags để giữ nội dung)
    $cleanStr = function($conn, $val) {
        return mysqli_real_escape_string($conn, trim((string)$val));
    };

    $name        = $cleanStr($conn, $body['name']);
    $short       = $cleanStr($conn, $body['short_name'] ?? $body['name']);
    $cat         = $cleanStr($conn, $body['category']);
    $alc         = (float)($body['alc'] ?? 0);
    $vol         = (int)($body['volume'] ?? 750);
    $price       = (int)$body['price'];
    $origin      = $cleanStr($conn, $body['origin']      ?? '');
    $flavor      = $cleanStr($conn, $body['flavor']      ?? '');
    $desc        = $cleanStr($conn, $body['description'] ?? '');
    $image       = $cleanStr($conn, $body['image']       ?? '');
    $badge       = $cleanStr($conn, $body['badge']       ?? 'new');
    $badge_text  = $cleanStr($conn, $body['badge_text']  ?? 'Mới');
    $gift_note   = $cleanStr($conn, $body['gift_note']   ?? '');
    $occasion    = $cleanStr($conn, $body['occasion']    ?? '');
    $is_active   = isset($body['is_active']) ? (int)$body['is_active'] : 1;
    $stock       = (int)($body['stock'] ?? 0);
    $avg_price   = (int)($body['avg_import_price'] ?? 0);
    $is_gift     = (int)($body['is_gift'] ?? 0);
    $show_taste  = isset($body['show_taste']) ? (int)$body['show_taste'] : 0;

    $price_old_sql   = !empty($body['price_old'])  ? (int)$body['price_old']  : 'NULL';
    $taste_body_sql  = isset($body['taste_body'])  && $body['taste_body']  !== null ? (int)$body['taste_body']  : 'NULL';
    $taste_sweet_sql = isset($body['taste_sweet']) && $body['taste_sweet'] !== null ? (int)$body['taste_sweet'] : 'NULL';

    $max_order  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(sort_order) as m FROM products"));
    $sort_order = (int)($max_order['m'] ?? 0) + 1;

    $sql = "INSERT INTO products
            (name, short_name, category, alc, volume, price, price_old, origin, flavor,
             description, image, badge, badge_text, is_active, stock, total_imported,
             avg_import_price, sort_order, is_gift, gift_note, occasion, show_taste, taste_body, taste_sweet)
            VALUES
            ('$name','$short','$cat',$alc,$vol,$price,$price_old_sql,
             '$origin','$flavor','$desc','$image','$badge','$badge_text',
             $is_active,$stock,$stock,$avg_price,$sort_order,$is_gift,
             '$gift_note','$occasion',$show_taste,$taste_body_sql,$taste_sweet_sql)";

    if (!mysqli_query($conn, $sql)) {
        $err = mysqli_error($conn);
        mysqli_close($conn);
        jsonResponse(['error' => 'Lỗi thêm sản phẩm: ' . $err], 500);
    }
    $new_id = mysqli_insert_id($conn);
    if ($stock > 0) {
        $stmt = mysqli_prepare($conn, "INSERT INTO inventory_log (type, product_id, qty, note) VALUES ('in', ?, ?, 'Tồn kho ban đầu')");
        mysqli_stmt_bind_param($stmt, 'ii', $new_id, $stock);
        mysqli_stmt_execute($stmt);
    }
    $product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id = $new_id"));
    mysqli_close($conn);
    jsonResponse(['success' => true, 'product' => formatProduct($product)], 201);
}

// ── PUT ──────────────────────────────────────────────────────
if ($method === 'PUT') {
    requireAdmin();
    if (!$id) jsonResponse(['error' => 'Thiếu id sản phẩm'], 400);

    $conn = getDB();
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    $allowed = ['name','short_name','category','alc','volume','price','price_old',
                'origin','flavor','description','image','badge','badge_text',
                'is_active','stock','avg_import_price','sort_order','is_gift',
                'gift_note','occasion','show_taste','taste_body','taste_sweet'];

    $intFields   = ['volume','price','price_old','is_active','stock','avg_import_price',
                    'sort_order','is_gift','show_taste','taste_body','taste_sweet'];
    $nullFields  = ['price_old','image','taste_body','taste_sweet'];

    $fields = [];
    foreach ($allowed as $field) {
        if (!array_key_exists($field, $body)) continue;
        $val = $body[$field];

        if (is_null($val) || $val === '') {
            if (in_array($field, $nullFields)) {
                $fields[] = "$field = NULL";
            }
            // Bỏ qua các field string rỗng — không xóa dữ liệu cũ
            continue;
        }

        if ($field === 'alc') {
            $fields[] = "$field = " . (float)$val;
        } elseif (in_array($field, $intFields)) {
            $fields[] = "$field = " . (int)$val;
        } else {
            // Dùng mysqli_real_escape_string trực tiếp để giữ nội dung HTML/đặc biệt
            $escaped  = mysqli_real_escape_string($conn, trim((string)$val));
            $fields[] = "$field = '$escaped'";
        }
    }

    if (empty($fields)) jsonResponse(['error' => 'Không có dữ liệu để cập nhật'], 400);

    $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = $id";
    if (!mysqli_query($conn, $sql)) {
        $err = mysqli_error($conn);
        mysqli_close($conn);
        jsonResponse(['error' => 'Lỗi cập nhật: ' . $err], 500);
    }

    $product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id = $id"));
    mysqli_close($conn);
    if (!$product) jsonResponse(['error' => 'Không tìm thấy sản phẩm'], 404);
    jsonResponse(['success' => true, 'product' => formatProduct($product)]);
}

// ── DELETE ───────────────────────────────────────────────────
if ($method === 'DELETE') {
    requireAdmin();
    if (!$id) jsonResponse(['error' => 'Thiếu id sản phẩm'], 400);

    $conn = getDB();
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as cnt FROM order_items WHERE product_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $cnt = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['cnt'];

    if ($cnt > 0) {
        mysqli_query($conn, "UPDATE products SET is_active = 0 WHERE id = $id");
        mysqli_close($conn);
        jsonResponse(['success' => true, 'message' => 'Sản phẩm đã được ẩn (có đơn hàng liên quan)']);
    } else {
        mysqli_query($conn, "DELETE FROM inventory_log WHERE product_id = $id");
        mysqli_query($conn, "DELETE FROM products WHERE id = $id");
        mysqli_close($conn);
        jsonResponse(['success' => true, 'message' => 'Đã xóa sản phẩm thành công']);
    }
}

jsonResponse(['error' => 'Method không hợp lệ'], 405);