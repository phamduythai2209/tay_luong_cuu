<?php
// ============================================================
// FILE: admin/products.php — Quản lý sản phẩm
// ============================================================
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản Phẩm — Tây Lương Cửu Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script>(function(){var t=localStorage.getItem('tlc-theme')||'dark';document.documentElement.setAttribute('data-theme',t);})();</script>
    <style>
        /* ── PAGE TABS ── */
        .page-tabs { display:flex; gap:0; border-bottom:2px solid var(--border); margin-bottom:24px; }
        .page-tab { padding:12px 28px; font-size:.82rem; letter-spacing:.08em; font-weight:500; cursor:pointer; border:none; background:transparent; color:var(--t3); border-bottom:2px solid transparent; margin-bottom:-2px; transition:all .2s; font-family:'Inter',sans-serif; }
        .page-tab:hover { color:var(--t1); }
        .page-tab.active { color:var(--gold); border-bottom-color:var(--gold); }
        .page-panel { display:none; }
        .page-panel.active { display:block; }

        /* ── SORT PANEL ── */
        #productsTableBody tr { cursor:grab; transition:background .15s,opacity .2s; }
        #productsTableBody tr:active { cursor:grabbing; }
        #productsTableBody tr.dragging { opacity:.4; background:rgba(201,151,58,.08); }
        #productsTableBody tr.drag-over { background:rgba(201,151,58,.15); border-top:2px solid #C9973A; }
        .drag-handle { color:var(--t3); font-size:1.1rem; cursor:grab; padding:0 8px; user-select:none; transition:color .2s; }
        .drag-handle:hover { color:#C9973A; }
        .sort-badge { display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; border-radius:50%; background:rgba(201,151,58,.12); border:1px solid rgba(201,151,58,.25); color:#C9973A; font-size:.75rem; font-weight:700; }
        .save-order-bar { display:none; position:fixed; bottom:24px; left:50%; transform:translateX(-50%); background:var(--card); border:1px solid rgba(201,151,58,.4); border-radius:40px; padding:10px 20px; gap:12px; align-items:center; box-shadow:0 8px 32px rgba(0,0,0,.4); z-index:999; animation:slideUp .3s ease; }
        .save-order-bar.show { display:flex; }
        @keyframes slideUp { from{transform:translateX(-50%) translateY(20px);opacity:0} to{transform:translateX(-50%) translateY(0);opacity:1} }
        .btn-save-order { padding:8px 20px; background:#C9973A; color:#0A0202; border:none; border-radius:20px; cursor:pointer; font-size:.82rem; font-weight:700; transition:all .2s; }
        .btn-save-order:hover { background:#E2B865; transform:scale(1.05); }
        .btn-cancel-order { padding:8px 14px; background:transparent; color:var(--t3); border:1px solid var(--border); border-radius:20px; cursor:pointer; font-size:.82rem; }

        /* ── CATEGORY TABS (danh sách) ── */
        .cat-tabs-wrap { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
        .cat-tab { display:flex; align-items:center; gap:6px; padding:6px 14px; border-radius:20px; border:1px solid var(--border); background:transparent; color:var(--t2); cursor:pointer; font-size:.78rem; font-family:'Inter',sans-serif; transition:all .2s; white-space:nowrap; }
        .cat-tab:hover { border-color:var(--gold); color:var(--gold); }
        .cat-tab.active { background:var(--gold); color:#1A0A00; border-color:var(--gold); font-weight:600; }
        .cat-tab .cat-count { font-size:.65rem; padding:1px 6px; border-radius:10px; background:rgba(0,0,0,.15); }

        /* ── CATEGORY GROUP HEADER ── */
        .cat-group-header { display:flex; align-items:center; gap:10px; padding:10px 16px; background:rgba(201,151,58,.06); border-top:1px solid rgba(201,151,58,.15); border-bottom:1px solid rgba(201,151,58,.15); }
        .cat-group-title { font-size:.7rem; letter-spacing:.15em; text-transform:uppercase; color:var(--gold); font-weight:600; }
        .cat-group-count { font-size:.65rem; color:var(--t3); }

        /* ── SEARCH BAR ── */
        .list-toolbar { display:flex; align-items:center; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
        .list-search-wrap { position:relative; flex:1; min-width:200px; }
        .list-search-wrap input { width:100%; padding:9px 36px 9px 14px; background:var(--card2); border:1px solid var(--border); border-radius:8px; color:var(--t1); font-size:.85rem; font-family:'Inter',sans-serif; outline:none; box-sizing:border-box; transition:border-color .2s; }
        .list-search-wrap input:focus { border-color:var(--gold); }
        .list-search-wrap .search-icon { position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--t3); font-size:.9rem; pointer-events:none; }
        .list-search-clear { position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--t3); font-size:.8rem; cursor:pointer; background:none; border:none; padding:0; display:none; }
        .list-search-clear:hover { color:var(--t1); }

        /* Taste Profile */
        .taste-admin-wrap { margin-top:12px; padding:16px; background:rgba(201,151,58,.04); border:1px solid rgba(201,151,58,.15); border-radius:10px; }
        .taste-admin-row { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .taste-admin-item label { font-size:.7rem; color:var(--t3); text-transform:uppercase; letter-spacing:.08em; display:flex; justify-content:space-between; margin-bottom:8px; }
        .taste-admin-item label span { color:var(--gold); font-weight:600; }
        .taste-admin-item input[type=range] { width:100%; accent-color:#C9973A; height:6px; cursor:pointer; }
        .taste-admin-ends { display:flex; justify-content:space-between; font-size:.62rem; color:var(--t4); margin-top:3px; }
        .taste-preview-bar { margin-top:12px; padding:8px 14px; background:rgba(0,0,0,.15); border-radius:6px; display:flex; gap:20px; font-size:.75rem; color:var(--t2); }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <div class="topbar-title">Quản Lý Sản Phẩm</div>
                <div class="topbar-date" id="todayDate"></div>
            </div>
            <div class="topbar-right">
                <button onclick="toggleAdminTheme()" style="background:none;border:1px solid var(--border);border-radius:20px;padding:6px 14px;color:var(--t2);cursor:pointer;font-size:.85rem;">Giao diện</button>
                <span class="topbar-user"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></span>
                <a href="#" class="btn-logout" onclick="doLogout(event)">Đăng xuất</a>
            </div>
        </div>

        <div class="admin-content">

            <!-- PAGE TABS -->
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:0;">
                <div class="page-tabs">
                    <button class="page-tab active" onclick="switchPageTab('list')">📋 Danh Sách Sản Phẩm</button>
                    <button class="page-tab" onclick="switchPageTab('sort')">↕ Thứ Tự Sản Phẩm</button>
                </div>
                <button class="btn-add" onclick="openProductModal()" style="margin-bottom:2px;">+ Thêm Sản Phẩm</button>
            </div>

            <!-- ══ PANEL: DANH SÁCH ══ -->
            <div class="page-panel active" id="panelList">
                <div class="table-card" style="margin-top:0;">

                    <!-- Toolbar: search + count -->
                    <div class="list-toolbar">
                        <div class="list-search-wrap">
                            <input type="text" id="listSearch" placeholder="🔍  Tìm tên, danh mục, xuất xứ..."
                                   oninput="onListSearch(this.value)">
                            <button class="list-search-clear" id="listSearchClear" onclick="clearListSearch()">✕</button>
                        </div>
                        <span style="font-size:.8rem;color:var(--t3);white-space:nowrap;">
                            <span id="listCount">0</span> sản phẩm
                        </span>
                    </div>

                    <!-- Category tabs -->
                    <div class="cat-tabs-wrap" id="catTabs">
                        <button class="cat-tab active" data-cat="" onclick="setCatFilter('')">
                            Tất cả <span class="cat-count" id="countAll">0</span>
                        </button>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:40px;">#</th>
                                    <th>Sản Phẩm</th><th>Danh Mục</th><th>Độ Cồn</th>
                                    <th>Giá Bán</th><th>Tồn Kho</th><th>Trạng Thái</th><th>Thao Tác</th>
                                </tr>
                            </thead>
                            <tbody id="listTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ══ PANEL: THỨ TỰ ══ -->
            <div class="page-panel" id="panelSort">
                <div class="table-card" style="margin-top:0;">
                    <div class="table-card-head">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <span>Kéo thả để sắp xếp thứ tự hiển thị (<span id="sortCount">0</span> sản phẩm)</span>
                            <span style="font-size:.72rem;color:var(--t3);background:rgba(201,151,58,.08);border:1px solid rgba(201,151,58,.2);border-radius:20px;padding:3px 10px;">
                                Thứ tự này sẽ hiển thị trên trang chủ
                            </span>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:40px;"></th>
                                    <th style="width:50px;">#</th>
                                    <th>Sản Phẩm</th><th>Danh Mục</th>
                                    <th>Giá Bán</th><th>Tồn Kho</th><th>Trạng Thái</th>
                                </tr>
                            </thead>
                            <tbody id="productsTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<!-- Thanh lưu thứ tự nổi -->
<div class="save-order-bar" id="saveOrderBar">
    <span>Thứ tự đã thay đổi —</span>
    <button class="btn-save-order" onclick="saveOrder()">💾 Lưu thứ tự</button>
    <button class="btn-cancel-order" onclick="cancelOrder()">Hoàn tác</button>
</div>

<!-- MODAL THÊM / SỬA -->
<div class="modal-overlay" id="productModal">
    <div class="modal-box" style="width:640px;">
        <div class="modal-head">
            <h3 id="modalTitle">+ Thêm Sản Phẩm Mới</h3>
            <button class="modal-close" onclick="closeModal('productModal')">✕</button>
        </div>
        <div class="modal-body">
            <div class="field">
                <label>Ảnh Sản Phẩm</label>
                <div class="img-upload" id="imgUploadBox" onclick="document.getElementById('imgFile').click()">
                    <input type="file" id="imgFile" accept="image/*" style="display:none" onchange="handleImgUpload(this)">
                    <div id="imgPreview" style="width:100%;height:100%;position:relative;">
                        <span class="img-placeholder">Nhấn để chọn ảnh (tối đa 5MB)</span>
                    </div>
                </div>
                <input type="hidden" id="mpImg">
            </div>
            <div class="form-row">
                <div class="field"><label>Tên Sản Phẩm *</label><input class="form-input" id="mpName" placeholder="VD: Rượu Nếp Cẩm Đặc Biệt"></div>
                <div class="field"><label>Tên Ngắn</label><input class="form-input" id="mpShort" placeholder="VD: Nếp Cẩm"></div>
            </div>
            <div class="form-row">
                <div class="field">
                    <label>Danh Mục *</label>
                    <input class="form-input" id="mpCat" placeholder="VD: Rượu Vang, Rượu Trắng..." list="catSuggestions" autocomplete="off">
                    <datalist id="catSuggestions"></datalist>
                </div>
                <div class="field"><label>Xuất Xứ</label><input class="form-input" id="mpOrigin" placeholder="VD: Hà Giang"></div>
            </div>
            <div class="form-row">
                <div class="field"><label>Độ Cồn (°)</label><input class="form-input" id="mpAlc" type="number" step="0.5" placeholder="29.5"></div>
                <div class="field"><label>Dung Tích (ml)</label><input class="form-input" id="mpVol" type="number" placeholder="750"></div>
            </div>
            <div class="form-row">
                <div class="field"><label>Giá Bán (₫) *</label><input class="form-input" id="mpPrice" type="number" placeholder="680000"></div>
                <div class="field"><label>Giá Gốc (₫) — để trống nếu không KM</label><input class="form-input" id="mpPriceOld" type="number" placeholder="950000"></div>
            </div>
            <div class="form-row">
                <div class="field"><label>Tồn Kho (chai)</label><input class="form-input" id="mpStock" type="number" placeholder="0"></div>
                <div class="field"><label>Hương Vị</label><input class="form-input" id="mpFlavor" placeholder="VD: Ngọt thanh, thơm nồng..."></div>
            </div>
            <div class="form-row">
                <div class="field">
                    <label>Badge</label>
                    <select class="form-select" id="mpBadge">
                        <option value="new">Mới</option><option value="hot">Bán Chạy</option>
                        <option value="limited">Limited</option><option value="sale">Sale</option>
                    </select>
                </div>
                <div class="field">
                    <label>Hiển Thị</label>
                    <select class="form-select" id="mpActive">
                        <option value="1">Đang bán</option><option value="0">Tạm ẩn</option>
                    </select>
                </div>
            </div>
            <div class="field">
                <label>Phù hợp dịp nào <span style="color:var(--t3);font-size:.75rem;">(chọn nhiều)</span></label>
                <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:8px;">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.85rem;color:var(--t2);"><input type="checkbox" id="occ_gift" value="gift" style="accent-color:#C9973A;width:15px;height:15px;"> Quà tặng</label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.85rem;color:var(--t2);"><input type="checkbox" id="occ_health" value="health" style="accent-color:#C9973A;width:15px;height:15px;"> Bổ sức khoẻ</label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.85rem;color:var(--t2);"><input type="checkbox" id="occ_party" value="party" style="accent-color:#C9973A;width:15px;height:15px;"> Tiệc / Liên hoan</label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.85rem;color:var(--t2);"><input type="checkbox" id="occ_daily" value="daily" style="accent-color:#C9973A;width:15px;height:15px;"> Uống hằng ngày</label>
                </div>
            </div>
            <div class="field">
                <label>Ghi chú thêm cho AI tư vấn <span style="color:var(--t3);font-size:.75rem;">(không bắt buộc)</span></label>
                <input class="form-input" id="mpGiftNote" placeholder="VD: Phù hợp tặng bố mẹ lớn tuổi, tốt cho xương khớp">
            </div>
            <div class="field">
                <label style="display:flex;align-items:center;justify-content:space-between;">
                    <span>Taste Profile</span>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:.82rem;color:var(--t2);text-transform:none;letter-spacing:0;font-weight:400;">
                        <input type="checkbox" id="mpShowTaste" style="accent-color:#C9973A;width:14px;height:14px;"
                               onchange="document.getElementById('tasteSliders').style.display=this.checked?'block':'none'">
                        Hiện Taste Profile trên trang sản phẩm
                    </label>
                </label>
                <div id="tasteSliders" style="display:none;">
                    <div class="taste-admin-wrap">
                        <div class="taste-admin-row">
                            <div class="taste-admin-item">
                                <label>Body <span id="bodyValLabel">Medium Bodied</span></label>
                                <input type="range" id="mpTasteBody" min="1" max="10" value="5" oninput="updateTasteLabel('body',this.value)">
                                <div class="taste-admin-ends"><span>Light</span><span>Full</span></div>
                            </div>
                            <div class="taste-admin-item">
                                <label>Sweetness <span id="sweetValLabel">Off-Dry</span></label>
                                <input type="range" id="mpTasteSweet" min="1" max="10" value="4" oninput="updateTasteLabel('sweet',this.value)">
                                <div class="taste-admin-ends"><span>Dry</span><span>Sweet</span></div>
                            </div>
                        </div>
                        <div class="taste-preview-bar">
                            <span>🍾 <span id="bodyDescPreview">Vị cân bằng, mượt mà</span></span>
                            <span>🍷 <span id="sweetDescPreview">Hơi ngọt, cân bằng</span></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="field">
                <label>Mô Tả Sản Phẩm</label>
                <textarea class="form-textarea" id="mpDesc" rows="3" placeholder="Mô tả chi tiết..."></textarea>
            </div>
        </div>
        <div class="modal-foot">
            <button class="btn-cancel" onclick="closeModal('productModal')">Hủy</button>
            <button class="btn-save" id="btnSaveProduct" onclick="saveProduct()">Lưu Sản Phẩm</button>
        </div>
    </div>
</div>

<div id="toast" class="toast"></div>
<script src="../assets/js/admin.js"></script>
<script>
let allProducts = [], editingId = 0, currentImg = '';
let orderChanged = false, originalOrder = [];
let dragSrcRow   = null;
let activeCat    = '';
let listSearchQ  = '';
let activePageTab = 'list';

document.getElementById('todayDate').textContent =
    new Date().toLocaleDateString('vi-VN',{weekday:'long',year:'numeric',month:'long',day:'numeric'});

function imgUrl(path) {
    if (!path) return null;
    if (path.startsWith('http')||path.startsWith('/')) return path;
    return '../'+path;
}

// ── Page tabs ─────────────────────────────────────────────
function switchPageTab(tab) {
    activePageTab = tab;
    document.querySelectorAll('.page-tab').forEach((btn,i)=>{
        btn.classList.toggle('active', (i===0&&tab==='list')||(i===1&&tab==='sort'));
    });
    document.getElementById('panelList').classList.toggle('active', tab==='list');
    document.getElementById('panelSort').classList.toggle('active', tab==='sort');
    if (tab==='sort') renderSortTable();
}

// ── Taste Profile ─────────────────────────────────────────
function updateTasteLabel(type,val) {
    val=parseInt(val);
    if (type==='body') {
        const label=val>=8?'Full Bodied':val>=5?'Medium Bodied':val>=3?'Light-Medium':'Light Bodied';
        const desc=val>=8?'Rượu đậm, kết cấu dày':val>=5?'Vị cân bằng, mượt mà':val>=3?'Khá nhẹ nhàng':'Rượu nhẹ, thanh thoát';
        document.getElementById('bodyValLabel').textContent=label;
        document.getElementById('bodyDescPreview').textContent=desc;
    } else {
        const label=val>=8?'Sweet':val>=5?'Off-Dry':val>=3?'Semi-Dry':'Dry';
        const desc=val>=8?'Vị ngọt dịu, dễ uống':val>=5?'Hơi ngọt, cân bằng':val>=3?'Hơi khô':'Không ngọt, vị khô';
        document.getElementById('sweetValLabel').textContent=label;
        document.getElementById('sweetDescPreview').textContent=desc;
    }
}

// ── Load ─────────────────────────────────────────────────
async function loadProducts() {
    try {
        allProducts = await adminFetch('../api/products.php');
        allProducts.sort((a,b)=>(a.sort_order||0)-(b.sort_order||0));
        originalOrder = allProducts.map(p=>p.id);
        buildCatTabs();
        applyListFilter();
        if (activePageTab==='sort') renderSortTable();
    } catch(e) { showToast('Lỗi: '+e.message,'error'); }
}

// ── Category tabs ─────────────────────────────────────────
function buildCatTabs() {
    const counts={};
    allProducts.forEach(p=>{ const c=(p.cat||'Chưa phân loại').trim(); counts[c]=(counts[c]||0)+1; });
    const cats=Object.keys(counts).sort();
    const tabsEl=document.getElementById('catTabs');
    const allTab=tabsEl.querySelector('[data-cat=""]');
    document.getElementById('countAll').textContent=allProducts.length;
    tabsEl.innerHTML=''; tabsEl.appendChild(allTab);
    cats.forEach(cat=>{
        const btn=document.createElement('button');
        btn.className='cat-tab'+(activeCat===cat?' active':'');
        btn.dataset.cat=cat; btn.onclick=()=>setCatFilter(cat);
        btn.innerHTML=`${cat} <span class="cat-count">${counts[cat]}</span>`;
        tabsEl.appendChild(btn);
    });
    const dl=document.getElementById('catSuggestions');
    if (dl) dl.innerHTML=cats.map(c=>`<option value="${c}">`).join('');
}

function setCatFilter(cat) {
    activeCat=cat;
    document.querySelectorAll('.cat-tab').forEach(btn=>btn.classList.toggle('active',btn.dataset.cat===cat));
    applyListFilter();
}

function onListSearch(q) {
    listSearchQ=q.toLowerCase();
    document.getElementById('listSearchClear').style.display=q?'block':'none';
    applyListFilter();
}
function clearListSearch() {
    document.getElementById('listSearch').value='';
    onListSearch('');
}

function applyListFilter() {
    let data=allProducts;
    if (activeCat) data=data.filter(p=>(p.cat||'Chưa phân loại').trim()===activeCat);
    if (listSearchQ) data=data.filter(p=>
        p.name.toLowerCase().includes(listSearchQ)||
        (p.cat||'').toLowerCase().includes(listSearchQ)||
        (p.origin||'').toLowerCase().includes(listSearchQ)||
        (p.flavor||'').toLowerCase().includes(listSearchQ)
    );
    renderListTable(data);
}

// ── Render LIST table (group theo danh mục nếu Tất cả + không search) ──
function renderListTable(data) {
    document.getElementById('listCount').textContent=data.length;
    const tbody=document.getElementById('listTableBody');
    if (!data.length) {
        tbody.innerHTML='<tr><td colspan="8" class="text-center text-muted" style="padding:40px;">Không tìm thấy sản phẩm nào</td></tr>';
        return;
    }
    if (!activeCat && !listSearchQ) {
        // Group
        const groups={};
        data.forEach(p=>{ const c=(p.cat||'Chưa phân loại').trim(); if(!groups[c])groups[c]=[]; groups[c].push(p); });
        let html='', idx=0;
        Object.keys(groups).sort().forEach(cat=>{
            const items=groups[cat];
            html+=`<tr style="pointer-events:none;"><td colspan="8" style="padding:0;">
                <div class="cat-group-header">
                    <span class="cat-group-title">${cat}</span>
                    <span class="cat-group-count">${items.length} sản phẩm</span>
                </div></td></tr>`;
            items.forEach(p=>{ idx++; html+=buildListRow(p,idx); });
        });
        tbody.innerHTML=html;
    } else {
        tbody.innerHTML=data.map((p,i)=>buildListRow(p,i+1)).join('');
    }
}

function buildListRow(p,idx) {
    const src=imgUrl(p.img);
    return `<tr>
        <td style="text-align:center;color:var(--t3);font-size:.8rem;">${idx}</td>
        <td>
            <div style="display:flex;align-items:center;gap:10px;">
                ${src
                    ?`<img src="${src}" style="width:44px;height:44px;object-fit:cover;border-radius:4px;border:1px solid var(--border);" onerror="this.style.display='none';">`
                    :`<div style="width:44px;height:44px;background:var(--card2);border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:1rem;border:1px solid var(--border);color:var(--t3);">R</div>`}
                <div>
                    <div style="color:var(--ivory);font-size:.86rem;font-weight:500;">${p.name}</div>
                    <div style="color:var(--t3);font-size:.72rem;margin-top:2px;">${p.origin||''} ${p.flavor?'· '+p.flavor:''}</div>
                </div>
            </div>
        </td>
        <td><span style="font-size:.72rem;padding:3px 8px;border-radius:4px;background:rgba(201,151,58,.1);color:var(--gold);border:1px solid rgba(201,151,58,.2);">${p.cat||'—'}</span></td>
        <td>${p.alc}°</td>
        <td class="text-gold">₫${Number(p.price).toLocaleString('vi-VN')}</td>
        <td style="color:${p.stock<50?'#D06060':'var(--t2)'}">${p.stock} chai</td>
        <td><span class="badge badge-${p.is_active?'success':'danger'}">${p.is_active?'Đang bán':'Tạm ẩn'}</span></td>
        <td>
            <div class="action-btns">
                <button class="act-btn" onclick="editProduct(${p.id})" title="Sửa">&#9998;</button>
                <button class="act-btn" onclick="toggleActive(${p.id},${p.is_active})" title="${p.is_active?'Ẩn':'Hiện'}">${p.is_active?'&#9646;':'&#9654;'}</button>
                <button class="act-btn danger" onclick="deleteProduct(${p.id})" title="Xóa">&#128465;</button>
            </div>
        </td>
    </tr>`;
}

// ── Render SORT table (kéo thả) ───────────────────────────
function renderSortTable() {
    document.getElementById('sortCount').textContent=allProducts.length;
    const tbody=document.getElementById('productsTableBody');
    if (!allProducts.length) {
        tbody.innerHTML='<tr><td colspan="7" class="text-center text-muted" style="padding:40px;">Chưa có sản phẩm nào</td></tr>';
        return;
    }
    tbody.innerHTML=allProducts.map((p,idx)=>{
        const src=imgUrl(p.img);
        return `<tr draggable="true" data-id="${p.id}" ondragstart="onDragStart(event)" ondragover="onDragOver(event)" ondrop="onDrop(event)" ondragleave="onDragLeave(event)" ondragend="onDragEnd(event)">
            <td style="text-align:center;padding:0 4px;"><span class="drag-handle" title="Kéo để sắp xếp">&#8597;</span></td>
            <td style="text-align:center;"><span class="sort-badge">${idx+1}</span></td>
            <td>
                <div style="display:flex;align-items:center;gap:10px;">
                    ${src?`<img src="${src}" style="width:40px;height:40px;object-fit:cover;border-radius:4px;border:1px solid var(--border);" onerror="this.style.display='none';">`:`<div style="width:40px;height:40px;background:var(--card2);border-radius:4px;display:flex;align-items:center;justify-content:center;color:var(--t3);border:1px solid var(--border);">R</div>`}
                    <div style="color:var(--ivory);font-size:.86rem;font-weight:500;">${p.name}</div>
                </div>
            </td>
            <td><span style="font-size:.72rem;padding:3px 8px;border-radius:4px;background:rgba(201,151,58,.1);color:var(--gold);border:1px solid rgba(201,151,58,.2);">${p.cat||'—'}</span></td>
            <td class="text-gold">₫${Number(p.price).toLocaleString('vi-VN')}</td>
            <td style="color:${p.stock<50?'#D06060':'var(--t2)'}">${p.stock} chai</td>
            <td><span class="badge badge-${p.is_active?'success':'danger'}">${p.is_active?'Đang bán':'Tạm ẩn'}</span></td>
        </tr>`;
    }).join('');
}

// ── Drag & Drop ───────────────────────────────────────────
function onDragStart(e) { dragSrcRow=e.currentTarget; dragSrcRow.classList.add('dragging'); e.dataTransfer.effectAllowed='move'; e.dataTransfer.setData('text/plain',dragSrcRow.dataset.id); }
function onDragOver(e) { e.preventDefault(); e.dataTransfer.dropEffect='move'; const row=e.currentTarget; if(row!==dragSrcRow) row.classList.add('drag-over'); }
function onDragLeave(e) { e.currentTarget.classList.remove('drag-over'); }
function onDrop(e) {
    e.preventDefault();
    const tr=e.currentTarget; tr.classList.remove('drag-over');
    if (!dragSrcRow||dragSrcRow===tr) return;
    const tbody=document.getElementById('productsTableBody');
    const rows=[...tbody.querySelectorAll('tr')];
    const si=rows.indexOf(dragSrcRow), ti=rows.indexOf(tr);
    if (si<ti) tbody.insertBefore(dragSrcRow,tr.nextSibling);
    else tbody.insertBefore(dragSrcRow,tr);
    updateSortBadges(); syncProductsOrder();
    orderChanged=true; document.getElementById('saveOrderBar').classList.add('show');
}
function onDragEnd(e) { e.currentTarget.classList.remove('dragging'); document.querySelectorAll('#productsTableBody tr').forEach(r=>r.classList.remove('drag-over')); }
function updateSortBadges() { document.querySelectorAll('#productsTableBody tr').forEach((row,idx)=>{ const b=row.querySelector('.sort-badge'); if(b)b.textContent=idx+1; }); }
function syncProductsOrder() { const rows=[...document.querySelectorAll('#productsTableBody tr')]; const newOrder=rows.map(r=>parseInt(r.dataset.id)); allProducts.sort((a,b)=>newOrder.indexOf(a.id)-newOrder.indexOf(b.id)); }

async function saveOrder() {
    const rows=[...document.querySelectorAll('#productsTableBody tr')];
    const orders=rows.map((r,idx)=>({id:parseInt(r.dataset.id),sort_order:idx+1}));
    try {
        await adminFetch('../api/products.php?action=reorder',{method:'POST',body:JSON.stringify({orders})});
        originalOrder=orders.map(o=>o.id); orderChanged=false;
        document.getElementById('saveOrderBar').classList.remove('show');
        showToast('✅ Đã lưu thứ tự hiển thị!'); await loadProducts();
    } catch(e) { showToast('Lỗi: '+e.message,'error'); }
}
function cancelOrder() { orderChanged=false; document.getElementById('saveOrderBar').classList.remove('show'); loadProducts(); }

// ── Modal thêm ────────────────────────────────────────────
function openProductModal() {
    editingId=0; currentImg='';
    document.getElementById('modalTitle').textContent='+ Thêm Sản Phẩm Mới';
    ['mpName','mpShort','mpCat','mpOrigin','mpAlc','mpVol','mpPrice','mpPriceOld','mpStock','mpFlavor','mpDesc','mpGiftNote'].forEach(id=>document.getElementById(id).value='');
    document.getElementById('mpBadge').value='new';
    document.getElementById('mpActive').value='1';
    ['occ_gift','occ_health','occ_party','occ_daily'].forEach(id=>document.getElementById(id).checked=false);
    document.getElementById('mpImg').value='';
    document.getElementById('imgPreview').innerHTML='<span class="img-placeholder">Nhấn để chọn ảnh (tối đa 5MB)</span>';
    document.getElementById('mpShowTaste').checked=false;
    document.getElementById('tasteSliders').style.display='none';
    document.getElementById('mpTasteBody').value=5;
    document.getElementById('mpTasteSweet').value=4;
    updateTasteLabel('body',5); updateTasteLabel('sweet',4);
    openModal('productModal');
}

// ── Modal sửa ─────────────────────────────────────────────
function editProduct(id) {
    const p=allProducts.find(x=>x.id===id); if(!p) return;
    editingId=id; currentImg=p.img||'';
    document.getElementById('modalTitle').textContent='Sửa Sản Phẩm';
    document.getElementById('mpName').value=p.name;
    document.getElementById('mpShort').value=p.short||'';
    document.getElementById('mpCat').value=p.cat||'';
    document.getElementById('mpOrigin').value=p.origin||'';
    document.getElementById('mpAlc').value=p.alc||0;
    document.getElementById('mpVol').value=p.vol||750;
    document.getElementById('mpPrice').value=p.price||0;
    document.getElementById('mpPriceOld').value=p.priceOld||'';
    document.getElementById('mpStock').value=p.stock||0;
    document.getElementById('mpFlavor').value=p.flavor||'';
    document.getElementById('mpDesc').value=p.desc||'';
    document.getElementById('mpBadge').value=p.badge||'new';
    document.getElementById('mpActive').value=p.is_active??1;
    document.getElementById('mpGiftNote').value=p.gift_note||'';
    const occ=(p.occasion||'').split(',');
    ['occ_gift','occ_health','occ_party','occ_daily'].forEach(id=>{document.getElementById(id).checked=occ.includes(document.getElementById(id).value);});
    document.getElementById('mpImg').value=currentImg;
    const prev=document.getElementById('imgPreview');
    const src=imgUrl(currentImg);
    prev.innerHTML=src?`<img src="${src}" style="width:100%;height:100%;object-fit:cover;border-radius:2px;" onerror="this.parentNode.innerHTML='<span class=img-placeholder>Ảnh lỗi, chọn lại</span>'"><button style="position:absolute;top:6px;right:6px;background:#8B2020;color:#fff;border:none;border-radius:3px;width:24px;height:24px;cursor:pointer;font-size:.8rem;" onclick="event.stopPropagation();removeImg()">✕</button>`:'<span class="img-placeholder">Nhấn để chọn ảnh</span>';
    const showT=!!(p.show_taste||p.taste_body);
    document.getElementById('mpShowTaste').checked=showT;
    document.getElementById('tasteSliders').style.display=showT?'block':'none';
    const tb=p.taste_body||5,ts=p.taste_sweet||4;
    document.getElementById('mpTasteBody').value=tb;
    document.getElementById('mpTasteSweet').value=ts;
    updateTasteLabel('body',tb); updateTasteLabel('sweet',ts);
    openModal('productModal');
}

async function handleImgUpload(input) {
    if (!input.files[0]) return;
    const file=input.files[0];
    if (file.size>5*1024*1024) { showToast('Ảnh quá lớn! Tối đa 5MB','warn'); return; }
    const reader=new FileReader();
    reader.onload=e=>{document.getElementById('imgPreview').innerHTML=`<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`;};
    reader.readAsDataURL(file);
    const formData=new FormData(); formData.append('image',file);
    try {
        const res=await fetch('../api/upload.php',{method:'POST',credentials:'same-origin',body:formData});
        const data=await res.json();
        if (data.url) { currentImg=data.url; document.getElementById('mpImg').value=data.url; showToast('Ảnh đã upload!'); }
        else throw new Error(data.error||'Upload thất bại');
    } catch(e) { showToast('Lỗi: '+e.message,'error'); }
}

function removeImg() {
    currentImg=''; document.getElementById('mpImg').value=''; document.getElementById('imgFile').value='';
    document.getElementById('imgPreview').innerHTML='<span class="img-placeholder">Nhấn để chọn ảnh</span>';
}

async function saveProduct() {
    const name=document.getElementById('mpName').value.trim();
    const cat=document.getElementById('mpCat').value.trim();
    const price=+document.getElementById('mpPrice').value;
    if (!name) { showToast('Vui lòng nhập tên sản phẩm!','warn'); return; }
    if (!cat)  { showToast('Vui lòng nhập danh mục!','warn'); return; }
    if (!price){ showToast('Vui lòng nhập giá bán!','warn'); return; }
    const g=id=>document.getElementById(id).value.trim();
    const showTaste=document.getElementById('mpShowTaste').checked;
    const payload={
        name, short_name:g('mpShort'), category:cat, origin:g('mpOrigin'),
        alc:+g('mpAlc')||0, volume:+g('mpVol')||750, price,
        price_old:+g('mpPriceOld')||null, stock:+g('mpStock')||0,
        flavor:g('mpFlavor'), description:g('mpDesc'), badge:g('mpBadge'),
        is_active:+g('mpActive'),
        occasion:['occ_gift','occ_health','occ_party','occ_daily'].filter(id=>document.getElementById(id).checked).map(id=>document.getElementById(id).value).join(','),
        gift_note:g('mpGiftNote'), image:document.getElementById('mpImg').value||'',
        show_taste:showTaste?1:0,
        taste_body:showTaste?(parseInt(document.getElementById('mpTasteBody').value)||5):null,
        taste_sweet:showTaste?(parseInt(document.getElementById('mpTasteSweet').value)||4):null,
    };
    const btn=document.getElementById('btnSaveProduct');
    btn.textContent='Đang lưu...'; btn.disabled=true;
    try {
        if (editingId) { await adminFetch('../api/products.php?id='+editingId,{method:'PUT',body:JSON.stringify(payload)}); showToast('Đã cập nhật sản phẩm!'); }
        else           { await adminFetch('../api/products.php',{method:'POST',body:JSON.stringify(payload)}); showToast('Đã thêm sản phẩm mới!'); }
        closeModal('productModal'); await loadProducts();
    } catch(e) { showToast('Lỗi: '+e.message,'error'); }
    finally { btn.textContent='Lưu Sản Phẩm'; btn.disabled=false; }
}

async function toggleActive(id,current) {
    try { await adminFetch('../api/products.php?id='+id,{method:'PUT',body:JSON.stringify({is_active:current?0:1})}); showToast(current?'Đã ẩn sản phẩm':'Đã hiện sản phẩm'); await loadProducts(); }
    catch(e) { showToast('Lỗi: '+e.message,'error'); }
}

async function deleteProduct(id) {
    if (!confirm('Xóa sản phẩm này?')) return;
    try { const res=await adminFetch('../api/products.php?id='+id,{method:'DELETE'}); showToast(res.message); await loadProducts(); }
    catch(e) { showToast('Lỗi: '+e.message,'error'); }
}

async function doLogout(e) {
    e.preventDefault();
    await adminFetch('../api/auth.php',{method:'POST',body:JSON.stringify({action:'logout'})});
    window.location.href='login.php';
}

function toggleAdminTheme() {
    var t=document.documentElement.getAttribute('data-theme')==='light'?'dark':'light';
    document.documentElement.setAttribute('data-theme',t);
    localStorage.setItem('tlc-theme',t);
}

loadProducts();
</script>
</body>
</html>