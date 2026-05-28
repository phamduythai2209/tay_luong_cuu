// ============================================================
// FILE: assets/js/filter.js
// Các hàm filter sản phẩm — thêm vào sau app.js
// ============================================================

(function() {

    // ── State lọc hiện tại ──────────────────────────────────
    var filterState = {
        cat:   '',   // danh mục
        price: '',   // khoảng giá "min-max"
        alc:   '',   // khoảng độ cồn "min-max"
        search: ''   // từ khóa tìm kiếm
    };

    // ── Lọc theo danh mục ───────────────────────────────────
    window.setCatFilter = function(btn) {
        filterState.cat = btn.dataset.cat || '';
        // Bỏ active tất cả tag trong #filterCats
        document.querySelectorAll('#filterCats .filter-tag').forEach(function(b) {
            b.classList.remove('active');
        });
        btn.classList.add('active');
        applyFilters();
    };

    // ── Lọc theo giá ────────────────────────────────────────
    window.setPriceFilter = function(btn) {
        filterState.price = btn.dataset.price || '';
        document.querySelectorAll('[data-price]').forEach(function(b) {
            b.classList.remove('active');
        });
        btn.classList.add('active');
        applyFilters();
    };

    // ── Lọc theo độ cồn ─────────────────────────────────────
    window.setAlcFilter = function(btn) {
        filterState.alc = btn.dataset.alc || '';
        document.querySelectorAll('[data-alc]').forEach(function(b) {
            b.classList.remove('active');
        });
        btn.classList.add('active');
        applyFilters();
    };

    // ── Áp dụng tất cả filter ───────────────────────────────
    window.applyFilters = function() {
        // Lấy giá trị search từ input
        var searchEl = document.getElementById('filterSearch');
        filterState.search = searchEl ? searchEl.value.trim().toLowerCase() : '';

        var list = (typeof productsList !== 'undefined') ? productsList : [];
        var result = list.filter(function(p) {
            if (!p.is_active) return false;

            // Lọc danh mục
            if (filterState.cat) {
                var pCat = (p.category || p.danh_muc || p.loai || p.cat || '').trim();
                if (pCat !== filterState.cat) return false;
            }

            // Lọc giá
            if (filterState.price) {
                var parts = filterState.price.split('-');
                var minP = parseFloat(parts[0]) || 0;
                var maxP = parseFloat(parts[1]) || Infinity;
                var price = parseFloat(p.price) || 0;
                if (price < minP || price > maxP) return false;
            }

            // Lọc độ cồn
            if (filterState.alc) {
                var alcParts = filterState.alc.split('-');
                var minA = parseFloat(alcParts[0]) || 0;
                var maxA = parseFloat(alcParts[1]) || Infinity;
                var alc = parseFloat(p.alc) || 0;
                if (alc < minA || alc > maxA) return false;
            }

            // Lọc tìm kiếm
            if (filterState.search) {
                var name  = (p.name  || '').toLowerCase();
                var sname = (p.short || '').toLowerCase();
                var desc  = (p.desc  || '').toLowerCase();
                var cat   = (p.category || p.cat || '').toLowerCase();
                if (
                    name.indexOf(filterState.search)  === -1 &&
                    sname.indexOf(filterState.search) === -1 &&
                    desc.indexOf(filterState.search)  === -1 &&
                    cat.indexOf(filterState.search)   === -1
                ) return false;
            }

            return true;
        });

        // Render kết quả
        renderFilteredProducts(result);

        // Hiện/ẩn số kết quả
        var countEl = document.getElementById('filterCount');
        var hasFilter = filterState.cat || filterState.price || filterState.alc || filterState.search;
        if (countEl) {
            if (hasFilter) {
                countEl.style.display = '';
                countEl.textContent = 'Tìm thấy ' + result.length + ' sản phẩm';
            } else {
                countEl.style.display = 'none';
            }
        }

        // Hiện/ẩn nút xoá lọc
        var resetBtn = document.getElementById('filterReset');
        if (resetBtn) {
            resetBtn.style.display = hasFilter ? '' : 'none';
        }
    };

    // ── Reset tất cả filter ─────────────────────────────────
    window.resetFilters = function() {
        filterState = { cat: '', price: '', alc: '', search: '' };

        // Reset input search
        var searchEl = document.getElementById('filterSearch');
        if (searchEl) searchEl.value = '';

        // Reset active buttons — danh mục
        document.querySelectorAll('#filterCats .filter-tag').forEach(function(b) {
            b.classList.toggle('active', b.dataset.cat === '');
        });

        // Reset active buttons — giá
        document.querySelectorAll('[data-price]').forEach(function(b) {
            b.classList.toggle('active', b.dataset.price === '');
        });

        // Reset active buttons — độ cồn
        document.querySelectorAll('[data-alc]').forEach(function(b) {
            b.classList.toggle('active', b.dataset.alc === '');
        });

        applyFilters();
    };

    // ── Render sản phẩm đã lọc ──────────────────────────────
    function renderFilteredProducts(list) {
        var grid = document.getElementById('productGrid');
        if (!grid) return;

        if (!list.length) {
            grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:var(--t3);font-size:.95rem;">'
                + '🔍 Không tìm thấy sản phẩm phù hợp'
                + '<br><small style="font-size:.8rem;margin-top:8px;display:block;">Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm</small>'
                + '</div>';
            return;
        }

        // Dùng hàm buildCard từ app.js nếu có
        if (typeof buildCard === 'function') {
            grid.innerHTML = list.map(function(p) { return buildCard(p); }).join('');
        }
    }

    // ── Build category filters từ productsList ───────────────
    // Gọi sau khi productsList đã load xong
    function buildCategoryFilters() {
        var list = (typeof productsList !== 'undefined') ? productsList : [];
        var cats = [];
        list.forEach(function(p) {
            var cat = (p.category || p.danh_muc || p.loai || p.cat || '').trim();
            if (cat && cats.indexOf(cat) === -1) cats.push(cat);
        });
        cats.sort();

        var container = document.getElementById('filterCats');
        if (!container) return;

        // Xoá hết tag cũ (trừ nút Tất cả)
        var allBtn = container.querySelector('[data-cat=""]');
        container.innerHTML = '';
        if (allBtn) container.appendChild(allBtn);
        else {
            allBtn = document.createElement('button');
            allBtn.className = 'filter-tag active';
            allBtn.dataset.cat = '';
            allBtn.textContent = 'Tất cả';
            allBtn.setAttribute('onclick', 'setCatFilter(this)');
            container.appendChild(allBtn);
        }

        cats.forEach(function(cat, i) {
            var btn = document.createElement('button');
            btn.className = 'filter-tag filter-tag-new';
            btn.dataset.cat = cat;
            btn.textContent = cat;
            btn.style.animationDelay = (i * 0.05) + 's';
            btn.setAttribute('onclick', 'setCatFilter(this)');
            container.appendChild(btn);
            // Xoá animation class sau khi xong
            setTimeout(function() { btn.classList.remove('filter-tag-new'); }, 500);
        });
    }

    // ── Hook vào fetchProducts của app.js ───────────────────
    // Sau khi app.js fetch xong → build lại category filters
    document.addEventListener('DOMContentLoaded', function() {
        // Chờ productsList load (fetchProducts là async)
        var maxWait = 50; // 50 * 100ms = 5 giây
        var waited  = 0;
        var interval = setInterval(function() {
            waited++;
            var list = (typeof productsList !== 'undefined') ? productsList : [];
            if (list.length > 0 || waited >= maxWait) {
                clearInterval(interval);
                buildCategoryFilters();
                // Render ban đầu (hiện tất cả)
                applyFilters();
            }
        }, 100);
    });

})();