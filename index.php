<?php
// FILE: index.php — Trang chủ bán hàng
require_once __DIR__ . '/config/database.php';

$conn    = getDB();
$result  = mysqli_query($conn, "SELECT * FROM products WHERE is_active = 1 ORDER BY display_order ASC, id ASC ");
$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $products[] = formatProduct($row);
}
$footerResult   = mysqli_query($conn, "SELECT id, name FROM products WHERE is_active = 1 ORDER BY display_order ASC, id ASC LIMIT 4");
$footerProducts = [];
while ($row = mysqli_fetch_assoc($footerResult)) {
    $footerProducts[] = $row;
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tây Lương Cửu — Rượu Truyền Thống Cao Cấp</title>
    <meta name="description" content="Rượu truyền thống Việt Nam cao cấp — Nếp Cẩm, Thuốc Bắc, Ngô Men Lá Hà Giang">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Cormorant+Garamond:wght@300;400;500&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- AGE GATE -->
<div class="age-gate" id="ageGate">
    <div class="age-box">
        <div style="margin-bottom:16px;"><img src="assets/uploads/logo.png" alt="Logo" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid #C9A96E;"></div>
        <h2>Xác Nhận Độ Tuổi</h2>
        <p>Trang web chứa nội dung về rượu.<br>Bạn có đủ <strong>18 tuổi</strong> trở lên không?</p>
        <div class="age-btns">
            <button class="age-yes" onclick="closeAgeGate()">Có, tôi đủ 18 tuổi</button>
            <button class="age-no"  onclick="alert('Bạn chưa đủ tuổi truy cập trang này.')">Không</button>
        </div>
    </div>
</div>

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
        <div class="cart-empty">Giỏ hàng trống<br><small>Hãy chọn những chai rượu ưa thích ✦</small></div>
    </div>
    <div class="cart-foot">
        <div class="cart-total-row">
            <span class="cart-total-label">Tổng cộng</span>
            <span class="cart-total-val" id="cartTotal">₫0</span>
        </div>
        <div class="cart-checkout-btns">
            <button class="btn-primary" style="font-size:.6rem;padding:11px 8px;" onclick="openCheckout('online')">Thanh Toán Online</button>
            <button class="btn-outline"  style="font-size:.6rem;padding:10px 8px;" onclick="openCheckout('cod')">Trả Sau (COD)</button>
        </div>
        <button class="btn-outline" style="width:100%;margin-top:8px;font-size:.6rem;padding:9px;" onclick="toggleCart()">Tiếp Tục Mua Sắm</button>
    </div>
</div>

<!-- NAVBAR -->
<nav id="navbar">
    <div class="nav-logo" onclick="scrollToSection('heroSection')">
        <img src="assets/uploads/logo.png" alt="Tây Lương Cửu" style="width:42px;height:42px;border-radius:50%;object-fit:cover;border:2px solid #C9A96E;">
        <div>
            <div class="logo-text">Tây Lương <span>Cửu</span></div>
            <div class="logo-sub">Rượu Truyền Thống Cao Cấp</div>
        </div>
    </div>
    <ul class="nav-links">
        <li><a onclick="scrollToSection('heroSection')">Trang Chủ</a></li>
        <li><a onclick="scrollToSection('productsSection')">Sản Phẩm</a></li>
        <li><a onclick="scrollToSection('aiSection')">Tư Vấn</a></li>
        <li><a onclick="scrollToSection('testimonialsSection')">Đánh Giá</a></li>
    </ul>
    <div class="nav-actions">
        <button class="admin-btn" onclick="window.location.href='admin/login.php'">Quản Trị</button>
        <div id="customerNavBtn">
            <button class="admin-btn" style="background:rgba(201,151,58,.15);border-color:var(--gold);" onclick="openModal('authModal')" id="btnLoginNav">Đăng Nhập</button>
            <button class="admin-btn" style="background:rgba(201,151,58,.15);border-color:var(--gold);display:none;" onclick="openModal('myAccountModal')" id="btnAccountNav"><span id="navCustomerName"></span></button>
        </div>
        <div class="cart-wrap">
            <button class="cart-btn" onclick="toggleCart()">&#128722;</button>
            <span class="cart-badge" id="cartCount">0</span>
        </div>
    </div>
</nav>

<!-- HERO -->
<section class="hero-sec" id="heroSection">
    <div class="hero-grid"></div>
    <div class="hero-inner">
        <div>
            <div class="hero-label">Tinh Hoa Việt Nam · Từ 1995</div>
            <h1 class="hero-title">
                Rượu truyền thống<br>
                <em>đỉnh cao nghệ thuật</em>
            </h1>
            <p class="hero-desc">
                Những chai rượu được chưng cất bằng phương pháp cổ truyền,
                lưu giữ hồn của đất và người Việt qua từng giọt tinh tế.
            </p>
            <div class="hero-actions">
                <button class="btn-primary" onclick="scrollToSection('productsSection')"><span>Khám phá ngay</span></button>
                <button class="btn-outline" onclick="scrollToSection('aiSection')">Tư vấn AI</button>
            </div>
            <div class="hero-stats">
                <div><div class="hero-stat-val">28+</div><div class="hero-stat-lbl">Năm kinh nghiệm</div></div>
                <div><div class="hero-stat-val"><?= count($products) ?>+</div><div class="hero-stat-lbl">Dòng sản phẩm</div></div>
                <div><div class="hero-stat-val">10k+</div><div class="hero-stat-lbl">Khách hài lòng</div></div>
            </div>
        </div>
        <div class="hero-visual">
            <img src="assets/uploads/logo.png" alt="Tây Lương Cửu"
                 style="width:min(380px,80%);height:auto;border-radius:24px;
                        box-shadow:0 30px 80px rgba(139,32,32,.5),0 0 0 2px rgba(201,151,58,.2),0 0 40px rgba(201,151,58,.08);
                        opacity:.95;">
        </div>
    </div>
</section>

<!-- PRODUCTS -->
<section id="productsSection" style="padding:100px 48px;">
    <div style="max-width:1300px;margin:0 auto;">
        <div class="products-header reveal">
            <div>
                <div class="section-label">Đặc sản nổi tiếng</div>
                <h2 class="section-title">Các loại rượu <em>truyền thống</em></h2>
            </div>
            <a class="view-all-link" onclick="scrollToSection('productsSection')">Xem tất cả ›</a>
        </div>

        <!-- THANH FILTER -->
        <div class="filter-bar reveal" id="filterBar">
            <div class="filter-row filter-row-top">
                <div class="filter-search-wrap">
                    <input class="filter-search" id="filterSearch" placeholder="Tìm tên rượu..." oninput="applyFilters()">
                    <span class="filter-search-icon">&#9906;</span>
                </div>
                <div class="filter-sep"></div>
                <div class="filter-group">
                    <span class="filter-label">Danh mục</span>
                    <div class="filter-tags" id="filterCats">
                        <button class="filter-tag active" data-cat="" onclick="setCatFilter(this)">Tất cả</button>
                    </div>
                </div>
            </div>
            <div class="filter-row filter-row-bottom">
                <div class="filter-group">
                    <span class="filter-label">Giá</span>
                    <div class="filter-tags">
                        <button class="filter-tag active" data-price="" onclick="setPriceFilter(this)">Tất cả</button>
                        <button class="filter-tag" data-price="0-300000" onclick="setPriceFilter(this)">Dưới 300k</button>
                        <button class="filter-tag" data-price="300000-600000" onclick="setPriceFilter(this)">300–600k</button>
                        <button class="filter-tag" data-price="600000-1000000" onclick="setPriceFilter(this)">600k–1tr</button>
                        <button class="filter-tag" data-price="1000000-999999999" onclick="setPriceFilter(this)">Trên 1tr</button>
                    </div>
                </div>
                <div class="filter-sep"></div>
                <div class="filter-group">
                    <span class="filter-label">Độ cồn</span>
                    <div class="filter-tags">
                        <button class="filter-tag active" data-alc="" onclick="setAlcFilter(this)">Tất cả</button>
                        <button class="filter-tag" data-alc="0-20" onclick="setAlcFilter(this)">&lt;20°</button>
                        <button class="filter-tag" data-alc="20-35" onclick="setAlcFilter(this)">20–35°</button>
                        <button class="filter-tag" data-alc="35-100" onclick="setAlcFilter(this)">&gt;35°</button>
                    </div>
                </div>
                <button class="filter-reset" id="filterReset" onclick="resetFilters()" style="display:none;">✕ Xoá lọc</button>
            </div>
        </div>

        <div class="filter-result-count" id="filterCount" style="display:none;"></div>
        <div class="products-grid" id="productGrid"></div>
    </div>
</section>

<!-- AI TƯ VẤN -->
<section class="ai-section" id="aiSection">
    <div class="ai-center" style="max-width:1100px;">
        <div class="section-label reveal" style="justify-content:center;">Tư Vấn Thông Minh</div>
        <h2 class="section-title reveal" style="margin-bottom:12px;">Tìm rượu <em>phù hợp với bạn</em></h2>
        <p class="reveal" style="font-size:1rem;color:var(--t2);line-height:1.9;margin-bottom:32px;">
            Trợ lý AI sẽ gợi ý sản phẩm hoàn hảo dựa trên dịp và sở thích của bạn.
        </p>
        <div class="ai-chatbox reveal">
            <div class="ai-chatbox-head">
                <div class="ai-avatar">✦</div>
                <div>
                    <div class="ai-name">Trợ Lý Tây Lương Cửu</div>
                    <div class="ai-role">AI Sommelier</div>
                </div>
                <div class="ai-online"></div>
                <button onclick="resetAiChat()" title="Bắt đầu cuộc hội thoại mới"
                    style="margin-left:auto;background:none;border:1px solid var(--border);
                           color:var(--t3);border-radius:6px;padding:5px 12px;cursor:pointer;
                           font-size:.72rem;letter-spacing:.05em;transition:all .2s;"
                    onmouseover="this.style.borderColor='var(--gold)';this.style.color='var(--gold)'"
                    onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--t3)'">
                    🔄 Chat mới
                </button>
            </div>
            <div class="ai-messages" id="aiMessages">
                <div class="ai-msg-bot">
                    <div class="ai-avatar" style="flex-shrink:0;">✦</div>
                    <div class="ai-bubble">
                        Xin chào! Tôi là trợ lý của Tây Lương Cửu.<br>
                        Cho tôi biết dịp gì, tặng ai — tôi sẽ gợi ý ngay!
                    </div>
                </div>
            </div>
            <div class="ai-tags" id="aiTags">
                <span class="ai-tag" onclick="sendAiMessage(this.textContent)">Quà tặng</span>
                <span class="ai-tag" onclick="sendAiMessage(this.textContent)">Bổ sức khoẻ</span>
                <span class="ai-tag" onclick="sendAiMessage(this.textContent)">Tiệc liên hoan</span>
                <span class="ai-tag" onclick="sendAiMessage(this.textContent)">Dưới 700k</span>
                <span class="ai-tag" onclick="sendAiMessage(this.textContent)">Ngâm rượu mơ</span>
                <span class="ai-tag" onclick="sendAiMessage(this.textContent)">Pha cocktail</span>
            </div>
            <div class="ai-input-row">
                <input class="ai-input" id="aiInput" placeholder="Nhập câu hỏi..."
                       onkeydown="if(event.key==='Enter') sendAiMessage()">
                <button class="ai-send" onclick="sendAiMessage()">Gửi ✦</button>
            </div>
        </div>
    </div>
