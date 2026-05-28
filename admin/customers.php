<?php
// ============================================================
// FILE: admin/customers.php — Quản lý khách hàng
// ============================================================
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khách Hàng — Tây Lương Cửu Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script>(function(){var t=localStorage.getItem('tlc-theme')||'dark';document.documentElement.setAttribute('data-theme',t);})();</script>
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <div class="topbar-title">Quản Lý Khách Hàng</div>
                <div class="topbar-date" id="todayDate"></div>
            </div>
            <div class="topbar-right">
                <button onclick="toggleAdminTheme()"
                    style="background:none;border:1px solid var(--border);border-radius:20px;padding:6px 14px;color:var(--t2);cursor:pointer;font-size:.85rem;">
                    🌓 Giao diện
                </button>
                <span class="topbar-user">👤 <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></span>
                <a href="login.php?logout=1" class="btn-logout" onclick="doLogout(event)">Đăng xuất</a>
            </div>
        </div>

        <div class="admin-content">

            <!-- STAT CARDS -->
            <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:22px;">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-val" id="sTotalCust">—</div>
                    <div class="stat-lbl">Tổng Khách Hàng</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-val" id="sActiveCust">—</div>
                    <div class="stat-lbl">Đang Hoạt Động</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-val" id="sOrderedCust">—</div>
                    <div class="stat-lbl">Đã Đặt Hàng</div>
                </div>
            </div>

            <!-- FILTER + SEARCH -->
            <div class="filter-bar">
                <div class="filter-tabs">
                    <button class="filter-tab active" onclick="setFilter('all',this)">Tất Cả</button>
                    <button class="filter-tab" onclick="setFilter('active',this)">Hoạt Động</button>
                    <button class="filter-tab" onclick="setFilter('locked',this)">Đã Khoá</button>
                    <button class="filter-tab" onclick="setFilter('ordered',this)">Đã Đặt Hàng</button>
                </div>
                <input class="search-input" id="searchInput" type="text"
                    placeholder="🔍 Tìm tên, email, SĐT..." oninput="renderTable()">
            </div>

            <!-- TABLE -->
            <div class="table-card">
                <div class="table-card-head">
                    <span>Danh Sách Khách Hàng</span>
                    <span id="custCount" style="font-family:'Inter',sans-serif;font-size:.72rem;color:var(--t3);text-transform:none;letter-spacing:0;"></span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Khách Hàng</th>
                                <th>SĐT</th>
                                <th>Đơn Hàng</th>
                                <th>Tổng Chi Tiêu</th>
                                <th>Ngày ĐK</th>
                                <th>Trạng Thái</th>
                                <th>Thao Tác</th>
                            </tr>
                        </thead>
                        <tbody id="custBody">
                            <tr><td colspan="7" class="text-center text-muted" style="padding:40px;">⏳ Đang tải...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- MODAL CHI TIẾT -->
<div class="modal-overlay" id="custModal">
    <div class="modal-box" style="width:600px;">
        <div class="modal-head">
            <h3 id="modalTitle">Chi Tiết Khách Hàng</h3>
            <button class="modal-close" onclick="closeModal()">✕</button>
        </div>
        <div class="modal-body">
            <div class="info-grid" id="modalInfo"></div>
            <div style="margin-top:18px;">
                <div style="font-size:.6rem;letter-spacing:.18em;text-transform:uppercase;color:var(--gold);
                            padding-bottom:10px;border-bottom:1px solid var(--border2);margin-bottom:12px;">
                    📦 Đơn Hàng Gần Nhất
                </div>
                <div id="modalOrders"></div>
            </div>
        </div>
        <div class="modal-foot">
            <button class="btn-cancel" onclick="closeModal()">Đóng</button>
            <button class="btn-save" id="btnLock" onclick="doToggleLock()">🔒 Khoá</button>
        </div>
    </div>
</div>

<div id="toast" class="toast"></div>

<script src="../assets/js/admin.js"></script>
<script>
const BASE = '../';
let allCustomers = [];
let currentFilter = 'all';
let currentCustId = null;

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('todayDate').textContent =
        new Date().toLocaleDateString('vi-VN', {weekday:'long',year:'numeric',month:'long',day:'numeric'});
    loadCustomers();
});

