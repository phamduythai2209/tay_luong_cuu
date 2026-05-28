<?php
// ============================================================
// FILE: api/upload.php
// POST multipart/form-data field 'image'
//   → Kiểm tra MIME thật, kích thước, đổi tên random
//   → Lưu vào /assets/uploads/
//   → Trả về { success, url, filename }
// ============================================================
session_start();
require_once __DIR__ . '/../config/database.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['error' => 'Chỉ hỗ trợ POST'], 405);
if (empty($_FILES['image']))               jsonResponse(['error' => 'Không có file ảnh'], 400);

$file     = $_FILES['image'];
$max_size = 5 * 1024 * 1024; // 5MB

// ── Kiểm tra lỗi upload ────────────────────────────────────
$upload_errors = [
    UPLOAD_ERR_INI_SIZE   => 'File vượt quá giới hạn php.ini',
    UPLOAD_ERR_FORM_SIZE  => 'File vượt quá giới hạn form',
    UPLOAD_ERR_PARTIAL    => 'Upload không hoàn tất',
    UPLOAD_ERR_NO_FILE    => 'Không có file',
    UPLOAD_ERR_NO_TMP_DIR => 'Thiếu thư mục tạm',
    UPLOAD_ERR_CANT_WRITE => 'Không thể ghi file',
];
if ($file['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['error' => $upload_errors[$file['error']] ?? 'Lỗi upload không xác định'], 400);
}

// ── Kiểm tra kích thước ────────────────────────────────────
if ($file['size'] > $max_size) jsonResponse(['error' => 'File quá lớn! Tối đa 5MB'], 400);
if ($file['size'] === 0)       jsonResponse(['error' => 'File trống'], 400);

// ── Kiểm tra MIME thật (không tin extension) ───────────────
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed_mime = [
    'image/jpeg' => 'jpg',
    'image/jpg'  => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];
if (!isset($allowed_mime[$mime])) {
    jsonResponse(['error' => 'Chỉ chấp nhận ảnh JPG, PNG, WEBP, GIF'], 400);
}

// ── Kiểm tra đây là ảnh thật (tránh file giả) ─────────────
$image_info = @getimagesize($file['tmp_name']);
if (!$image_info) jsonResponse(['error' => 'File không phải ảnh hợp lệ'], 400);

// ── Tạo tên file an toàn ───────────────────────────────────
$ext      = $allowed_mime[$mime];
$filename = 'product_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;

$upload_dir = __DIR__ . '/../assets/uploads/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$filepath = $upload_dir . $filename;

// ── Lưu file ───────────────────────────────────────────────
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    jsonResponse(['error' => 'Lỗi lưu file vào server'], 500);
}

// ── Trả về URL ─────────────────────────────────────────────
$script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$base       = rtrim(dirname($script_dir), '/');
$url        = $base . '/assets/uploads/' . $filename;

jsonResponse([
    'success'  => true,
    'url'      => $url,
    'filename' => $filename,
    'size'     => $file['size'],
    'mime'     => $mime,
    'width'    => $image_info[0],
    'height'   => $image_info[1],
]);