</section>

<!-- TESTIMONIALS -->
<section class="testimonials-sec" id="testimonialsSection">
    <div style="max-width:1300px;margin:0 auto;">
        <div class="reveal" style="text-align:center;margin-bottom:52px;">
            <div class="section-label" style="justify-content:center;">Khách Hàng Nói Gì</div>
            <h2 class="section-title">Hơn <em>10,000 khách hàng</em> tin tưởng</h2>
        </div>
        <div class="testimonials-grid reveal">
            <div class="testimonial-card">
                <div class="stars">★★★★★</div>
                <p class="review-text">"Rượu nếp cẩm thơm ngon tuyệt vời. Tặng bố trong ngày sinh nhật, ông rất thích!"</p>
                <div class="reviewer-name">Nguyễn Minh Tuấn</div>
                <div class="reviewer-loc">Hà Nội</div>
            </div>
            <div class="testimonial-card">
                <div class="stars">★★★★★</div>
                <p class="review-text">"Rượu thuốc bắc 18 vị uống rất bổ. Giao hàng nhanh, đóng gói cẩn thận."</p>
                <div class="reviewer-name">Trần Thị Lan</div>
                <div class="reviewer-loc">TP. Hồ Chí Minh</div>
            </div>
            <div class="testimonial-card">
                <div class="stars">★★★★★</div>
                <p class="review-text">"Ngô men lá Hà Giang rất đặc biệt, đúng chất rượu vùng cao! Sẽ mua thêm."</p>
                <div class="reviewer-name">Lê Văn Hùng</div>
                <div class="reviewer-loc">Hà Giang</div>
            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <div class="footer-grid">
        <div>
            <div class="footer-brand">Tây Lương <span>Cửu</span></div>
            <p class="footer-desc">Tinh hoa rượu Việt — chưng cất bằng tâm huyết và bí quyết gia truyền hơn 28 năm.</p>
        </div>
        <div>
            <div class="footer-col-title">Sản Phẩm</div>
           <ul class="footer-links">
    <?php foreach ($footerProducts as $p): ?>
    <li><a onclick="scrollToSection('productsSection')"><?= htmlspecialchars($p['name']) ?></a></li>
    <?php endforeach; ?>
    <li><a onclick="scrollToSection('productsSection')" 
           style="color:var(--gold);font-style:italic;">Xem tất cả →</a></li>
