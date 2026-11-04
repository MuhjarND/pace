(function () {
    'use strict';
    const labels = ['Pilih provinsi', 'Pilih kabupaten / kota', 'Pilih distrik', 'Pilih kampung / kelurahan'];
    function enhancePickers(root) {
        root.querySelectorAll('[data-region-picker]').forEach(picker => {
            if (picker.dataset.enhanced) return;
            picker.dataset.enhanced = 'true';
            const selects = Array.from(picker.querySelectorAll('[data-region-level]'));
            const value = picker.querySelector('[data-region-value]');
            const feedback = picker.querySelector('.region-feedback');
            const retry = picker.querySelector('[data-region-retry]');
            let controller, sequence = 0, failedIndex = 0;
            async function changed(index) {
                failedIndex = index;
                const current = ++sequence;
                if (controller) controller.abort();
                for (let n = index + 1; n < selects.length; n++) {
                    selects[n].replaceChildren(new Option(labels[n], '')); selects[n].disabled = true;
                }
                value.value = selects.slice(0, index + 1).map(s => s.value).filter(Boolean).pop() || '';
                feedback.textContent = ''; retry.hidden = true;
                if (!selects[index].value || index === selects.length - 1) return;
                controller = new AbortController();
                const active = controller;
                const timer = setTimeout(() => active.abort(), 12000);
                feedback.textContent = 'Memuat wilayah…';
                try {
                    const url = new URL(picker.dataset.endpoint, location.href);
                    url.searchParams.set('parent', selects[index].value);
                    const response = await fetch(url.href, {signal: active.signal, headers: {Accept: 'application/json'}});
                    if (!response.ok || response.redirected) throw new Error();
                    const data = await response.json();
                    if (current !== sequence) return;
                    data.regions.forEach(r => selects[index + 1].add(new Option(r.name, r.code)));
                    selects[index + 1].disabled = !data.regions.length;
                    feedback.textContent = data.regions.length ? '' : 'Belum ada data wilayah turunan. Hubungi PTSP untuk konfirmasi.';
                } catch (e) {
                    if (current !== sequence) return;
                    feedback.textContent = 'Wilayah belum dapat dimuat. Pilihan sebelumnya tetap tersimpan.'; retry.hidden = false;
                } finally { clearTimeout(timer); }
            }
            selects.forEach((select, index) => select.addEventListener('change', () => changed(index)));
            retry.addEventListener('click', () => changed(failedIndex));
        });
    }
    enhancePickers(document);

    const parties = document.getElementById('feeParties');
    if (parties) {
        let next = 0;
        parties.querySelectorAll('select[name$="[role]"]').forEach(select => {
            const match = select.name.match(/parties\[(\d+)\]/); if (match) next = Math.max(next, Number(match[1]) + 1);
        });
        const add = document.getElementById('addFeeParty');
        const status = document.getElementById('feePartyStatus');
        function update() {
            const count = parties.querySelectorAll('fieldset').length;
            add.disabled = count >= 10;
            parties.querySelectorAll('[data-remove-party]').forEach(button => { button.disabled = count <= 1; });
        }
        add.addEventListener('click', () => {
            if (parties.querySelectorAll('fieldset').length >= 10) return;
            const source = document.getElementById('feePartyTemplate');
            // Trusted, server-rendered template only; never interpolate user content as HTML.
            const template = document.createElement('template');
            template.innerHTML = source.innerHTML.replaceAll('__INDEX__', String(next++));
            const row = template.content.firstElementChild;
            parties.append(row); enhancePickers(row);
            if (window.PTSPUI) window.PTSPUI.enhanceForms(parties.closest('form'));
            row.querySelector('select').focus(); update(); status.textContent = 'Pihak ditambahkan.';
        });
        parties.addEventListener('click', e => {
            const button = e.target.closest('[data-remove-party]'); if (!button || button.disabled) return;
            button.closest('fieldset').remove(); update(); add.focus(); status.textContent = 'Pihak dihapus dari perkiraan.';
        });
        update();
    }

    const ticket = document.querySelector('[data-ticket-status]');
    if (ticket) {
        let busy = false;
        const feedback = ticket.querySelector('[data-ticket-feedback]');
        const refresh = ticket.querySelector('[data-ticket-refresh]');
        async function poll() {
            if (busy || document.hidden) return;
            busy = true; refresh.disabled = true;
            const controller = new AbortController(); const timeout = setTimeout(() => controller.abort(), 12000);
            try {
                const response = await fetch(ticket.dataset.endpoint, {cache: 'no-store', signal:controller.signal, headers:{Accept:'application/json'}});
                if (!response.ok || response.redirected) throw new Error(response.status === 404 || response.status === 410 ? 'Tiket kedaluwarsa atau sesi tidak lagi tersedia. Muat ulang halaman.' : 'Status belum dapat diperbarui. Coba lagi.');
                const data = await response.json();
                ticket.querySelector('[data-queue-label]').textContent = data.queue.status;
                ticket.querySelector('[data-verification]').textContent = data.verification;
                ticket.querySelector('[data-queue-ahead]').textContent = data.queue.position ? 'Ada ' + data.queue.ahead + ' antrean sebelum Anda.' : 'Antrean tersedia setelah jadwal diverifikasi dan tiba waktunya.';
                ticket.querySelector('[data-wa-status]').textContent = data.notification ? 'WhatsApp: ' + data.notification : 'WhatsApp dikirim saat petugas memanggil Anda.';
                ticket.querySelector('[data-ticket-join]').disabled = !data.can_join;
                feedback.textContent = 'Status terbaru sudah dimuat. Diperiksa otomatis setiap 10 detik.';
            } catch (error) { feedback.textContent = error.message || 'Status belum dapat dimuat. Coba lagi.'; }
            finally {clearTimeout(timeout); busy = false; refresh.disabled = false;}
        }
        refresh.addEventListener('click',poll); setInterval(poll,10000); document.addEventListener('visibilitychange',poll);
    }

    const messages = document.getElementById('guestMessages');
    if (messages) {
        let busy = false;
        let last = Number(messages.dataset.lastId);
        const refresh = document.getElementById('guestChatRefresh');
        const older = document.getElementById('guestChatOlder');
        const status = document.getElementById('guestChatStatus');
        function render(row) {
            const article = document.createElement('article'); article.className = 'guest-message' + (row.mine ? ' mine' : ''); article.dataset.messageId = row.id;
            const name = document.createElement('strong'); name.textContent = row.name;
            const text = document.createElement('p'); text.textContent = row.text;
            const time = document.createElement('time'); time.textContent = row.time;
            article.append(name,text,time); return article;
        }
        async function poll(before) {
            if (busy || document.hidden) return; busy = true; refresh.disabled = true; older.disabled = true;
            const controller = new AbortController(); const timer = setTimeout(() => controller.abort(),12000);
            try {
                const url = new URL(messages.dataset.endpoint,location.href); url.searchParams.set(before ? 'before' : 'after',before || last);
                const response = await fetch(url.href,{cache:'no-store',signal:controller.signal,headers:{Accept:'application/json'}});
                if (!response.ok || response.redirected) throw new Error();
                const data = await response.json(); const frag = document.createDocumentFragment();
                data.messages.forEach(row => {
                    if (!messages.querySelector('[data-message-id="' + Number(row.id) + '"]')) frag.append(render(row));
                    last = Math.max(last,row.id);
                });
                if (before) {
                    messages.prepend(frag); older.hidden = data.messages.length < 50;
                    if (data.messages.length) older.dataset.before = data.messages[0].id;
                } else {
                    const atBottom = messages.scrollHeight - messages.scrollTop - messages.clientHeight < 90;
                    messages.append(frag); if (atBottom) messages.scrollTop = messages.scrollHeight;
                }
                status.textContent = 'Pesan terbaru sudah diperiksa.';
            } catch (error) { status.textContent = 'Pesan belum dapat diperbarui. Pesan yang sudah dimuat tetap tersedia. Coba lagi atau muat ulang halaman.'; }
            finally {clearTimeout(timer);busy=false;refresh.disabled=false;older.disabled=false;}
        }
        refresh.addEventListener('click',()=>poll()); older.addEventListener('click',()=>poll(older.dataset.before));
        setInterval(()=>poll(),10000); document.addEventListener('visibilitychange',()=>poll()); messages.scrollTop = messages.scrollHeight;
    }
})();
