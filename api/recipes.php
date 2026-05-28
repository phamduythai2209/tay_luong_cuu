<?php
// FILE: api/recipes.php — CRUD có mix_rules
session_start();
if (empty($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit; }
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');

$conn   = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'GET') {
    $rows = [];
    $res  = mysqli_query($conn, "SELECT * FROM ai_recipes ORDER BY is_active DESC, id DESC");
    while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
    echo json_encode($rows); exit;
}

if ($method === 'POST' && $action === 'create') {
    $d = json_decode(file_get_contents('php://input'), true) ?? [];
    $title       = trim($d['title']       ?? '');
    $ingredients = trim($d['ingredients'] ?? '');
    $steps       = trim($d['steps']       ?? '');
    $occasion    = trim($d['occasion']    ?? '');
    $note        = trim($d['note']        ?? '');
    $mix_rules   = trim($d['mix_rules']   ?? '[]');
    $is_active   = isset($d['is_active']) ? (int)$d['is_active'] : 1;
    if (!$title || !$ingredients || !$steps) { echo json_encode(['error'=>'Thiếu thông tin']); exit; }
    $stmt = mysqli_prepare($conn, "INSERT INTO ai_recipes (title,ingredients,steps,occasion,note,mix_rules,is_active) VALUES (?,?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt,'ssssssi',$title,$ingredients,$steps,$occasion,$note,$mix_rules,$is_active);
    mysqli_stmt_execute($stmt);
    echo json_encode(['success'=>true,'id'=>mysqli_insert_id($conn)]); exit;
}

if ($method === 'POST' && $action === 'update') {
    $d = json_decode(file_get_contents('php://input'), true) ?? [];
    $id          = (int)($d['id']         ?? 0);
    $title       = trim($d['title']       ?? '');
    $ingredients = trim($d['ingredients'] ?? '');
    $steps       = trim($d['steps']       ?? '');
    $occasion    = trim($d['occasion']    ?? '');
    $note        = trim($d['note']        ?? '');
    $mix_rules   = trim($d['mix_rules']   ?? '[]');
    $is_active   = isset($d['is_active']) ? (int)$d['is_active'] : 1;
    if (!$id || !$title || !$ingredients || !$steps) { echo json_encode(['error'=>'Thiếu thông tin']); exit; }
    $stmt = mysqli_prepare($conn, "UPDATE ai_recipes SET title=?,ingredients=?,steps=?,occasion=?,note=?,mix_rules=?,is_active=? WHERE id=?");
    mysqli_stmt_bind_param($stmt,'ssssssii',$title,$ingredients,$steps,$occasion,$note,$mix_rules,$is_active,$id);
    mysqli_stmt_execute($stmt);
    echo json_encode(['success'=>true]); exit;
}

if ($method === 'POST' && $action === 'delete') {
    $d = json_decode(file_get_contents('php://input'), true) ?? [];
    $id = (int)($d['id'] ?? 0);
    if (!$id) { echo json_encode(['error'=>'ID không hợp lệ']); exit; }
    mysqli_query($conn, "DELETE FROM ai_recipes WHERE id=$id");
    echo json_encode(['success'=>true]); exit;
}

echo json_encode(['error'=>'Action không hợp lệ']);