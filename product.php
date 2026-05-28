<?php
// FILE: product.php — Trang chi tiết sản phẩm
require_once __DIR__ . '/config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: index.php'); exit; }

$conn = getDB();
$stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE id = ? AND is_active = 1 LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$raw = mysqli_fetch_assoc($result);
if (!$raw) { header('Location: index.php'); exit; }
$p = formatProduct($raw);

$rel_stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE category = ? AND id != ? AND is_active = 1 ORDER BY RAND() LIMIT 4");
mysqli_stmt_bind_param($rel_stmt, 'si', $raw['category'], $id);
mysqli_stmt_execute($rel_stmt);
$rel_result = mysqli_stmt_get_result($rel_stmt);
$related = [];
while ($row = mysqli_fetch_assoc($rel_result)) $related[] = formatProduct($row);

$all_result = mysqli_query($conn, "SELECT * FROM products WHERE is_active = 1 ORDER BY id ASC");
$allProducts = [];
while ($row = mysqli_fetch_assoc($all_result)) $allProducts[] = formatProduct($row);
mysqli_close($conn);

// Taste Profile
$show_taste  = !empty($raw['show_taste']);
$taste_body  = (isset($raw['taste_body'])  && $raw['taste_body']  !== null) ? (int)$raw['taste_body']  : 5;
$taste_sweet = (isset($raw['taste_sweet']) && $raw['taste_sweet'] !== null) ? (int)$raw['taste_sweet'] : 4;
$body_pct    = round($taste_body  / 10 * 100);
$sweet_pct   = round($taste_sweet / 10 * 100);
$body_label  = $taste_body  >= 8 ? 'Full Bodied'   : ($taste_body  >= 5 ? 'Medium Bodied' : ($taste_body >= 3 ? 'Light-Medium' : 'Light Bodied'));
$sweet_label = $taste_sweet >= 8 ? 'Sweet'         : ($taste_sweet >= 5 ? 'Off-Dry'       : ($taste_sweet >= 3 ? 'Semi-Dry'    : 'Dry'));
$body_desc   = $taste_body  >= 8 ? 'Rượu đậm, kết cấu dày' : ($taste_body  >= 5 ? 'Vị cân bằng, mượt mà' : ($taste_body  >= 3 ? 'Khá nhẹ nhàng' : 'Rượu nhẹ, thanh thoát'));
$sweet_desc  = $taste_sweet >= 8 ? 'Vị ngọt dịu, dễ uống' : ($taste_sweet >= 5 ? 'Hơi ngọt, cân bằng'   : ($taste_sweet >= 3 ? 'Hơi khô'       : 'Không ngọt, vị khô'));

