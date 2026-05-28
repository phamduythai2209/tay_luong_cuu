// ============================================================
// FILE: assets/js/app.js
// ============================================================

var BASE = (function() {
    var scripts = document.getElementsByTagName('script');
    for (var i = 0; i < scripts.length; i++) {
        var src = scripts[i].src;
        if (src && src.indexOf('assets/js/app.js') > -1) {
            return src.replace('assets/js/app.js', '');
        }
    }
    return '/';
})();

const API = {
    products   : BASE + 'api/products.php',
    orders     : BASE + 'api/orders.php',
    suggestions: BASE + 'api/suggestions.php',
    auth       : BASE + 'api/auth.php',
    upload     : BASE + 'api/upload.php',
};

if (typeof productsList === 'undefined') { var productsList = []; }
var cart = [];
var selectedPayment = '';

// ══════════════════════════════════════════════════════════════
// THEME
// ══════════════════════════════════════════════════════════════
var currentTheme = 'dark';
try { currentTheme = localStorage.getItem('tlc-theme') || 'dark'; } catch(e) {}

function applyTheme(t) {
    currentTheme = t;
    document.documentElement[t === 'light' ? 'setAttribute' : 'removeAttribute']('data-theme', 'light');
    const icon = document.getElementById('themeIcon');
    if (icon) icon.textContent = t === 'light' ? '☀️' : '🌙';
    try { localStorage.setItem('tlc-theme', t); } catch(e) {}
}
function toggleTheme() { applyTheme(currentTheme === 'dark' ? 'light' : 'dark'); }

function toggleHeroFlip() {
    const card = document.getElementById('heroFlipCard');
    const hint = document.getElementById('hvTapHint');
    if (!card) return;
    card.classList.toggle('flipped');
    if (hint) hint.textContent = card.classList.contains('flipped') ? 'Bấm để quay lại' : 'Bấm để xem chai rượu';
}

// ══════════════════════════════════════════════════════════════
// API HELPER
// ══════════════════════════════════════════════════════════════
async function apiFetch(url, options = {}) {
    const res  = await fetch(url, {
        credentials: 'include',
        headers:     { 'Content-Type': 'application/json' },
        ...options,
    });
    const text = await res.text();
    let data;
    try {
        data = JSON.parse(text);
    } catch(e) {
        console.error('API không trả JSON:', url, text.substring(0, 200));
        throw new Error('Lỗi server: ' + url.split('/').pop());
    }
    if (!res.ok && data && data.error) throw new Error(data.error);
    return data;
}

// ══════════════════════════════════════════════════════════════
// AGE GATE
// ══════════════════════════════════════════════════════════════
function closeAgeGate() {
    const el = document.getElementById('ageGate');
    if (el) el.style.display = 'none';
    try { localStorage.setItem('tlc_age_verified', 'true'); } catch(e) {}
}

function checkAgeGate() {
    try {
        const verified = localStorage.getItem('tlc_age_verified');
        if (verified === 'true') {
            const el = document.getElementById('ageGate');
            if (el) el.style.display = 'none';
        }
    } catch(e) {}
}

// ══════════════════════════════════════════════════════════════
// FETCH & RENDER SẢN PHẨM
// ══════════════════════════════════════════════════════════════
async function fetchProducts() {
    try {
        const data   = await apiFetch(API.products);
        productsList = data;
        renderProducts();
    } catch (err) {
        console.error('Lỗi tải sản phẩm:', err);
    }
}

function renderProducts() {
    const grid = document.getElementById('productGrid');
    if (!grid) return;
    const active = productsList.filter(p => p.is_active);
    if (!active.length) {
        grid.innerHTML = '<p style="text-align:center;color:var(--t3);padding:60px;grid-column:1/-1;">Đang tải sản phẩm...</p>';
        return;
    }
    grid.innerHTML = active.map(p => buildCard(p)).join('');
}

function buildCard(p) {
    const badgeMap   = { hot:'badge-hot', new:'badge-new', limited:'badge-limited', sale:'badge-sale' };
    const badgeClass = badgeMap[p.badge] || 'badge-new';
    const outOfStock = (p.stock || 0) <= 0;
    const priceOldHtml = p.priceOld ? `<span class="card-price-old">₫${Number(p.priceOld).toLocaleString('vi-VN')}</span>` : '';

    var imgSrc = null;
    if (p.img) {
        if (p.img.indexOf('http') === 0 || p.img.indexOf('//') === 0) {
            imgSrc = p.img;
        } else if (p.img.indexOf('/') === 0) {
            imgSrc = p.img;
        } else {
            imgSrc = BASE + p.img;
        }
    }
    const imgHtml = p.img
        ? `<img src="${imgSrc}" alt="${p.short||p.name}" onerror="this.parentElement.innerHTML=buildBottleSVG({id:${p.id||0},cat:'',short:'${(p.short||'').replace(/'/g,'')}'});this.remove();">`
        : buildBottleSVG(p);

    return `<div class="product-card" onclick="window.location.href='product.php?id=${p.id}'" style="cursor:pointer;">
        <div class="card-img">
            ${imgHtml}
            ${p.badge ? `<div class="card-badge ${badgeClass}">${p.badgeText || p.badge}</div>` : ''}
            <div class="card-overlay">
                <p class="overlay-note">${p.desc || p.flavor || ''}</p>
            </div>
        </div>
        <div class="card-body">
            <div class="card-origin">${p.origin || ''}</div>
            <div class="card-name">${p.name}</div>
            <div class="card-sub">${p.alc}° · ${p.vol}ml${p.flavor ? ' · ' + p.flavor : ''}</div>
            <div class="card-footer">
                <div>
                    <span class="card-price">₫${Number(p.price).toLocaleString('vi-VN')}</span>
                    ${priceOldHtml}
                    ${outOfStock ? '<small style="color:#D06060;margin-left:6px;font-size:.65rem;">Hết hàng</small>' : ''}
                </div>
                <div style="display:flex;gap:5px;">
                    <button class="card-cmp-btn" id="cmpBtn${p.id}"
                        onclick="event.stopPropagation();toggleCompare(${p.id})"
                        title="So sánh"
                        style="width:30px;height:30px;border-radius:50%;border:1px solid var(--border);
                               background:transparent;color:var(--t3);cursor:pointer;font-size:.75rem;
                               display:flex;align-items:center;justify-content:center;transition:all .2s;">⚖</button>
                    <button class="card-add" onclick="event.stopPropagation();addToCart(${p.id})"
                        ${outOfStock ? 'disabled' : ''}>+</button>
                </div>
            </div>
        </div>
    </div>`;
}

function buildBottleSVG(p) {
    const colors = { 'Rượu Nếp':['#1A0820','#4A1A5A'], 'Rượu Thuốc':['#1A0C02','#4A2808'], 'Rượu Ngô':['#0A1A08','#1E3A1A'] };
    const c = colors[p.cat] || ['#1A0808','#3A1818'];
    const gid = 'g'+p.id;
    return `<svg style="height:220px;width:auto;" viewBox="0 0 100 300" fill="none">
        <defs><linearGradient id="${gid}" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" stop-color="${c[0]}"/><stop offset="50%" stop-color="${c[1]}"/><stop offset="100%" stop-color="${c[0]}"/>
        </linearGradient></defs>
        <rect x="38" y="10" width="24" height="7" rx="2" fill="#C9973A"/>
        <rect x="35" y="16" width="30" height="4" rx="1" fill="#A07020"/>
        <path d="M40 20L37 55L63 55L60 20Z" fill="url(#${gid})"/>
        <path d="M37 55Q30 75 28 95L72 95Q70 75 63 55Z" fill="url(#${gid})"/>
        <rect x="28" y="95" width="44" height="178" rx="4" fill="url(#${gid})"/>
        <rect x="32" y="118" width="36" height="120" rx="2" fill="rgba(201,151,58,.07)" stroke="rgba(201,151,58,.3)" stroke-width=".7"/>
        <text x="50" y="158" text-anchor="middle" font-family="Georgia,serif" font-size="5" letter-spacing="2" fill="rgba(201,151,58,.85)">TÂY LƯƠNG</text>
        <text x="50" y="172" text-anchor="middle" font-family="Georgia,serif" font-size="5" fill="rgba(201,151,58,.7)">CỬU</text>
        <text x="50" y="195" text-anchor="middle" font-family="Georgia,serif" font-size="4.5" fill="rgba(248,240,224,.55)">${(p.short||p.name).toUpperCase().slice(0,12)}</text>
    </svg>`;
}