</ul>
        </div>
        <div>
            <div class="footer-col-title">Thông Tin</div>
            <ul class="footer-links">
                <li><a href="about.php">Về chúng tôi</a></li>
                <li><a href="policy.php">Chính sách đổi trả</a></li>
                <li><a href="guide.php">Hướng dẫn mua hàng</a></li>
            </ul>
        </div>
        <div>
            <div class="footer-col-title">Liên Hệ</div>
            <ul class="footer-links">
                <li><a>092 878 7046</a></li>
                <li><a>info@tayluongcuu.vn</a></li>
                <li><a>Hà Nội, Việt Nam</a></li>
                <li><a onclick="window.location.href='admin/login.php'">Quản Trị Viên</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <span>© <?= date('Y') ?> Tây Lương Cửu</span>
        <span>Chỉ dành cho người đủ 18 tuổi · Uống có trách nhiệm</span>
    </div>
</footer>

<!-- MODALS -->

<!-- PRODUCT DETAIL MODAL -->
<div class="modal-overlay" id="productDetailModal">
    <div class="modal" style="width:1200px;max-width:96vw;padding:0;overflow:hidden;position:relative;">
        <button onclick="closeModal('productDetailModal')"
            style="position:absolute;top:14px;right:14px;z-index:10;background:rgba(0,0,0,.5);
                   border:1px solid var(--border);color:var(--t2);width:32px;height:32px;
                   border-radius:50%;cursor:pointer;font-size:.9rem;display:flex;align-items:center;
                   justify-content:center;line-height:1;">✕</button>
        <div style="display:grid;grid-template-columns:1fr 1fr;">
            <div id="pdImg" style="background:var(--card2);display:flex;align-items:center;justify-content:center;
                                    padding:40px;min-height:500px;border-right:1px solid var(--border2);"></div>
            <div style="padding:40px 36px;overflow-y:auto;max-height:86vh;font-family:'Times New Roman',Times,serif;">
                <div id="pdBadge" style="margin-bottom:12px;"></div>
                <h2 id="pdName" style="font-family:'Times New Roman',Times,serif;font-size:2rem;font-weight:700;color:var(--ivory);line-height:1.3;margin-bottom:8px;"></h2>
                <div id="pdSub" style="font-family:'Times New Roman',Times,serif;font-size:1rem;color:var(--t3);margin-bottom:20px;letter-spacing:.05em;"></div>
                <div id="pdPrice" style="font-family:'Times New Roman',Times,serif;font-size:2.2rem;color:var(--gold);margin-bottom:12px;line-height:1.2;font-weight:700;"></div>
                <div id="pdStock" style="font-family:'Times New Roman',Times,serif;font-size:.95rem;margin-bottom:18px;"></div>
                <div style="padding:10px 14px;background:rgba(155,40,40,.1);border:1px solid rgba(155,40,40,.3);border-radius:6px;margin-bottom:18px;font-size:.88rem;font-family:'Times New Roman',Times,serif;color:#D08080;line-height:1.7;">
                    Bạn phải từ 18 tuổi trở lên mới được mua rượu tại Việt Nam. Uống có trách nhiệm.
                </div>
                <div id="pdDesc" style="font-family:'Times New Roman',Times,serif;font-size:1rem;color:var(--t2);line-height:2;margin-bottom:20px;padding-bottom:18px;border-bottom:1px solid var(--border2);"></div>
                <div style="font-family:'Times New Roman',Times,serif;font-size:.72rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:12px;">Thông Số Chi Tiết</div>
                <div id="pdSpecs" style="margin-bottom:24px;"></div>
                <button id="pdAddBtn" class="btn-primary" data-pid=""
                        style="width:100%;padding:16px;font-size:.88rem;letter-spacing:.12em;font-family:'Times New Roman',Times,serif;">
                    + Thêm vào giỏ hàng
                </button>
                <button class="btn-outline" style="width:100%;margin-top:10px;padding:13px;font-size:.85rem;font-family:'Times New Roman',Times,serif;"
                        onclick="closeModal('productDetailModal')">Tiếp tục xem</button>
            </div>
        </div>
        <div id="pdCompareSection" style="border-top:1px solid var(--border);padding:22px 32px;background:var(--card2);">
            <div style="font-size:.65rem;letter-spacing:.2em;text-transform:uppercase;font-family:'Times New Roman',Times,serif;color:var(--gold);margin-bottom:12px;">So Sánh Sản Phẩm</div>
            <div id="pdCompare"></div>
        </div>
    </div>
