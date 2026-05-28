<?php
// FILE: api/gemini_chat.php — AI Bartender có lịch sử + mix rules + link sản phẩm
require_once __DIR__ . '/../config/database.php';
$apiKey = 'AIzaSyDYu_ZbY2VZd0ikchIWD7y1hHuynFUZq-c';

// ── Base URL động ──
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$BASE_URL = $protocol . '://' . $_SERVER['HTTP_HOST'];
// Tự phát hiện subfolder (vd: /tay_luong_cuu/)
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); // /tay_luong_cuu/api
$baseDir   = rtrim(dirname($scriptDir), '/') . '/';                    // /tay_luong_cuu/
$BASE_URL .= $baseDir;

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['test'] ?? '') === '1') {
    $userMsg = 'pha cocktail với rượu nếp';
    $history = [];
} elseif ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method không hợp lệ'], 405);
} else {
    $body    = json_decode(file_get_contents('php://input'), true) ?? [];
    $userMsg = trim($body['message'] ?? '');
    $history = $body['history'] ?? [];
    if (!$userMsg) jsonResponse(['error' => 'Tin nhắn trống'], 400);
}

$conn = getDB();

// Sản phẩm
$products = [];
$res = mysqli_query($conn, "SELECT id, name, category, price, alc, flavor, description, occasion, gift_note, stock FROM products WHERE is_active=1 AND stock > 0 ORDER BY alc DESC");
if (!$res) jsonResponse(['error' => 'DB products: ' . mysqli_error($conn)], 500);
while ($row = mysqli_fetch_assoc($res)) $products[] = $row;

// Công thức + mix_rules
$recipes = [];
$res2 = mysqli_query($conn, "SELECT title, ingredients, steps, occasion, note, mix_rules FROM ai_recipes WHERE is_active=1 ORDER BY id ASC");
if (!$res2) jsonResponse(['error' => 'DB ai_recipes: ' . mysqli_error($conn)], 500);
while ($row = mysqli_fetch_assoc($res2)) $recipes[] = $row;

mysqli_close($conn);

// Build product map (id => data) để tra cứu mix_rules
$productMap = [];
foreach ($products as $p) $productMap[$p['id']] = $p;

// Build product list — có kèm link
$occasionMap = ['gift'=>'QUÀ TẶNG','health'=>'BỔ SỨC KHOẺ','party'=>'TIỆC/NHẬU','daily'=>'HẰNG NGÀY'];
$product_list = implode("\n", array_map(function($p) use ($occasionMap, $BASE_URL) {
    $tags = [];
    foreach (explode(',', $p['occasion'] ?? '') as $occ) {
        $occ = trim($occ);
        if (!empty($occ) && isset($occasionMap[$occ])) $tags[] = $occasionMap[$occ];
    }
    if (!empty($p['gift_note'])) $tags[] = $p['gift_note'];
    $tagStr  = !empty($tags) ? ' ['.implode(', ',$tags).']' : '';
    $alcNote = $p['alc'] < 20 ? ' — NHẸ' : ($p['alc'] >= 38 ? ' — MẠNH' : '');
    $link    = $BASE_URL . 'product.php?id=' . $p['id'];
    return "• {$p['name']} (ID:{$p['id']}): ".number_format($p['price'])."đ | {$p['alc']}°{$alcNote} | Hương: {$p['flavor']}{$tagStr} | Link: {$link}";
}, $products));

// Build recipe list với mix_rules đã giải mã
$intentLabels = [
    'heavy' => 'Thêm độ nặng',
    'aroma' => 'Thêm hương thơm',
    'light' => 'Làm nhẹ hơn',
    'color' => 'Tạo màu sắc',
    'taste' => 'Thêm vị ngọt',
    'food'  => 'Kết hợp món ăn',
];
$occasionLabelMap = ['party'=>'Tiệc','gift'=>'Quà tặng','health'=>'Sức khoẻ','daily'=>'Hằng ngày'];

$recipe_list = empty($recipes) ? '(Chưa có công thức)' : implode("\n\n", array_map(function($r) use ($occasionLabelMap, $intentLabels, $productMap, $BASE_URL) {
    $occs   = array_filter(array_map('trim', explode(',', $r['occasion'] ?? '')));
    $occStr = empty($occs) ? '' : ' ['.implode(', ', array_map(fn($o) => $occasionLabelMap[$o] ?? $o, $occs)).']';
    $lines  = [
        "◆ {$r['title']}{$occStr}",
        "  Nguyên liệu: " . str_replace("\n", ' + ', trim($r['ingredients'])),
        "  Cách pha: "    . str_replace("\n", ' > ', trim($r['steps'])),
    ];
    if (!empty($r['note'])) $lines[] = "  Lưu ý: {$r['note']}";

    // Parse và hiển thị mix_rules kèm link
    try {
        $rules = json_decode($r['mix_rules'] ?? '[]', true) ?: [];
        if (!empty($rules)) {
            $lines[] = "  Gợi ý mix theo yêu cầu khách:";
            foreach ($rules as $rule) {
                $intentLabel = $intentLabels[$rule['intent']] ?? $rule['intent'];
                $prod        = $productMap[$rule['product_id']] ?? null;
                $link        = $prod ? $BASE_URL . 'product.php?id=' . $prod['id'] : '';
                $prodName    = $prod
                    ? "{$prod['name']} ({$prod['alc']}° — ".number_format($prod['price'])."đ) | Link: {$link}"
                    : "ID#{$rule['product_id']}";
                $noteStr     = !empty($rule['note']) ? " — {$rule['note']}" : '';
                $lines[]     = "    → Khi khách muốn [{$intentLabel}]: thêm {$prodName}{$noteStr}";
            }
        }
    } catch(\Exception $e) {}

    return implode("\n", $lines);
}, $recipes));

