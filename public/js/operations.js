// The queue fragment is rendered by one escaped Blade partial on both initial load and refresh.
(function () {
    'use strict';
    const board = document.getElementById('queueBoard');
    const refresh = document.getElementById('queueRefresh');
    const status = document.getElementById('queueUpdateStatus');
    if (!board || !refresh || !status) return;
    let timer, controller, running = false, stopped = false;
    const schedule = () => {
        clearTimeout(timer);
        if (!document.hidden && !stopped) timer = setTimeout(() => update(false), 10000);
    };
    async function update(manual) {
        if (running || stopped || document.hidden) return;
        if (document.querySelector('form[data-busy="true"]') || document.body.classList.contains('has-modal')) { schedule(); return; }
        running = true;
        refresh.disabled = true;
        refresh.setAttribute('aria-busy', 'true');
        controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 14000);
        try {
            const endpoint = new URL(board.dataset.endpoint, location.href);
            if (endpoint.origin !== location.origin) throw new Error('Endpoint tidak valid.');
            const response = await fetch(endpoint.href, { signal: controller.signal, cache: 'no-store', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (response.status === 401 || response.status === 403) {
                stopped = true;
                status.textContent = 'Sesi atau akses berubah. Muat ulang halaman untuk masuk kembali.';
                return;
            }
            if (!response.ok || response.redirected) throw new Error('Pembaruan gagal.');
            const data = await response.json();
            if (typeof data.html !== 'string' || typeof data.updated_at !== 'string') throw new Error('Respons tidak lengkap.');
            // Never replace controls while somebody is using them, including the review dialog.
            if (!manual && (board.contains(document.activeElement) || document.querySelector('form[data-busy="true"]') || document.body.classList.contains('has-modal'))) {
                status.textContent = 'Data baru tersedia. Pilih Muat ulang saat siap.';
                return;
            }
            const parsed = new DOMParser().parseFromString(data.html, 'text/html');
            board.replaceChildren(...Array.from(parsed.body.childNodes));
            if (window.PTSPUI) window.PTSPUI.enhanceForms(board);
            status.textContent = 'Diperbarui ' + data.updated_at + ' · otomatis tiap 10 detik';
            status.classList.remove('operations-update-error');
        } catch (error) {
            if (!document.hidden) {
                status.textContent = 'Pembaruan gagal. Data terakhir tetap ditampilkan; coba Muat ulang.';
                status.classList.add('operations-update-error');
            }
        } finally {
            clearTimeout(timeout);
            running = false;
            refresh.disabled = false;
            refresh.removeAttribute('aria-busy');
            schedule();
        }
    }
    refresh.addEventListener('click', () => { if (stopped) location.reload(); else update(true); });
    document.addEventListener('visibilitychange', () => {
        clearTimeout(timer);
        if (document.hidden && controller) controller.abort();
        else update(false);
    });
    window.addEventListener('pagehide', () => { clearTimeout(timer); if (controller) controller.abort(); });
    window.addEventListener('pageshow', event => { if (event.persisted) update(false); });
    update(false);
})();