</div>

<!-- Checkout SePay -->
<div class="modal-overlay" id="checkoutOnlineModal">
    <div class="modal" style="width:480px;">
        <div class="modal-head">
            <h3 id="sePayModalTitle">Thanh Toán SePay</h3>
            <button class="modal-close-btn" onclick="closeModal('checkoutOnlineModal')">✕</button>
        </div>
        <div id="sePayStep1">
            <div class="modal-body">
                <div id="onlineSummary"></div>
                <div class="form-row2">
                    <div class="form-field"><label>Họ Tên *</label><input class="form-control" id="onName" placeholder="Nguyễn Văn A"></div>
                    <div class="form-field"><label>Điện Thoại *</label><input class="form-control" id="onPhone" placeholder="0901 234 567"></div>
                </div>
                <div class="form-field">
                    <label>Địa Chỉ *</label>
                    <div class="goong-wrap">
                        <input class="form-control" id="onAddress" placeholder="Số nhà, đường, quận, tỉnh/TP" autocomplete="off" oninput="goongSuggest('onAddress','goongDropOn')">
                        <div class="goong-drop" id="goongDropOn"></div>
                    </div>
                </div>
                <div class="form-field"><label>Ghi Chú</label><textarea class="form-control" id="onNote" rows="2" placeholder="Yêu cầu đặc biệt..."></textarea></div>
            </div>
            <div class="modal-foot">
                <button class="btn-outline" style="padding:10px 20px;font-size:.7rem;" onclick="closeModal('checkoutOnlineModal')">Quay lại</button>
                <button class="btn-primary" style="padding:11px 24px;font-size:.7rem;" id="btnShowQR" onclick="createSePayOrder()">Tiếp tục → Hiện QR</button>
            </div>
        </div>
        <div id="sePayStep2" style="display:none;">
            <div class="modal-body" style="text-align:center;">
                <div style="font-size:.65rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:16px;">Quét mã QR để thanh toán</div>
                <div style="background:#fff;display:inline-block;padding:12px;border-radius:8px;margin-bottom:16px;">
                    <img id="sePayQR" src="" alt="QR SePay" style="width:220px;height:220px;display:block;">
                </div>
                <div style="background:rgba(255,255,255,.04);border:1px solid var(--border2);padding:14px;text-align:left;font-size:.82rem;line-height:2;margin-bottom:16px;">
                    <div>Ngân hàng: <strong id="qrBank" style="color:var(--t1)"></strong></div>
                    <div>Số TK: <strong id="qrAccNo" style="color:var(--gold)"></strong></div>
                    <div>Chủ TK: <strong id="qrAccName" style="color:var(--t1)"></strong></div>
                    <div>Số tiền: <strong id="qrAmount" style="color:var(--gold)"></strong></div>
                    <div>Nội dung: <strong id="qrContent" style="color:var(--gold)"></strong></div>
                </div>
                <div id="sePayCountdown" style="display:none;text-align:center;font-family:'Playfair Display',serif;font-size:.82rem;color:var(--t3);letter-spacing:.06em;margin-bottom:10px;">
                    Hết hạn sau: <span id="countdownTime" style="color:var(--gold);font-size:1rem;font-weight:600;">05:00</span>
                </div>
                <div id="sePayStatus" style="padding:10px;border-radius:6px;font-size:.82rem;background:rgba(212,168,75,.08);border:1px solid var(--border2);color:var(--t2);">Đang chờ thanh toán...</div>
            </div>
            <div class="modal-foot" style="justify-content:center;">
                <button class="btn-outline" style="padding:10px 20px;font-size:.7rem;" onclick="cancelSePayOrder()">Hủy</button>
            </div>
        </div>
    </div>
