import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const token = document.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.getAttribute('content');
}

// ─── Page loader ─────────────────────────────────────────────
// window.load attend que TOUT soit prêt : CDN fonts, FA, Alpine, images
window.addEventListener('load', () => {
    const loader = document.getElementById('page-loader');
    if (!loader) return;
    loader.classList.add('loader-fade-out');
    setTimeout(() => loader.remove(), 350);
});

// Show loader on internal link navigation
document.addEventListener('click', e => {
    const link = e.target.closest('a[href]');
    if (!link) return;
    const href = link.getAttribute('href');
    if (
        !href || href.startsWith('#') || href.startsWith('javascript:') ||
        link.target === '_blank' || e.ctrlKey || e.metaKey || e.shiftKey
    ) return;
    showPageLoader();
}, true);

function showPageLoader() {
    if (document.getElementById('page-loader')) return;
    const el = document.createElement('div');
    el.id = 'page-loader';
    el.innerHTML = '<div class="loader-ring"></div>';
    document.body.appendChild(el);
}

// ─── Button / form loader ─────────────────────────────────────
document.addEventListener('submit', e => {
    const btn = e.target.querySelector('[type="submit"]:not([data-no-loader])');
    if (!btn) return;
    btn.disabled = true;
    btn.dataset.originalHtml = btn.innerHTML;
    btn.classList.add('btn-loading');
    setTimeout(() => resetBtn(btn), 15000);
});

function resetBtn(btn) {
    btn.disabled = false;
    btn.classList.remove('btn-loading');
    if (btn.dataset.originalHtml) btn.innerHTML = btn.dataset.originalHtml;
}
