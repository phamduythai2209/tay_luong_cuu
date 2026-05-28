<?php
// ============================================================
// FILE: admin/orders.php — Quản lý đơn hàng
// ============================================================
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn Hàng — Tây Lương Cửu Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script>
        // Đồng bộ theme với trang chủ
        (function() {
            var t = localStorage.getItem('tlc-theme') || 'dark';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="admin-main">

        <div class="admin-topbar">
            <div>
                <div class="topbar-title">Quản Lý Đơn Hàng</div>
                <div class="topbar-date" id="todayDate"></div>
            </div>
            <div class="topbar-right">
                <button onclick="toggleAdminTheme()" title="Đổi giao diện"
                    style="background:none;border:1px solid var(--border);border-radius:20px;
                           padding:6px 14px;color:var(--t2);cursor:pointer;font-size:.85rem;">
                    🌓 Giao diện
                </button>
                <span class="topbar-user">👤 <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                <a href="#" class="btn-logout" onclick="doLogout(event)">Đăng xuất</a>
            </div>
        </div>

        <div class="admin-content">

            <!-- Bộ lọc -->
            <div class="filter-bar">
                <input class="search-input" id="searchOrder" placeholder="🔍 Tìm mã đơn, tên khách, SĐT..." oninput="filterOrders()">
                <div class="filter-tabs" id="filterTabs">
                    <button class="filter-tab active" data-status="" onclick="setStatusFilter(this, '')">Tất cả</button>
                    <button class="filter-tab" data-status="Chờ duyệt"   onclick="setStatusFilter(this, 'Chờ duyệt')">⏳ Chờ duyệt</button>
                    <button class="filter-tab" data-status="Đang giao"   onclick="setStatusFilter(this, 'Đang giao')">🚚 Đang giao</button>
                    <button class="filter-tab" data-status="Đã giao"     onclick="setStatusFilter(this, 'Đã giao')">✅ Đã giao</button>
                    <button class="filter-tab" data-status="Đã hủy"      onclick="setStatusFilter(this, 'Đã hủy')">❌ Đã hủy</button>
                </div>
            </div>

            <div class="table-card">
                <div class="table-card-head">
                    <span>Danh Sách Đơn Hàng (<span id="orderCount">0</span>)</span>
                    <button class="btn-add" onclick="openCreateOrderModal()">+ Tạo Đơn</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Mã Đơn</th>
                                <th>Khách Hàng</th>
                                <th>Sản Phẩm</th>
                                <th>Tổng Tiền</th>
                                <th>Thanh Toán</th>
                                <th>Trạng Thái</th>
                                <th>Ngày Đặt</th>
                                <th>Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody id="ordersTableBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div>

<!-- ══ MODAL CHI TIẾT ĐƠN HÀNG ══ -->
<div class="modal-overlay" id="orderDetailModal">
    <div class="modal-box" style="width:580px;">
        <div class="modal-head">
            <h3>Chi Tiết Đơn Hàng — <span id="detailCode" class="text-gold"></span></h3>
            <button class="modal-close" onclick="closeModal('orderDetailModal')">✕</button>
        </div>
        <div class="modal-body" id="orderDetailBody"></div>
        <div class="modal-foot">
            <button class="btn-cancel" onclick="closeModal('orderDetailModal')">Đóng</button>
            <button class="btn-save" id="btnUpdateStatus" onclick="updateStatus()">Cập Nhật Trạng Thái</button>
        </div>
    </div>
</div>

<!-- ══ MODAL TẠO ĐƠN ══ -->
<div class="modal-overlay" id="createOrderModal">
    <div class="modal-box" style="width:520px;">
        <div class="modal-head">
            <h3>+ Tạo Đơn Hàng Mới</h3>
            <button class="modal-close" onclick="closeModal('createOrderModal')">✕</button>
        </div>
        <div class="modal-body">
            <div class="form-row">
                <div class="field"><label>Họ Tên *</label><input class="form-input" id="coName" placeholder="Nguyễn Văn A"></div>
                <div class="field"><label>Điện Thoại *</label><input class="form-input" id="coPhone" placeholder="0901 234 567"></div>
            </div>
            <div class="field"><label>Địa Chỉ *</label><input class="form-input" id="coAddress" placeholder="Số nhà, đường, quận, tỉnh/TP"></div>
            <div class="form-row">
                <div class="field">
                    <label>Sản Phẩm *</label>
                    <select class="form-select" id="coProduct" onchange="updateOrderPreview()"></select>
                </div>
                <div class="field">
                    <label>Số Lượng *</label>
                    <input class="form-input" id="coQty" type="number" min="1" value="1" oninput="updateOrderPreview()">
                </div>
            </div>
            <div class="field"><label>Ghi Chú</label><textarea class="form-textarea" id="coNote" rows="2" placeholder="Ghi chú đơn hàng..."></textarea></div>
            <div class="field"><label>Phương Thức</label>
                <select class="form-select" id="coPayment">
                    <option value="COD">COD (Trả khi nhận hàng)</option>
                    <option value="Chuyển khoản">Chuyển khoản</option>
                    <option value="MoMo">MoMo</option>
                    <option value="VNPay">VNPay</option>
                </select>
            </div>
            <div class="order-preview" id="orderPreview" style="display:none;">
                <span>Tổng tiền:</span>
                <span class="text-gold" id="previewTotal">₫0</span>
            </div>
        </div>
        <div class="modal-foot">
            <button class="btn-cancel" onclick="closeModal('createOrderModal')">Hủy</button>
            <button class="btn-save" id="btnCreateOrder" onclick="createOrder()">✅ Tạo Đơn Hàng</button>
        </div>
    </div>
</div>

<div id="toast" class="toast"></div>
<script src="../assets/js/admin.js"></script>
<script>
let allOrders    = [];
let filteredOrders = [];
let statusFilter = '';
let currentOrderId = 0;
let products     = [];

document.getElementById('todayDate').textContent =
    new Date().toLocaleDateString('vi-VN', {weekday:'long',year:'numeric',month:'long',day:'numeric'});

const STATUS_CLASS = { 'Chờ duyệt':'warning', 'Đang giao':'info', 'Đã giao':'success', 'Đã hủy':'danger' };
const STATUS_CYCLE = ['Chờ duyệt', 'Đang giao', 'Đã giao'];

// ── Load đơn hàng ─────────────────────────────────────────
async function loadOrders() {
    try {
        allOrders = await adminFetch('../api/orders.php');
        applyFilters();
    } catch(e) { showToast('❌ ' + e.message, 'error'); }
}

function setStatusFilter(btn, status) {
    statusFilter = status;
    document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilters();
}

function applyFilters() {
    const q = document.getElementById('searchOrder').value.toLowerCase();
    filteredOrders = allOrders.filter(o => {
        const matchStatus = !statusFilter || o.status === statusFilter;
        const matchSearch = !q || o.order_code.toLowerCase().includes(q)
            || o.customer_name.toLowerCase().includes(q)
            || (o.phone||'').includes(q);
        return matchStatus && matchSearch;
    });
    renderOrders(filteredOrders);
}

function filterOrders() { applyFilters(); }

function renderOrders(data) {
    document.getElementById('orderCount').textContent = data.length;
    const tbody = document.getElementById('ordersTableBody');
    if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted" style="padding:40px;">Không có đơn hàng nào</td></tr>';
        return;
    }
    tbody.innerHTML = data.map(o => `
        <tr>
            <td class="text-gold" style="cursor:pointer;" onclick="viewDetail(${o.id})">${o.order_code}</td>
            <td>
                <div style="color:#F8F0E0;">${o.customer_name}</div>
                <div style="color:#6B4030;font-size:.7rem;">${o.phone||''}</div>
            </td>
            <td style="max-width:160px;font-size:.78rem;color:#9A7060;">${o.product_summary||'—'}</td>
            <td class="text-gold">₫${Number(o.total).toLocaleString('vi-VN')}</td>
            <td style="font-size:.78rem;">${o.payment_method||'COD'}</td>
            <td><span class="badge badge-${STATUS_CLASS[o.status]||'info'}">${o.status}</span></td>
            <td style="font-size:.75rem;color:#6B4030;">${formatDate(o.created_at)}</td>
            <td>
                <div class="action-btns">
                    <button class="act-btn" onclick="viewDetail(${o.id})" title="Chi tiết">👁</button>
                    <button class="act-btn" onclick="quickNextStatus(${o.id}, '${o.status}')" title="Chuyển trạng thái tiếp theo">🔄</button>
                    <button class="act-btn danger" onclick="deleteOrder(${o.id})" title="Xóa">🗑</button>
                </div>
            </td>
        </tr>`).join('');
}

// ── Xem chi tiết ─────────────────────────────────────────
async function viewDetail(id) {
    currentOrderId = id;
    try {
        const o = await adminFetch('../api/orders.php?id=' + id);
        document.getElementById('detailCode').textContent = o.order_code;

        const statusOptions = ['Chờ duyệt','Đang giao','Đã giao','Đã hủy']
            .map(s => `<option value="${s}" ${o.status===s?'selected':''}>${s}</option>`).join('');

        const itemsHtml = (o.items||[]).map(i => `
            <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(201,151,58,.08);">
                <span style="color:#9A7060;">${i.name||''} × ${i.qty}</span>
                <span class="text-gold">₫${Number(i.price * i.qty).toLocaleString('vi-VN')}</span>
            </div>`).join('');

        document.getElementById('orderDetailBody').innerHTML = `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                <div><label class="detail-label">Khách Hàng</label><div class="detail-val">${o.customer_name}</div></div>
                <div><label class="detail-label">Điện Thoại</label><div class="detail-val">${o.phone||'—'}</div></div>
                <div style="grid-column:1/-1;"><label class="detail-label">Địa Chỉ</label><div class="detail-val">${o.address||'—'}</div></div>
                <div><label class="detail-label">Thanh Toán</label><div class="detail-val">${o.payment_method||'COD'}</div></div>
                <div><label class="detail-label">Ngày Đặt</label><div class="detail-val">${formatDate(o.created_at)}</div></div>
                ${o.note ? `<div style="grid-column:1/-1;"><label class="detail-label">Ghi Chú</label><div class="detail-val">${o.note}</div></div>` : ''}
            </div>
            <div style="margin-bottom:20px;">${itemsHtml}</div>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-top:1px solid rgba(201,151,58,.2);">
                <strong style="font-size:.8rem;letter-spacing:.1em;text-transform:uppercase;color:#6B4030;">Tổng Cộng</strong>
                <strong class="text-gold" style="font-size:1.2rem;">₫${Number(o.total).toLocaleString('vi-VN')}</strong>
            </div>
            <div class="field" style="margin-top:16px;">
                <label>Cập Nhật Trạng Thái</label>
                <select class="form-select" id="newStatus">${statusOptions}</select>
            </div>`;

        openModal('orderDetailModal');
    } catch(e) { showToast('❌ ' + e.message, 'error'); }
}

async function updateStatus() {
    const status = document.getElementById('newStatus').value;
    try {
        await adminFetch('../api/orders.php?id=' + currentOrderId, {
            method: 'PUT', body: JSON.stringify({ status })
        });
        showToast('✅ Đã cập nhật trạng thái: ' + status);
        closeModal('orderDetailModal');
        await loadOrders();
    } catch(e) { showToast('❌ ' + e.message, 'error'); }
}

async function quickNextStatus(id, current) {
    const idx  = STATUS_CYCLE.indexOf(current);
    if (idx < 0 || idx >= STATUS_CYCLE.length - 1) { showToast('⚠ Không thể chuyển tiếp từ trạng thái này', 'warn'); return; }
    const next = STATUS_CYCLE[idx + 1];
    if (!confirm(`Chuyển đơn sang "${next}"?`)) return;
    try {
        await adminFetch('../api/orders.php?id=' + id, { method:'PUT', body: JSON.stringify({ status: next }) });
        showToast('✅ Chuyển sang: ' + next);
        await loadOrders();
    } catch(e) { showToast('❌ ' + e.message, 'error'); }
}

async function deleteOrder(id) {
    if (!confirm('Xóa đơn hàng này?')) return;
    try {
        await adminFetch('../api/orders.php?id=' + id, { method:'DELETE' });
        showToast('✅ Đã xóa đơn hàng');
        await loadOrders();
    } catch(e) { showToast('❌ ' + e.message, 'error'); }
}

// ── Tạo đơn thủ công ─────────────────────────────────────
async function openCreateOrderModal() {
    try {
        products = await adminFetch('../api/products.php');
        const sel = document.getElementById('coProduct');
        sel.innerHTML = products.filter(p=>p.stock>0).map(p =>
            `<option value="${p.id}">${p.name} — ₫${Number(p.price).toLocaleString('vi-VN')}</option>`
        ).join('');
        ['coName','coPhone','coAddress','coNote'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('coQty').value = 1;
        document.getElementById('orderPreview').style.display = 'none';
        openModal('createOrderModal');
    } catch(e) { showToast('❌ ' + e.message, 'error'); }
}

function updateOrderPreview() {
    const pid = +document.getElementById('coProduct').value;
    const qty = +document.getElementById('coQty').value || 0;
    const p   = products.find(x => x.id === pid);
    if (!p || !qty) { document.getElementById('orderPreview').style.display = 'none'; return; }
    document.getElementById('previewTotal').textContent = '₫' + Number(p.price * qty).toLocaleString('vi-VN');
    document.getElementById('orderPreview').style.display = 'flex';
}

async function createOrder() {
    const name    = document.getElementById('coName').value.trim();
    const phone   = document.getElementById('coPhone').value.trim();
    const address = document.getElementById('coAddress').value.trim();
    const pid     = +document.getElementById('coProduct').value;
    const qty     = +document.getElementById('coQty').value;
    if (!name)    { showToast('⚠ Nhập họ tên!', 'warn'); return; }
    if (!phone)   { showToast('⚠ Nhập điện thoại!', 'warn'); return; }
    if (!address) { showToast('⚠ Nhập địa chỉ!', 'warn'); return; }
    if (!qty)     { showToast('⚠ Nhập số lượng!', 'warn'); return; }

    const btn = document.getElementById('btnCreateOrder');
    btn.disabled = true; btn.textContent = 'Đang tạo...';
    try {
        const res = await adminFetch('../api/orders.php', {
            method: 'POST',
            body: JSON.stringify({
                customer_name: name, phone, address,
                note: document.getElementById('coNote').value,
                payment_method: document.getElementById('coPayment').value,
                items: [{ product_id: pid, qty }]
            })
        });
        showToast('✅ Tạo đơn ' + res.order_code + ' thành công!');
        closeModal('createOrderModal');
        await loadOrders();
    } catch(e) { showToast('❌ ' + e.message, 'error'); }
    finally { btn.disabled = false; btn.textContent = '✅ Tạo Đơn Hàng'; }
}

// ── Helpers ───────────────────────────────────────────────
function formatDate(str) {
    if (!str) return '—';
    return new Date(str).toLocaleDateString('vi-VN', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
}
async function doLogout(e) {
    e.preventDefault();
    await adminFetch('../api/auth.php', { method:'POST', body: JSON.stringify({ action:'logout' }) });
    window.location.href = 'login.php';
}

loadOrders();
</script>
<script>
function toggleAdminTheme() {
    var t = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('tlc-theme', t);
}
</script>
</body>
</html>