</div>

<!-- Checkout COD -->
<div class="modal-overlay" id="checkoutCodModal">
    <div class="modal" style="width:500px;">
        <div class="modal-head">
            <h3>Đặt Hàng Trả Sau (COD)</h3>
            <button class="modal-close-btn" onclick="closeModal('checkoutCodModal')">✕</button>
        </div>
        <div class="modal-body">
            <div id="codSummary"></div>
            <div class="form-row2">
                <div class="form-field"><label>Họ Tên *</label><input class="form-control" id="codName" placeholder="Nguyễn Văn A"></div>
                <div class="form-field"><label>Điện Thoại *</label><input class="form-control" id="codPhone" placeholder="0901 234 567"></div>
            </div>
            <div class="form-field">
                <label>Địa Chỉ *</label>
                <div class="goong-wrap">
                    <input class="form-control" id="codAddress" placeholder="Số nhà, đường, quận, tỉnh/TP" autocomplete="off" oninput="goongSuggest('codAddress','goongDropCod')">
                    <div class="goong-drop" id="goongDropCod"></div>
                </div>
            </div>
            <div class="form-row2">
                <div class="form-field">
                    <label>Loại Rượu *</label>
                    <select class="form-control" id="codProduct" onchange="updateCodTotal()">
                        <option value="">-- Chọn sản phẩm --</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Số Lượng *</label>
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
            <button class="btn-primary" style="padding:11px 24px;font-size:.7rem;background:#3D8A4E;" onclick="confirmCodOrder()">Xác Nhận Đặt Hàng</button>
        </div>
    </div>
