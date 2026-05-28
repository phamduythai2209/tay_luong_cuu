<?php require_once __DIR__ . '/config/database.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chính Sách Đổi Trả — Tây Lương Cửu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .page-hero {
            background: linear-gradient(135deg, #0A0202 0%, #1A0808 60%, #0A0A0A 100%);
            padding: 100px 64px 80px;
            text-align: center;
            position: relative; overflow: hidden;
        }
        .page-hero::before {
            content: ''; position: absolute; inset: 0;
            background-image: radial-gradient(circle, rgba(201,151,58,.15) 1px, transparent 1px);
            background-size: 32px 32px; opacity: .5;
        }
        .page-hero h1 { font-family:'Playfair Display',serif;font-size:clamp(2rem,5vw,3.5rem);font-weight:400;color:#F5ECD5;position:relative;z-index:1; }
        .page-hero h1 em { color:#C9973A;font-style:italic; }
        .page-hero p { font-size:1.05rem;color:rgba(245,236,213,.6);margin-top:16px;position:relative;z-index:1; }
        .page-content { max-width:900px;margin:0 auto;padding:80px 48px;font-family:'Times New Roman',Times,serif; }
        .policy-block { margin-bottom:48px; }
        .policy-block h2 { font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:400;color:var(--ivory);margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid rgba(201,151,58,.3); }
        .policy-block h2 span { color:#C9973A; }
        .policy-block p { font-size:1.05rem;line-height:2;color:var(--t2);margin-bottom:12px; }
        .policy-list { list-style:none;padding:0;margin:16px 0; }
        .policy-list li { display:flex;gap:12px;align-items:flex-start;padding:12px 0;border-bottom:1px solid var(--border2);font-size:1rem;color:var(--t2);line-height:1.8; }
        .policy-list li::before { content:'✦';color:#C9973A;flex-shrink:0;margin-top:2px; }
        .highlight-box { background:rgba(201,151,58,.08);border:1px solid rgba(201,151,58,.25);border-radius:8px;padding:20px 24px;margin:20px 0; }
        .highlight-box p { color:var(--ivory);margin:0;font-size:1rem;line-height:1.9; }
        .no-return-list li::before { content:'✕';color:#e07070; }
        .back-link { display:inline-flex;align-items:center;gap:8px;color:#C9973A;font-family:sans-serif;font-size:.85rem;letter-spacing:.1em;text-decoration:none;margin-bottom:48px;transition:gap .2s; }
        .back-link:hover { gap:12px; }
        [data-theme="light"] .page-hero { background:linear-gradient(135deg,#FFF0DC 0%,#FFE8C8 60%,#FFF5E8 100%); }
        [data-theme="light"] .page-hero h1 { color:#1A0A00; }
    </style>
</head>
<body>

<nav id="navbar">
    <div class="nav-logo" onclick="window.location.href='index.php'">
        <img src="assets/uploads/logo.png" alt="Tây Lương Cửu" style="width:42px;height:42px;border-radius:50%;object-fit:cover;border:2px solid #C9A96E;">
        <div>
            <div class="logo-text">Tây Lương <span>Cửu</span></div>
            <div class="logo-sub">Rượu Truyền Thống Cao Cấp</div>
        </div>
    </div>
    <ul class="nav-links">
        <li><a onclick="window.location.href='index.php'">Trang Chủ</a></li>
        <li><a onclick="window.location.href='index.php'">Sản Phẩm</a></li>
    </ul>
    <div class="nav-actions">
        <button class="theme-btn" onclick="toggleTheme()" title="Chuyển sáng/tối"><span id="themeIcon"></span></button>
    </div>
</nav>

<div class="page-hero">
    <h1>Chính Sách <em>Đổi Trả</em></h1>
    <p>Cam kết bảo vệ quyền lợi khách hàng — minh bạch và công bằng</p>
</div>

<div class="page-content">
    <a href="index.php" class="back-link">← Quay về trang chủ</a>

    <div class="highlight-box">
        <p>Tây Lương Cửu cam kết mang đến sản phẩm chất lượng cao. Nếu vì bất kỳ lý do nào sản phẩm không đáp ứng kỳ vọng của quý khách, chúng tôi sẵn sàng hỗ trợ đổi trả theo chính sách dưới đây.</p>
    </div>

    <div class="policy-block">
        <h2>Điều kiện <span>được đổi trả</span></h2>
        <p>Quý khách có thể yêu cầu đổi trả trong các trường hợp sau:</p>
        <ul class="policy-list">
            <li>Sản phẩm bị vỡ, hỏng, rò rỉ trong quá trình vận chuyển do lỗi của bên giao hàng</li>
            <li>Sản phẩm giao không đúng với đơn hàng (sai loại, sai dung tích, sai số lượng)</li>
            <li>Sản phẩm có dấu hiệu hỏng hóc do lỗi sản xuất (nút chai lỏng, seal bị hở trước khi mở)</li>
            <li>Sản phẩm có mùi lạ, màu sắc bất thường khác với mô tả</li>
        </ul>
    </div>

    <div class="policy-block">
        <h2>Thời hạn <span>đổi trả</span></h2>
        <ul class="policy-list">
            <li>Phản ánh trong vòng <strong style="color:#C9973A;">24 giờ</strong> kể từ khi nhận hàng đối với hàng hỏng, vỡ do vận chuyển</li>
            <li>Phản ánh trong vòng <strong style="color:#C9973A;">48 giờ</strong> đối với hàng giao sai, thiếu</li>
            <li>Quý khách cần chụp ảnh sản phẩm và gửi kèm theo yêu cầu đổi trả để được xử lý nhanh nhất</li>
        </ul>
    </div>

    <div class="policy-block">
        <h2>Trường hợp <span>không được đổi trả</span></h2>
        <ul class="policy-list no-return-list">
            <li>Sản phẩm đã được mở nắp, sử dụng một phần (trừ trường hợp phát hiện lỗi chất lượng)</li>
            <li>Thay đổi ý định cá nhân sau khi đã nhận hàng</li>
            <li>Sản phẩm bị hỏng do bảo quản không đúng cách sau khi nhận</li>
            <li>Quá thời hạn đổi trả theo quy định</li>
        </ul>
    </div>

    <div class="policy-block">
        <h2>Quy trình <span>đổi trả</span></h2>
        <ul class="policy-list">
            <li>Bước 1: Liên hệ hotline <strong style="color:#C9973A;">092 878 7046</strong> hoặc email <strong style="color:#C9973A;">info@tayluongcuu.vn</strong> thông báo yêu cầu đổi trả</li>
            <li>Bước 2: Cung cấp mã đơn hàng, mô tả vấn đề và hình ảnh sản phẩm lỗi</li>
            <li>Bước 3: Đội ngũ xác nhận yêu cầu trong vòng 2-4 giờ làm việc</li>
            <li>Bước 4: Giao sản phẩm mới hoặc hoàn tiền trong vòng 1-3 ngày làm việc</li>
        </ul>
    </div>

    <div class="policy-block">
        <h2>Chính sách <span>hoàn tiền</span></h2>
        <p>Trong trường hợp sản phẩm thay thế không còn hàng, chúng tôi sẽ hoàn tiền 100% giá trị đơn hàng qua:</p>
        <ul class="policy-list">
            <li>Chuyển khoản ngân hàng trong vòng 1-2 ngày làm việc</li>
            <li>Tiền mặt khi gặp trực tiếp tại Hà Nội</li>
        </ul>
        <div class="highlight-box" style="margin-top:24px;">
            <p>Mọi chi phí đổi trả do lỗi từ phía Tây Lương Cửu sẽ do chúng tôi hoàn toàn chịu trách nhiệm. Quý khách không mất thêm bất kỳ chi phí nào.</p>
        </div>
    </div>

    <div class="policy-block">
        <h2>Liên hệ <span>hỗ trợ</span></h2>
        <p>Hotline: <strong style="color:#C9973A;">092 878 7046</strong> — Hỗ trợ 7 ngày/tuần, 8:00 – 21:00</p>
        <p>Email: <strong style="color:#C9973A;">info@tayluongcuu.vn</strong></p>
    </div>
</div>

<footer>
    <div class="footer-bottom" style="text-align:center;padding:24px;">
        <span>© <?= date('Y') ?> Tây Lương Cửu · <a href="index.php" style="color:var(--gold);">Trang chủ</a></span>
    </div>
</footer>

<script src="assets/js/app.js"></script>
</body>
</html>