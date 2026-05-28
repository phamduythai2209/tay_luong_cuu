<?php require_once __DIR__ . '/config/database.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hướng Dẫn Mua Hàng — Tây Lương Cửu</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .page-hero { background:linear-gradient(135deg,#0A0202 0%,#1A0808 60%,#0A0A0A 100%);padding:100px 64px 80px;text-align:center;position:relative;overflow:hidden; }
        .page-hero::before { content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(201,151,58,.15) 1px,transparent 1px);background-size:32px 32px;opacity:.5; }
        .page-hero h1 { font-family:'Playfair Display',serif;font-size:clamp(2rem,5vw,3.5rem);font-weight:400;color:#F5ECD5;position:relative;z-index:1; }
        .page-hero h1 em { color:#C9973A;font-style:italic; }
        .page-hero p { font-size:1.05rem;color:rgba(245,236,213,.6);margin-top:16px;position:relative;z-index:1; }
        .page-content { max-width:900px;margin:0 auto;padding:80px 48px;font-family:'Times New Roman',Times,serif; }
        .guide-block { margin-bottom:56px; }
        .guide-block h2 { font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:400;color:var(--ivory);margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid rgba(201,151,58,.3); }
        .guide-block h2 span { color:#C9973A; }
        .guide-block p { font-size:1.05rem;line-height:2;color:var(--t2);margin-bottom:12px; }
        .steps { display:flex;flex-direction:column;gap:16px;margin-top:20px; }
        .step { display:flex;gap:20px;align-items:flex-start;background:var(--card2);border:1px solid var(--border);border-radius:10px;padding:20px 24px; }
        .step-num { width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#9A6A10,#C9973A);color:#0A0202;display:flex;align-items:center;justify-content:center;font-family:'Playfair Display',serif;font-size:1.2rem;font-weight:600;flex-shrink:0; }
        .step-body h3 { font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--ivory);font-weight:400;margin-bottom:6px; }
        .step-body p { font-size:.95rem;color:var(--t2);line-height:1.8;margin:0; }
        .pay-methods { display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:20px; }
        .pay-card { background:var(--card2);border:1px solid var(--border);border-radius:10px;padding:22px 24px; }
        .pay-card h3 { font-family:'Playfair Display',serif;font-size:1.1rem;color:#C9973A;font-weight:400;margin-bottom:10px; }
        .pay-card p { font-size:.95rem;color:var(--t2);line-height:1.8;margin:0; }
        .shipping-table { width:100%;border-collapse:collapse;margin-top:20px;font-size:.95rem; }
        .shipping-table th { padding:12px 16px;text-align:left;background:rgba(201,151,58,.1);color:#C9973A;border:1px solid rgba(201,151,58,.2);font-family:'Playfair Display',serif;font-weight:400; }
        .shipping-table td { padding:12px 16px;border:1px solid var(--border2);color:var(--t2);line-height:1.7; }
        .shipping-table tr:hover td { background:rgba(255,255,255,.02); }
        .highlight-box { background:rgba(201,151,58,.08);border:1px solid rgba(201,151,58,.25);border-radius:8px;padding:20px 24px;margin:20px 0; }
        .highlight-box p { color:var(--ivory);margin:0;font-size:1rem;line-height:1.9; }
        .faq-item { border-bottom:1px solid var(--border2);padding:18px 0; }
        .faq-item h3 { font-family:'Times New Roman',Times,serif;font-size:1rem;color:var(--ivory);font-weight:600;margin-bottom:8px; }
        .faq-item p { font-size:.95rem;color:var(--t2);line-height:1.8;margin:0; }
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
    <h1>Hướng Dẫn <em>Mua Hàng</em></h1>
    <p>Đặt hàng dễ dàng — nhận rượu tận nơi chỉ trong vài bước</p>
</div>

<div class="page-content">
    <a href="index.php" class="back-link">← Quay về trang chủ</a>

    <div class="guide-block">
        <h2>Các bước <span>đặt hàng</span></h2>
        <div class="steps">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-body">
                    <h3>Chọn sản phẩm</h3>
                    <p>Duyệt qua danh sách sản phẩm, bấm vào từng chai để xem thông tin chi tiết — độ cồn, dung tích, hương vị, xuất xứ. Bấm "Thêm vào giỏ hàng" khi đã ưng ý.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-body">
                    <h3>Kiểm tra giỏ hàng</h3>
                    <p>Bấm biểu tượng giỏ hàng ở góc trên phải để xem lại các sản phẩm đã chọn. Có thể thay đổi số lượng hoặc xóa sản phẩm trước khi thanh toán.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-body">
                    <h3>Chọn phương thức thanh toán</h3>
                    <p>Chọn "Thanh toán Online" (chuyển khoản qua QR SePay) hoặc "Trả sau COD" (thanh toán khi nhận hàng). Điền đầy đủ thông tin giao hàng.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">4</div>
                <div class="step-body">
                    <h3>Xác nhận đơn hàng</h3>
                    <p>Sau khi đặt hàng thành công, quý khách nhận mã đơn hàng. Đội ngũ sẽ liên hệ xác nhận trong vòng 30 phút trong giờ làm việc.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">5</div>
                <div class="step-body">
                    <h3>Nhận hàng</h3>
                    <p>Sản phẩm được đóng gói cẩn thận, giao hàng toàn quốc. Quý khách kiểm tra hàng trước khi ký nhận. Nếu có vấn đề, liên hệ ngay hotline để được hỗ trợ.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="guide-block">
        <h2>Phương thức <span>thanh toán</span></h2>
        <div class="pay-methods">
            <div class="pay-card">
                <h3>Thanh toán Online (SePay)</h3>
                <p>Quét mã QR bằng ứng dụng ngân hàng bất kỳ. Đơn hàng xác nhận tự động ngay sau khi chuyển khoản thành công. Hỗ trợ tất cả ngân hàng Việt Nam.</p>
            </div>
            <div class="pay-card">
                <h3>Trả sau khi nhận hàng (COD)</h3>
                <p>Thanh toán tiền mặt trực tiếp cho shipper khi nhận hàng. Áp dụng toàn quốc. Phù hợp với khách hàng muốn kiểm tra sản phẩm trước khi trả tiền.</p>
            </div>
        </div>
    </div>

    <div class="guide-block">
        <h2>Thông tin <span>giao hàng</span></h2>
        <table class="shipping-table">
            <tr>
                <th>Khu vực</th>
                <th>Thời gian</th>
                <th>Phí ship</th>
            </tr>
            <tr>
                <td>Nội thành Hà Nội</td>
                <td>2 – 4 giờ (trong ngày)</td>
                <td>Miễn phí đơn từ 500.000đ</td>
            </tr>
            <tr>
                <td>Ngoại thành Hà Nội</td>
                <td>1 – 2 ngày</td>
                <td>30.000 – 50.000đ</td>
            </tr>
            <tr>
                <td>Các tỉnh miền Bắc</td>
                <td>1 – 3 ngày</td>
                <td>40.000 – 70.000đ</td>
            </tr>
            <tr>
                <td>Miền Trung & Miền Nam</td>
                <td>3 – 5 ngày</td>
                <td>60.000 – 100.000đ</td>
            </tr>
        </table>
        <div class="highlight-box" style="margin-top:20px;">
            <p>Miễn phí giao hàng toàn quốc cho đơn hàng từ 1.000.000đ. Sản phẩm được đóng gói bằng xốp chống sốc và hộp carton chắc chắn, đảm bảo an toàn tuyệt đối trong suốt quá trình vận chuyển.</p>
        </div>
    </div>

    <div class="guide-block">
        <h2>Câu hỏi <span>thường gặp</span></h2>
        <div class="faq-item">
            <h3>Tôi có thể đặt hàng số lượng lớn để làm quà tặng không?</h3>
            <p>Hoàn toàn được! Chúng tôi nhận đơn số lượng lớn và có dịch vụ đóng gói quà tặng cao cấp riêng. Liên hệ hotline 092 878 7046 để được báo giá và tư vấn trực tiếp.</p>
        </div>
        <div class="faq-item">
            <h3>Sản phẩm có hạn sử dụng không?</h3>
            <p>Rượu truyền thống không có hạn sử dụng cố định nếu được bảo quản đúng cách (nơi thoáng mát, tránh ánh nắng trực tiếp). Rượu càng lâu năm thường càng ngon hơn.</p>
        </div>
        <div class="faq-item">
            <h3>Tôi có thể đến mua trực tiếp tại cửa hàng không?</h3>
            <p>Có! Quý khách có thể ghé trực tiếp tại địa chỉ Hà Nội để xem và chọn sản phẩm, đồng thời được tư vấn và thử nếm miễn phí. Vui lòng gọi trước để đặt lịch.</p>
        </div>
        <div class="faq-item">
            <h3>Làm thế nào để theo dõi đơn hàng?</h3>
            <p>Sau khi đặt hàng, quý khách đăng nhập vào tài khoản và vào mục "Đơn hàng của tôi" để theo dõi trạng thái. Chúng tôi cũng cập nhật qua điện thoại khi có thay đổi.</p>
        </div>
        <div class="faq-item">
            <h3>Rượu có được kiểm định chất lượng không?</h3>
            <p>Tất cả sản phẩm của Tây Lương Cửu đều được kiểm định an toàn thực phẩm đầy đủ. Chúng tôi sản xuất hoàn toàn tự nhiên, không chất bảo quản, không phẩm màu công nghiệp.</p>
        </div>
    </div>

    <div class="guide-block">
        <h2>Cần hỗ trợ <span>thêm?</span></h2>
        <p>Liên hệ đội ngũ tư vấn của chúng tôi:</p>
        <p>Hotline: <strong style="color:#C9973A;">092 878 7046</strong> — 8:00 – 21:00 mỗi ngày</p>
        <p>Email: <strong style="color:#C9973A;">info@tayluongcuu.vn</strong></p>
        <p>Hoặc chat trực tiếp với trợ lý AI của chúng tôi ngay trên trang chủ!</p>
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