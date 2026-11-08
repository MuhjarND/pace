(function () {
    'use strict';
    const form = document.getElementById('consultationForm');
    if (!form) return;
    const date = document.getElementById('consultationDate');
    const time = document.getElementById('consultationTime');
    const status = document.getElementById('availabilityStatus');
    const retry = document.getElementById('availabilityRetry');
    const submit = document.getElementById('bookConsultation');
    const unit = document.getElementById(form.dataset.unitSelector || '');
    const channel = document.getElementById(form.dataset.channelSelector || '');
    function channelValue() {
        if (!channel) return 'meet';
        if (channel.tagName === 'SELECT') return channel.value;
        const checked = channel.querySelector('input[name="channel"]:checked');
        return checked ? checked.value : 'chat';
    }
    function isMeet() { return channelValue() === 'meet'; }
    function maceGuide() {
        const link = document.getElementById('guestMaceGuide');
        if (!link) return;
        link.hidden = !unit.value;
        link.href = link.dataset.endpoint.replace('__UNIT__', unit.value);
    }
    function channelState() {
        if (!channel) return;
        const meet = isMeet();
        const mace = channelValue() === 'mace';
        document.getElementById('guestMaceInfo').hidden = !mace;
        maceGuide();
        submit.querySelector('[data-submit-label]').textContent = mace ? 'Ambil nomor antrean Mace' : 'Ajukan konsultasi';
        document.getElementById('guestMeetFields').hidden = !meet;
        date.disabled = !meet; date.required = meet;
        time.required = meet; time.disabled = !meet;
        document.getElementById('guestPhone').required = meet;
        if (!meet) { submit.disabled = false; if (controller) controller.abort(); sequence++; }
        else loadTimes(true);
    }
    let controller, sequence = 0, first = true;
    async function loadTimes(preserve) {
        if (form.dataset.busy === 'true') return;
        if (!isMeet()) return;
        if (unit && !unit.value) { status.textContent = 'Pilih satker untuk melihat jadwal.'; time.disabled = true; submit.disabled = true; return; }
        const selected = first ? form.dataset.selectedTime : (preserve ? time.value : '');
        first = false;
        const requestId = ++sequence;
        if (controller) controller.abort();
        controller = new AbortController();
        const current = controller;
        const timeout = setTimeout(() => current.abort(), 12000);
        time.disabled = true; submit.disabled = true; retry.hidden = true;
        time.setAttribute('aria-busy', 'true');
        status.textContent = 'Memuat waktu yang tersedia…';
        try {
            const url = new URL(form.dataset.availabilityEndpoint.replace('__UNIT__',unit ? unit.value : ''), location.href);
            url.searchParams.set('date', date.value);
            const response = await fetch(url.href, { signal: current.signal, cache: 'no-store', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok || response.redirected) throw new Error('Jadwal tidak dapat dimuat.');
            const data = await response.json();
            if (requestId !== sequence) return;
            time.replaceChildren(new Option('Pilih waktu konsultasi', ''));
            if (data.can_now) time.add(new Option('Sekarang · Masuk antrean', 'now'));
            data.slots.forEach(slot => time.add(new Option(slot + ' WIT', slot)));
            const stillAvailable = Array.from(time.options).some(option => option.value === selected);
            time.value = stillAvailable ? selected : '';
            const hasOptions = data.can_now || data.slots.length > 0;
            time.disabled = !hasOptions; submit.disabled = !hasOptions;
            const quota = data.quota_remaining === null ? '' : ' Sisa ' + data.quota_remaining + ' kuota.';
            status.textContent = (selected && selected !== 'now' && !stillAvailable ? 'Waktu pilihan sudah tidak tersedia. ' : '') + data.message + quota;
        } catch (error) {
            if (requestId !== sequence) return;
            status.textContent = 'Waktu belum dapat dimuat. Coba lagi; jika sesi berakhir, masuk kembali.';
            retry.hidden = false;
        } finally {
            clearTimeout(timeout);
            if (requestId === sequence) time.removeAttribute('aria-busy');
        }
    }
    date.addEventListener('change', () => loadTimes(false));
    retry.addEventListener('click', () => loadTimes(true));
    document.addEventListener('visibilitychange', () => { if (!document.hidden) loadTimes(true); });
    window.addEventListener('pagehide', () => { if (controller) controller.abort(); });
    window.addEventListener('pageshow', event => { if (event.persisted) loadTimes(true); });
    if (unit) unit.addEventListener('change',() => { maceGuide(); loadTimes(false); });
    if (channel) { channel.addEventListener('change',channelState); channelState(); }
    else loadTimes(true);
})();