// Occasion Tags
$allOcc = ['gift'=>'🎁 Quà Tặng','health'=>'💪 Bổ Sức Khoẻ','dinner'=>'🥩 Ăn Tối','party'=>'🎉 Tiệc Tùng','romance'=>'🌹 Lãng Mạn','tet'=>'🏮 Lễ Tết'];
$occRaw = $raw['occasion'] ?? '';
$activeOcc = array_filter(array_map('trim', explode(',', $occRaw)));
if (empty($activeOcc)) $activeOcc = [];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($p['name']) ?> — Tây Lương Cửu</title>
    <meta name="description" content="<?= htmlspecialchars($p['desc'] ?? '') ?>">
    <!-- Fix age gate nhấp nháy -->
    <script>
        (function(){
            try {
                if(localStorage.getItem('tlc_age_verified')==='true'){
                    var s=document.createElement('style');
                    s.textContent='#ageGate{display:none!important}';
                    document.head.appendChild(s);
                }
            }catch(e){}
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Cormorant+Garamond:wght@300;400;500&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .pd-wrap { max-width:1200px; margin:0 auto; padding:110px 48px 80px; }
        .pd-breadcrumb { display:flex; align-items:center; gap:8px; font-size:.7rem; letter-spacing:.12em; text-transform:uppercase; color:var(--t3); margin-bottom:40px; }
        .pd-breadcrumb a { color:var(--t3); cursor:pointer; transition:color .2s; text-decoration:none; }
        .pd-breadcrumb a:hover { color:var(--gold); }
        .pd-breadcrumb span { color:var(--t4); }
        .pd-grid { display:grid; grid-template-columns:1fr 1fr; gap:64px; align-items:start; margin-bottom:80px; }
        .pd-gallery { position:sticky; top:100px; }
        .pd-main-img { width:100%; aspect-ratio:1; background:var(--card2); border:1px solid var(--border); border-radius:12px; overflow:hidden; display:flex; align-items:center; justify-content:center; cursor:zoom-in; position:relative; margin-bottom:16px; }
        .pd-main-img img { width:100%; height:100%; object-fit:contain; transition:transform .2s ease; transform-origin:center; }
        .pd-main-img:hover img { transform:scale(1.08); }
        .pd-img-no { font-size:4rem; color:var(--t4); }
        .pd-badge-wrap { position:absolute; top:16px; left:16px; }
        .pd-thumb-row { display:flex; gap:10px; }
        .pd-thumb { width:72px; height:72px; border-radius:8px; border:2px solid var(--border2); background:var(--card2); overflow:hidden; cursor:pointer; transition:border-color .2s; display:flex; align-items:center; justify-content:center; }
        .pd-thumb.active, .pd-thumb:hover { border-color:var(--gold); }
        .pd-thumb img { width:100%; height:100%; object-fit:cover; }
        .pd-cat-label { font-size:.6rem; letter-spacing:.28em; text-transform:uppercase; color:var(--gold); margin-bottom:10px; }
        .pd-name { font-family:'Playfair Display',serif; font-size:clamp(1.6rem,3vw,2.4rem); font-weight:600; color:var(--ivory); line-height:1.2; margin-bottom:8px; }
        .pd-sub { font-size:.88rem; color:var(--t3); margin-bottom:20px; letter-spacing:.05em; }
        .pd-price-row { display:flex; align-items:baseline; gap:12px; margin-bottom:10px; }
        .pd-price { font-family:'Playfair Display',serif; font-size:2.2rem; color:var(--gold); line-height:1; }
        .pd-price-old { font-size:1rem; color:var(--t4); text-decoration:line-through; }
        .pd-stock { font-size:.88rem; margin-bottom:20px; }
        .pd-age-warn { padding:10px 14px; background:rgba(155,40,40,.1); border:1px solid rgba(155,40,40,.3); border-radius:6px; font-size:.82rem; color:#D08080; line-height:1.7; margin-bottom:20px; }
        .pd-desc { font-size:.95rem; color:var(--t2); line-height:2; margin-bottom:24px; padding-bottom:20px; border-bottom:1px solid var(--border2); }
        .pd-specs-title { font-size:.65rem; letter-spacing:.2em; text-transform:uppercase; color:var(--gold); margin-bottom:12px; }
        .pd-spec-row { display:flex; justify-content:space-between; padding:11px 0; border-bottom:1px solid var(--border2); font-size:.9rem; }
        .pd-spec-label { color:var(--t3); }
        .pd-spec-val { color:var(--t1); font-weight:600; }
        .pd-qty-section { margin:24px 0 16px; display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
        .pd-qty-label { font-size:.7rem; letter-spacing:.15em; text-transform:uppercase; color:var(--t3); }
        .pd-qty-ctrl { display:flex; align-items:center; border:1px solid var(--border); border-radius:4px; overflow:hidden; }
        .pd-qty-btn { width:36px; height:36px; background:var(--card2); border:none; color:var(--gold); font-size:1.1rem; cursor:pointer; transition:background .2s; }
        .pd-qty-btn:hover { background:rgba(201,151,58,.15); }
        .pd-qty-input { width:52px; height:36px; border:none; border-left:1px solid var(--border); border-right:1px solid var(--border); background:var(--card); color:var(--ivory); text-align:center; font-size:.95rem; font-family:'Playfair Display',serif; outline:none; }
        .pd-cta-row { display:flex; gap:10px; flex-wrap:wrap; }
        .pd-cta-row .btn-primary { flex:1; justify-content:center; padding:14px 20px; font-size:.75rem; }
        .pd-cta-row .btn-outline { padding:13px 20px; font-size:.75rem; }
        /* Taste Profile */
        .taste-section { padding:36px 0 0; border-top:1px solid var(--border2); margin-top:20px; }
        .taste-title { font-size:.65rem; letter-spacing:.22em; text-transform:uppercase; color:var(--gold); margin-bottom:24px; display:flex; align-items:center; gap:8px; }
        .taste-title::after { content:''; flex:1; height:1px; background:rgba(201,151,58,.2); }
        .taste-sliders { display:grid; grid-template-columns:1fr 1fr; gap:32px; margin-bottom:24px; }
        .taste-slider-track { position:relative; height:20px; background:rgba(255,255,255,.08); border-radius:10px; margin-bottom:10px; overflow:visible; }
        .taste-slider-fill { position:absolute; top:0; left:0; height:100%; border-radius:10px; background:linear-gradient(90deg,#8B1A1A,#C0503A); transition:width 1.2s cubic-bezier(.34,1.2,.64,1); }
        .taste-slider-dot { position:absolute; top:50%; transform:translate(-50%,-50%); width:22px; height:22px; background:#fff; border-radius:50%; box-shadow:0 2px 8px rgba(0,0,0,.4); border:2px solid rgba(192,80,58,.6); transition:left 1.2s cubic-bezier(.34,1.2,.64,1); }
        .taste-slider-label { font-size:.82rem; color:var(--t2); font-weight:600; text-align:center; }
        .taste-cards { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px; }
        .taste-card { display:flex; align-items:center; gap:14px; padding:14px 18px; background:var(--card2); border:1px solid var(--border2); border-radius:10px; }
        .taste-card-icon { font-size:2rem; flex-shrink:0; }
        .taste-card-text { font-size:.82rem; color:var(--t2); line-height:1.5; }
        .taste-card-name { font-size:.72rem; letter-spacing:.1em; text-transform:uppercase; color:var(--t3); margin-bottom:2px; }
        /* Occasion */
        .occ-wrap { border-top:1px solid rgba(255,255,255,.06); padding-top:18px; margin-top:4px; }
        .occ-lbl { font-size:.58rem; letter-spacing:.2em; text-transform:uppercase; color:var(--t3); margin-bottom:10px; }
        .occ-tags { display:flex; flex-wrap:wrap; gap:8px; }
        .occ-tag { font-size:.7rem; padding:5px 13px; border-radius:2px; border:1px solid; letter-spacing:.04em; cursor:default; }
        .occ-tag.active   { background:rgba(201,151,58,.1); border-color:rgba(201,151,58,.45); color:var(--gold); }
        .occ-tag.inactive { background:transparent; border-color:rgba(255,255,255,.07); color:var(--t4); opacity:.4; }
        /* Related */
        .pd-related { margin-top:80px; border-top:1px solid var(--border2); padding-top:60px; }
        .pd-related-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:24px; margin-top:28px; }
        /* BuyNow modal */
        .buynow-btns { display:flex; flex-direction:column; gap:12px; padding:8px 0; }
        [data-theme="light"] .pd-main-img { background:#f5ede0; border-color:rgba(139,80,10,.15); }
        [data-theme="light"] .taste-slider-track { background:rgba(0,0,0,.08); }
        [data-theme="light"] .taste-card { background:#fff8f0; border-color:rgba(139,80,10,.1); }
        @media(max-width:900px){
            .pd-wrap { padding:100px 24px 60px; }
            .pd-grid { grid-template-columns:1fr; gap:36px; }
            .pd-gallery { position:static; }
            .taste-sliders, .taste-cards { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>

<div class="age-gate" id="ageGate">
    <div class="age-box">
        <div style="margin-bottom:16px;"><img src="assets/uploads/logo.png" alt="Logo" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid #C9A96E;"></div>
        <h2>Xác Nhận Độ Tuổi</h2>
        <p>Trang web chứa nội dung về rượu.<br>Bạn có đủ <strong>18 tuổi</strong> trở lên không?</p>
        <div class="age-btns">
            <button class="age-yes" onclick="closeAgeGate()">Có, tôi đủ 18 tuổi</button>
            <button class="age-no" onclick="alert('Bạn chưa đủ tuổi truy cập trang này.')">Không</button>
        </div>
    </div>
</div>

<div class="toast" id="toast"><span id="toast-msg"></span></div>

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
            <button class="btn-outline" style="font-size:.6rem;padding:10px 8px;" onclick="openCheckout('cod')">Trả Sau (COD)</button>
        </div>
        <button class="btn-outline" style="width:100%;margin-top:8px;font-size:.6rem;padding:9px;" onclick="toggleCart()">Tiếp Tục Mua Sắm</button>
    </div>
</div>

<nav id="navbar">
    <div class="nav-logo" onclick="window.location.href='index.php'">
        <img src="assets/uploads/logo.png" alt="Tây Lương Cửu" style="width:42px;height:42px;border-radius:50%;object-fit:cover;border:2px solid #C9A96E;">
        <div><div class="logo-text">Tây Lương <span>Cửu</span></div><div class="logo-sub">Rượu Truyền Thống Cao Cấp</div></div>
    </div>
    <ul class="nav-links">
        <li><a href="index.php">Trang Chủ</a></li>
        <li><a href="index.php#productsSection">Sản Phẩm</a></li>
        <li><a href="index.php#aiSection">Tư Vấn</a></li>
        <li><a href="index.php#testimonialsSection">Đánh Giá</a></li>
    </ul>
    <div class="nav-actions">
        <button class="theme-btn" onclick="toggleTheme()" title="Chuyển sáng/tối"><span id="themeIcon"></span></button>
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

<div class="pd-wrap">
    <div class="pd-breadcrumb">
        <a href="index.php">Trang Chủ</a><span>›</span>
        <a href="index.php#productsSection">Sản Phẩm</a><span>›</span>
        <span style="color:var(--t2);"><?= htmlspecialchars($p['name']) ?></span>
    </div>
    <div class="pd-grid">
        <!-- CỘT ẢNH -->
        <div class="pd-gallery">
            <div class="pd-main-img" id="pdMainImgWrap">
                <?php if ($p['img']): ?>
                <div class="pd-badge-wrap">
                    <?php if ($p['badge']): $badgeMap=['hot'=>'badge-hot','new'=>'badge-new','limited'=>'badge-limited','sale'=>'badge-sale']; $bc=$badgeMap[$p['badge']]??'badge-new'; ?>
                    <span class="card-badge <?= $bc ?>"><?= htmlspecialchars($p['badgeText']??$p['badge']) ?></span>
                    <?php endif; ?>
                </div>
                <img id="pdMainImg" src="<?= htmlspecialchars($p['img']) ?>" alt="<?= htmlspecialchars($p['name']) ?>"
                     onmousemove="zoomImgPD(event,this)" onmouseleave="resetZoomPD(this)"
                     onerror="this.style.display='none';document.getElementById('pdImgFallback').style.display='flex';">
                <div id="pdImgFallback" style="display:none;position:absolute;inset:0;align-items:center;justify-content:center;font-size:5rem;">🍶</div>
                <?php else: ?><span class="pd-img-no">🍶</span><?php endif; ?>
            </div>
            <?php if ($p['img']): ?>
            <div class="pd-thumb-row"><div class="pd-thumb active"><img src="<?= htmlspecialchars($p['img']) ?>" alt=""></div></div>
            <?php endif; ?>
        </div>

        <!-- CỘT THÔNG TIN -->
        <div class="pd-info">
            <div class="pd-cat-label"><?= htmlspecialchars($p['cat']??'') ?></div>
            <h1 class="pd-name"><?= htmlspecialchars($p['name']) ?></h1>
            <div class="pd-sub"><?= (int)$p['alc'] ?>° · <?= (int)$p['vol'] ?>ml<?= $p['flavor']?' · '.htmlspecialchars($p['flavor']):'' ?></div>
            <div class="pd-price-row">
                <span class="pd-price">₫<?= number_format($p['price'],0,',','.') ?></span>
                <?php if ($p['priceOld']): ?><span class="pd-price-old">₫<?= number_format($p['priceOld'],0,',','.') ?></span><?php endif; ?>
            </div>
            <div class="pd-stock">
                <?php if (($p['stock']??0)>0): ?>
                <span style="color:#6DD880;">✅ Còn hàng</span> <span style="color:var(--t3);font-size:.8rem;">(<?= (int)$p['stock'] ?> chai)</span>
                <?php else: ?><span style="color:#e07070;">⚠ Hết hàng</span><?php endif; ?>
            </div>
            <div class="pd-age-warn">⚠ Bạn phải từ 18 tuổi trở lên mới được mua rượu tại Việt Nam. Uống có trách nhiệm.</div>
            <p class="pd-desc"><?= nl2br(htmlspecialchars($p['desc']??'Sản phẩm rượu truyền thống Tây Lương Cửu.')) ?></p>

            <div class="pd-specs-title">Thông Số Chi Tiết</div>
            <?php foreach ([['Danh mục',$p['cat']??'—'],['Độ cồn',$p['alc']?$p['alc'].'°':'—'],['Dung tích',$p['vol']?$p['vol'].' ml':'—'],['Xuất xứ',$p['origin']??'—'],['Hương vị',$p['flavor']??'—']] as [$label,$val]): ?>
            <div class="pd-spec-row"><span class="pd-spec-label"><?= $label ?></span><span class="pd-spec-val"><?= htmlspecialchars($val) ?></span></div>
            <?php endforeach; ?>

            <?php if (($p['stock']??0)>0): ?>
            <div class="pd-qty-section">
                <span class="pd-qty-label">Số lượng</span>
                <div class="pd-qty-ctrl">
                    <button class="pd-qty-btn" onclick="pdPageChangeQty(-1)">−</button>
                    <input class="pd-qty-input" type="number" id="pdPageQty" value="1" min="1" max="<?= (int)$p['stock'] ?>" oninput="pdPageSyncQty(this)">
                    <button class="pd-qty-btn" onclick="pdPageChangeQty(1)">+</button>
                </div>
                <span style="font-size:.8rem;color:var(--t3);">Tối đa <?= (int)$p['stock'] ?> chai</span>
            </div>
            <?php endif; ?>

            <div class="pd-cta-row">
                <?php if (($p['stock']??0)>0): ?>
                <button class="btn-primary" onclick="pdPageAddToCart()"><span>+ Thêm vào giỏ hàng</span></button>
                <button class="btn-outline" onclick="pdPageBuyNow()">Mua ngay</button>
                <?php else: ?>
                <button class="btn-primary" disabled style="opacity:.5;cursor:not-allowed;flex:1;justify-content:center;">— Hết hàng —</button>
                <?php endif; ?>
            </div>

            <!-- TASTE PROFILE — chỉ hiện nếu admin bật -->
            <?php if ($show_taste): ?>
            <div class="taste-section">
                <div class="taste-title">✦ Taste Profile</div>
                <div class="taste-sliders">
                    <div class="taste-slider-item">
                        <div class="taste-slider-track">
                            <div class="taste-slider-fill" id="sliderBodyFill" style="width:0%"></div>
                            <div class="taste-slider-dot" id="sliderBodyDot" style="left:0%"></div>
                        </div>
                        <div class="taste-slider-label"><?= $body_label ?></div>
                    </div>
                    <div class="taste-slider-item">
                        <div class="taste-slider-track">
                            <div class="taste-slider-fill" id="sliderSweetFill" style="width:0%"></div>
                            <div class="taste-slider-dot" id="sliderSweetDot" style="left:0%"></div>
                        </div>
                        <div class="taste-slider-label"><?= $sweet_label ?></div>
                    </div>
                </div>
                <div class="taste-cards">
                    <div class="taste-card"><span class="taste-card-icon">🍾</span><div><div class="taste-card-name">Body</div><div class="taste-card-text"><?= $body_desc ?></div></div></div>
                    <div class="taste-card"><span class="taste-card-icon">🍷</span><div><div class="taste-card-name">Sweetness</div><div class="taste-card-text"><?= $sweet_desc ?></div></div></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- OCCASION TAGS -->
            <?php if (!empty($activeOcc)): ?>
            <div class="occ-wrap">
                <div class="occ-lbl">Dịp Phù Hợp</div>
                <div class="occ-tags">
                    <?php foreach ($allOcc as $k=>$lbl): ?>
                    <span class="occ-tag <?= in_array($k,$activeOcc)?'active':'inactive' ?>"><?= htmlspecialchars($lbl) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <?php if (!empty($related)): ?>
    <div class="pd-related reveal">
        <div class="section-label">Có thể bạn thích</div>
        <h2 class="section-title">Sản phẩm <em>liên quan</em></h2>
        <div class="pd-related-grid" id="relatedGrid"></div>
    </div>
    <?php endif; ?>
</div>

<footer>
    <div class="footer-grid">
        <div><div class="footer-brand">Tây Lương <span>Cửu</span></div><p class="footer-desc">Tinh hoa rượu Việt — chưng cất bằng tâm huyết và bí quyết gia truyền hơn 28 năm.</p></div>
        <div><div class="footer-col-title">Sản Phẩm</div><ul class="footer-links"><?php foreach ($allProducts as $ap): ?><li><a href="product.php?id=<?= $ap['id'] ?>"><?= htmlspecialchars($ap['name']) ?></a></li><?php endforeach; ?></ul></div>
        <div><div class="footer-col-title">Thông Tin</div><ul class="footer-links"><li><a href="about.php">Về chúng tôi</a></li><li><a href="policy.php">Chính sách đổi trả</a></li><li><a href="guide.php">Hướng dẫn mua hàng</a></li></ul></div>
        <div><div class="footer-col-title">Liên Hệ</div><ul class="footer-links"><li><a>092 878 7046</a></li><li><a>info@tayluongcuu.vn</a></li><li><a>Hà Nội, Việt Nam</a></li></ul></div>
    </div>
    <div class="footer-bottom"><span>© <?= date('Y') ?> Tây Lương Cửu</span><span>Chỉ dành cho người đủ 18 tuổi · Uống có trách nhiệm</span></div>
</footer>

<!-- AUTH MODAL -->
<div class="modal-overlay" id="authModal">
    <div class="modal" style="width:420px;">
        <div class="modal-head"><h3 id="authModalTitle">Đăng Nhập</h3><button class="modal-close-btn" onclick="closeModal('authModal')">✕</button></div>
        <div class="modal-body">
            <div style="display:flex;gap:8px;margin-bottom:20px;">
                <button id="tabLogin" onclick="switchAuthTab('login')" style="flex:1;padding:8px;background:var(--gold);color:#1A0A00;border:none;cursor:pointer;font-size:.75rem;letter-spacing:.1em;">ĐĂNG NHẬP</button>
                <button id="tabRegister" onclick="switchAuthTab('register')" style="flex:1;padding:8px;background:transparent;color:var(--t2);border:1px solid var(--border);cursor:pointer;font-size:.75rem;letter-spacing:.1em;">ĐĂNG KÝ</button>
            </div>
            <div id="formLogin">
                <div class="form-field"><label>Email</label><input class="form-control" id="loginEmail" type="email" placeholder="email@example.com"></div>
                <div class="form-field"><label>Mật Khẩu</label><input class="form-control" id="loginPass" type="password" placeholder="••••••"></div>
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
        </div>
    </div>
</div>

<!-- SUCCESS MODAL -->
<div class="modal-overlay" id="successModal">
    <div class="modal" style="width:420px;text-align:center;">
        <div class="modal-body" style="padding:48px 32px;">
            <div style="font-size:3rem;margin-bottom:16px;">🎉</div>
            <h3 style="font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:400;color:var(--gold);margin-bottom:12px;">Đặt Hàng Thành Công!</h3>
            <p id="successMsg" style="font-size:.95rem;color:var(--t2);line-height:1.9;margin-bottom:8px;"></p>
            <p id="successOrderCode" style="font-size:.82rem;color:var(--gold);letter-spacing:.15em;margin-bottom:32px;"></p>
            <button class="btn-primary" onclick="closeModal('successModal')"><span>Tiếp tục mua sắm</span></button>
        </div>
    </div>
</div>

<!-- MUA NGAY — chọn hình thức thanh toán -->
<div class="modal-overlay" id="buyNowModal">
    <div class="modal" style="width:360px;">
        <div class="modal-head">
            <h3>Chọn Hình Thức Thanh Toán</h3>
            <button class="modal-close-btn" onclick="closeModal('buyNowModal')">✕</button>
        </div>
        <div class="modal-body">
            <div class="buynow-btns">
                <button class="btn-primary" style="padding:14px;font-size:.85rem;" onclick="closeModal('buyNowModal');openCheckout('online')">
                    💳 Thanh Toán Online (QR)
                </button>
                <button class="btn-outline" style="padding:13px;font-size:.85rem;" onclick="closeModal('buyNowModal');openCheckout('cod')">
                    🚚 Trả Sau Khi Nhận Hàng (COD)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- CHECKOUT ONLINE -->
<div class="modal-overlay" id="checkoutOnlineModal">
    <div class="modal" style="width:480px;">
        <div class="modal-head"><h3 id="sePayModalTitle">Thanh Toán SePay</h3><button class="modal-close-btn" onclick="closeModal('checkoutOnlineModal')">✕</button></div>
        <div id="sePayStep1">
            <div class="modal-body">
                <div id="onlineSummary"></div>
                <div class="form-row2">
                    <div class="form-field"><label>Họ Tên *</label><input class="form-control" id="onName" placeholder="Nguyễn Văn A"></div>
                    <div class="form-field"><label>Điện Thoại *</label><input class="form-control" id="onPhone" placeholder="0901 234 567"></div>
                </div>
                <div class="form-field"><label>Địa Chỉ *</label><div class="goong-wrap"><input class="form-control" id="onAddress" placeholder="Số nhà, đường, quận, tỉnh/TP" autocomplete="off" oninput="goongSuggest('onAddress','goongDropOn')"><div class="goong-drop" id="goongDropOn"></div></div></div>
                <div class="form-field"><label>Ghi Chú</label><textarea class="form-control" id="onNote" rows="2" placeholder="Yêu cầu đặc biệt..."></textarea></div>
            </div>
            <div class="modal-foot">
                <button class="btn-outline" style="padding:10px 20px;font-size:.7rem;" onclick="closeModal('checkoutOnlineModal')">Quay lại</button>
                <button class="btn-primary" style="padding:11px 24px;font-size:.7rem;" onclick="createSePayOrder()">Tiếp tục → Hiện QR</button>
            </div>
        </div>
        <div id="sePayStep2" style="display:none;">
            <div class="modal-body" style="text-align:center;">
                <div style="background:#fff;display:inline-block;padding:12px;border-radius:8px;margin-bottom:16px;"><img id="sePayQR" src="" alt="QR SePay" style="width:220px;height:220px;display:block;"></div>
                <div style="background:rgba(255,255,255,.04);border:1px solid var(--border2);padding:14px;text-align:left;font-size:.82rem;line-height:2;margin-bottom:16px;">
                    <div>Ngân hàng: <strong id="qrBank" style="color:var(--t1)"></strong></div>
                    <div>Số TK: <strong id="qrAccNo" style="color:var(--gold)"></strong></div>
                    <div>Chủ TK: <strong id="qrAccName" style="color:var(--t1)"></strong></div>
                    <div>Số tiền: <strong id="qrAmount" style="color:var(--gold)"></strong></div>
                    <div>Nội dung: <strong id="qrContent" style="color:var(--gold)"></strong></div>
                </div>
                <div id="sePayCountdown" style="display:none;text-align:center;font-size:.82rem;color:var(--t3);margin-bottom:10px;">Hết hạn sau: <span id="countdownTime" style="color:var(--gold);font-size:1rem;font-weight:600;">05:00</span></div>
                <div id="sePayStatus" style="padding:10px;border-radius:6px;font-size:.82rem;background:rgba(212,168,75,.08);border:1px solid var(--border2);color:var(--t2);">Đang chờ thanh toán...</div>
            </div>
            <div class="modal-foot" style="justify-content:center;"><button class="btn-outline" style="padding:10px 20px;font-size:.7rem;" onclick="cancelSePayOrder()">Hủy</button></div>
        </div>
    </div>
</div>

<!-- CHECKOUT COD -->
<div class="modal-overlay" id="checkoutCodModal">
    <div class="modal" style="width:500px;">
        <div class="modal-head"><h3>Đặt Hàng Trả Sau (COD)</h3><button class="modal-close-btn" onclick="closeModal('checkoutCodModal')">✕</button></div>
        <div class="modal-body">
            <div id="codSummary"></div>
            <div class="form-row2">
                <div class="form-field"><label>Họ Tên *</label><input class="form-control" id="codName" placeholder="Nguyễn Văn A"></div>
                <div class="form-field"><label>Điện Thoại *</label><input class="form-control" id="codPhone" placeholder="0901 234 567"></div>
            </div>
            <div class="form-field"><label>Địa Chỉ *</label><div class="goong-wrap"><input class="form-control" id="codAddress" placeholder="Số nhà, đường, quận, tỉnh/TP" autocomplete="off" oninput="goongSuggest('codAddress','goongDropCod')"><div class="goong-drop" id="goongDropCod"></div></div></div>
            <div class="form-row2">
                <div class="form-field"><label>Loại Rượu *</label><select class="form-control" id="codProduct" onchange="updateCodTotal()"><option value="">-- Chọn sản phẩm --</option></select></div>
                <div class="form-field"><label>Số Lượng *</label><input class="form-control" id="codQty" type="number" min="1" value="1" oninput="updateCodTotal()"></div>
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

<!-- MY ACCOUNT -->
<div class="modal-overlay" id="myAccountModal">
    <div class="modal" style="width:500px;">
        <div class="modal-head"><h3>Tài Khoản Của Tôi</h3><button class="modal-close-btn" onclick="closeModal('myAccountModal')">✕</button></div>
        <div class="modal-body"><div id="myOrdersList" style="max-height:400px;overflow-y:auto;"><div style="text-align:center;color:var(--t3);padding:20px;">Đang tải...</div></div></div>
        <div class="modal-foot" style="justify-content:space-between;">
            <span id="accEmailDisplay" style="font-size:.78rem;color:var(--t3);"></span>
            <button class="btn-outline" style="padding:8px 18px;font-size:.7rem;color:#e07070;border-color:#e07070;" onclick="doLogout()">Đăng Xuất</button>
        </div>
    </div>
</div>

<script>
var productsList          = <?= json_encode($allProducts, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
var CURRENT_PRODUCT_ID    = <?= (int)$p['id'] ?>;
var CURRENT_PRODUCT_STOCK = <?= (int)($p['stock']??0) ?>;
var TASTE_BODY_PCT        = <?= $body_pct ?>;
var TASTE_SWEET_PCT       = <?= $sweet_pct ?>;
var SHOW_TASTE            = <?= $show_taste ? 'true' : 'false' ?>;
var RELATED_IDS           = <?= json_encode(array_column($related,'id')) ?>;
</script>

<script type="module">
import { initializeApp } from "https://www.gstatic.com/firebasejs/11.6.0/firebase-app.js";
import { getAuth, GoogleAuthProvider, signInWithPopup, signOut, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/11.6.0/firebase-auth.js";
const firebaseConfig = {apiKey:"AIzaSyDQii8PSCfJsB-WMJgkmBb2929st-oSlis",authDomain:"tayluongcuu.firebaseapp.com",projectId:"tayluongcuu",storageBucket:"tayluongcuu.firebasestorage.app",messagingSenderId:"608129663962",appId:"1:608129663962:web:f7a7ec09f974e4e4c3b6e3"};
const app=initializeApp(firebaseConfig);const auth=getAuth(app);const provider=new GoogleAuthProvider();
window.loginWithGoogle=async function(){try{const r=await signInWithPopup(auth,provider);await syncFirebaseUser(r.user);}catch(e){if(e.code!=='auth/popup-closed-by-user')showToast('Lỗi Google: '+e.message);}};
window.logoutFirebase=async function(){await signOut(auth);};
async function syncFirebaseUser(user){try{const token=await user.getIdToken();const res=await fetch(BASE+'api/firebase_auth.php',{method:'POST',credentials:'include',headers:{'Content-Type':'application/json'},body:JSON.stringify({uid:user.uid,email:user.email,name:user.displayName,photo:user.photoURL,token:token})});const data=await res.json();if(data.id){saveCustomer(data);closeModal('authModal');showToast('Chào mừng '+data.name+'!');}}catch(e){showToast('Lỗi đồng bộ tài khoản');}}
onAuthStateChanged(auth,user=>{if(!user){const saved=loadCustomer();if(saved&&saved.isGoogle)clearCustomer();}});
const _origLogout=window.doLogout;window.doLogout=async function(){const c=loadCustomer();if(c&&c.isGoogle)await signOut(auth);if(_origLogout)await _origLogout();else{clearCustomer();closeModal('myAccountModal');showToast('Đã đăng xuất');}};
</script>

<script src="assets/js/app.js"></script>
<script>
function zoomImgPD(e, img) {
    var rect=img.getBoundingClientRect();
    img.style.transformOrigin=((e.clientX-rect.left)/rect.width*100)+'% '+((e.clientY-rect.top)/rect.height*100)+'%';
    img.style.transform='scale(2)';
}
function resetZoomPD(img) { img.style.transform='scale(1)'; img.style.transformOrigin='center'; }

function pdPageChangeQty(delta) {
    var input=document.getElementById('pdPageQty'); if(!input) return;
    var max=parseInt(input.max)||99, val=(parseInt(input.value)||1)+delta;
    if(val<1) val=1; if(val>max){val=max;showToast('⚠ Chỉ còn '+max+' chai!');}
    input.value=val;
}
function pdPageSyncQty(input) {
    var max=parseInt(input.max)||99, v=parseInt(input.value)||1;
    if(v<1) v=1; if(v>max){v=max;showToast('⚠ Chỉ còn '+max+' chai!');}
    input.value=v;
}

function pdPageAddToCart() {
    var qty=parseInt(document.getElementById('pdPageQty')?.value)||1;
    if(typeof addToCartQty==='function') addToCartQty(CURRENT_PRODUCT_ID,qty);
    else for(var i=0;i<qty;i++) addToCart(CURRENT_PRODUCT_ID);
    toggleCart();
}

// Mua ngay — hiện modal chọn hình thức thanh toán
function pdPageBuyNow() {
    var qty=parseInt(document.getElementById('pdPageQty')?.value)||1;
    cart=[];
    if(typeof addToCartQty==='function') addToCartQty(CURRENT_PRODUCT_ID,qty);
    else addToCart(CURRENT_PRODUCT_ID);
    openModal('buyNowModal');
}

function animateTasteSliders() {
    if (!SHOW_TASTE) return;
    setTimeout(function(){
        var bf=document.getElementById('sliderBodyFill'), bd=document.getElementById('sliderBodyDot');
        if(bf) bf.style.width=TASTE_BODY_PCT+'%'; if(bd) bd.style.left=TASTE_BODY_PCT+'%';
    }, 400);
    setTimeout(function(){
        var sf=document.getElementById('sliderSweetFill'), sd=document.getElementById('sliderSweetDot');
        if(sf) sf.style.width=TASTE_SWEET_PCT+'%'; if(sd) sd.style.left=TASTE_SWEET_PCT+'%';
    }, 600);
}

function renderRelated() {
    var grid=document.getElementById('relatedGrid'); if(!grid||!RELATED_IDS.length) return;
    var rel=productsList.filter(function(p){return RELATED_IDS.indexOf(p.id)>-1;});
    if(rel.length&&typeof buildCard==='function') grid.innerHTML=rel.map(function(p){return buildCard(p);}).join('');
}

window.openProductDetail=function(id){ window.location.href='product.php?id='+id; };

document.addEventListener('DOMContentLoaded',function(){
    animateTasteSliders();
    var tries=0, iv=setInterval(function(){tries++; if(productsList.length||tries>30){clearInterval(iv);renderRelated();}},100);
    document.querySelectorAll('.reveal').forEach(function(el){
        new IntersectionObserver(function(entries){entries.forEach(function(e){if(e.isIntersecting)e.target.classList.add('visible');});},{threshold:0.1}).observe(el);
    });
});
</script>
</body>
</html>