</div>

<!-- AUTH MODAL -->
<div class="modal-overlay" id="authModal">
    <div class="modal" style="width:420px;">
        <div class="modal-head">
            <h3 id="authModalTitle">Đăng Nhập</h3>
            <button class="modal-close-btn" onclick="closeModal('authModal')">✕</button>
        </div>
        <div class="modal-body">
            <button onclick="loginWithGoogle()"
                style="width:100%;display:flex;align-items:center;justify-content:center;gap:10px;
                       padding:11px 16px;border:1px solid #dadce0;border-radius:6px;background:#fff;
                       cursor:pointer;font-size:.85rem;color:#3c4043;font-weight:500;margin-bottom:16px;transition:box-shadow .2s;"
                onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,.15)'"
                onmouseout="this.style.boxShadow='none'">
                <svg width="18" height="18" viewBox="0 0 48 48">
                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                </svg>
                Đăng nhập bằng Google
            </button>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                <div style="flex:1;height:1px;background:var(--border);"></div>
                <span style="font-size:.7rem;color:var(--t3);letter-spacing:.1em;">HOẶC</span>
                <div style="flex:1;height:1px;background:var(--border);"></div>
            </div>
            <div style="display:flex;gap:8px;margin-bottom:20px;">
                <button id="tabLogin" onclick="switchAuthTab('login')" style="flex:1;padding:8px;background:var(--gold);color:#1A0A00;border:none;cursor:pointer;font-size:.75rem;letter-spacing:.1em;">ĐĂNG NHẬP</button>
                <button id="tabRegister" onclick="switchAuthTab('register')" style="flex:1;padding:8px;background:transparent;color:var(--t2);border:1px solid var(--border);cursor:pointer;font-size:.75rem;letter-spacing:.1em;">ĐĂNG KÝ</button>
            </div>
            <div id="formLogin">
                <div class="form-field"><label>Email</label><input class="form-control" id="loginEmail" type="email" placeholder="email@example.com"></div>
                <div class="form-field"><label>Mật Khẩu</label><input class="form-control" id="loginPass" type="password" placeholder="••••••"></div>
                <div style="text-align:right;margin:-8px 0 12px;"><a onclick="switchAuthTab('forgot')" style="font-size:.75rem;color:var(--gold);cursor:pointer;">Quên mật khẩu?</a></div>
                <div id="loginError" style="color:#e07070;font-size:.8rem;margin-bottom:10px;display:none;"></div>
                <button class="btn-primary" style="width:100%;" onclick="doLogin()">Đăng Nhập</button>
            </div>
            <div id="formRegister" style="display:none;">
                <div class="form-row2">
                    <div class="form-field"><label>Họ Tên *</label><input class="form-control" id="regName" placeholder="Nguyễn Văn A"></div>
                    <div class="form-field"><label>Số Điện Thoại</label><input class="form-control" id="regPhone" placeholder="0901 234 567"></div>
                </div>
                <div class="form-field"><label>Email *</label><input class="form-control" id="regEmail" type="email" placeholder="email@example.com"></div>
                <div class="form-field"><label>Mật Khẩu *</label><input class="form-control" id="regPass" type="password" placeholder="Tối thiểu 6 ký tự"></div>
                <div id="regError" style="color:#e07070;font-size:.8rem;margin-bottom:10px;display:none;"></div>
                <button class="btn-primary" style="width:100%;" onclick="doRegister()">Tạo Tài Khoản</button>
            </div>
            <div id="formForgot" style="display:none;">
                <p style="font-size:.82rem;color:var(--t2);margin-bottom:14px;">Nhập email để nhận mã đặt lại mật khẩu.</p>
                <div class="form-field"><label>Email</label><input class="form-control" id="forgotEmail" type="email" placeholder="email@example.com"></div>
                <div id="forgotMsg" style="font-size:.8rem;margin-bottom:10px;display:none;"></div>
                <button class="btn-primary" style="width:100%;" onclick="doForgot()">Gửi Mã</button>
                <div id="resetForm" style="display:none;margin-top:16px;">
                    <div class="form-field"><label>Mã 6 số</label><input class="form-control" id="resetToken" placeholder="123456" maxlength="6"></div>
                    <div class="form-field"><label>Mật Khẩu Mới</label><input class="form-control" id="resetPass" type="password" placeholder="Tối thiểu 6 ký tự"></div>
                    <button class="btn-primary" style="width:100%;margin-top:8px;" onclick="doReset()">Đặt Lại Mật Khẩu</button>
                </div>
                <div style="text-align:center;margin-top:12px;"><a onclick="switchAuthTab('login')" style="font-size:.75rem;color:var(--gold);cursor:pointer;">← Quay lại đăng nhập</a></div>
            </div>
        </div>
    </div>