// ══════════════════════════════════════════════════════════════
// PRODUCT DETAIL MODAL
// ══════════════════════════════════════════════════════════════
function openProductDetail(id) {
    const p = productsList.find(x => x.id === id);
    if (!p) return;

    const src = p.img
        ? (p.img.startsWith('http') || p.img.startsWith('/') ? p.img : BASE + p.img)
        : null;

    const outOfStock = (p.stock || 0) <= 0;
    const priceOldHtml = p.priceOld
        ? `<span style="font-size:1rem;color:var(--t3);text-decoration:line-through;margin-left:10px;">₫${Number(p.priceOld).toLocaleString('vi-VN')}</span>`
        : '';

    const imgWrapHtml = src
        ? `<div style="width:100%;height:100%;overflow:hidden;cursor:zoom-in;position:relative;"
               id="pdImgWrap"
               onmousemove="zoomImg(event,this)"
               onmouseleave="resetZoom(this)">
               <img id="pdMainImg" src="${src}" alt="${p.name}"
                   style="width:100%;height:100%;object-fit:contain;transition:transform .15s ease;transform-origin:center center;"
                   onerror="this.style.display='none'">
           </div>`
        : `<div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;">${buildBottleSVG(p)}</div>`;
    document.getElementById('pdImg').innerHTML = imgWrapHtml;

    document.getElementById('pdBadge').innerHTML = p.badge
        ? `<span class="card-badge badge-${p.badge}" style="position:static;display:inline-block;">${p.badgeText||p.badge}</span>`
        : '';
    document.getElementById('pdName').textContent = p.name;
    document.getElementById('pdSub').textContent  = `${p.alc}° · ${p.vol}ml${p.flavor ? ' · ' + p.flavor : ''}`;
    document.getElementById('pdPrice').innerHTML  = `₫${Number(p.price).toLocaleString('vi-VN')}${priceOldHtml}`;
    document.getElementById('pdDesc').textContent = p.desc || p.flavor || 'Sản phẩm rượu truyền thống Tây Lương Cửu.';
    document.getElementById('pdStock').innerHTML  = outOfStock
        ? '<span style="color:#e07070;">⚠ Hết hàng</span>'
        : `<span style="color:#6DD880;">✅ Còn hàng</span> <span style="color:var(--t3);font-size:.8rem;">(${p.stock} chai)</span>`;

    document.getElementById('pdSpecs').innerHTML = [
        ['Danh mục',  p.cat     || '—'],
        ['Độ cồn',    p.alc ? p.alc + '°' : '—'],
        ['Dung tích', p.vol ? p.vol + ' ml' : '—'],
        ['Xuất xứ',   p.origin  || '—'],
        ['Hương vị',  p.flavor  || '—'],
    ].map(([label, val]) => `
        <div style="display:flex;justify-content:space-between;align-items:flex-start;
                    padding:12px 0;border-bottom:1px solid var(--border2);">
            <span style="font-family:'Times New Roman',Times,serif;font-size:.9rem;color:var(--t3);min-width:100px;flex-shrink:0;">${label}</span>
            <span style="font-family:'Times New Roman',Times,serif;font-size:1rem;color:var(--t1);font-weight:600;text-align:right;line-height:1.5;">${val}</span>
        </div>`).join('');

    const maxQty = outOfStock ? 0 : Math.min(p.stock, 99);
    const pdQtySection = document.getElementById('pdQtySection');
    const pdQtyInput   = document.getElementById('pdQtyInput');
    if (pdQtySection) pdQtySection.style.display = outOfStock ? 'none' : '';
    if (pdQtyInput)   { pdQtyInput.max = maxQty; pdQtyInput.value = 1; }

    const addBtn = document.getElementById('pdAddBtn');
    addBtn.dataset.pid = p.id;
    addBtn.disabled    = outOfStock;
    addBtn.textContent = outOfStock ? '— Hết hàng —' : '+ Thêm vào giỏ hàng';
    addBtn.onclick = () => { addToCart(p.id); closeModal('productDetailModal'); };

    const cmpSec = document.getElementById('pdCompareSection');
    cmpSec.style.display = 'none';

    openModal('productDetailModal');
}

function zoomImg(e, wrap) {
    const img  = wrap.querySelector('#pdMainImg');
    if (!img) return;
    const rect = wrap.getBoundingClientRect();
    const x    = ((e.clientX - rect.left) / rect.width)  * 100;
    const y    = ((e.clientY - rect.top)  / rect.height) * 100;
    img.style.transformOrigin = `${x}% ${y}%`;
    img.style.transform       = 'scale(2.2)';
}
function resetZoom(wrap) {
    const img = wrap.querySelector('#pdMainImg');
    if (img) { img.style.transform = 'scale(1)'; img.style.transformOrigin = 'center center'; }
}

function pdGetCurrentProduct() {
    const pid = parseInt(document.getElementById('pdAddBtn')?.dataset.pid);
    return pid ? productsList.find(x => x.id === pid) : null;
}
function pdSyncQty(input) {
    const max = parseInt(input.max) || 99;
    let v = parseInt(input.value) || 1;
    if (v < 1) v = 1;
    if (v > max) { v = max; showToast(`⚠ Chỉ còn ${max} chai trong kho!`); }
    input.value = v;
    const p = pdGetCurrentProduct();
    if (p) updatePdTotal(p);
}
function pdSelectQty(val) {
    const input = document.getElementById('pdQtyInput');
    if (!input) return;
    input.value = Math.min(val, parseInt(input.max) || 99);
    pdSyncQty(input);
}
function pdChangeQty(delta) {
    const input = document.getElementById('pdQtyInput');
    if (!input) return;
    input.value = (parseInt(input.value) || 1) + delta;
    pdSyncQty(input);
}
function updatePdTotal(p) {
    const qty   = parseInt(document.getElementById('pdQtyInput')?.value) || 1;
    const total = qty * (p.price || 0);
    const el    = document.getElementById('pdTotalPrice');
    if (el) el.textContent = '₫' + total.toLocaleString('vi-VN');
}
window.pdChangeQty   = pdChangeQty;
window.pdSyncQty     = pdSyncQty;
window.pdSelectQty   = pdSelectQty;
window.updatePdTotal = updatePdTotal;

function addToCartQty(id, qty) {
    const p = productsList.find(x => x.id === id);
    if (!p || qty < 1) return;
    if ((p.stock || 0) <= 0) { showToast('⚠ Sản phẩm đã hết hàng!'); return; }
    if (qty > p.stock) { showToast(`⚠ Chỉ còn ${p.stock} chai!`); return; }
    var fullImg = p.img;
    if (fullImg && fullImg.indexOf('http') !== 0 && fullImg.indexOf('//') !== 0 && fullImg.indexOf('/') !== 0) {
        fullImg = BASE + fullImg;
    }
    const ex = cart.find(i => i.id === id);
    if (ex) {
        const newQty = ex.qty + qty;
        ex.qty = newQty > p.stock ? p.stock : newQty;
        if (newQty > p.stock) showToast(`⚠ Chỉ còn ${p.stock} chai!`);
    } else {
        cart.push({ ...p, img: fullImg, qty });
    }
    updateCartUI();
    showToast(`✦ Đã thêm ${qty} × ${p.short || p.name}`);
}

