// ============================================================
// FILE: assets/js/admin.js
// ============================================================

'use strict';

// ── API Helper ───────────────────────────────────────────────
async function adminFetch(url, options = {}) {
    const defaults = {
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
    };
    if (options.body instanceof FormData) {
        delete defaults.headers['Content-Type'];
    }
    const res  = await fetch(url, { ...defaults, ...options });
    const text = await res.text(); // Đọc text trước

    // Kiểm tra nếu server trả về HTML (lỗi PHP)
    if (text.trim().startsWith('<')) {
        // Lấy dòng lỗi từ HTML nếu có
        const match = text.match(/Fatal error.*?on line \d+/s) ||
                      text.match(/Parse error.*?on line \d+/s) ||
                      text.match(/<b>(.*?)<\/b>/s);
        const errMsg = match ? match[0].replace(/<[^>]+>/g,'').trim() : 'Server lỗi PHP';
        console.error('Server trả HTML thay vì JSON:', text.substring(0, 500));
        throw new Error(errMsg);
    }

    let data;
    try {
        data = JSON.parse(text);
    } catch(e) {
        console.error('JSON parse lỗi:', text.substring(0, 300));
        throw new Error('Response không phải JSON: ' + text.substring(0, 80));
    }

    if (res.status === 401) {
        window.location.href = 'login.php';
        return null;
    }
    if (!res.ok && data.error) throw new Error(data.error);
    return data;
}

// ── Toast Notification ───────────────────────────────────────
let toastTimer;
function showToast(msg, type = 'success') {
    const el = document.getElementById('toast');
    if (!el) return;
    el.textContent = msg;
    el.className   = `toast show${type !== 'success' ? ' ' + type : ''}`;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove('show'), 3500);
}

// ── Modal Helpers ────────────────────────────────────────────
function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('open');
    }
});
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
    }
});

// ── Build Bar Chart ──────────────────────────────────────────
function buildBarChart(containerId, values, labels) {
    const el = document.getElementById(containerId);
    if (!el) return;
    const max = Math.max(...values, 1);
    el.innerHTML = values.map((v, i) => `
        <div class="bar-wrap">
            <div class="bar" style="height:${(v / max * 110).toFixed(0)}px" title="${labels[i]}: ${v}tr"></div>
            <div class="bar-label">${labels[i]}<br><span style="color:var(--gold);font-size:.58rem;">${v}tr</span></div>
        </div>`).join('');
}

function fmtPrice(n) { return '₫' + Number(n).toLocaleString('vi-VN'); }
function fmtDate(str) {
    if (!str) return '—';
    return new Date(str).toLocaleDateString('vi-VN', {day:'2-digit',month:'2-digit',year:'numeric'});
}
function fmtDateTime(str) {
    if (!str) return '—';
    return new Date(str).toLocaleDateString('vi-VN', {day:'2-digit',month:'2-digit',year:'numeric',hour:'2-digit',minute:'2-digit'});
}