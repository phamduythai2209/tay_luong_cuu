-- ============================================================
-- FILE: database.sql
-- Tạo toàn bộ bảng + dữ liệu mẫu cho Tây Lương Cửu
-- Chạy 1 lần trong phpMyAdmin hoặc MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS tay_luong_cuu
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE tay_luong_cuu;

-- ── BẢNG SẢN PHẨM ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(255)    NOT NULL,
    short_name       VARCHAR(100),
    category         VARCHAR(100),
    alc              DECIMAL(4,1)    DEFAULT 0,
    volume           INT             DEFAULT 750,
    price            INT             NOT NULL,
    price_old        INT             DEFAULT NULL,
    origin           VARCHAR(255),
    flavor           VARCHAR(255),
    description      TEXT,
    image            VARCHAR(500)    DEFAULT NULL,
    badge            VARCHAR(20)     DEFAULT 'new',
    badge_text       VARCHAR(50)     DEFAULT 'Mới',
    is_active        TINYINT(1)      DEFAULT 1,
    stock            INT             DEFAULT 0,
    total_imported   INT             DEFAULT 0,
    total_exported   INT             DEFAULT 0,
    avg_import_price INT             DEFAULT 0,
    created_at       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active   (is_active),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── BẢNG ĐƠN HÀNG ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    order_code     VARCHAR(20)  UNIQUE,
    customer_name  VARCHAR(255) NOT NULL,
    phone          VARCHAR(20),
    address        TEXT,
    note           TEXT,
    total          BIGINT       NOT NULL DEFAULT 0,
    status         ENUM('Chờ duyệt','Đang giao','Đã giao','Đã hủy') DEFAULT 'Chờ duyệt',
    payment_method VARCHAR(50)  DEFAULT 'COD',
    created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status     (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── BẢNG CHI TIẾT ĐƠN HÀNG ─────────────────────────────────
CREATE TABLE IF NOT EXISTS order_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT NOT NULL,
    product_id INT NOT NULL,
    qty        INT NOT NULL DEFAULT 1,
    price      INT NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_order_id   (order_id),
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── BẢNG KHO HÀNG ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS inventory_log (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    type         ENUM('in','out') NOT NULL,
    product_id   INT NOT NULL,
    qty          INT NOT NULL,
    note         TEXT,
    supplier     VARCHAR(255),
    import_price INT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_type       (type),
    INDEX idx_product_id (product_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── BẢNG ADMIN ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admins (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100) UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,
    full_name  VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- DỮ LIỆU MẪU
-- ════════════════════════════════════════════════════════════

-- Admin mặc định: username=admin, password=123456
-- Hash bcrypt của "123456" — tạo đúng bằng: php setup_admin.php
INSERT IGNORE INTO admins (username, password, full_name)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Quản Trị Viên');
-- ⚠ Hash trên là của "password", KHÔNG phải "123456"
-- Chạy setup_admin.php để tạo admin đúng!

-- Sản phẩm mẫu
INSERT IGNORE INTO products
    (id, name, short_name, category, alc, volume, price, price_old, origin, flavor,
     description, badge, badge_text, is_active, stock, total_imported, avg_import_price)
VALUES
(1, 'Rượu Nếp Cẩm Tây Lương Cửu', 'Nếp Cẩm', 'Rượu Nếp', 29.5, 750, 680000, NULL,
 'Miền Bắc Việt Nam', 'Ngọt thanh, hương thơm nồng ấm',
 'Vị ngọt thanh của nếp cẩm, màu đỏ tím quyến rũ. Phù hợp làm quà tặng hoặc thưởng thức trong các dịp lễ tết.',
 'hot', 'Bán Chạy', 1, 920, 920, 420000),

(2, 'Rượu Thuốc Bắc Cổ Phương 18 Vị', 'Thuốc Bắc', 'Rượu Thuốc', 35.0, 700, 950000, 1200000,
 'Cổ Phương Ngàn Năm', 'Đậm đà, hương thuốc bắc đặc trưng',
 'Tam thất, kỷ tử, nhục quế — bí phương 18 vị thuốc quý. Bổ thận, ích khí, an thần, tăng cường sức khoẻ.',
 'new', 'Mới', 1, 560, 560, 620000),

(3, 'Rượu Ngô Men Lá Hà Giang', 'Ngô Men Lá', 'Rượu Ngô', 40.0, 500, 520000, NULL,
 'Hà Giang · Vùng Cao', 'Hương núi rừng hoang sơ, vị êm dịu',
 'Ngô nương vùng cao Hà Giang, lên men bằng lá rừng cổ thụ. Đặc sản vùng cao được nhiều người yêu thích.',
 'limited', 'Limited', 1, 360, 360, 340000);

-- Đơn hàng mẫu
INSERT IGNORE INTO orders (id, order_code, customer_name, phone, address, total, status, payment_method)
VALUES
(1, '#TLC0247', 'Nguyễn Văn An',  '0901234567', '24 Lý Thường Kiệt, Hà Nội',     1360000, 'Đã giao',   'COD'),
(2, '#TLC0246', 'Trần Thị Bình',  '0912345678', '156 Điện Biên Phủ, TP.HCM',     950000,  'Đang giao', 'MoMo'),
(3, '#TLC0245', 'Lê Văn Cường',   '0923456789', '88 Trần Hưng Đạo, Hà Nội',      1560000, 'Chờ duyệt', 'COD'),
(4, '#TLC0244', 'Phạm Thị Dung',  '0934567890', '45 Nguyễn Văn Linh, Đà Nẵng',   2150000, 'Đã giao',   'VNPay'),
(5, '#TLC0243', 'Hoàng Văn Em',   '0945678901', '12 Lạch Tray, Hải Phòng',        1900000, 'Đã hủy',    'COD');

-- Chi tiết đơn
INSERT IGNORE INTO order_items (order_id, product_id, qty, price) VALUES
(1, 1, 2, 680000),
(2, 2, 1, 950000),
(3, 3, 3, 520000),
(4, 1, 1, 680000),
(4, 2, 1, 950000),
(5, 2, 2, 950000);

-- Log kho mẫu
INSERT IGNORE INTO inventory_log (type, product_id, qty, note, supplier, import_price) VALUES
('in', 1, 500, 'Nhập lô đầu tháng', 'Xưởng Tây Lương',  420000),
('in', 2, 300, 'Nhập lô đầu tháng', 'Xưởng Tây Lương',  620000),
('in', 3, 200, 'Nhập từ Hà Giang',  'HTX Đồng Văn',     340000),
('out',1,  15, 'Đơn #TLC0220',      NULL, 0),
('out',2,   8, 'Đơn #TLC0221',      NULL, 0);