// ══════════════════════════════════════════════════════════════
// HỆ THỐNG SO SÁNH SẢN PHẨM
// ══════════════════════════════════════════════════════════════
var compareList = [];

function toggleCompare(id) {
    const idx = compareList.indexOf(id);
    if (idx > -1) { compareList.splice(idx, 1); } else { compareList.push(id); }
    updateCompareBar();
}
function removeFromCompare(id) { compareList = compareList.filter(x => x !== id); updateCompareBar(); }
function clearCompare() { compareList = []; updateCompareBar(); }

function updateCompareBar() {
    document.querySelectorAll('.card-cmp-btn').forEach(btn => {
        const id = parseInt(btn.id.replace('cmpBtn',''));
        const active = compareList.includes(id);
        btn.style.background  = active ? 'var(--gold)' : 'transparent';
        btn.style.color       = active ? '#1A0A00'     : 'var(--t3)';
        btn.style.borderColor = active ? 'var(--gold)' : 'var(--border)';
    });

    var bar = document.getElementById('compareBar');
    if (!bar) return;

    if (compareList.length === 0) {
        bar.style.transform = 'translateY(100%)';
        bar.style.opacity   = '0';
        return;
    }

    bar.style.transform = 'translateY(0)';
    bar.style.opacity   = '1';

    const items = compareList.map(id => {
        const p = productsList.find(x => x.id === id);
        if (!p) return '';
        const src = p.img ? (p.img.startsWith('http') || p.img.startsWith('/') ? p.img : BASE + p.img) : null;
        return `<div style="display:flex;align-items:center;gap:8px;background:rgba(255,255,255,.06);
                            padding:6px 10px;border-radius:6px;border:1px solid var(--border);">
            <div style="width:36px;height:36px;background:var(--card2);border-radius:4px;
                        display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;">
                ${src ? `<img src="${src}" style="width:100%;height:100%;object-fit:cover;">` : `<span style="font-size:1.2rem;">🍶</span>`}
            </div>
            <span style="font-size:.75rem;color:var(--t1);max-width:120px;white-space:nowrap;
                         overflow:hidden;text-overflow:ellipsis;">${p.short||p.name}</span>
            <button onclick="removeFromCompare(${id})"
                style="background:none;border:none;color:var(--t3);cursor:pointer;font-size:.8rem;padding:0;line-height:1;flex-shrink:0;">✕</button>
        </div>`;
    }).join('');

    document.getElementById('compareBarItems').innerHTML = items;
    document.getElementById('compareBarCount').textContent = compareList.length;
}

function openCompareModal() {
    if (compareList.length < 2) { showToast('⚠ Chọn ít nhất 2 sản phẩm để so sánh!'); return; }
    const cols = compareList.map(id => productsList.find(x => x.id === id)).filter(Boolean);
    const fields = [
        ['Giá bán',   x => `<strong style="color:var(--gold);">₫${Number(x.price).toLocaleString('vi-VN')}</strong>`],
        ['Giá cũ',    x => x.priceOld ? `<s style="color:var(--t3);">₫${Number(x.priceOld).toLocaleString('vi-VN')}</s>` : '—'],
        ['Độ cồn',    x => x.alc ? `<strong>${x.alc}°</strong>` : '—'],
        ['Dung tích', x => x.vol ? `${x.vol} ml` : '—'],
        ['Xuất xứ',   x => x.origin || '—'],
        ['Danh mục',  x => x.cat || '—'],
        ['Hương vị',  x => x.flavor || '—'],
        ['Mô tả',     x => `<span style="font-size:.82rem;line-height:1.7;">${x.desc || '—'}</span>`],
        ['Tồn kho',   x => (x.stock||0) > 0 ? `<span style="color:#6DD880;font-weight:600;">${x.stock} chai</span>` : `<span style="color:#e07070;">Hết hàng</span>`],
    ];
    const colW = Math.max(160, Math.floor(700 / cols.length));
    document.getElementById('cmpModalBody').innerHTML = `
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:.84rem;min-width:${120 + cols.length*colW}px;">
            <thead><tr>
                <th style="width:130px;padding:14px 16px;text-align:left;border-bottom:2px solid var(--border);
                           color:var(--t3);font-size:.65rem;letter-spacing:.15em;text-transform:uppercase;
                           background:var(--card2);position:sticky;left:0;z-index:1;"></th>
                ${cols.map(x => {
                    const src = x.img ? (x.img.startsWith('http') || x.img.startsWith('/') ? x.img : BASE + x.img) : null;
                    return `<th style="padding:14px 16px;text-align:center;border-bottom:2px solid var(--gold);min-width:${colW}px;">
                        <div style="width:72px;height:72px;margin:0 auto 10px;background:var(--card2);border-radius:8px;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                            ${src ? `<img src="${src}" style="width:100%;height:100%;object-fit:cover;">` : `<span style="font-size:2rem;">🍶</span>`}
                        </div>
                        <div style="font-size:.82rem;color:var(--ivory);font-weight:600;line-height:1.3;margin-bottom:6px;">${x.name}</div>
                        <button onclick="addToCart(${x.id})" style="font-size:.65rem;padding:5px 12px;background:var(--gold);color:#1A0A00;border:none;border-radius:4px;cursor:pointer;letter-spacing:.08em;">+ Thêm giỏ</button>
                    </th>`;
                }).join('')}
            </tr></thead>
            <tbody>
                ${fields.map(([label, fn], ri) => `
                <tr style="background:${ri%2===0?'rgba(255,255,255,.025)':'transparent'};">
                    <td style="padding:12px 16px;color:var(--t3);font-size:.78rem;border-bottom:1px solid var(--border2);
                               background:${ri%2===0?'rgba(255,255,255,.025)':'var(--bg)'};position:sticky;left:0;z-index:1;">${label}</td>
                    ${cols.map(x => `<td style="padding:12px 16px;text-align:center;border-bottom:1px solid var(--border2);color:var(--t2);">${fn(x)}</td>`).join('')}
                </tr>`).join('')}
            </tbody>
        </table></div>`;
    openModal('compareModal');
}

async function loadSuggestions(productId, containerId = 'suggestionsGrid') {
    try {
        const data = await apiFetch(`${API.suggestions}?product_id=${productId}`);
        const el   = document.getElementById(containerId);
        if (!el) return;
        el.innerHTML = data.length
            ? data.map(p => buildCard(p)).join('')
            : '<p style="color:var(--t3);text-align:center;padding:20px;">Không có gợi ý phù hợp</p>';
    } catch (err) { console.error('Gợi ý thất bại:', err); }
}

// ══════════════════════════════════════════════════════════════
// CART
// ══════════════════════════════════════════════════════════════
function toggleCart() {
    document.getElementById('cartDrawer')?.classList.toggle('open');
    document.getElementById('cartOverlay')?.classList.toggle('open');
}

