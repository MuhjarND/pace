// ============================================
// PTSP Online - Main JavaScript
// ============================================

document.addEventListener('DOMContentLoaded', function () {
    initSidebar();
    initSearch();
});

// ---- Sidebar Toggle ----
function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    const menuToggle = document.getElementById('menuToggle');
    const sidebarClose = document.getElementById('sidebarClose');

    const scrim = document.getElementById('sidebarScrim');
    const main = document.getElementById('mainContent');
    function setOpen(open) {
        sidebar.inert = !open && window.innerWidth <= 768;
        sidebar.classList.toggle('open', open);
        if (open) { sidebar.setAttribute('role', 'dialog'); sidebar.setAttribute('aria-modal', 'true'); sidebar.setAttribute('aria-label', 'Menu navigasi'); }
        else { sidebar.removeAttribute('role'); sidebar.removeAttribute('aria-modal'); sidebar.removeAttribute('aria-label'); }
        if (menuToggle) menuToggle.setAttribute('aria-expanded', String(open));
        if (scrim) scrim.hidden = !open;
        document.body.classList.toggle('drawer-open', open);
        if (main) main.inert = open;
        if (open) { const first = sidebar.querySelector('button,a'); if (first) first.focus(); }
        else if (menuToggle) menuToggle.focus();
    }
    if (menuToggle) menuToggle.addEventListener('click', () => setOpen(!sidebar.classList.contains('open')));
    if (sidebarClose) sidebarClose.addEventListener('click', () => setOpen(false));
    if (scrim) scrim.addEventListener('click', () => setOpen(false));
    sidebar.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
        if (window.innerWidth <= 768) setOpen(false);
    }));
    document.addEventListener('keydown', event => {
        if (!sidebar.classList.contains('open')) return;
        if (event.key === 'Escape') { event.preventDefault(); setOpen(false); }
        if (event.key === 'Tab') {
            const links = Array.from(sidebar.querySelectorAll('button,a[href]')).filter(element => element.getClientRects().length);
            const first = links[0], last = links[links.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    sidebar.inert = window.innerWidth <= 768;
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768 && sidebar.classList.contains('open')) setOpen(false);
        sidebar.inert = window.innerWidth <= 768 && !sidebar.classList.contains('open');
    });

}

// ---- Global Search ----
function initSearch() {
    const searchInput = document.getElementById('globalSearch');
    if (!searchInput) return;

    searchInput.addEventListener('input', (e) => {
        if (e.isComposing) return;
        const query = e.target.value.toLocaleLowerCase('id-ID');
        // Highlight matching nav items
        document.querySelectorAll('.nav-item span').forEach(span => {
            const text = span.textContent.toLowerCase();
            if (query && text.includes(query)) {
                span.style.color = 'var(--secondary)';
                span.style.fontWeight = '600';
            } else {
                span.style.color = '';
                span.style.fontWeight = '';
            }
        });
    });
    searchInput.addEventListener('compositionend', () => searchInput.dispatchEvent(new Event('input', {bubbles:true})));
}

// ---- AJAX Helper ----
function ajaxRequest(url, method, data, callback) {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function () {
        if (xhr.status >= 200 && xhr.status < 300) {
            callback(null, JSON.parse(xhr.responseText));
        } else {
            callback(new Error(xhr.statusText));
        }
    };
    xhr.onerror = function () { callback(new Error('Network Error')); };
    xhr.send(data ? JSON.stringify(data) : null);
}

// ---- Format Time ----
function formatTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

// ---- Notification Sound ----
function playNotificationSound() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.frequency.value = 800;
        gain.gain.value = 0.1;
        osc.start();
        osc.stop(ctx.currentTime + 0.15);
    } catch (e) { }
}
