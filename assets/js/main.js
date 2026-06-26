/* ============================================================
   IndustriaMG — main.js   Utilidades globales
   ============================================================ */

// ── TOAST ───────────────────────────────────────────────────
const Toast = {
    container: null,
    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
    },
    show(msg, type = 'default', duration = 3500) {
        this.init();
        const t = document.createElement('div');
        t.className = `toast ${type}`;
        const icons = { success: '✓', error: '✕', warning: '⚠', default: 'ℹ' };
        t.innerHTML = `<span>${icons[type] || icons.default}</span><span>${msg}</span>`;
        this.container.appendChild(t);
        setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, duration);
    },
    success(msg) { this.show(msg, 'success'); },
    error(msg)   { this.show(msg, 'error'); },
    warning(msg) { this.show(msg, 'warning'); },
};

// ── MODAL ───────────────────────────────────────────────────
const Modal = {
    open(id) {
        const el = document.getElementById(id);
        if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
    },
    close(id) {
        const el = document.getElementById(id);
        if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
    },
    closeAll() {
        document.querySelectorAll('.modal-overlay.open').forEach(m => {
            m.classList.remove('open');
        });
        document.body.style.overflow = '';
    }
};

// Cerrar modal al clicar overlay
document.addEventListener('click', e => {
    if (e.target.classList.contains('modal-overlay')) Modal.closeAll();
    if (e.target.classList.contains('modal-close'))   Modal.closeAll();
});

// ── TABS ────────────────────────────────────────────────────
function initTabs(container = document) {
    if (typeof container === 'string') container = document.getElementById(container) || document;
    container.querySelectorAll('.tabs').forEach(tabs => {
        // Pattern 1: .tab with data-group + data-target
        tabs.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const group = tab.dataset.group || 'default';
                const target = tab.dataset.target;
                tabs.querySelectorAll(`.tab[data-group="${group}"]`).forEach(t => t.classList.remove('active'));
                document.querySelectorAll(`.tab-content[data-group="${group}"]`).forEach(c => c.classList.remove('active'));
                tab.classList.add('active');
                const tc = document.getElementById(target);
                if (tc) tc.classList.add('active');
            });
        });
        // Pattern 2: .tab-btn with data-tab (id="tab-{name}")
        const tabBtns = tabs.querySelectorAll('.tab-btn');
        if (tabBtns.length > 0) {
            const tabIds = Array.from(tabBtns).map(b => 'tab-' + b.dataset.tab);
            tabBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabIds.forEach(id => { const el = document.getElementById(id); if (el) el.classList.remove('active'); });
                    btn.classList.add('active');
                    const tc = document.getElementById('tab-' + btn.dataset.tab);
                    if (tc) tc.classList.add('active');
                });
            });
        }
    });
}
document.addEventListener('DOMContentLoaded', () => initTabs());

// ── API FETCH ───────────────────────────────────────────────
async function apiGet(url) {
    const res = await fetch(url);
    const json = await res.json();
    if (!res.ok) throw new Error(json.error || `HTTP ${res.status}`);
    return json;
}
async function apiPost(url, data) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    const json = await res.json();
    if (!res.ok) throw new Error(json.error || `HTTP ${res.status}`);
    return json;
}

// ── FORMATO ─────────────────────────────────────────────────
function formatMoney(n) {
    return 'S/ ' + Number(n).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function formatDate(str) {
    if (!str) return '—';
    const d = new Date(str + 'T00:00:00');
    return d.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
}
function diffDays(dateStr) {
    const today = new Date(); today.setHours(0,0,0,0);
    const d = new Date(dateStr + 'T00:00:00');
    return Math.round((d - today) / 86400000);
}
function badge(estado) {
    return `<span class="badge badge-${estado.toLowerCase()}">${estado.charAt(0).toUpperCase() + estado.slice(1)}</span>`;
}

// ── CONFIRMACIÓN ────────────────────────────────────────────
function confirm2(msg, onConfirm) {
    if (window.confirm(msg)) onConfirm();
}

// ── PROGRESS COLOR ──────────────────────────────────────────
function progressClass(pct) {
    if (pct >= 80) return 'green';
    if (pct >= 40) return '';
    return 'red';
}
