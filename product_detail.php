<?php
// ============================================================
// FILE: product_detail.php — Trang chi tiết sản phẩm
// ============================================================
require_once __DIR__ . '/config/database.php';

$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: index.php'); exit; }

$conn = getDB();
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ? AND is_active = 1");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$p) { mysqli_close($conn); header('Location: index.php'); exit; }

// Lấy tất cả sản phẩm active để dùng cho cart JS
$res      = mysqli_query($conn, "SELECT * FROM products WHERE is_active = 1");
$allProds = [];
while ($row = mysqli_fetch_assoc($res)) $allProds[] = formatProduct($row);
mysqli_close($conn);

$product = formatProduct($p);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> — Tây Lương Cửu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Cormorant+Garamond:wght@300;400;500&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .detail-wrap  { max-width:1200px; margin:0 auto; padding:120px 48px 80px; }
        .detail-grid  { display:grid; grid-template-columns:1fr 1fr; gap:64px; align-items:start; }
        .detail-img   { background:linear-gradient(135deg,#1A0808,#0A0808);
                        border:1px solid var(--border); height:480px;
                        display:flex; align-items:center; justify-content:center;
                        position:sticky; top:90px; overflow:hidden; }
        .detail-img img { max-height:420px; object-fit:contain; }
        .detail-badge { display:inline-block; padding:4px 12px; font-size:.6rem;
                        font-weight:700; letter-spacing:.15em; text-transform:uppercase;
                        margin-bottom:14px; }
        .detail-name  { font-family:'Playfair Display',serif; font-size:2.2rem;
                        font-weight:400; color:var(--ivory); line-height:1.2; margin-bottom:10px; }
        .detail-origin { font-size:.65rem; letter-spacing:.22em; text-transform:uppercase;
                         color:var(--gold); margin-bottom:20px; }
        .detail-specs { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin:24px 0; }
        .spec-item    { background:rgba(255,255,255,.03); border:1px solid var(--border2);
                        padding:12px 16px; }
        .spec-label   { font-size:.58rem; letter-spacing:.18em; text-transform:uppercase;
                        color:var(--t3); margin-bottom:4px; }
        .spec-val     { font-size:.92rem; color:var(--t1); }
        .detail-price { font-family:'Playfair Display',serif; font-size:2rem; color:var(--gold);
                        margin:20px 0 8px; }
        .detail-price-old { font-size:1rem; color:var(--t4); text-decoration:line-through; margin-left:10px; }
        .detail-stock { font-size:.75rem; color:var(--t3); margin-bottom:24px; }
        .detail-stock span { color:<?= $product['stock'] > 50 ? '#5CB870' : ($product['stock'] > 0 ? '#D4A017' : '#D06060') ?>; }
        .detail-desc  { font-size:.95rem; color:var(--t2); line-height:2; margin-bottom:28px;
                        border-left:2px solid var(--gold); padding-left:16px; }
        .detail-actions { display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
        .qty-input-wrap { display:flex; align-items:center; gap:6px; border:1px solid var(--border);
                          padding:0 4px; }
        .qty-input-wrap button { width:32px; height:40px; background:none; border:none;
                                  color:var(--gold); font-size:1.1rem; cursor:pointer; }
        .qty-input-wrap input  { width:44px; text-align:center; background:none; border:none;
                                  color:var(--ivory); font-size:.9rem; padding:0; }
        .divider { border:none; border-top:1px solid var(--border2); margin:32px 0; }
        .suggestions-sec { padding:60px 48px 100px; }
        .suggestions-sec > div { max-width:1200px; margin:0 auto; }
        @media(max-width:900px){
            .detail-grid { grid-template-columns:1fr; gap:32px; }
            .detail-img  { position:static; height:320px; }
            .detail-wrap { padding:100px 20px 60px; }
            .suggestions-sec { padding:40px 20px 60px; }
        }
    </style>
</head>
<body>

<!-- TOAST -->
<div class="toast" id="toast"><span id="toast-msg"></span></div>

<!-- CART OVERLAY + DRAWER -->
<div class="cart-overlay" id="cartOverlay" onclick="toggleCart()"></div>
<div class="cart-drawer" id="cartDrawer">
    <div class="cart-head">
        <h3>Giỏ Hàng (<span id="cartCountNav">0</span>)</h3>
        <button class="cart-close" onclick="toggleCart()">✕</button>
    </div>
    <div class="cart-body" id="cartBody">
        <div class="cart-empty">Giỏ hàng trống</div>
    </div>
    <div class="cart-foot">
        <div class="cart-total-row">
            <span class="cart-total-label">Tổng cộng</span>
            <span class="cart-total-val" id="cartTotal">₫0</span>
        </div>
        <div class="cart-checkout-btns">
            <button class="btn-primary" style="font-size:.6rem;padding:11px 8px;" onclick="openCheckout('online')">💳 Online</button>
            <button class="btn-outline"  style="font-size:.6rem;padding:10px 8px;" onclick="openCheckout('cod')">🤝 COD</button>
        </div>
        <button class="btn-outline" style="width:100%;margin-top:8px;font-size:.6rem;padding:9px;" onclick="window.location.href='cart.php'">Xem giỏ hàng</button>
    </div>
</div>

<!-- NAVBAR -->
<nav id="navbar">
    <div class="nav-logo" onclick="window.location.href='index.php'">
        <div style="font-size:1.8rem;">🍶</div>
        <div>
            <div class="logo-text">Tây Lương <span>Cửu</span></div>
            <div class="logo-sub">Rượu Truyền Thống Cao Cấp</div>
        </div>
    </div>
    <ul class="nav-links">
        <li><a onclick="window.location.href='index.php'">Trang Chủ</a></li>
        <li><a onclick="window.location.href='index.php#productsSection'">Sản Phẩm</a></li>
        <li><a onclick="window.location.href='cart.php'">Giỏ Hàng</a></li>
    </ul>
    <div class="nav-actions">
        <button class="theme-btn" onclick="toggleTheme()"><span id="themeIcon">🌙</span></button>
        <div class="cart-wrap">
            <button class="cart-btn" onclick="toggleCart()">🛒</button>
            <span class="cart-badge" id="cartCount">0</span>
        </div>
    </div>
</nav>

<!-- CHI TIẾT SẢN PHẨM -->
<div class="detail-wrap">
    <div class="detail-grid">

        <!-- ẢNH -->
        <div class="detail-img">
            <?php if ($product['img']): ?>
                <img src="<?= htmlspecialchars($product['img']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            <?php else: ?>
                <div style="font-size:8rem;opacity:.4;">🍶</div>
            <?php endif; ?>
        </div>

        <!-- THÔNG TIN -->
        <div>
            <?php
            $badgeClass = ['hot'=>'badge-hot','new'=>'badge-new','limited'=>'badge-limited','sale'=>'badge-sale'];
            $bc = $badgeClass[$product['badge']] ?? 'badge-new';
            ?>
            <div class="card-badge <?= $bc ?>" style="border-radius:2px;">
                <?= htmlspecialchars($product['badgeText'] ?: $product['badge']) ?>
            </div>

            <div class="detail-origin"><?= htmlspecialchars($product['origin'] ?: '') ?></div>
            <h1 class="detail-name"><?= htmlspecialchars($product['name']) ?></h1>

            <!-- Specs -->
            <div class="detail-specs">
                <div class="spec-item">
                    <div class="spec-label">Độ Cồn</div>
                    <div class="spec-val"><?= $product['alc'] ?>°</div>
                </div>
                <div class="spec-item">
                    <div class="spec-label">Dung Tích</div>
                    <div class="spec-val"><?= $product['vol'] ?>ml</div>
                </div>
                <div class="spec-item">
                    <div class="spec-label">Danh Mục</div>
                    <div class="spec-val"><?= htmlspecialchars($product['cat'] ?: '—') ?></div>
                </div>
                <div class="spec-item">
                    <div class="spec-label">Hương Vị</div>
                    <div class="spec-val"><?= htmlspecialchars($product['flavor'] ?: '—') ?></div>
                </div>
            </div>

            <!-- Giá -->
            <div>
                <span class="detail-price">₫<?= number_format($product['price'], 0, ',', '.') ?></span>
                <?php if ($product['priceOld']): ?>
                    <span class="detail-price-old">₫<?= number_format($product['priceOld'], 0, ',', '.') ?></span>
                <?php endif; ?>
            </div>
            <div class="detail-stock">
                Tồn kho: <span><?= $product['stock'] > 0 ? $product['stock'] . ' chai' : 'Hết hàng' ?></span>
            </div>

            <!-- Mô tả -->
            <?php if ($product['desc']): ?>
                <p class="detail-desc"><?= nl2br(htmlspecialchars($product['desc'])) ?></p>
            <?php endif; ?>

            <!-- Thêm vào giỏ -->
            <div class="detail-actions">
                <div class="qty-input-wrap">
                    <button onclick="changeDetailQty(-1)">−</button>
                    <input type="number" id="detailQty" value="1" min="1" max="<?= $product['stock'] ?>">
                    <button onclick="changeDetailQty(1)">+</button>
                </div>
                <?php if ($product['stock'] > 0): ?>
                    <button class="btn-primary" onclick="addDetailToCart()"><span>🛒 Thêm Vào Giỏ</span></button>
                    <button class="btn-outline"  onclick="buyNow()">Mua Ngay →</button>
                <?php else: ?>
                    <button class="btn-primary" disabled style="opacity:.5;cursor:not-allowed;">Hết Hàng</button>
                <?php endif; ?>
            </div>

            <hr class="divider">
            <div style="display:flex;gap:24px;font-size:.78rem;color:var(--t3);">
                <span>🚚 Giao hàng toàn quốc</span>
                <span>🔄 Đổi trả trong 7 ngày</span>
                <span>✅ Cam kết chính hãng</span>
            </div>
        </div>
    </div>
</div>

<!-- GỢI Ý SẢN PHẨM -->
<section class="suggestions-sec">
    <div>
        <div class="section-label" style="margin-bottom:14px;">Có Thể Bạn Thích</div>
        <h2 class="section-title" style="margin-bottom:32px;">Sản phẩm <em>tương tự</em></h2>
        <div class="products-grid" id="suggestionsGrid">
            <p style="color:var(--t3);grid-column:1/-1;">Đang tải gợi ý...</p>
        </div>
    </div>
</section>

<!-- MODALS CHECKOUT (giống index.php) -->
<div class="modal-overlay" id="checkoutOnlineModal">
    <div class="modal" style="width:520px;">
        <div class="modal-head">
            <h3>💳 Thanh Toán Online</h3>
            <button class="modal-close-btn" onclick="closeModal('checkoutOnlineModal')">✕</button>
        </div>
        <div class="modal-body">
            <div id="onlineSummary"></div>
            <div class="form-field">
                <label>Phương Thức</label>
                <div class="payment-grid">
                    <div class="pm-card" id="pm-momo"    onclick="selectPayment('momo')">   <div class="pm-icon">💜</div><div class="pm-name">MoMo</div></div>
                    <div class="pm-card" id="pm-vnpay"   onclick="selectPayment('vnpay')">  <div class="pm-icon">🔵</div><div class="pm-name">VNPay</div></div>
                    <div class="pm-card" id="pm-banking" onclick="selectPayment('banking')"><div class="pm-icon">🏦</div><div class="pm-name">Chuyển khoản</div></div>
                    <div class="pm-card" id="pm-card"    onclick="selectPayment('card')">   <div class="pm-icon">💳</div><div class="pm-name">Thẻ Visa/Master</div></div>
                </div>
                <div class="banking-info" id="bankingInfo" style="display:none;">
                    🏦 Ngân hàng: <strong>Vietcombank</strong><br>
                    💳 Số TK: <strong style="color:var(--gold)">1234 5678 9012</strong><br>
                    👤 Chủ TK: <strong>CÔNG TY TÂY LƯƠNG CỬU</strong>
                </div>
            </div>
            <div class="form-row2">
                <div class="form-field"><label>Họ Tên *</label><input class="form-control" id="onName" placeholder="Nguyễn Văn A"></div>
                <div class="form-field"><label>Điện Thoại *</label><input class="form-control" id="onPhone" placeholder="0901 234 567"></div>
            </div>
            <div class="form-field"><label>Địa Chỉ *</label><input class="form-control" id="onAddress" placeholder="Địa chỉ giao hàng"></div>
            <div class="form-field"><label>Ghi Chú</label><textarea class="form-control" id="onNote" rows="2"></textarea></div>
        </div>
        <div class="modal-foot">
            <button class="btn-outline" style="padding:10px 20px;font-size:.7rem;" onclick="closeModal('checkoutOnlineModal')">Quay lại</button>
            <button class="btn-primary" style="padding:11px 24px;font-size:.7rem;" onclick="confirmOnlinePayment()">🚀 Xác Nhận</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="checkoutCodModal">
    <div class="modal" style="width:500px;">
        <div class="modal-head">
            <h3>🤝 Đặt Hàng COD</h3>
            <button class="modal-close-btn" onclick="closeModal('checkoutCodModal')">✕</button>
        </div>
        <div class="modal-body">
            <div id="codSummary"></div>
            <div class="form-row2">
                <div class="form-field"><label>Họ Tên *</label><input class="form-control" id="codName" placeholder="Nguyễn Văn A"></div>
                <div class="form-field"><label>Điện Thoại *</label><input class="form-control" id="codPhone" placeholder="0901 234 567"></div>
            </div>
            <div class="form-field"><label>Địa Chỉ *</label><input class="form-control" id="codAddress" placeholder="Địa chỉ giao hàng"></div>
            <div class="form-row2">
                <div class="form-field"><label>Sản Phẩm *</label>
                    <select class="form-control" id="codProduct" onchange="updateCodTotal()"></select>
                </div>
                <div class="form-field"><label>Số Lượng *</label>
                    <input class="form-control" id="codQty" type="number" min="1" value="1" oninput="updateCodTotal()">
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;padding:12px 14px;background:rgba(201,151,58,.06);border:1px solid var(--border);margin-bottom:14px;">
                <span style="font-size:.68rem;letter-spacing:.15em;text-transform:uppercase;color:var(--t3);">Tổng tiền</span>
                <span style="font-family:'Playfair Display',serif;font-size:1.2rem;color:var(--gold);" id="codTotalDisplay">₫0</span>
            </div>
            <div class="form-field"><label>Ghi Chú</label><textarea class="form-control" id="codNote" rows="2"></textarea></div>
        </div>
        <div class="modal-foot">
            <button class="btn-outline" style="padding:10px 20px;font-size:.7rem;" onclick="closeModal('checkoutCodModal')">Quay lại</button>
            <button class="btn-primary" style="padding:11px 24px;font-size:.7rem;background:#3D8A4E;" onclick="confirmCodOrder()">✅ Xác Nhận</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="successModal">
    <div class="modal" style="width:420px;text-align:center;">
        <div class="modal-body" style="padding:48px 32px;">
            <div style="font-size:3rem;margin-bottom:16px;">🎉</div>
            <h3 style="font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:400;color:var(--gold);margin-bottom:12px;">Đặt Hàng Thành Công!</h3>
            <p id="successMsg" style="font-size:.95rem;color:var(--t2);line-height:1.9;margin-bottom:8px;"></p>
            <p id="successOrderCode" style="font-size:.82rem;color:var(--gold);letter-spacing:.15em;margin-bottom:32px;"></p>
            <button class="btn-primary" onclick="closeModal('successModal');window.location.href='index.php'"><span>Về Trang Chủ</span></button>
        </div>
    </div>
</div>

<script>
var productsList   = <?= json_encode($allProds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
var currentProduct = <?= json_encode($product,  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function changeDetailQty(d) {
    const input = document.getElementById('detailQty');
    const max   = currentProduct.stock;
    let val = +input.value + d;
    if (val < 1)   val = 1;
    if (val > max) val = max;
    input.value = val;
}

function addDetailToCart() {
    const qty = +document.getElementById('detailQty').value || 1;
    for (let i = 0; i < qty; i++) addToCart(currentProduct.id);
}

function buyNow() {
    addDetailToCart();
    toggleCart();
}
</script>
<script src="assets/js/app.js"></script>
<script>
// Load gợi ý sau khi app.js đã sẵn sàng
document.addEventListener('DOMContentLoaded', () => {
    loadSuggestions(currentProduct.id, 'suggestionsGrid');
});
</script>
</body>
</html>