function addToCart(id) {
    const p = productsList.find(x => x.id === id);
    if (!p) return;
    if ((p.stock || 0) <= 0) { showToast('⚠ Sản phẩm đã hết hàng!'); return; }
    const ex = cart.find(i => i.id === id);
    if (ex) {
        if (ex.qty >= p.stock) { showToast(`⚠ Chỉ còn ${p.stock} chai!`); return; }
        ex.qty++;
    } else {
        var fullImg = p.img;
        if (fullImg) {
            if (fullImg.indexOf('http') !== 0 && fullImg.indexOf('//') !== 0 && fullImg.indexOf('/') !== 0) {
                fullImg = BASE + fullImg;
            }
        }
        cart.push({ ...p, img: fullImg, qty: 1 });
    }
    updateCartUI();
    showToast(`✦ Đã thêm ${p.short || p.name}`);
}

function removeFromCart(id) { cart = cart.filter(i => i.id !== id); updateCartUI(); }

function changeQty(id, delta) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    item.qty += delta;
    if (item.qty <= 0) { removeFromCart(id); return; }
    const p = productsList.find(x => x.id === id);
    if (p && item.qty > p.stock) { item.qty = p.stock; showToast(`⚠ Chỉ còn ${p.stock} chai!`); }
    updateCartUI();
}

function updateCartUI() {
    const total = cart.reduce((s, i) => s + i.price * i.qty, 0);
    const count = cart.reduce((s, i) => s + i.qty, 0);
    ['cartCount','cartCountNav'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = count;
            el.style.animation = 'none';
            el.offsetHeight;
            el.style.animation = 'badgeBounce .4s cubic-bezier(.34,1.56,.64,1)';
        }
    });
    const totalEl = document.getElementById('cartTotal');
    if (totalEl) totalEl.textContent = '₫' + total.toLocaleString('vi-VN');

    const body = document.getElementById('cartBody');
    if (!body) return;
    if (!cart.length) {
        body.innerHTML = '<div class="cart-empty">Giỏ hàng trống<br><small>Hãy chọn những chai rượu ưa thích ✦</small></div>';
        return;
    }
    body.innerHTML = cart.map(item => `
        <div class="cart-item">
            <div class="cart-item-img">
                ${item.img
                    ? `<img src="${item.img}" style="width:100%;height:100%;object-fit:cover;border-radius:4px;" onerror="this.style.display='none';this.parentElement.innerHTML='<span style=font-size:1.6rem>🍶</span>';">`
                    : '<span style="font-size:1.6rem;">🍶</span>'}
            </div>
            <div style="flex:1;">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-sub">${item.alc}° · ${item.vol}ml</div>
                <div class="cart-item-row">
                    <span class="cart-item-price">₫${Number(item.price * item.qty).toLocaleString('vi-VN')}</span>
                    <div class="qty-ctrl">
                        <button class="qty-btn" onclick="changeQty(${item.id},-1)">−</button>
                        <span class="qty-val">${item.qty}</span>
                        <button class="qty-btn" onclick="changeQty(${item.id},1)">+</button>
                    </div>
                    <button class="cart-del" onclick="removeFromCart(${item.id})">✕</button>
                </div>
            </div>
        </div>`).join('');
}

// ══════════════════════════════════════════════════════════════
// CHECKOUT — SEPAY
// ══════════════════════════════════════════════════════════════
var sePayOrderCode = '';
var sePayPollTimer = null;
var sePayTickTimer = null;

function openCheckout(type) {
    if (!cart.length) { showToast('⚠ Giỏ hàng đang trống!'); return; }
    if (!loadCustomer()) {
        showToast('👤 Vui lòng đăng nhập để thanh toán!');
        setTimeout(() => openModal('authModal'), 300);
        return;
    }
    const total   = cart.reduce((s, i) => s + i.price * i.qty, 0);
    const summary = cart.map(i => `${i.short||i.name} × ${i.qty}`).join(', ');

    if (type === 'online') {
        clearInterval(sePayPollTimer); clearInterval(sePayTickTimer);
        var s1 = document.getElementById('sePayStep1');
        var s2 = document.getElementById('sePayStep2');
        var tt = document.getElementById('sePayModalTitle');
        var cd = document.getElementById('sePayCountdown');
        if (s1) s1.style.display = 'block';
        if (s2) s2.style.display = 'none';
        if (tt) tt.textContent   = '📱 Thanh Toán SePay';
        if (cd) cd.style.display = 'none';
        var sumEl = document.getElementById('onlineSummary');
        if (sumEl) sumEl.innerHTML = '<div class="order-summary">' + summary +
            '<div class="summary-total"><span>Tổng</span><span>₫' + total.toLocaleString('vi-VN') + '</span></div></div>';
        openModal('checkoutOnlineModal');
    } else {
        syncCodDropdown();
        const sumEl = document.getElementById('codSummary');
        if (sumEl) sumEl.innerHTML = `<div class="order-summary">${summary}
            <div class="summary-total"><span>Tổng</span><span>₫${total.toLocaleString('vi-VN')}</span></div></div>`;
        openModal('checkoutCodModal');
    }
}

async function createSePayOrder() {
    const name    = document.getElementById('onName')?.value.trim();
    const phone   = document.getElementById('onPhone')?.value.trim();
    const address = document.getElementById('onAddress')?.value.trim();
    const note    = document.getElementById('onNote')?.value.trim();
    if (!name)    { showToast('⚠ Nhập họ tên!'); return; }
    if (!phone)   { showToast('⚠ Nhập SĐT!'); return; }
    if (!address) { showToast('⚠ Nhập địa chỉ!'); return; }
    const btn = document.getElementById('btnShowQR');
    if (btn) { btn.textContent = 'Đang tạo QR...'; btn.disabled = true; }
    try {
        const res = await apiFetch(BASE + 'api/payment.php', {
            method: 'POST',
            body: JSON.stringify({ customer_name: name, phone, address, note,
                customer_id: loadCustomer()?.id || 0,
                items: cart.map(i => ({ product_id: i.id, qty: i.qty })) })
        });
        sePayOrderCode = res.order_code;
        document.getElementById('sePayStep1').style.display    = 'none';
        document.getElementById('sePayStep2').style.display    = 'block';
        document.getElementById('sePayModalTitle').textContent = '📱 Quét QR Thanh Toán';
        document.getElementById('sePayQR').src                 = res.qr_url;
        document.getElementById('qrBank').textContent          = res.bank;
        document.getElementById('qrAccNo').textContent         = res.account_no;
        document.getElementById('qrAccName').textContent       = res.account_name;
        document.getElementById('qrAmount').textContent        = '₫' + Number(res.total).toLocaleString('vi-VN');
        document.getElementById('qrContent').textContent       = res.content;
        startSePayPolling();
    } catch(err) {
        showToast('❌ ' + err.message);
    } finally {
        if (btn) { btn.textContent = '📱 Tiếp tục → Hiện QR'; btn.disabled = false; }
    }
}

