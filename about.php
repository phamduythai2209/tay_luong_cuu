<?php require_once __DIR__ . '/config/database.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Về Chúng Tôi — Tây Lương Cửu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .page-hero {
            background: linear-gradient(135deg, #0A0202 0%, #1A0808 60%, #0A0A0A 100%);
            padding: 100px 64px 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .page-hero::before {
            content: '';
            position: absolute; inset: 0;
            background-image: radial-gradient(circle, rgba(201,151,58,.15) 1px, transparent 1px);
            background-size: 32px 32px;
            opacity: .5;
        }
        .page-hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2rem, 5vw, 3.5rem);
            font-weight: 400;
            color: #F5ECD5;
            position: relative;
            z-index: 1;
        }
        .page-hero h1 em { color: #C9973A; font-style: italic; }
        .page-hero p {
            font-size: 1.05rem;
            color: rgba(245,236,213,.6);
            margin-top: 16px;
            position: relative; z-index: 1;
        }
        .page-content {
            max-width: 900px;
            margin: 0 auto;
            padding: 80px 48px;
            font-family: 'Times New Roman', Times, serif;
        }
        .section-block {
            margin-bottom: 64px;
        }
        .section-block h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 400;
            color: var(--ivory);
            margin-bottom: 8px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(201,151,58,.3);
        }
        .section-block h2 span { color: #C9973A; }
        .section-block p {
            font-size: 1.05rem;
            line-height: 2;
            color: var(--t2);
            margin-top: 18px;
        }
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin: 48px 0;
        }
        .stat-card {
            background: var(--card);
            border: 1px solid rgba(201,151,58,.2);
            border-radius: 8px;
            padding: 28px;
            text-align: center;
        }
        .stat-card .num {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #C9973A;
            display: block;
        }
        .stat-card .lbl {
            font-size: .85rem;
            color: var(--t3);
            margin-top: 6px;
            font-family: sans-serif;
            letter-spacing: .1em;
            text-transform: uppercase;
        }
        .values-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 24px;
        }
        .value-item {
            background: var(--card2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 22px 24px;
        }
        .value-item h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            color: #C9973A;
            margin-bottom: 8px;
            font-weight: 400;
        }
        .value-item p {
            font-size: .95rem;
            color: var(--t2);
            line-height: 1.8;
            margin: 0;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #C9973A;
            font-family: sans-serif;
            font-size: .85rem;
            letter-spacing: .1em;
            text-decoration: none;
            margin-bottom: 48px;
            transition: gap .2s;
        }
        .back-link:hover { gap: 12px; }
        [data-theme="light"] .page-hero {
            background: linear-gradient(135deg, #FFF0DC 0%, #FFE8C8 60%, #FFF5E8 100%);
        }
        [data-theme="light"] .page-hero h1 { color: #1A0A00; }
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
        <li><a onclick="window.location.href='index.php#productsSection'">Sản Phẩm</a></li>
    </ul>
    <div class="nav-actions">
        <button class="theme-btn" onclick="toggleTheme()" title="Chuyển sáng/tối"><span id="themeIcon"></span></button>
    </div>
</nav>

<div class="page-hero">
    <h1>Về <em>Tây Lương Cửu</em></h1>
    <p>Hơn 28 năm gìn giữ tinh hoa rượu truyền thống Việt Nam</p>
</div>

<div class="page-content">
    <a href="index.php" class="back-link">← Quay về trang chủ</a>

    <div class="stats-row">
        <div class="stat-card">
            <span class="num">1995</span>
            <span class="lbl">Năm thành lập</span>
        </div>
        <div class="stat-card">
            <span class="num">28+</span>
            <span class="lbl">Năm kinh nghiệm</span>
        </div>
        <div class="stat-card">
            <span class="num">10.000+</span>
            <span class="lbl">Khách hàng tin tưởng</span>
        </div>
    </div>

    <div class="section-block">
        <h2>Câu chuyện <span>của chúng tôi</span></h2>
        <p>Tây Lương Cửu được thành lập năm 1995 tại Hà Nội, khởi nguồn từ niềm đam mê bảo tồn và phát huy nghề chưng cất rượu truyền thống của gia đình qua nhiều thế hệ. Từ một xưởng nhỏ với những bí quyết gia truyền, chúng tôi đã và đang mang đến những sản phẩm rượu thủ công chất lượng cao, giữ trọn hồn rượu Việt.</p>
        <p>Tên "Tây Lương Cửu" gợi nhớ hình ảnh những dòng rượu quý được ủ chín theo năm tháng — tinh tế, đằm thắm và đậm đà bản sắc. Mỗi chai rượu của chúng tôi là kết tinh của nguyên liệu thuần Việt, quy trình thủ công tỉ mỉ và tâm huyết của những người thợ lành nghề.</p>
        <p>Trải qua hơn 28 năm, Tây Lương Cửu tự hào phục vụ hơn 10.000 khách hàng trên khắp cả nước, từ những bữa tiệc gia đình ấm cúng đến các sự kiện doanh nghiệp trang trọng. Chúng tôi luôn đặt chất lượng và sự hài lòng của khách hàng lên hàng đầu.</p>
    </div>

    <div class="section-block">
        <h2>Sản phẩm <span>đặc trưng</span></h2>
        <p>Chúng tôi chuyên sản xuất các dòng rượu truyền thống Việt Nam gồm: Rượu Nếp Cẩm thơm ngọt đặc trưng, Rượu Thuốc Bắc 18 vị bồi bổ sức khỏe, Rượu Ngô Men Lá đặc sản vùng cao Hà Giang và các dòng rượu hoa quả tự nhiên. Tất cả đều được chưng cất hoàn toàn thủ công, không phẩm màu, không hương liệu nhân tạo.</p>
        <p>Nguyên liệu được tuyển chọn kỹ càng từ các vùng nông nghiệp sạch — nếp cái hoa vàng Hải Hậu, ngô nếp Đồng Văn, thảo dược thiên nhiên từ rừng núi miền Bắc. Quy trình lên men và chưng cất tuân thủ nghiêm ngặt theo bí quyết gia truyền, đảm bảo hương vị thuần khiết và an toàn tuyệt đối.</p>
    </div>

    <div class="section-block">
        <h2>Giá trị <span>cốt lõi</span></h2>
        <div class="values-grid">
            <div class="value-item">
                <h3>Chất lượng trên hết</h3>
                <p>Mỗi mẻ rượu đều được kiểm tra nghiêm ngặt trước khi đến tay khách hàng. Chúng tôi không bao giờ đánh đổi chất lượng lấy số lượng.</p>
            </div>
            <div class="value-item">
                <h3>Truyền thống & Bản sắc</h3>
                <p>Gìn giữ và phát huy nghề rượu truyền thống Việt Nam — đó là sứ mệnh và niềm tự hào của Tây Lương Cửu suốt hơn 28 năm.</p>
            </div>
            <div class="value-item">
                <h3>Uy tín & Minh bạch</h3>
                <p>Nguồn gốc nguyên liệu rõ ràng, quy trình sản xuất minh bạch. Khách hàng có thể trực tiếp tham quan xưởng sản xuất bất cứ lúc nào.</p>
            </div>
            <div class="value-item">
                <h3>Tận tâm phục vụ</h3>
                <p>Đội ngũ tư vấn chuyên nghiệp, am hiểu sâu về rượu, luôn sẵn sàng giúp khách hàng tìm được sản phẩm phù hợp nhất.</p>
            </div>
        </div>
    </div>

    <div class="section-block">
        <h2>Liên hệ <span>với chúng tôi</span></h2>
        <p>Hotline: <strong style="color:#C9973A;">092 878 7046</strong> — Hỗ trợ 7 ngày/tuần, 8:00 – 21:00</p>
        <p>Email: <strong style="color:#C9973A;">info@tayluongcuu.vn</strong></p>
        <p>Địa chỉ: Hà Nội, Việt Nam</p>
        <p>Chúng tôi luôn chào đón quý khách ghé thăm trực tiếp để được tư vấn và thử sản phẩm miễn phí.</p>
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