</div>

<!-- MY ACCOUNT MODAL -->
<div class="modal-overlay" id="myAccountModal">
    <div class="modal" style="width:860px;">
        <div class="modal-head">
            <h3>Tài Khoản Của Tôi</h3>
            <button class="modal-close-btn" onclick="closeModal('myAccountModal')">✕</button>
        </div>
        <div class="modal-body">
            <div style="display:flex;gap:8px;margin-bottom:20px;">
                <button id="tabOrders" onclick="switchAccTab('orders')" style="flex:1;padding:8px;background:var(--gold);color:#1A0A00;border:none;cursor:pointer;font-size:.75rem;letter-spacing:.1em;">ĐƠN HÀNG</button>
                <button id="tabChgPass" onclick="switchAccTab('chgpass')" style="flex:1;padding:8px;background:transparent;color:var(--t2);border:1px solid var(--border);cursor:pointer;font-size:.75rem;letter-spacing:.1em;">ĐỔI MẬT KHẨU</button>
            </div>
            <div id="accOrders">
                <div id="myOrdersList" style="max-height:520px;overflow-y:auto;padding-right:4px;">
                    <div style="text-align:center;color:var(--t3);padding:20px;">Đang tải...</div>
                </div>
            </div>
            <div id="accChgPass" style="display:none;">
                <div class="form-field"><label>Mật Khẩu Cũ</label><input class="form-control" id="oldPass" type="password" placeholder="••••••"></div>
                <div class="form-field"><label>Mật Khẩu Mới</label><input class="form-control" id="newPass" type="password" placeholder="Tối thiểu 6 ký tự"></div>
                <div class="form-field"><label>Xác Nhận Mật Khẩu Mới</label><input class="form-control" id="newPass2" type="password" placeholder="Nhập lại"></div>
                <div id="chgPassMsg" style="font-size:.8rem;margin-bottom:10px;display:none;"></div>
                <button class="btn-primary" style="width:100%;" onclick="doChangePassword()">Đổi Mật Khẩu</button>
            </div>
        </div>
        <div class="modal-foot" style="justify-content:space-between;">
            <span id="accEmailDisplay" style="font-size:.78rem;color:var(--t3);"></span>
            <button class="btn-outline" style="padding:8px 18px;font-size:.7rem;color:#e07070;border-color:#e07070;" onclick="doLogout()">Đăng Xuất</button>
        </div>
    </div>
</div>

