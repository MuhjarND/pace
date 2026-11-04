(function () {
    'use strict';
    window.addEventListener('pageshow', event => {
        if (event.persisted && document.querySelector('[data-complaint-private]')) window.location.reload();
    });
    document.querySelectorAll('[data-complaint-files]').forEach(input => {
        const list = input.closest('.form-group').querySelector('[data-file-list]');
        const status = input.closest('.form-group').querySelector('[data-file-status]');
        function render() {
            list.replaceChildren();
            const files = Array.from(input.files || []);
            let error = files.length > 3 ? 'Pilih paling banyak 3 berkas bukti.' : '';
            files.forEach((file, index) => {
                if (file.size > 5 * 1024 * 1024) error = 'Setiap berkas bukti maksimal 5 MB.';
                if (file.type && !['application/pdf','image/jpeg','image/png'].includes(file.type)) error = 'Bukti hanya boleh berupa PDF, JPG, atau PNG.';
                const row = document.createElement('li'), text = document.createElement('span'), size = document.createElement('small');
                text.textContent = file.name; size.textContent = Math.ceil(file.size / 1024).toLocaleString('id-ID') + ' KB'; text.append(size);
                const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn btn-secondary btn-sm';
                remove.textContent = 'Hapus'; remove.setAttribute('aria-label','Hapus bukti ' + file.name);
                remove.addEventListener('click', () => {
                    try { const transfer = new DataTransfer(); files.forEach((item, i) => { if (i !== index) transfer.items.add(item); }); input.files = transfer.files; }
                    catch (e) { input.value = ''; }
                    render(); input.dispatchEvent(new Event('input',{bubbles:true})); input.focus();
                });
                row.append(text, remove); list.append(row);
            });
            input.setCustomValidity(error);
            status.textContent = error || (files.length ? files.length + ' berkas dipilih. Bukti dikirim bersama laporan.' : 'Belum ada berkas dipilih.');
        }
        input.addEventListener('change',render); render();
    });
    document.querySelectorAll('[data-copy-code]').forEach(button => {
        button.addEventListener('click', async () => {
            const input = document.getElementById(button.dataset.copyCode), status = button.parentElement.querySelector('[data-copy-status]');
            try { if (!navigator.clipboard) throw new Error(); await navigator.clipboard.writeText(input.value); status.textContent = 'Kode rahasia disalin. Simpan di tempat pribadi.'; }
            catch (e) { const toggle = input.parentElement.querySelector('.password-toggle'); if (input.type === 'password') { if (toggle) toggle.click(); else input.type = 'text'; } input.focus(); input.select(); status.textContent = 'Kode ditampilkan dan dipilih. Gunakan salin pada perangkat Anda, lalu simpan di tempat pribadi.'; }
        });
    });
})();