function startSePayPolling() {
    clearInterval(sePayPollTimer); clearInterval(sePayTickTimer);
    var attempts = 0, maxAttempts = 60, secsLeft = 300;
    var statusEl    = document.getElementById('sePayStatus');
    var timeEl      = document.getElementById('countdownTime');
    var countdownEl = document.getElementById('sePayCountdown');
    if (statusEl) { statusEl.innerHTML = '⏳ Đang chờ thanh toán...'; statusEl.style.background = ''; statusEl.style.borderColor = ''; }
    if (countdownEl) countdownEl.style.display = '';
    if (timeEl) { timeEl.textContent = '05:00'; timeEl.style.color = 'var(--gold)'; }
    sePayTickTimer = setInterval(function() {
        secsLeft--;
        if (secsLeft <= 0) { clearInterval(sePayTickTimer); return; }
        var m = Math.floor(secsLeft / 60), s = secsLeft % 60;
        if (timeEl) { timeEl.textContent = (m<10?'0':'')+m+':'+(s<10?'0':'')+s; timeEl.style.color = secsLeft < 60 ? '#e07070' : 'var(--gold)'; }
    }, 1000);
    sePayPollTimer = setInterval(async function() {
        attempts++;
        if (attempts > maxAttempts) {
            clearInterval(sePayPollTimer); clearInterval(sePayTickTimer);
            if (statusEl) statusEl.innerHTML = '⏰ Hết thời gian thanh toán. <a onclick="openCheckout(\'online\')" style="color:var(--gold);cursor:pointer;text-decoration:underline;">Thử lại</a>';
            if (countdownEl) countdownEl.style.display = 'none';
            return;
        }
        try {
            const res = await apiFetch(BASE + 'api/payment.php?action=check&order_code=' + encodeURIComponent(sePayOrderCode));
            if (res.paid) {
                clearInterval(sePayPollTimer); clearInterval(sePayTickTimer);
                if (statusEl) { statusEl.innerHTML = '<span style="color:#6DD880;font-size:1.1rem;">✅ Thanh toán thành công!</span>'; statusEl.style.background = 'rgba(61,138,78,.15)'; statusEl.style.borderColor = 'rgba(61,138,78,.4)'; }
                if (countdownEl) countdownEl.style.display = 'none';
                setTimeout(function() {
                    closeModal('checkoutOnlineModal');
                    cart = []; updateCartUI();
                    const customerName = document.getElementById('onName')?.value || '';
                    document.getElementById('successMsg').innerHTML = 'Cảm ơn <strong>' + customerName + '</strong>! Đơn hàng đã được xác nhận.';
                    document.getElementById('successOrderCode').textContent = sePayOrderCode;
                    openModal('successModal');
                    fetchProducts();
                }, 1500);
            }
        } catch(e) {}
    }, 5000);
}

function cancelSePayOrder() {
    clearInterval(sePayPollTimer); clearInterval(sePayTickTimer);
    closeModal('checkoutOnlineModal');
    var s1 = document.getElementById('sePayStep1'), s2 = document.getElementById('sePayStep2');
    var tt = document.getElementById('sePayModalTitle'), cd = document.getElementById('sePayCountdown');
    if (s1) s1.style.display = 'block'; if (s2) s2.style.display = 'none';
    if (tt) tt.textContent = '📱 Thanh Toán SePay'; if (cd) cd.style.display = 'none';
}

function selectPayment(type) { selectedPayment = type; }

function syncCodDropdown() {
    const sel = document.getElementById('codProduct');
    const qtyInput = document.getElementById('codQty');
    if (!sel) return;
    sel.innerHTML = productsList.filter(p => p.is_active && p.stock > 0).map(p =>
        `<option value="${p.id}">${p.name} — ₫${Number(p.price).toLocaleString('vi-VN')}/chai</option>`
    ).join('');
    if (cart.length >= 1) {
        sel.value = cart[0].id;
        if (qtyInput) qtyInput.value = cart[0].qty;
    }
    updateCodTotal();
}

function updateCodTotal() {
    const pid = +document.getElementById('codProduct')?.value;
    const qty = +document.getElementById('codQty')?.value || 0;
    const p   = productsList.find(x => x.id === pid);
    const el  = document.getElementById('codTotalDisplay');
    if (el) el.textContent = (p && qty) ? '₫' + Number(p.price * qty).toLocaleString('vi-VN') : '₫0';
}

async function confirmCodOrder() {
    const name    = document.getElementById('codName')?.value.trim();
    const phone   = document.getElementById('codPhone')?.value.trim();
    const address = document.getElementById('codAddress')?.value.trim();
    const pid     = +document.getElementById('codProduct')?.value;
    const qty     = +document.getElementById('codQty')?.value;
    const note    = document.getElementById('codNote')?.value.trim();
    if (!name)    { showToast('⚠ Nhập họ tên!'); return; }
    if (!phone)   { showToast('⚠ Nhập số điện thoại!'); return; }
    if (!address) { showToast('⚠ Nhập địa chỉ!'); return; }
    if (!pid)     { showToast('⚠ Chọn sản phẩm!'); return; }
    if (!qty)     { showToast('⚠ Nhập số lượng!'); return; }
    await placeOrder({ customer_name: name, phone, address, note, payment_method: 'COD', items: [{ product_id: pid, qty }] });
}

async function placeOrder(data) {
    const items = data.items || cart.map(i => ({ product_id: i.id, qty: i.qty }));
    try {
        const res = await apiFetch(API.orders, {
            method: 'POST',
            body: JSON.stringify({ ...data, items, customer_id: loadCustomer()?.id || 0 }),
        });
        closeModal('checkoutOnlineModal'); closeModal('checkoutCodModal');
        cart = []; updateCartUI();
        document.getElementById('successMsg').innerHTML = `Cảm ơn <strong>${data.customer_name}</strong>! Đơn hàng đã được ghi nhận.`;
        document.getElementById('successOrderCode').textContent = res.order_code;
        openModal('successModal');
        await fetchProducts();
    } catch (err) { showToast('❌ ' + err.message); }
}

// ══════════════════════════════════════════════════════════════
// AI CHATBOT — lưu history, sessionStorage, nút reset
// ══════════════════════════════════════════════════════════════
var aiChatHistory = [];

function saveChatToSession() {
    try {
        sessionStorage.setItem('tlc_chat_history', JSON.stringify(aiChatHistory));
        const msgs = document.getElementById('aiMessages');
        if (msgs) sessionStorage.setItem('tlc_chat_html', msgs.innerHTML);
    } catch(e) {}
}

function restoreChatFromSession() {
    try {
        const html    = sessionStorage.getItem('tlc_chat_html');
        const history = sessionStorage.getItem('tlc_chat_history');
        const msgs    = document.getElementById('aiMessages');
        if (msgs && html) {
            msgs.innerHTML = html;
            msgs.scrollTop = msgs.scrollHeight;
            document.getElementById('aiTags')?.style.setProperty('display', 'none');
        }
        if (history) aiChatHistory = JSON.parse(history);
    } catch(e) {}
}

function resetAiChat() {
    aiChatHistory = [];
    try { sessionStorage.removeItem('tlc_chat_html'); sessionStorage.removeItem('tlc_chat_history'); } catch(e) {}
    const msgs = document.getElementById('aiMessages');
    if (msgs) msgs.innerHTML = `
        <div class="ai-msg-bot">
            <div class="ai-avatar" style="flex-shrink:0;">✦</div>
            <div class="ai-bubble">Xin chào! Tôi là trợ lý của Tây Lương Cửu.<br>Cho tôi biết dịp gì, tặng ai — tôi sẽ gợi ý ngay!</div>
        </div>`;
    const tags = document.getElementById('aiTags');
    if (tags) tags.style.removeProperty('display');
    showToast('✦ Đã bắt đầu cuộc hội thoại mới');
}

function sendAiMessage(prefill) {
    const input = document.getElementById('aiInput');
    const msg   = prefill || input?.value.trim();
    if (!msg) return;
    if (input) input.value = '';
    document.getElementById('aiTags')?.style.setProperty('display', 'none');
    appendAiMsg(msg, true);
    aiChatHistory.push({ role: 'user', content: msg });
    showTyping();

    fetch(BASE + 'api/gemini_chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msg, history: aiChatHistory })
    })
    .then(r => r.json())
    .then(data => {
        removeTyping();
        let reply = data.reply || 'Xin lỗi, tôi chưa trả lời được lúc này. Vui lòng thử lại!';
        // Giữ <a> tag nguyên, chỉ strip markdown
        reply = reply
            .replace(/\*\*(.*?)\*\*/g, '$1')
            .replace(/\*(.*?)\*/g, '$1')
            .replace(/__(.*?)__/g, '$1')
            .replace(/#{1,6}\s/g, '')
            .replace(/\n/g, '<br>');
        appendAiMsg(reply, false);
        aiChatHistory.push({ role: 'assistant', content: data.reply || reply });
        saveChatToSession();
    })
    .catch(() => {
        removeTyping();
        appendAiMsg('Không kết nối được, vui lòng thử lại! 🙏', false);
    });
}

