/* Inline public disclosure navigation; staff drawer remains owned by app.js. */
(() => {
    'use strict';
    const header = document.querySelector('[data-pace-header]');
    if (!header) return;
    const toggle = header.querySelector('[data-navigation-toggle]');
    const nav = header.querySelector('#paceNavigation');
    const mobile = window.matchMedia('(max-width: 1050px)');
    const icon = toggle.querySelector('i');
    let expanded = false;
    const syncHeight = () => {
        document.documentElement.style.setProperty('--pace-header-height', `${header.getBoundingClientRect().height}px`);
    };
    const render = () => {
        nav.hidden = mobile.matches && !expanded;
        toggle.hidden = !mobile.matches;
        toggle.setAttribute('aria-expanded', String(expanded));
        toggle.setAttribute('aria-label', expanded ? 'Tutup menu layanan' : 'Buka menu layanan');
        icon.className = expanded ? 'fas fa-xmark' : 'fas fa-bars';
        syncHeight();
    };
    header.setAttribute('data-enhanced', '');
    toggle.addEventListener('click', () => { expanded = !expanded; render(); });
    header.addEventListener('keydown', event => {
        if (event.key === 'Escape' && expanded && mobile.matches) {
            expanded = false; render(); toggle.focus();
        }
    });
    document.addEventListener('click', event => {
        if (expanded && !header.contains(event.target)) { expanded = false; render(); }
    });
    nav.addEventListener('click', event => {
        if (event.target.closest('a') && mobile.matches) { expanded = false; render(); }
    });
    const resize = () => {
        const focused = document.activeElement;
        expanded = false; render();
        if (mobile.matches && nav.contains(focused)) toggle.focus();
        if (!mobile.matches && focused === toggle) header.querySelector('a').focus();
    };
    if (mobile.addEventListener) mobile.addEventListener('change', resize);
    else mobile.addListener(resize);
    if ('ResizeObserver' in window) new ResizeObserver(syncHeight).observe(header);
    else window.addEventListener('resize', syncHeight);
    render();
})();
