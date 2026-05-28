<?php
// ============================================================
// FILE: cart.php — Trang giỏ hàng + Thanh toán
// ============================================================
require_once __DIR__ . '/config/database.php';

// Lấy sản phẩm để dùng cho dropdown COD
$conn    = getDB();
$res     = mysqli_query($conn, "SELECT * FROM products WHERE is_active = 1 AND stock > 0 ORDER BY id ASC");
$products = [];
while ($row = mysqli_fetch_assoc($res)) $products[] = formatProduct($row);
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ Hàng — Tây Lương Cửu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Cormorant+Garamond:wght@300;400;500&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .cart-page    { max-width:1100px; margin:0 auto; padding:120px 48px 80px; }
        .cart-page-title { font-family:'Playfair Display',serif; font-size:2rem; font-weight:400; color:var(--ivory); margin-bottom:8px; }
        .cart-page-sub   { font-size:.75rem; letter-spacing:.15em; text-transform:uppercase; color:var(--t3); margin-bottom:48px; }
        .cart-layout  { display:grid; grid-template-columns:1fr 360px; gap:32px; align-items:start; }
        .cart-list    { background:var(--card); border:1px solid var(--border); }
        .cart-list-head { padding:16px 20px; border-bottom:1px solid var(--border2); font-size:.6rem; letter-spacing:.2em; text-transform:uppercase; color:var(--gold); }
        .cart-list-empty { padding:60px; text-align:center; color:var(--t3); font-size:.95rem; line-height:2; }
        .cart-row     { display:grid; grid-template-columns:72px 1fr auto auto; gap:16px; align-items:center; padding:18px 20px; border-bottom:1px solid var(--border2); }
        .cart-row:last-child { border-bottom:none; }
        .ci-img       { width:72px; height:72px; background:var(--card2); display:flex; align-items:center; justify-content:center; font-size:1.5rem; overflow:hidden; }
        .ci-img img   { width:100%; height:100%; object-fit:cover; }
        .ci-name      { font-size:.9rem; color:var(--ivory); margin-bottom:3px; }
        .ci-sub       { font-size:.7rem; color:var(--t3); margin-bottom:8px; }
        .ci-unit      { font-size:.72rem; color:var(--t2); }
        .ci-qty       { display:flex; align-items:center; gap:4px; }
        .ci-qty button { width:28px; height:28px; background:rgba(201,151,58,.1); border:1px solid var(--border); color:var(--gold); font-size:.9rem; transition:background .2s; }
        .ci-qty button:hover { background:rgba(201,151,58,.25); }
        .ci-qty input  { width:40px; text-align:center; background:none; border:1px solid var(--border2); color:var(--ivory); font-size:.85rem; padding:4px 0; font-family:inherit; }
        .ci-price     { font-family:'Playfair Display',serif; font-size:1.05rem; color:var(--gold); text-align:right; min-width:110px; }
        .ci-del       { background:none; border:none; color:var(--t4); font-size:.85rem; cursor:pointer; padding:4px 6px; transition:color .2s; display:block; margin-top:4px; }
        .ci-del:hover { color:#D06060; }

        /* Summary box */
        .cart-summary  { background:var(--card); border:1px solid var(--border); padding:24px; position:sticky; top:90px; }
        .summary-title { font-family:'Playfair Display',serif; font-size:1.15rem; font-weight:400; color:var(--ivory); margin-bottom:24px; padding-bottom:14px; border-bottom:1px solid var(--border2); }
        .summary-row   { display:flex; justify-content:space-between; font-size:.82rem; color:var(--t2); margin-bottom:10px; }
        .summary-row.total { font-family:'Playfair Display',serif; font-size:1.2rem; color:var(--gold); border-top:1px solid var(--border2); padding-top:14px; margin-top:14px; margin-bottom:24px; font-weight:400; }
        .summary-note  { font-size:.72rem; color:var(--t3); margin-bottom:20px; line-height:1.7; padding:10px 12px; background:rgba(255,255,255,.02); border:1px solid var(--border2); }
        .btn-checkout  { width:100%; padding:13px; background:var(--gold); color:#000; border:none; font-size:.7rem; font-weight:700; letter-spacing:.2em; text-transform:uppercase; cursor:pointer; transition:background .25s; margin-bottom:10px; }
        .btn-checkout:hover    { background:var(--gold2); }
        .btn-checkout.cod      { background:#3D8A4E; color:#fff; margin-bottom:0; }
        .btn-checkout.cod:hover{ background:#4A9E5E; }
        .btn-back      { display:block; text-align:center; font-size:.7rem; color:var(--t3); margin-top:14px; cursor:pointer; transition:color .2s; }
        .btn-back:hover{ color:var(--gold); }

        /* Breadcrumb */
        .breadcrumb   { font-size:.72rem; color:var(--t3); margin-bottom:20px; }
        .breadcrumb a { color:var(--gold); cursor:pointer; }
        .breadcrumb a:hover { text-decoration:underline; }

        @media(max-width:900px){
            .cart-page   { padding:100px 20px 60px; }
            .cart-layout { grid-template-columns:1fr; }
            .cart-summary{ position:static; }
            .cart-row    { grid-template-columns:56px 1fr; }
            .ci-price, .ci-qty { justify-self:start; }
        }
    </style>
</head>
<body>

<!-- TOAST -->
<div class="toast" id="toast"><span id="toast-msg"></span></div>

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
        <li><a style="color:var(--gold)">Giỏ Hàng</a></li>
    </ul>
    <div class="nav-actions">
        <button class="theme-btn" onclick="toggleTheme()"><span id="themeIcon">🌙</span></button>
        <div class="cart-wrap">
            <button class="cart-btn" onclick="window.location.href='cart.php'">🛒</button>
            <span class="cart-badge" id="cartCount">0</span>
        </div>
    </div>
</nav>

<!-- CART PAGE -->
<div class="cart-page">
    <div class="breadcrumb">
        <a onclick="window.location.href='index.php'">Trang chủ</a> › Giỏ hàng
    </div>
    <h1 class="cart-page-title">Giỏ Hàng</h1>
    <div class="cart-page-sub">Của bạn (<span id="cartItemCount">0</span> sản phẩm)</div>

    <div class="cart-layout">

        <!-- DANH SÁCH SẢN PHẨM -->
        <div class="cart-list">
            <div class="cart-list-head">Sản Phẩm</div>
            <div id="cartPageItems">
                <div class="cart-list-empty">
                    🛒 Giỏ hàng đang trống<br>
                    <small><a onclick="window.location.href='index.php'" style="color:var(--gold);cursor:pointer;">← Quay lại mua sắm</a></small>
                </div>
            </div>
        </div>

        <!-- SUMMARY + CHECKOUT -->
        <div class="cart-summary">
            <div class="summary-title">Tóm Tắt Đơn Hàng</div>
            <div class="summary-row"><span>Số sản phẩm</span><span id="summaryCount">0</span></div>
            <div class="summary-row"><span>Tạm tính</span><span id="summarySubtotal">₫0</span></div>
            <div class="summary-row"><span>Phí vận chuyển</span><span style="color:#5CB870;">Miễn phí</span></div>
            <div class="summary-row total"><span>Tổng cộng</span><span id="summaryTotal">₫0</span></div>
            <div class="summary-note">
                🚚 Giao hàng toàn quốc · 3–5 ngày làm việc<br>
                🔄 Đổi trả trong 7 ngày nếu lỗi nhà sản xuất<br>
                ✅ Cam kết sản phẩm chính hãng 100%
            </div>
            <button class="btn-checkout"     id="btnOnline" onclick="openCheckout('online')">💳 Thanh Toán Online</button>
            <button class="btn-checkout cod" id="btnCod"    onclick="openCheckout('cod')">🤝 Trả Sau (COD)</button>
            <a class="btn-back" onclick="window.location.href='index.php'">← Tiếp tục mua sắm</a>
        </div>

    </div>
</div>

<!-- MODALS CHECKOUT -->
<div class="modal-overlay" id="checkoutOnlineModal">
    <div class="modal" style="width:520px;">
        <div class="modal-head">
            <h3>💳 Thanh Toán Online</h3>
            <button class="modal-close-btn" onclick="closeModal('checkoutOnlineModal')">✕</button>
        </div>
        <div class="modal-body">
            <div id="onlineSummary"></div>
            <div class="form-field">
                <label>Phương Thức Thanh Toán</label>
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
            <div class="form-field"><label>Địa Chỉ Giao Hàng *</label><input class="form-control" id="onAddress" placeholder="Số nhà, đường, quận, tỉnh/TP"></div>
            <div class="form-field"><label>Ghi Chú</label><textarea class="form-control" id="onNote" rows="2" placeholder="Yêu cầu đặc biệt..."></textarea></div>
        </div>
        <div class="modal-foot">
            <button class="btn-outline" style="padding:10px 20px;font-size:.7rem;" onclick="closeModal('checkoutOnlineModal')">Quay lại</button>
            <button class="btn-primary" style="padding:11px 24px;font-size:.7rem;" onclick="confirmOnlinePayment()">🚀 Xác Nhận Đặt Hàng</button>
        </div>
    </div>
</div>

<div class="modal-overlay" id="checkoutCodModal">
    <div class="modal" style="width:500px;">
        <div class="modal-head">
            <h3>🤝 Đặt Hàng Trả Sau (COD)</h3>
            <button class="modal-close-btn" onclick="closeModal('checkoutCodModal')">✕</button>
        </div>
        <div class="modal-body">
            <div style="background:rgba(61,138,78,.07);border:1px solid rgba(61,138,78,.2);padding:10px 14px;font-size:.78rem;color:var(--t2);margin-bottom:16px;">
                ✅ Bạn sẽ thanh toán <strong style="color:var(--t1)">khi nhận hàng</strong>. Không cần trả trước!
            </div>
            <div id="codSummary"></div>
            <div class="form-row2">
                <div class="form-field"><label>Họ Tên *</label><input class="form-control" id="codName" placeholder="Nguyễn Văn A"></div>
                <div class="form-field"><label>Điện Thoại *</label><input class="form-control" id="codPhone" placeholder="0901 234 567"></div>
            </div>
            <div class="form-field"><label>Địa Chỉ Giao Hàng *</label><input class="form-control" id="codAddress" placeholder="Số nhà, đường, quận, tỉnh/TP"></div>
            <div class="form-row2">
                <div class="form-field"><label>Loại Rượu *</label>
                    <select class="form-control" id="codProduct" onchange="updateCodTotal()">
                        <option value="">-- Chọn sản phẩm --</option>
                    </select>
                </div>
                <div class="form-field"><label>Số Lượng *</label>
                    <input class="form-control" id="codQty" type="number" min="1" value="1" oninput="updateCodTotal()">
                </div>
            </div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 14px;background:rgba(201,151,58,.06);border:1px solid var(--border);margin-bottom:14px;">
                <span style="font-size:.68rem;letter-spacing:.15em;text-transform:uppercase;color:var(--t3);">Tổng tiền COD</span>
                <span style="font-family:'Playfair Display',serif;font-size:1.2rem;color:var(--gold);" id="codTotalDisplay">₫0</span>
            </div>
            <div class="form-field"><label>Ghi Chú</label><textarea class="form-control" id="codNote" rows="2" placeholder="Yêu cầu đặc biệt..."></textarea></div>
        </div>
        <div class="modal-foot">
            <button class="btn-outline" style="padding:10px 20px;font-size:.7rem;" onclick="closeModal('checkoutCodModal')">Quay lại</button>
            <button class="btn-primary" style="padding:11px 24px;font-size:.7rem;background:#3D8A4E;" onclick="confirmCodOrder()">✅ Xác Nhận Đặt Hàng</button>
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
            <button class="btn-primary" onclick="window.location.href='index.php'"><span>← Về Trang Chủ</span></button>
        </div>
    </div>
</div>

<script>
var productsList = <?= json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="assets/js/app.js"></script>
<script>
// Override updateCartUI để render thêm cart page list
const _origUpdateCartUI = updateCartUI;
function updateCartUI() {
    _origUpdateCartUI();
    renderCartPage();
}

function renderCartPage() {
    const total = cart.reduce((s, i) => s + i.price * i.qty, 0);
    const count = cart.reduce((s, i) => s + i.qty, 0);

    document.getElementById('cartItemCount').textContent = cart.length;
    document.getElementById('summaryCount').textContent  = count;
    document.getElementById('summarySubtotal').textContent = '₫' + total.toLocaleString('vi-VN');
    document.getElementById('summaryTotal').textContent    = '₫' + total.toLocaleString('vi-VN');

    const el     = document.getElementById('cartPageItems');
    const btnOn  = document.getElementById('btnOnline');
    const btnCod = document.getElementById('btnCod');

    if (!cart.length) {
        el.innerHTML = `<div class="cart-list-empty">🛒 Giỏ hàng đang trống<br>
            <small><a onclick="window.location.href='index.php'" style="color:var(--gold);cursor:pointer;">← Quay lại mua sắm</a></small></div>`;
        if (btnOn)  btnOn.disabled  = true;
        if (btnCod) btnCod.disabled = true;
        return;
    }
    if (btnOn)  btnOn.disabled  = false;
    if (btnCod) btnCod.disabled = false;

    el.innerHTML = cart.map(item => `
        <div class="cart-row">
            <div class="ci-img">
                ${item.img ? `<img src="${item.img}" alt="${item.name}">` : '🍶'}
            </div>
            <div>
                <div class="ci-name">${item.name}</div>
                <div class="ci-sub">${item.alc}° · ${item.vol}ml · ${item.origin||''}</div>
                <div class="ci-unit">₫${Number(item.price).toLocaleString('vi-VN')}/chai</div>
            </div>
            <div class="ci-qty">
                <button onclick="changeQty(${item.id},-1)">−</button>
                <input type="number" value="${item.qty}" min="1"
                       onchange="setQty(${item.id}, +this.value)"
                       style="width:44px;text-align:center;background:none;border:1px solid var(--border2);color:var(--ivory);padding:4px 0;font-family:inherit;">
                <button onclick="changeQty(${item.id},1)">+</button>
                <button class="ci-del" onclick="removeFromCart(${item.id})">✕ Xóa</button>
            </div>
            <div class="ci-price">₫${Number(item.price * item.qty).toLocaleString('vi-VN')}</div>
        </div>`).join('');
}

function setQty(id, qty) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    const p = productsList.find(x => x.id === id);
    item.qty = Math.max(1, Math.min(qty, p ? p.stock : 999));
    updateCartUI();
}

document.addEventListener('DOMContentLoaded', renderCartPage);
</script>
</body>
</html>