function appendAiMsg(text, isUser) {
    const c = document.getElementById('aiMessages');
    if (!c) return;
    const d = document.createElement('div');
    d.className = isUser ? 'ai-msg-user' : 'ai-msg-bot';
    d.innerHTML = isUser
        ? `<div class="ai-bubble ai-bubble-user">${text}</div>`
        : `<div class="ai-avatar" style="flex-shrink:0;">✦</div><div class="ai-bubble">${text}</div>`;
    c.appendChild(d);
    c.scrollTop = c.scrollHeight;
    saveChatToSession();
}

function showTyping() {
    const c = document.getElementById('aiMessages');
    if (!c) return;
    const d = document.createElement('div');
    d.id = 'aiTyping'; d.className = 'ai-msg-bot';
    d.innerHTML = `<div class="ai-avatar" style="flex-shrink:0;">✦</div>
        <div class="ai-bubble" style="display:flex;gap:4px;align-items:center;">
            <span style="animation:blink .8s infinite">●</span>
            <span style="animation:blink .8s .2s infinite">●</span>
            <span style="animation:blink .8s .4s infinite">●</span>
        </div>`;
    c.appendChild(d); c.scrollTop = c.scrollHeight;
}
function removeTyping() { document.getElementById('aiTyping')?.remove(); }

// ══════════════════════════════════════════════════════════════
// MODAL HELPERS
// ══════════════════════════════════════════════════════════════
function openModal(id)  { document.getElementById(id)?.classList.add('open'); }
function closeModal(id) { document.getElementById(id)?.classList.remove('open'); }

// ══════════════════════════════════════════════════════════════
// TOAST
// ══════════════════════════════════════════════════════════════
var toastTimer;
function showToast(msg) {
    const t = document.getElementById('toast');
    const m = document.getElementById('toast-msg');
    if (!t || !m) return;
    m.textContent = msg;
    t.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 2800);
}

// ══════════════════════════════════════════════════════════════
// NAVBAR + SCROLL
// ══════════════════════════════════════════════════════════════
window.addEventListener('scroll', () => {
    document.getElementById('navbar')?.classList.toggle('scrolled', window.scrollY > 60);
});
function scrollToSection(id) {
    var el = document.getElementById(id);
    if (el) el.scrollIntoView({ behavior: 'smooth' });
}

// ══════════════════════════════════════════════════════════════
// REVEAL ANIMATION
// ══════════════════════════════════════════════════════════════
const observer = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.1 });

// ══════════════════════════════════════════════════════════════
// INIT
// ══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    checkAgeGate();
    restoreChatFromSession();
    restoreFchatFromSession();
    applyTheme(currentTheme);
    fetchProducts();
    updateCartUI();
    document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
    document.querySelectorAll('.modal-overlay').forEach(ov => {
        ov.addEventListener('click', e => { if (e.target === ov) ov.classList.remove('open'); });
    });
});

// ══════════════════════════════════════════════════════════════
// CUSTOMER AUTH
// ══════════════════════════════════════════════════════════════
var currentCustomer = null;

function saveCustomer(data) {
    localStorage.setItem('tlc_customer', JSON.stringify({id:data.id, name:data.name, email:data.email, token:data.token}));
    currentCustomer = data;
    updateCustomerNav(data);
}
function loadCustomer() {
    try { return JSON.parse(localStorage.getItem('tlc_customer')); } catch(e) { return null; }
}
function clearCustomer() {
    localStorage.removeItem('tlc_customer');
    currentCustomer = null;
    updateCustomerNav(null);
}
async function checkCustomerSession() {
    const saved = loadCustomer();
    if (saved && saved.id && saved.token) { currentCustomer = saved; updateCustomerNav(saved); }
}
function updateCustomerNav(customer) {
    document.getElementById('btnLoginNav').style.display   = customer ? 'none' : '';
    document.getElementById('btnAccountNav').style.display = customer ? ''     : 'none';
    if (customer) document.getElementById('navCustomerName').textContent = customer.name.split(' ').pop();
}

function switchAuthTab(tab) {
    ['login','register','forgot'].forEach(t => {
        document.getElementById('form' + t.charAt(0).toUpperCase() + t.slice(1)).style.display = t === tab ? '' : 'none';
    });
    document.getElementById('tabLogin').style.background    = tab==='login'    ? 'var(--gold)' : 'transparent';
    document.getElementById('tabLogin').style.color         = tab==='login'    ? '#1A0A00'     : 'var(--t2)';
    document.getElementById('tabRegister').style.background = tab==='register' ? 'var(--gold)' : 'transparent';
    document.getElementById('tabRegister').style.color      = tab==='register' ? '#1A0A00'     : 'var(--t2)';
    document.getElementById('authModalTitle').textContent   = tab==='login' ? '🔐 Đăng Nhập' : tab==='register' ? '📝 Đăng Ký' : '🔑 Quên Mật Khẩu';
}

async function doLogin() {
    const email = document.getElementById('loginEmail').value.trim();
    const pass  = document.getElementById('loginPass').value;
    const errEl = document.getElementById('loginError');
    errEl.style.display = 'none';
    try {
        const res = await apiFetch(BASE + 'api/customer_auth.php?action=login', {method:'POST', body:JSON.stringify({email, password: pass})});
        if (res.error) { errEl.textContent = res.error; errEl.style.display = ''; return; }
        saveCustomer(res); closeModal('authModal'); showToast('✦ Chào mừng ' + res.name + '!');
    } catch(e) { errEl.textContent = 'Đăng nhập thất bại'; errEl.style.display = ''; }
}

async function doRegister() {
    const name  = document.getElementById('regName').value.trim();
    const phone = document.getElementById('regPhone').value.trim();
    const email = document.getElementById('regEmail').value.trim();
    const pass  = document.getElementById('regPass').value;
    const errEl = document.getElementById('regError');
    errEl.style.display = 'none';
    try {
        const res = await apiFetch(BASE + 'api/customer_auth.php?action=register', {method:'POST', body:JSON.stringify({name, phone, email, password: pass})});
        if (res.error) { errEl.textContent = res.error; errEl.style.display = ''; return; }
        saveCustomer(res); closeModal('authModal'); showToast('✦ Đăng ký thành công! Chào ' + res.name);
    } catch(e) { errEl.textContent = 'Đăng ký thất bại'; errEl.style.display = ''; }
}

async function doLogout() { clearCustomer(); closeModal('myAccountModal'); showToast('Đã đăng xuất'); }

async function doForgot() {
    const email = document.getElementById('forgotEmail').value.trim();
    const msgEl = document.getElementById('forgotMsg');
    msgEl.style.display = 'none';
    try {
        const res = await apiFetch(BASE + 'api/customer_auth.php?action=forgot', {method:'POST', body:JSON.stringify({email})});
        if (res.error) { msgEl.textContent = res.error; msgEl.style.color='#e07070'; msgEl.style.display=''; return; }
        msgEl.style.color = '#6DD880';
        msgEl.textContent = res.dev_token ? 'Localhost: mã của bạn là ' + res.dev_token : 'Đã gửi mã về email!';
        msgEl.style.display = '';
        document.getElementById('resetForm').style.display = '';
        document.getElementById('resetForm').dataset.email = email;
    } catch(e) { msgEl.textContent = 'Lỗi, thử lại'; msgEl.style.color='#e07070'; msgEl.style.display=''; }
}

