<?php
// ============================================================
// FILE: admin/recipes.php — Quản lý công thức + Mix Rules
// ============================================================
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }

// Lấy danh sách sản phẩm cho dropdown
require_once __DIR__ . '/../config/database.php';
$conn = getDB();
$allProducts = [];
$res = mysqli_query($conn, "SELECT id, name, alc, price FROM products WHERE is_active=1 ORDER BY alc DESC");
while ($r = mysqli_fetch_assoc($res)) $allProducts[] = $r;
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Công Thức Pha Chế — Tây Lương Cửu Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script>(function() { var t = localStorage.getItem('tlc-theme') || 'dark'; document.documentElement.setAttribute('data-theme', t); })();</script>
    <style>
        .recipe-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(360px,1fr)); gap:20px; margin-top:24px; }
        .recipe-card { background:var(--card2); border:1px solid var(--border); border-radius:12px; padding:20px; position:relative; transition:border-color .2s; }
        .recipe-card:hover { border-color:var(--gold); }
        .recipe-card.inactive { opacity:.55; }
        .recipe-card-title { font-family:'Playfair Display',serif; font-size:1.05rem; color:var(--gold); font-weight:600; margin-bottom:8px; padding-right:80px; }
        .recipe-occasion-tags { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:12px; }
        .occ-tag { font-size:.65rem; padding:3px 9px; border-radius:20px; font-weight:600; letter-spacing:.06em; text-transform:uppercase; }
        .occ-party  { background:rgba(91,156,246,.15); color:#5b9cf6; border:1px solid rgba(91,156,246,.3); }
        .occ-gift   { background:rgba(201,151,58,.15); color:var(--gold); border:1px solid rgba(201,151,58,.3); }
        .occ-health { background:rgba(109,216,128,.15); color:#6DD880; border:1px solid rgba(109,216,128,.3); }
        .occ-daily  { background:rgba(180,180,180,.12); color:var(--t2); border:1px solid var(--border); }
        .recipe-section { margin-bottom:10px; }
        .recipe-section-label { font-size:.68rem; color:var(--t3); text-transform:uppercase; letter-spacing:.1em; margin-bottom:4px; }
        .recipe-section-val { font-size:.82rem; color:var(--t2); line-height:1.6; white-space:pre-line; }
        .recipe-card-actions { position:absolute; top:16px; right:16px; display:flex; gap:6px; }
        .btn-icon { width:30px; height:30px; border-radius:6px; border:1px solid var(--border); background:transparent; color:var(--t3); cursor:pointer; font-size:.8rem; display:flex; align-items:center; justify-content:center; transition:all .2s; }
        .btn-icon:hover { border-color:var(--gold); color:var(--gold); }
        .btn-icon.danger:hover { border-color:#e07070; color:#e07070; }

        /* Mix rules UI */
        .mix-rules-section { margin-top:18px; border-top:1px solid var(--border2); padding-top:16px; }
        .mix-rules-title { font-size:.72rem; color:var(--gold); text-transform:uppercase; letter-spacing:.12em; margin-bottom:12px; }
        .mix-rule-row { display:grid; grid-template-columns:140px 1fr 80px; gap:8px; align-items:center; margin-bottom:8px; }
        .mix-rule-tag { font-size:.72rem; padding:4px 10px; border-radius:4px; font-weight:600; text-align:center; white-space:nowrap; }
        .tag-heavy  { background:rgba(224,112,112,.15); color:#e07070; border:1px solid rgba(224,112,112,.3); }
        .tag-aroma  { background:rgba(201,151,58,.15);  color:var(--gold); border:1px solid rgba(201,151,58,.3); }
        .tag-light  { background:rgba(109,216,128,.15); color:#6DD880; border:1px solid rgba(109,216,128,.3); }
        .tag-color  { background:rgba(149,91,246,.15);  color:#9b5bf6; border:1px solid rgba(149,91,246,.3); }
        .mix-rule-desc { font-size:.75rem; color:var(--t3); padding:4px 0; }

        /* Mix card display */
        .mix-rules-display { margin-top:12px; }
        .mix-rule-item { display:flex; align-items:flex-start; gap:8px; padding:8px 0; border-bottom:1px solid var(--border2); }
        .mix-rule-item:last-child { border-bottom:none; }
        .mix-rule-product { font-size:.8rem; color:var(--t1); font-weight:500; }
        .mix-rule-note-small { font-size:.72rem; color:var(--t3); margin-top:2px; }

        /* Form */
        .form-row { margin-bottom:18px; }
        .form-row label { display:block; font-size:.78rem; color:var(--t3); text-transform:uppercase; letter-spacing:.08em; margin-bottom:7px; }
        .form-row input, .form-row textarea, .form-row select { width:100%; padding:10px 14px; background:var(--bg); border:1px solid var(--border); border-radius:8px; color:var(--t1); font-size:.9rem; font-family:'Inter',sans-serif; resize:vertical; transition:border-color .2s; box-sizing:border-box; }
        .form-row input:focus, .form-row textarea:focus, .form-row select:focus { outline:none; border-color:var(--gold); }
        .form-row textarea { min-height:90px; }
        .occasion-checkboxes { display:flex; gap:10px; flex-wrap:wrap; margin-top:4px; }
        .occ-cb-label { display:flex; align-items:center; gap:6px; font-size:.82rem; color:var(--t2); cursor:pointer; padding:6px 12px; border-radius:6px; border:1px solid var(--border); transition:all .2s; }
        .occ-cb-label:has(input:checked) { border-color:var(--gold); color:var(--gold); background:rgba(201,151,58,.08); }
        .occ-cb-label input { display:none; }

        /* Mix rule form rows */
        .mix-form-row { display:grid; grid-template-columns:150px 1fr auto; gap:8px; align-items:center; margin-bottom:8px; background:rgba(255,255,255,.03); padding:10px 12px; border-radius:8px; border:1px solid var(--border2); }
        .mix-form-row select, .mix-form-row input { padding:7px 10px; font-size:.82rem; }
        .mix-rule-intent-label { font-size:.75rem; font-weight:600; }
        .btn-remove-rule { background:none; border:none; color:var(--t3); cursor:pointer; font-size:1rem; padding:4px 8px; transition:color .2s; }
        .btn-remove-rule:hover { color:#e07070; }

        .modal-overlay { display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.65); backdrop-filter:blur(4px); align-items:center; justify-content:center; }
        .modal-overlay.open { display:flex; }
        .modal-box { background:var(--card); border:1px solid var(--border); border-radius:16px; width:100%; max-width:640px; max-height:92vh; overflow-y:auto; padding:32px; position:relative; }
        .modal-title { font-family:'Playfair Display',serif; font-size:1.3rem; color:var(--gold); margin-bottom:24px; font-weight:600; }
        .modal-close { position:absolute; top:18px; right:20px; background:none; border:none; color:var(--t3); font-size:1.2rem; cursor:pointer; }
        .modal-close:hover { color:var(--t1); }
        .modal-footer { display:flex; justify-content:flex-end; gap:10px; margin-top:24px; }
        .btn-primary { padding:10px 24px; background:var(--gold); color:#1A0A00; border:none; border-radius:8px; font-weight:600; font-size:.9rem; cursor:pointer; transition:opacity .2s; }
        .btn-primary:hover { opacity:.85; }
        .btn-cancel { padding:10px 20px; background:transparent; border:1px solid var(--border); color:var(--t2); border-radius:8px; cursor:pointer; font-size:.9rem; }
        .btn-cancel:hover { border-color:var(--t2); }
        .empty-state { text-align:center; padding:60px 20px; color:var(--t3); grid-column:1/-1; }
        .tip-box { background:rgba(201,151,58,.07); border:1px solid rgba(201,151,58,.2); border-radius:10px; padding:14px 18px; margin-bottom:24px; font-size:.82rem; color:var(--t2); line-height:1.7; }
        .tip-box strong { color:var(--gold); }
        .add-rule-btn { display:flex; align-items:center; gap:6px; padding:7px 14px; background:rgba(201,151,58,.1); border:1px dashed rgba(201,151,58,.4); border-radius:6px; color:var(--gold); cursor:pointer; font-size:.78rem; margin-top:8px; transition:all .2s; width:100%; justify-content:center; }
        .add-rule-btn:hover { background:rgba(201,151,58,.2); }
        .section-divider { font-size:.65rem; color:var(--t3); text-transform:uppercase; letter-spacing:.12em; margin:20px 0 12px; display:flex; align-items:center; gap:10px; }
        .section-divider::after { content:''; flex:1; height:1px; background:var(--border2); }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <div class="topbar-title">🍹 Công Thức Pha Chế</div>
                <div class="topbar-date">Nhân viên nhập công thức — AI tự động học và tư vấn khách</div>
            </div>
            <div class="topbar-right">
                <button class="btn-primary" onclick="openCreate()">+ Thêm công thức</button>
                <button onclick="toggleAdminTheme()" style="background:none;border:1px solid var(--border);border-radius:20px;padding:6px 14px;color:var(--t2);cursor:pointer;font-size:.85rem;">🌓 Giao diện</button>
                <span class="topbar-user">👤 <?php echo htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username']); ?></span>
                <a href="login.php?logout=1" class="btn-logout">Đăng xuất</a>
            </div>
        </div>
        <div class="admin-content">
            <div class="tip-box">
                <strong>💡 Hướng dẫn:</strong> Nhập công thức và định nghĩa <strong>Mix theo mục đích</strong> — khi khách nói "thêm nặng", "thêm thơm"... AI sẽ gợi ý đúng sản phẩm bạn chọn, không tự mò nữa!
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:4px;">
                <button class="occ-tag occ-daily" onclick="filterRecipes('')" id="fAll" style="cursor:pointer;padding:6px 14px;background:var(--gold);color:#1A0A00;border-color:var(--gold);">Tất cả</button>
                <button class="occ-tag occ-party"  onclick="filterRecipes('party')"  id="fParty"  style="cursor:pointer;padding:6px 14px;">🎉 Tiệc</button>
                <button class="occ-tag occ-gift"   onclick="filterRecipes('gift')"   id="fGift"   style="cursor:pointer;padding:6px 14px;">🎁 Quà tặng</button>
                <button class="occ-tag occ-health" onclick="filterRecipes('health')" id="fHealth" style="cursor:pointer;padding:6px 14px;">💚 Sức khoẻ</button>
                <button class="occ-tag occ-daily"  onclick="filterRecipes('daily')"  id="fDaily"  style="cursor:pointer;padding:6px 14px;">☀️ Hằng ngày</button>
            </div>
            <div class="recipe-grid" id="recipeGrid">
                <div class="empty-state"><div style="font-size:2.5rem;">⏳</div><p>Đang tải...</p></div>
            </div>
        </div>
    </main>
</div>

<!-- MODAL THÊM / SỬA -->
<div class="modal-overlay" id="recipeModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal()">✕</button>
        <div class="modal-title" id="modalTitle">Thêm Công Thức Mới</div>

        <div class="form-row">
            <label>Tên công thức *</label>
            <input type="text" id="fTitle" placeholder="VD: Gừng Nếp Ấm, Nếp Mojito...">
        </div>
        <div class="form-row">
            <label>Nguyên liệu * <span style="color:var(--t3);font-size:.72rem;text-transform:none;">(mỗi dòng 1 nguyên liệu)</span></label>
            <textarea id="fIngredients" placeholder="Rượu Nếp 50ml&#10;Nước cốt gừng 20ml&#10;Mật ong 1 thìa"></textarea>
        </div>
        <div class="form-row">
            <label>Cách pha chế * <span style="color:var(--t3);font-size:.72rem;text-transform:none;">(mỗi bước 1 dòng)</span></label>
            <textarea id="fSteps" placeholder="Hoà tan mật ong vào nước ấm&#10;Thêm rượu và khuấy đều&#10;Uống nóng"></textarea>
        </div>
        <div class="form-row">
            <label>Phù hợp dịp nào?</label>
            <div class="occasion-checkboxes">
                <label class="occ-cb-label"><input type="checkbox" value="party">🎉 Tiệc</label>
                <label class="occ-cb-label"><input type="checkbox" value="gift">🎁 Quà tặng</label>
                <label class="occ-cb-label"><input type="checkbox" value="health">💚 Sức khoẻ</label>
                <label class="occ-cb-label"><input type="checkbox" value="daily">☀️ Hằng ngày</label>
            </div>
        </div>
        <div class="form-row">
            <label>Ghi chú thêm</label>
            <textarea id="fNote" rows="2" placeholder="VD: Phù hợp phụ nữ, uống lạnh ngon hơn..."></textarea>
        </div>

        <!-- MIX RULES -->
        <div class="section-divider">🔀 Mix theo mục đích</div>
        <div style="font-size:.78rem;color:var(--t3);margin-bottom:12px;line-height:1.6;">
            Khi khách yêu cầu điều chỉnh, AI sẽ gợi ý đúng sản phẩm bạn chọn bên dưới.
        </div>
        <div id="mixRulesContainer"></div>
        <button class="add-rule-btn" onclick="addMixRule()">+ Thêm gợi ý mix</button>

        <div class="form-row" style="margin-top:18px;">
            <label>Trạng thái</label>
            <select id="fActive">
                <option value="1">✅ Hiển thị — AI sẽ dùng công thức này</option>
                <option value="0">⏸ Ẩn — AI sẽ không dùng tạm thời</option>
            </select>
        </div>

        <div class="modal-footer">
            <button class="btn-cancel" onclick="closeModal()">Huỷ</button>
            <button class="btn-primary" onclick="saveRecipe()">💾 Lưu công thức</button>
        </div>
    </div>
</div>

<div id="toast" class="toast"></div>
<script src="../assets/js/admin.js"></script>
<script>
// ── Data từ PHP ─────────────────────────────────────────────
const ALL_PRODUCTS = <?php echo json_encode($allProducts, JSON_UNESCAPED_UNICODE); ?>;

const INTENT_CONFIG = {
    heavy: { label: '🔥 Thêm độ nặng',  cls: 'tag-heavy',  placeholder: 'VD: Pha tỉ lệ 1:1, hương vị đậm hơn, độ cồn tăng lên ~40°' },
    aroma: { label: '🌸 Thêm hương thơm', cls: 'tag-aroma', placeholder: 'VD: Thêm 10ml, khuấy nhẹ, hương bưởi nổi bật hơn' },
    light: { label: '🧊 Làm nhẹ hơn',    cls: 'tag-light',  placeholder: 'VD: Pha loãng 1:2 với soda, uống nhẹ nhàng hơn' },
    color: { label: '🎨 Tạo màu sắc',    cls: 'tag-color',  placeholder: 'VD: Thêm vài giọt, màu hồng nhạt rất đẹp' },
    taste: { label: '🍯 Thêm vị ngọt',   cls: 'tag-aroma',  placeholder: 'VD: Thêm 1 thìa mật ong, vị dịu hơn nhiều' },
    food:  { label: '🍖 Kết hợp món ăn', cls: 'tag-daily',  placeholder: 'VD: Hợp nhất với hải sản, đặc biệt cua ghẹ' },
};

const API_RECIPES = '../api/recipes.php';
let allRecipes   = [];
let editingId    = null;
let ruleCounter  = 0;

// ── Load ───────────────────────────────────────────────────
async function loadRecipes() {
    try {
        allRecipes = await adminFetch(API_RECIPES);
        renderRecipes(allRecipes);
    } catch(e) { showToast('❌ ' + e.message, 'error'); }
}

function renderRecipes(list) {
    const grid = document.getElementById('recipeGrid');
    if (!list.length) {
        grid.innerHTML = `<div class="empty-state"><div style="font-size:2.5rem;">🍹</div><p>Chưa có công thức nào. Bấm <strong>+ Thêm công thức</strong>!</p></div>`;
        return;
    }
    const occLabels = { party:{cls:'occ-party',text:'🎉 Tiệc'}, gift:{cls:'occ-gift',text:'🎁 Quà'}, health:{cls:'occ-health',text:'💚 Sức khoẻ'}, daily:{cls:'occ-daily',text:'☀️ Hằng ngày'} };
    grid.innerHTML = list.map(r => {
        const occs = (r.occasion||'').split(',').map(s=>s.trim()).filter(Boolean);
        const tags = occs.map(o => { const lb=occLabels[o]; return lb?`<span class="occ-tag ${lb.cls}">${lb.text}</span>`:''; }).join('');

        // Parse mix_rules
        let mixRulesHtml = '';
        try {
            const rules = JSON.parse(r.mix_rules || '[]');
            if (rules.length) {
                const intentMap = { heavy:'🔥 Nặng thêm', aroma:'🌸 Thơm thêm', light:'🧊 Nhẹ hơn', color:'🎨 Màu sắc', taste:'🍯 Vị ngọt', food:'🍖 Món ăn' };
                mixRulesHtml = `<div class="mix-rules-section">
                    <div class="recipe-section-label">Gợi ý mix theo yêu cầu</div>
                    ${rules.map(rule => {
                        const prod = ALL_PRODUCTS.find(p => p.id == rule.product_id);
                        const intentLabel = intentMap[rule.intent] || rule.intent;
                        return `<div class="mix-rule-item">
                            <span class="occ-tag ${INTENT_CONFIG[rule.intent]?.cls||'occ-daily'}" style="font-size:.6rem;white-space:nowrap;flex-shrink:0;">${intentLabel}</span>
                            <div>
                                <div class="mix-rule-product">${prod ? prod.name + ' (' + prod.alc + '°)' : 'ID#'+rule.product_id}</div>
                                ${rule.note ? `<div class="mix-rule-note-small">${escHtml(rule.note)}</div>` : ''}
                            </div>
                        </div>`;
                    }).join('')}
                </div>`;
            }
        } catch(e) {}

        return `<div class="recipe-card ${r.is_active==1?'':'inactive'}">
            <div class="recipe-card-actions">
                <button class="btn-icon" onclick="openEdit(${r.id})" title="Sửa">✏️</button>
                <button class="btn-icon danger" onclick="deleteRecipe(${r.id},'${escHtml(r.title)}')" title="Xoá">🗑</button>
            </div>
            <div class="recipe-card-title">${escHtml(r.title)}</div>
            ${tags ? `<div class="recipe-occasion-tags">${tags}</div>` : ''}
            ${r.is_active==0 ? '<span style="font-size:.7rem;color:#e07070;margin-bottom:8px;display:block;">⏸ Đang ẩn</span>' : ''}
            <div class="recipe-section"><div class="recipe-section-label">Nguyên liệu</div><div class="recipe-section-val">${escHtml(r.ingredients)}</div></div>
            <div class="recipe-section"><div class="recipe-section-label">Cách pha</div><div class="recipe-section-val">${escHtml(r.steps)}</div></div>
            ${r.note ? `<div class="recipe-section"><div class="recipe-section-label">Ghi chú</div><div class="recipe-section-val" style="color:var(--t3);">${escHtml(r.note)}</div></div>` : ''}
            ${mixRulesHtml}
        </div>`;
    }).join('');
}

function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Filter ─────────────────────────────────────────────────
function filterRecipes(occ) {
    const filtered = occ ? allRecipes.filter(r => (r.occasion||'').includes(occ)) : allRecipes;
    renderRecipes(filtered);
    ['All','Party','Gift','Health','Daily'].forEach(k => {
        const btn = document.getElementById('f'+k);
        if (!btn) return;
        const active = (k==='All'&&!occ) || k.toLowerCase()===occ;
        btn.style.background  = active ? 'var(--gold)' : '';
        btn.style.color       = active ? '#1A0A00' : '';
        btn.style.borderColor = active ? 'var(--gold)' : '';
    });
}

// ── Mix Rules UI ───────────────────────────────────────────
function addMixRule(data = {}) {
    const id  = ++ruleCounter;
    const con = document.getElementById('mixRulesContainer');
    const div = document.createElement('div');
    div.className  = 'mix-form-row';
    div.dataset.id = id;

    const productOptions = ALL_PRODUCTS.map(p =>
        `<option value="${p.id}" ${p.id==data.product_id?'selected':''}>${p.name} (${p.alc}° — ${Number(p.price).toLocaleString('vi-VN')}đ)</option>`
    ).join('');

    const intentOptions = Object.entries(INTENT_CONFIG).map(([k,v]) =>
        `<option value="${k}" ${k===data.intent?'selected':''}>${v.label}</option>`
    ).join('');

    div.innerHTML = `
        <select class="intent-sel" onchange="updateRulePlaceholder(${id},this.value)">
            ${intentOptions}
        </select>
        <div style="display:flex;flex-direction:column;gap:5px;">
            <select class="product-sel">
                <option value="">-- Chọn sản phẩm --</option>
                ${productOptions}
            </select>
            <input type="text" class="rule-note" placeholder="${INTENT_CONFIG[data.intent||'heavy']?.placeholder||''}"
                   value="${escHtml(data.note||'')}" style="font-size:.75rem;padding:5px 8px;">
        </div>
        <button class="btn-remove-rule" onclick="this.closest('.mix-form-row').remove()" title="Xoá">✕</button>`;

    con.appendChild(div);
}

function updateRulePlaceholder(id, intent) {
    const row = document.querySelector(`.mix-form-row[data-id="${id}"]`);
    if (!row) return;
    const inp = row.querySelector('.rule-note');
    if (inp) inp.placeholder = INTENT_CONFIG[intent]?.placeholder || '';
}

function getMixRules() {
    const rows = document.querySelectorAll('.mix-form-row');
    const rules = [];
    rows.forEach(row => {
        const intent     = row.querySelector('.intent-sel')?.value;
        const product_id = row.querySelector('.product-sel')?.value;
        const note       = row.querySelector('.rule-note')?.value.trim();
        if (intent && product_id) rules.push({ intent, product_id: parseInt(product_id), note });
    });
    return rules;
}

function setMixRules(rules) {
    document.getElementById('mixRulesContainer').innerHTML = '';
    ruleCounter = 0;
    (rules || []).forEach(r => addMixRule(r));
}

// ── Modal ──────────────────────────────────────────────────
function openCreate() {
    editingId = null;
    document.getElementById('modalTitle').textContent = '✨ Thêm Công Thức Mới';
    ['fTitle','fIngredients','fSteps','fNote'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('fActive').value = '1';
    document.querySelectorAll('.occ-cb-label input').forEach(cb => cb.checked = false);
    setMixRules([]);
    document.getElementById('recipeModal').classList.add('open');
    setTimeout(() => document.getElementById('fTitle').focus(), 150);
}

function openEdit(id) {
    const r = allRecipes.find(x => x.id == id);
    if (!r) return;
    editingId = id;
    document.getElementById('modalTitle').textContent  = '✏️ Sửa Công Thức';
    document.getElementById('fTitle').value       = r.title;
    document.getElementById('fIngredients').value = r.ingredients;
    document.getElementById('fSteps').value       = r.steps;
    document.getElementById('fNote').value        = r.note || '';
    document.getElementById('fActive').value      = r.is_active;
    const occs = (r.occasion||'').split(',').map(s=>s.trim());
    document.querySelectorAll('.occ-cb-label input').forEach(cb => cb.checked = occs.includes(cb.value));
    try { setMixRules(JSON.parse(r.mix_rules || '[]')); } catch(e) { setMixRules([]); }
    document.getElementById('recipeModal').classList.add('open');
}

function closeModal() { document.getElementById('recipeModal').classList.remove('open'); }

// ── Save ───────────────────────────────────────────────────
async function saveRecipe() {
    const title       = document.getElementById('fTitle').value.trim();
    const ingredients = document.getElementById('fIngredients').value.trim();
    const steps       = document.getElementById('fSteps').value.trim();
    const note        = document.getElementById('fNote').value.trim();
    const is_active   = parseInt(document.getElementById('fActive').value);
    const occasion    = [...document.querySelectorAll('.occ-cb-label input:checked')].map(cb=>cb.value).join(',');
    const mix_rules   = JSON.stringify(getMixRules());

    if (!title)       { showToast('⚠ Nhập tên công thức!', 'error'); return; }
    if (!ingredients) { showToast('⚠ Nhập nguyên liệu!', 'error'); return; }
    if (!steps)       { showToast('⚠ Nhập cách pha chế!', 'error'); return; }

    try {
        const action = editingId ? 'update' : 'create';
        const body   = { title, ingredients, steps, occasion, note, is_active, mix_rules };
        if (editingId) body.id = editingId;
        await adminFetch(API_RECIPES + '?action=' + action, { method:'POST', body:JSON.stringify(body) });
        closeModal();
        showToast('✅ Đã lưu!', 'success');
        await loadRecipes();
    } catch(e) { showToast('❌ ' + e.message, 'error'); }
}

// ── Delete ─────────────────────────────────────────────────
async function deleteRecipe(id, title) {
    if (!confirm(`Xoá công thức "${title}"?`)) return;
    try {
        await adminFetch(API_RECIPES + '?action=delete', { method:'POST', body:JSON.stringify({id}) });
        showToast('🗑 Đã xoá!', 'success');
        await loadRecipes();
    } catch(e) { showToast('❌ ' + e.message, 'error'); }
}

document.addEventListener('DOMContentLoaded', () => {
    loadRecipes();
    document.getElementById('recipeModal').addEventListener('click', e => {
        if (e.target === document.getElementById('recipeModal')) closeModal();
    });
});
</script>
<script>
function toggleAdminTheme() {
    var t = document.documentElement.getAttribute('data-theme')==='light'?'dark':'light';
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('tlc-theme', t);
}
</script>
</body>
</html>