<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Giả lập POST request
$_SERVER['REQUEST_METHOD'] = 'POST';

// Bắt output
ob_start();

// Include payment logic
require_once __DIR__ . '/config/database.php';

// Test kết nối DB
$conn = getDB();
echo "✅ DB OK\n";

// Test payment_status column
$res = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'payment_status'");
$col = mysqli_fetch_assoc($res);
echo "payment_status column: " . ($col ? "✅ TỒN TẠI" : "❌ KHÔNG CÓ") . "\n";

// Test insert đơn hàng
$test = mysqli_query($conn, "INSERT INTO orders (order_code, customer_name, phone, address, total, payment_method, payment_status) VALUES ('TEST001','Test','0123','Test',1000,'SePay','pending')");
if ($test) {
    echo "✅ INSERT OK\n";
    mysqli_query($conn, "DELETE FROM orders WHERE order_code='TEST001'");
} else {
    echo "❌ INSERT LỖI: " . mysqli_error($conn) . "\n";
}

mysqli_close($conn);

$output = ob_get_clean();
header('Content-Type: text/plain');
echo $output;