async function doReset() {
    const email = document.getElementById('resetForm').dataset.email;
    const token = document.getElementById('resetToken').value.trim();
    const pass  = document.getElementById('resetPass').value;
    const msgEl = document.getElementById('forgotMsg');
    try {
        const res = await apiFetch(BASE + 'api/customer_auth.php?action=reset', {method:'POST', body:JSON.stringify({email, token, password: pass})});
        if (res.error) { msgEl.textContent = res.error; msgEl.style.color='#e07070'; msgEl.style.display=''; return; }
        msgEl.textContent = '✅ Đặt lại thành công! Đang chuyển sang đăng nhập...';
        msgEl.style.color = '#6DD880'; msgEl.style.display = '';
        setTimeout(() => { switchAuthTab('login'); document.getElementById('loginEmail').value = email; }, 1500);
    } catch(e) {}
}

function switchAccTab(tab) {
    document.getElementById('accOrders').style.display  = tab==='orders'  ? '' : 'none';
    document.getElementById('accChgPass').style.display = tab==='chgpass' ? '' : 'none';
    document.getElementById('tabOrders').style.background  = tab==='orders'  ? 'var(--gold)' : 'transparent';
    document.getElementById('tabOrders').style.color       = tab==='orders'  ? '#1A0A00'     : 'var(--t2)';
    document.getElementById('tabChgPass').style.background = tab==='chgpass' ? 'var(--gold)' : 'transparent';
    document.getElementById('tabChgPass').style.color      = tab==='chgpass' ? '#1A0A00'     : 'var(--t2)';
    if (tab==='orders') loadMyOrders();
}

async function loadMyOrders() {
    const el = document.getElementById('myOrdersList');
    el.innerHTML = '<div style="text-align:center;color:var(--t3);padding:20px;">Đang tải...</div>';
    try {
        const c = loadCustomer();
        if (!c) { el.innerHTML = '<div style="color:#e07070;text-align:center;padding:20px;">Chưa đăng nhập</div>'; return; }
        const res = await apiFetch(BASE + 'api/customer_auth.php?action=my_orders&cid=' + c.id + '&tok=' + c.token);
        if (!res.orders || !res.orders.length) {
            el.innerHTML = '<div style="text-align:center;color:var(--t3);padding:24px;">Chưa có đơn hàng nào</div>';
            return;
        }
        const statusColor = { 'Chờ duyệt':'#C9973A', 'Đang giao':'#5b9cf6', 'Đã giao':'#6DD880', 'Hủy':'#e07070' };
        el.innerHTML = res.orders.map(o => `
            <div style="border:1px solid var(--border);border-radius:10px;padding:20px 22px;margin-bottom:14px;background:var(--card2);">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                    <span style="font-family:'Playfair Display',serif;color:var(--gold);font-size:1.15rem;font-weight:600;letter-spacing:.05em;">#${o.order_code}</span>
                    <span style="font-size:.78rem;padding:5px 14px;border-radius:20px;font-weight:600;
                        background:${statusColor[o.status]||'#888'}22;color:${statusColor[o.status]||'#888'};
                        border:1px solid ${statusColor[o.status]||'#888'}55;">${o.status}</span>
                </div>
                <div style="display:grid;gap:8px;margin-bottom:14px;">
                    <div style="display:flex;gap:12px;"><span style="font-size:.82rem;color:var(--t3);min-width:90px;">Người nhận</span><span style="font-size:1.05rem;color:var(--ivory);font-weight:700;">${o.customer_name||'-'}</span></div>
                    <div style="display:flex;gap:12px;"><span style="font-size:.82rem;color:var(--t3);min-width:90px;">Điện thoại</span><span style="font-size:1rem;color:var(--ivory);font-weight:500;">${o.phone||'-'}</span></div>
                    <div style="display:flex;gap:12px;"><span style="font-size:.82rem;color:var(--t3);min-width:90px;">Địa chỉ</span><span style="font-size:1rem;color:var(--ivory);line-height:1.6;">${o.address||'-'}</span></div>
                    ${o.note?`<div style="display:flex;gap:12px;"><span style="font-size:.82rem;color:var(--t3);min-width:90px;">Ghi chú</span><span style="font-size:.95rem;color:var(--t2);font-style:italic;">${o.note}</span></div>`:''}
                </div>
                ${o.items&&o.items.length?`<div style="background:rgba(201,151,58,.05);border:1px solid rgba(201,151,58,.15);border-radius:6px;padding:10px 14px;margin-bottom:12px;">
                    ${o.items.map(item=>`<div style="display:flex;justify-content:space-between;font-size:.85rem;padding:3px 0;"><span style="color:var(--t2);">${item.name} x${item.qty}</span><span style="color:var(--gold);">₫${Number(item.price*item.qty).toLocaleString('vi-VN')}</span></div>`).join('')}
                </div>`:''}
                <div style="display:flex;justify-content:space-between;align-items:center;padding-top:12px;border-top:1px solid var(--border2);">
                    <div style="display:flex;flex-direction:column;gap:4px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font-size:.78rem;color:var(--t3);">${o.payment_method}</span>
                            ${o.payment_status==='paid'?'<span style="font-size:.78rem;color:#6DD880;font-weight:600;">Đã thanh toán</span>':'<span style="font-size:.78rem;color:#C9973A;font-weight:600;">Chờ thanh toán</span>'}
                        </div>
                        <span style="font-size:.72rem;color:var(--t3);">${new Date(o.created_at).toLocaleDateString('vi-VN',{day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'})}</span>
                    </div>
                    <span style="font-family:'Playfair Display',serif;color:var(--gold);font-size:1.25rem;font-weight:600;">₫${Number(o.total).toLocaleString('vi-VN')}</span>
                </div>
            </div>`).join('');
    } catch(e) { el.innerHTML = '<div style="color:#e07070;text-align:center;padding:20px;">Lỗi tải đơn hàng</div>'; }
}

async function doChangePassword() {
    const old = document.getElementById('oldPass').value;
    const np  = document.getElementById('newPass').value;
    const np2 = document.getElementById('newPass2').value;
    const msgEl = document.getElementById('chgPassMsg');
    msgEl.style.display = 'none';
    if (np !== np2) { msgEl.textContent='Mật khẩu mới không khớp'; msgEl.style.color='#e07070'; msgEl.style.display=''; return; }
    try {
        const c   = loadCustomer();
        const res = await apiFetch(BASE + 'api/customer_auth.php?action=change_password', {method:'POST', body:JSON.stringify({cid:c?.id, tok:c?.token, old_password:old, new_password:np})});
        if (res.error) { msgEl.textContent=res.error; msgEl.style.color='#e07070'; msgEl.style.display=''; return; }
        msgEl.textContent = '✅ Đổi mật khẩu thành công!'; msgEl.style.color='#6DD880'; msgEl.style.display='';
        document.getElementById('oldPass').value = document.getElementById('newPass').value = document.getElementById('newPass2').value = '';
    } catch(e) {}
}

const _origOpenModal = openModal;
openModal = function(id) {
    _origOpenModal(id);
    if (id === 'myAccountModal') {
        document.getElementById('accEmailDisplay').textContent = currentCustomer?.email || '';
        loadMyOrders();
    }
};

document.addEventListener('DOMContentLoaded', function() { checkCustomerSession(); });

/* ══════════════════════════════════════════
 * GOONG MAPS AUTOCOMPLETE
 * ══════════════════════════════════════════ */