<!-- THANH SO SÁNH NỔI -->
<div id="compareBar" style="position:fixed;bottom:0;left:0;right:0;z-index:999;
     background:var(--card);border-top:1px solid var(--border);
     padding:12px 24px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;
     box-shadow:0 -4px 24px rgba(0,0,0,.4);
     transform:translateY(100%);opacity:0;transition:transform .3s ease,opacity .3s ease;">
    <div style="font-size:.65rem;letter-spacing:.15em;text-transform:uppercase;color:var(--gold);white-space:nowrap;">So sánh (<span id="compareBarCount">0</span>)</div>
    <div id="compareBarItems" style="display:flex;gap:8px;flex-wrap:wrap;flex:1;"></div>
    <div style="display:flex;gap:8px;flex-shrink:0;">
        <button onclick="openCompareModal()" style="padding:9px 20px;background:var(--gold);color:#1A0A00;border:none;border-radius:4px;cursor:pointer;font-size:.75rem;letter-spacing:.1em;font-weight:600;">Xem So Sánh →</button>
        <button onclick="clearCompare()" style="padding:9px 14px;background:transparent;color:var(--t3);border:1px solid var(--border);border-radius:4px;cursor:pointer;font-size:.75rem;">Xoá tất cả</button>
    </div>
</div>

<!-- MODAL SO SÁNH -->
<div class="modal-overlay" id="compareModal">
    <div class="modal" style="width:960px;max-width:96vw;padding:0;overflow:hidden;">
        <div class="modal-head" style="padding:16px 24px;">
            <h3 style="font-size:1rem;letter-spacing:.1em;">SO SÁNH SẢN PHẨM</h3>
            <button class="modal-close-btn" onclick="closeModal('compareModal')">✕</button>
        </div>
        <div id="cmpModalBody" style="padding:0 24px 24px;overflow-x:auto;max-height:80vh;overflow-y:auto;"></div>
    </div>
</div>

<!-- Success -->
<div class="modal-overlay" id="successModal">
    <div class="modal" style="width:420px;text-align:center;">
        <div class="modal-body" style="padding:48px 32px;">
            <div style="font-size:3rem;margin-bottom:16px;">&#127881;</div>
            <h3 style="font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:400;color:var(--gold);margin-bottom:12px;">Đặt Hàng Thành Công!</h3>
            <p id="successMsg" style="font-size:.95rem;color:var(--t2);line-height:1.9;margin-bottom:8px;"></p>
            <p id="successOrderCode" style="font-size:.82rem;color:var(--gold);letter-spacing:.15em;margin-bottom:32px;"></p>
            <button class="btn-primary" onclick="closeModal('successModal')"><span>Tiếp tục mua sắm</span></button>
        </div>
    </div>
</div>

<!-- Inject data từ PHP -->
<script>
    var productsList = <?= json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<!-- Firebase SDK -->
<script type="module">
import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.0/firebase-app.js";
import { getAuth, GoogleAuthProvider, signInWithPopup, signOut, onAuthStateChanged }
    from "https://www.gstatic.com/firebasejs/11.6.0/firebase-auth.js";

const firebaseConfig = {
    apiKey: "AIzaSyDQii8PSCfJsB-WMJgkmBb2929st-oSlis",
    authDomain: "tayluongcuu.firebaseapp.com",
    projectId: "tayluongcuu",
    storageBucket: "tayluongcuu.firebasestorage.app",
    messagingSenderId: "608129663962",
    appId: "1:608129663962:web:f7a7ec09f974e4e4c3b6e3"
};

const app      = initializeApp(firebaseConfig);
const auth     = getAuth(app);
const provider = new GoogleAuthProvider();

window.loginWithGoogle = async function() {
    try {
        const result = await signInWithPopup(auth, provider);
        await syncFirebaseUser(result.user);
    } catch(e) {
        if (e.code !== 'auth/popup-closed-by-user')
            showToast('Lỗi đăng nhập Google: ' + e.message);
    }
};

window.logoutFirebase = async function() { await signOut(auth); };

async function syncFirebaseUser(user) {
    try {
        const token = await user.getIdToken();
        const res = await fetch(BASE + 'api/firebase_auth.php', {
            method: 'POST', credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ uid: user.uid, email: user.email, name: user.displayName, photo: user.photoURL, token })
        });
        const data = await res.json();
        if (data.id) { saveCustomer(data); closeModal('authModal'); showToast('Chào mừng ' + data.name + '!'); }
    } catch(e) { showToast('Lỗi đồng bộ tài khoản'); }
}

onAuthStateChanged(auth, user => {
    if (!user) { const saved = loadCustomer(); if (saved && saved.isGoogle) clearCustomer(); }
});

const _origLogout = window.doLogout;
window.doLogout = async function() {
    const c = loadCustomer();
    if (c && c.isGoogle) await signOut(auth);
    if (_origLogout) await _origLogout();
    else { clearCustomer(); closeModal('myAccountModal'); showToast('Đã đăng xuất'); }
};
</script>

<script src="assets/js/app.js"></script>
<script src="assets/js/filter.js"></script>

</body>
</html>