$systemPrompt = <<<PROMPT
Bạn là bartender kiêm tư vấn viên của Tây Lương Cửu — cửa hàng rượu truyền thống 28 năm tại Hà Nội. Hotline: 092 878 7046.

TÍNH CÁCH: Thân thiện như người bạn sành rượu, biết đúng ý khách, gợi ý tinh tế và sáng tạo.

QUY TẮC TRẢ LỜI:
- Trả lời thẳng vào vấn đề, văn xuôi tự nhiên 3-5 câu, KHÔNG dùng **, ##, --
- Nhớ toàn bộ lịch sử hội thoại để hiểu ngữ cảnh
- Chỉ chào 1 lần ở tin đầu tiên nếu khách chào trước

QUY TẮC CHÈN LINK SẢN PHẨM — BẮT BUỘC:
- Mỗi khi nhắc đến tên sản phẩm cụ thể, LUÔN chèn link dạng HTML:
  <a href="LINK_SẢN_PHẨM" target="_blank" style="color:#C9973A;text-decoration:underline;font-weight:600;">xem sản phẩm</a>
- Link lấy từ trường "Link:" trong danh sách sản phẩm bên dưới
- KHÔNG bịa link, chỉ dùng link có sẵn trong danh sách

QUY TẮC TƯ VẤN MIX RƯỢU — BẮT BUỘC TUÂN THEO:
- Khi khách hỏi về 1 sản phẩm cụ thể → hỏi thêm "Bạn muốn uống riêng hay thích mix thêm gì không? Tôi có vài combo hay lắm!"
- Khi khách muốn điều chỉnh (nặng/nhẹ/thơm/màu/ngọt) → BẮT BUỘC dùng đúng sản phẩm trong mục "Gợi ý mix theo yêu cầu khách" của công thức đang nói đến
- KHÔNG được tự bịa sản phẩm mix ngoài danh sách
- Sau khi gợi ý mix → luôn nêu: tên sản phẩm + link + giá + cách pha tỉ lệ + mời thêm vào giỏ hàng

QUY TẮC UPSELL:
- Combo 2-3 sản phẩm: tính tổng tiền luôn
- Quà tặng: hỏi ngân sách và tặng ai
- Rượu < 20°: nhấn mạnh phù hợp phụ nữ
- Rượu > 38°: nhấn mạnh đàn ông, tiệc lớn

DANH SÁCH SẢN PHẨM TRONG KHO:
{$product_list}

CÔNG THỨC PHA CHẾ VÀ GỢI Ý MIX (nhân viên đã định nghĩa sẵn — PHẢI dùng đúng):
{$recipe_list}
PROMPT;

// Build contents với lịch sử
$contents = [];
if (!empty($history) && count($history) > 1) {
    $historyToUse = array_slice($history, 0, -1);
    $isFirst = true;
    foreach ($historyToUse as $h) {
        $role = ($h['role'] === 'assistant') ? 'model' : 'user';
        $text = $h['content'] ?? '';
        if ($isFirst && $role === 'user') {
            $text    = $systemPrompt . "\n\n---\nKhách: " . $text;
            $isFirst = false;
        }
        $contents[] = ['role'=>$role,'parts'=>[['text'=>$text]]];
    }
    $contents[] = ['role'=>'user','parts'=>[['text'=>$userMsg]]];
} else {
    $contents[] = ['role'=>'user','parts'=>[['text'=>$systemPrompt."\n\n---\nKhách: ".$userMsg]]];
}

$models  = ['gemini-2.5-flash','gemini-2.0-flash','gemini-flash-latest'];
$reply   = null;
$lastErr = null;

foreach ($models as $model) {
    $url  = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
    $data = json_encode(['contents'=>$contents,'generationConfig'=>['temperature'=>0.85,'maxOutputTokens'=>4096,'candidateCount'=>1]]);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER,     ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST,           1);
    curl_setopt($ch, CURLOPT_POSTFIELDS,     $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT,        30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    $curlErr  = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($curlErr) { $lastErr="cURL:{$curlErr}"; continue; }
    $result = json_decode($response, true);
    if (isset($result['error'])) { $lastErr=$result['error']['message']??'API error'; continue; }
    $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if ($text) { $reply=$text; break; }
    $lastErr="No content (HTTP {$httpCode})";
}

if (!$reply) {
    @file_put_contents(__DIR__.'/../logs/gemini_error.log', date('Y-m-d H:i:s')." | {$lastErr} | msg:{$userMsg}\n", FILE_APPEND);
    jsonResponse(['error'=>'Gemini không phản hồi: '.$lastErr], 500);
}
jsonResponse(['reply'=>$reply]);