const GOONG_KEY = 'CJ1fTDAzk3BJ6jAM8jcB9X1GquXimvAJEhocVnwk';
let goongTimer = null;

async function goongSuggest(inputId, dropId) {
    const input = document.getElementById(inputId);
    const drop  = document.getElementById(dropId);
    if (!input || !drop) return;
    const q = input.value.trim();
    if (q.length < 2) { drop.innerHTML = ''; drop.classList.remove('open'); return; }
    clearTimeout(goongTimer);
    goongTimer = setTimeout(async () => {
        try {
            const url  = `https://rsapi.goong.io/Place/AutoComplete?api_key=${GOONG_KEY}&input=${encodeURIComponent(q)}&location=21.0285,105.8542&radius=50000`;
            const res  = await fetch(url);
            const data = await res.json();
            if (!data.predictions || !data.predictions.length) {
                drop.innerHTML = '<div class="goong-item goong-empty">Không tìm thấy địa chỉ</div>';
                drop.classList.add('open'); return;
            }
            drop.innerHTML = data.predictions.map(p => {
                const desc = p.description || '';
                const safe = desc.replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                return `<div class="goong-item" data-input="${inputId}" data-drop="${dropId}" data-desc="${safe}" onclick="goongSelectFromEl(this)">
                    <span class="goong-icon">◎</span>
                    <div><div class="goong-main">${p.structured_formatting?.main_text||desc}</div>
                    <div class="goong-sub">${p.structured_formatting?.secondary_text||''}</div></div>
                </div>`;
            }).join('');
            drop.classList.add('open');
        } catch(e) { console.error('Goong error:', e); }
    }, 300);
}

function goongSelectFromEl(el) { goongSelect(el.dataset.input, el.dataset.drop, el.dataset.desc); }
function goongSelect(inputId, dropId, description) {
    const input = document.getElementById(inputId);
    const drop  = document.getElementById(dropId);
    if (input) { input.value = description; input.focus(); }
    if (drop)  { drop.innerHTML = ''; drop.classList.remove('open'); }
}
document.addEventListener('click', (e) => {
    document.querySelectorAll('.goong-drop').forEach(d => {
        if (!d.closest('.goong-wrap')?.contains(e.target)) { d.innerHTML = ''; d.classList.remove('open'); }
    });
});

/* ══════════════════════════════════════════
 * FLOAT CHAT BUBBLE — lưu history, sessionStorage, nút reset
 * ══════════════════════════════════════════ */
var fchatHistory = [];

function saveFchatToSession() {
    try {
        sessionStorage.setItem('tlc_fchat_history', JSON.stringify(fchatHistory));
        const msgs = document.getElementById('fchatMessages');
        if (msgs) sessionStorage.setItem('tlc_fchat_html', msgs.innerHTML);
    } catch(e) {}
}

function restoreFchatFromSession() {
    try {
        const html    = sessionStorage.getItem('tlc_fchat_html');
        const history = sessionStorage.getItem('tlc_fchat_history');
        const msgs    = document.getElementById('fchatMessages');
        if (msgs && html) {
            msgs.innerHTML = html;
            document.getElementById('fchatTags')?.style.setProperty('display','none');
        }
        if (history) fchatHistory = JSON.parse(history);
    } catch(e) {}
}

function resetFchat() {
    fchatHistory = [];
    try { sessionStorage.removeItem('tlc_fchat_html'); sessionStorage.removeItem('tlc_fchat_history'); } catch(e) {}
    const msgs = document.getElementById('fchatMessages');
    if (msgs) msgs.innerHTML = `
        <div style="display:flex;gap:8px;align-items:flex-start;">
            <div class="ai-avatar" style="width:28px;height:28px;font-size:.65rem;flex-shrink:0;">✦</div>
            <div class="ai-bubble" style="font-size:.82rem;">Xin chào! Tôi có thể giúp gì cho bạn? 🍶</div>
        </div>`;
    const tags = document.getElementById('fchatTags');
    if (tags) tags.style.removeProperty('display');
    showToast('✦ Đã bắt đầu cuộc hội thoại mới');
}

function toggleFloatChat() {
    const box   = document.getElementById('floatChatBox');
    const btn   = document.getElementById('floatChatBtn');
    const badge = document.getElementById('floatChatBadge');
    if (!box) return;
    const isOpen = box.classList.toggle('open');
    btn.classList.toggle('active', isOpen);
    if (isOpen && badge) badge.style.display = 'none';
    if (isOpen) {
        setTimeout(() => document.getElementById('fchatInput')?.focus(), 300);
        const msgs = document.getElementById('fchatMessages');
        if (msgs) msgs.scrollTop = msgs.scrollHeight;
    }
}

let fchatTyping = false;

function sendFloatMsg(prefill) {
    const input = document.getElementById('fchatInput');
    const msg = prefill || input?.value.trim();
    if (!msg || fchatTyping) return;
    if (input) input.value = '';
    document.getElementById('fchatTags')?.style.setProperty('display','none');
    appendFchatMsg(msg, true);
    fchatHistory.push({ role: 'user', content: msg });
    fchatTyping = true;

    const msgs = document.getElementById('fchatMessages');
    const typing = document.createElement('div');
    typing.id = 'fchatTyping';
    typing.style.cssText = 'display:flex;gap:8px;align-items:flex-start;';
    typing.innerHTML = `
        <div class="ai-avatar" style="width:28px;height:28px;font-size:.65rem;flex-shrink:0;">✦</div>
        <div class="ai-bubble" style="font-size:.82rem;display:flex;gap:4px;align-items:center;padding:10px 14px;">
            <span style="animation:blink .8s infinite">●</span>
            <span style="animation:blink .8s .2s infinite">●</span>
            <span style="animation:blink .8s .4s infinite">●</span>
        </div>`;
    msgs.appendChild(typing);
    msgs.scrollTop = msgs.scrollHeight;

    fetch(BASE + 'api/gemini_chat.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: msg, history: fchatHistory })
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('fchatTyping')?.remove();
        fchatTyping = false;
        let reply = data.reply || 'Xin lỗi, thử lại nhé!';
        reply = reply
            .replace(/\*\*(.*?)\*\*/g, '$1')
            .replace(/\*(.*?)\*/g, '$1')
            .replace(/__(.*?)__/g, '$1')
            .replace(/#{1,6}\s/g, '')
            .replace(/\n/g, '<br>');
        appendFchatMsg(reply, false);
        fchatHistory.push({ role: 'assistant', content: data.reply || reply });
        saveFchatToSession();
        if (!document.getElementById('floatChatBox')?.classList.contains('open')) {
            const badge = document.getElementById('floatChatBadge');
            if (badge) badge.style.display = 'flex';
        }
    })
    .catch(() => {
        document.getElementById('fchatTyping')?.remove();
        fchatTyping = false;
        appendFchatMsg('Không kết nối được, thử lại nhé!', false);
    });
}

function appendFchatMsg(text, isUser) {
    const msgs = document.getElementById('fchatMessages');
    if (!msgs) return;
    const d = document.createElement('div');
    d.style.cssText = 'display:flex;gap:8px;align-items:flex-start;' + (isUser ? 'justify-content:flex-end;' : '');
    d.innerHTML = isUser
        ? `<div class="ai-bubble ai-bubble-user" style="font-size:.82rem;">${text}</div>`
        : `<div class="ai-avatar" style="width:28px;height:28px;font-size:.65rem;flex-shrink:0;">✦</div>
           <div class="ai-bubble" style="font-size:.82rem;">${text}</div>`;
    msgs.appendChild(d);
    msgs.scrollTop = msgs.scrollHeight;
    saveFchatToSession();
}