// ── LOAD ──────────────────────────────────────────────────────
async function loadCustomers() {
    try {
        const res  = await fetch(BASE + 'api/customers.php');
        const data = await res.json();
        allCustomers = data.customers || [];
        document.getElementById('sTotalCust').textContent   = allCustomers.length;
        document.getElementById('sActiveCust').textContent  = allCustomers.filter(c => c.is_active).length;
        document.getElementById('sOrderedCust').textContent = allCustomers.filter(c => c.order_count > 0).length;
        renderTable();
    } catch(e) {
        document.getElementById('custBody').innerHTML =
            '<tr><td colspan="7" class="text-center" style="color:#e07070;padding:40px;">❌ Lỗi tải dữ liệu</td></tr>';
    }
}

// ── FILTER / RENDER ───────────────────────────────────────────
function setFilter(f, btn) {
    currentFilter = f;
    document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    renderTable();
}

function renderTable() {
    const q = document.getElementById('searchInput').value.toLowerCase().trim();
    let list = [...allCustomers];
    if (currentFilter === 'active')  list = list.filter(c => c.is_active == 1);
    if (currentFilter === 'locked')  list = list.filter(c => c.is_active == 0);
    if (currentFilter === 'ordered') list = list.filter(c => c.order_count > 0);
    if (q) list = list.filter(c =>
        (c.name||'').toLowerCase().includes(q) ||
        (c.email||'').toLowerCase().includes(q) ||
        (c.phone||'').includes(q)
    );

    document.getElementById('custCount').textContent = list.length + ' khách hàng';

    if (!list.length) {
        document.getElementById('custBody').innerHTML =
            '<tr><td colspan="7" class="text-center text-muted" style="padding:40px;">Không tìm thấy khách hàng nào</td></tr>';
        return;
    }

    document.getElementById('custBody').innerHTML = list.map(c => {
        const initial  = (c.name || '?').trim().split(' ').pop()[0].toUpperCase();
        const isActive = c.is_active == 1;
        const joined   = c.created_at ? new Date(c.created_at).toLocaleDateString('vi-VN') : '—';
        return `<tr style="cursor:pointer;" onclick="openModal(${c.id})">
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="cust-avatar">${initial}</div>
                    <div>
                        <div class="cust-name">${esc(c.name)}</div>
                        <div class="cust-email">${esc(c.email||'—')}</div>
                    </div>
                </div>
            </td>
            <td>${esc(c.phone||'—')}</td>
            <td>
                <span style="background:rgba(212,168,75,.12);color:var(--gold);padding:2px 10px;
                             border-radius:20px;font-size:.78rem;font-weight:600;">
                    ${c.order_count} đơn
                </span>
            </td>
            <td style="color:var(--gold);font-weight:500;">₫${Number(c.total_spent||0).toLocaleString('vi-VN')}</td>
            <td style="color:var(--t3);font-size:.8rem;">${joined}</td>
            <td>
                ${isActive
                    ? '<span class="badge badge-success">Hoạt động</span>'
                    : '<span class="badge badge-danger">Đã khoá</span>'}
            </td>
            <td onclick="event.stopPropagation()">
                <div class="action-btns">
                    <button class="act-btn" title="Xem chi tiết" onclick="openModal(${c.id})">👁</button>
                    <button class="act-btn ${isActive?'danger':'success'}"
                        title="${isActive?'Khoá':'Mở khoá'}"
                        onclick="quickToggle(${c.id},${isActive?0:1})">
                        ${isActive ? '🔒' : '🔓'}
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

// ── MODAL ─────────────────────────────────────────────────────
async function openModal(id) {
    currentCustId = id;
    const c = allCustomers.find(x => x.id == id);
    if (!c) return;

    const isActive = c.is_active == 1;
    document.getElementById('modalTitle').textContent = c.name;
    document.getElementById('custModal').classList.add('open');

    const btn = document.getElementById('btnLock');
    btn.textContent      = isActive ? '🔒 Khoá Tài Khoản' : '🔓 Mở Khoá';
    btn.style.background = isActive ? '#7A1A1A' : 'var(--gold)';
    btn.style.color      = isActive ? '#fff' : '#111';

    document.getElementById('modalInfo').innerHTML = `
        <div class="info-item">
            <div class="info-label">Họ Tên</div>
            <div class="info-val">${esc(c.name)}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Email</div>
            <div class="info-val">${esc(c.email||'—')}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Số Điện Thoại</div>
            <div class="info-val">${esc(c.phone||'—')}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Ngày Đăng Ký</div>
            <div class="info-val">${c.created_at ? new Date(c.created_at).toLocaleDateString('vi-VN') : '—'}</div>
        </div>
        <div class="info-item">
            <div class="info-label">Tổng Đơn</div>
            <div class="info-val text-gold fw-600">${c.order_count} đơn</div>
        </div>
        <div class="info-item">
            <div class="info-label">Tổng Chi Tiêu</div>
            <div class="info-val text-gold fw-600">₫${Number(c.total_spent||0).toLocaleString('vi-VN')}</div>
        </div>
        <div class="info-item" style="grid-column:1/-1;">
            <div class="info-label">Trạng Thái</div>
            <div class="info-val">${isActive
                ? '<span class="badge badge-success">✅ Đang hoạt động</span>'
                : '<span class="badge badge-danger">🔒 Đã khoá</span>'}</div>
        </div>`;

    // Load đơn hàng
    const ordEl = document.getElementById('modalOrders');
    ordEl.innerHTML = '<div style="color:var(--t3);font-size:.82rem;text-align:center;padding:12px;">Đang tải...</div>';
    try {
        const res    = await fetch(BASE + `api/customers.php?action=orders&id=${id}`);
        const data   = await res.json();
        const orders = data.orders || [];
        if (!orders.length) {
            ordEl.innerHTML = '<div style="color:var(--t3);font-size:.82rem;text-align:center;padding:16px;">Chưa có đơn hàng nào</div>';
            return;
        }
        const sc = {'Chờ duyệt':'#C9973A','Đang giao':'#5b9cf6','Đã giao':'#6DD880','Hủy':'#e07070'};
        ordEl.innerHTML = orders.map(o => `
            <div style="display:flex;justify-content:space-between;align-items:center;
                        padding:10px 14px;border:1px solid var(--border2);border-radius:6px;
                        margin-bottom:6px;background:rgba(212,168,75,.03);">
                <div>
                    <div style="font-size:.85rem;color:var(--gold);font-weight:600;">${o.order_code}</div>
                    <div style="font-size:.72rem;color:var(--t3);">${o.payment_method} · ${new Date(o.created_at).toLocaleDateString('vi-VN')}</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:.8rem;color:${sc[o.status]||'#888'};">${o.status}</div>
                    <div style="font-family:'Playfair Display',serif;color:var(--gold);">₫${Number(o.total).toLocaleString('vi-VN')}</div>
                </div>
            </div>`).join('');
    } catch(e) {
        ordEl.innerHTML = '<div style="color:#e07070;font-size:.82rem;">Lỗi tải đơn hàng</div>';
    }
}

function closeModal() {
    document.getElementById('custModal').classList.remove('open');
    currentCustId = null;
}

// ── KHOÁ / MỞ KHOÁ ───────────────────────────────────────────
async function doToggleLock() {
    if (!currentCustId) return;
    const c = allCustomers.find(x => x.id == currentCustId);
    if (!c) return;
    await toggle(currentCustId, c.is_active == 1 ? 0 : 1);
    closeModal();
}

async function quickToggle(id, newActive) {
    await toggle(id, newActive);
}

async function toggle(id, newActive) {
    const action = newActive ? 'unlock' : 'lock';
    try {
        const res  = await fetch(BASE + `api/customers.php?action=${action}`, {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({id})
        });
        const data = await res.json();
        if (data.success) {
            showToast(newActive ? '🔓 Đã mở khoá tài khoản' : '🔒 Đã khoá tài khoản', newActive ? 'ok' : 'warn');
            await loadCustomers();
        }
    } catch(e) {
        showToast('❌ Lỗi: ' + e.message, 'error');
    }
}

// ── UTILS ─────────────────────────────────────────────────────
function esc(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function toggleAdminTheme() {
    const t = document.documentElement.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('tlc-theme', t);
}
async function doLogout(e) {
    e.preventDefault();
    await fetch(BASE + 'api/auth.php', {method:'POST', body: JSON.stringify({action:'logout'})});
    window.location.href = 'login.php';
}

// showToast fallback nếu admin.js chưa load
function showToast(msg, type='') {
    const t = document.getElementById('toast');
    if (!t) return;
    t.textContent = msg;
    t.className = 'toast show' + (type ? ' ' + type : '');
    setTimeout(() => t.classList.remove('show'), 3000);
}
</script>
</body>
</html>