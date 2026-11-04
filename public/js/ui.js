// Shared PTSP controls. Laravel remains authoritative for validation and permissions.
(function () {
    'use strict';
    let sequence = 0;
    let activeModal = null;
    let restoreFocus = null;
    let inertElements = [];
    const focusable = 'button:not(:disabled),a[href],input:not(:disabled):not([type=hidden]),select:not(:disabled),textarea:not(:disabled),[tabindex="0"]';

    function status(message) {
        let region = document.getElementById('uiStatus');
        if (!region) {
            region = document.createElement('div');
            region.id = 'uiStatus';
            region.className = 'ui-status';
            region.setAttribute('role', 'status');
            document.body.appendChild(region);
        }
        region.replaceChildren();
        const text = document.createElement('span');
        text.textContent = message;
        const close = document.createElement('button');
        close.type = 'button';
        close.textContent = '×';
        close.setAttribute('aria-label', 'Tutup pemberitahuan');
        close.addEventListener('click', () => region.remove());
        region.append(text, close);
    }

    function openModal(modal, trigger) {
        if (activeModal) closeModal();
        restoreFocus = trigger || document.activeElement;
        activeModal = modal;
        modal.classList.add('active');
        document.body.classList.add('has-modal');
        inertElements = Array.from(document.body.children).filter(element => element !== modal && !element.contains(modal) && !element.inert && element.tagName !== 'SCRIPT');
        // Existing Blade dialogs may be inside main: isolate siblings on their ancestor path.
        let ancestor = modal;
        while (ancestor.parentElement && ancestor.parentElement !== document.body) {
            Array.from(ancestor.parentElement.children).forEach(element => {
                if (element !== ancestor && !element.inert && !inertElements.includes(element)) inertElements.push(element);
            });
            ancestor = ancestor.parentElement;
        }
        inertElements.forEach(element => { element.inert = true; });
        const first = modal.querySelector('[data-dialog-initial]') || modal.querySelector('input:not([type=hidden]),select,textarea') || modal.querySelector(focusable);
        if (first) first.focus();
    }

    function closeModal() {
        if (!activeModal) return;
        activeModal.classList.remove('active');
        activeModal = null;
        inertElements.forEach(element => { element.inert = false; });
        inertElements = [];
        document.body.classList.remove('has-modal');
        if (restoreFocus && restoreFocus.isConnected) restoreFocus.focus();
    }

    function confirmSubmit(form, submitter) {
        let modal = document.getElementById('uiConfirm');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'uiConfirm';
            modal.className = 'modal-backdrop';
            modal.setAttribute('role', 'alertdialog');
            modal.setAttribute('aria-modal', 'true');
            modal.setAttribute('aria-labelledby', 'uiConfirmTitle');
            modal.setAttribute('aria-describedby', 'uiConfirmDescription');
            const panel = document.createElement('div');
            panel.className = 'modal-content';
            const title = document.createElement('h2');
            title.id = 'uiConfirmTitle';
            title.textContent = 'Batalkan jadwal konsultasi?';
            const description = document.createElement('p');
            description.id = 'uiConfirmDescription';
            description.className = 'page-description';
            description.textContent = 'Jadwal ini akan dibatalkan dan dikeluarkan dari antrean. Anda dapat mengajukan jadwal baru.';
            const actions = document.createElement('div');
            actions.className = 'consultation-actions';
            const cancel = document.createElement('button');
            cancel.type = 'button';
            cancel.className = 'btn btn-secondary';
            cancel.textContent = 'Kembali';
            cancel.dataset.dialogInitial = '';
            cancel.dataset.dialogClose = '';
            const commit = document.createElement('button');
            commit.type = 'button';
            commit.id = 'uiConfirmCommit';
            commit.className = 'btn btn-danger';
            commit.textContent = 'Batalkan jadwal';
            actions.append(cancel, commit);
            panel.append(title, description, actions);
            modal.append(panel);
            document.body.append(modal);
        }
        modal.querySelector('#uiConfirmTitle').textContent = form.dataset.confirmTitle || 'Batalkan jadwal konsultasi?';
        modal.querySelector('#uiConfirmDescription').textContent = form.dataset.confirmDescription || 'Jadwal ini akan dibatalkan dan dikeluarkan dari antrean. Anda dapat mengajukan jadwal baru.';
        modal.querySelector('#uiConfirmCommit').textContent = form.dataset.confirmAction || 'Batalkan jadwal';
        modal.querySelector('#uiConfirmCommit').onclick = () => {
            closeModal();
            form.dataset.confirmed = 'true';
            form.requestSubmit(submitter || undefined);
        };
        openModal(modal, submitter);
    }

    function fieldError(field, message) {
        const id = field.id + '-ui-error';
        let error = document.getElementById(id);
        if (!error) {
            error = document.createElement('p');
            error.id = id;
            error.className = 'field-error';
            const controlGroup = field.closest('.password-field, .operations-check');
            if (controlGroup) controlGroup.insertAdjacentElement('afterend', error);
            else field.parentElement.insertBefore(error, field.nextSibling);
        }
        error.textContent = message;
        error.hidden = !message;
        field.setAttribute('aria-invalid', message ? 'true' : 'false');
        const references = (field.getAttribute('aria-describedby') || '').split(' ').filter(Boolean);
        if (!references.includes(id)) references.push(id);
        field.setAttribute('aria-describedby', references.join(' '));
    }

    function messageFor(field) {
        if (field.validity.customError) return field.validationMessage;
        if (field.validity.valueMissing) return 'Isian ini perlu dilengkapi.';
        if (field.validity.typeMismatch && field.type === 'email') return 'Masukkan alamat email yang lengkap, misalnya nama@contoh.id.';
        if (field.validity.typeMismatch && field.type === 'url') return 'Masukkan tautan lengkap yang diawali https://.';
        if (field.validity.tooShort) return 'Gunakan sedikitnya ' + field.minLength + ' karakter.';
        if (field.validity.patternMismatch) return 'Periksa format isian ini.';
        if (!field.validity.valid) return 'Periksa kembali nilai isian ini.';
        return '';
    }

    function enhanceForms(root = document) {
        const errorsElement = document.getElementById('operationsValidationErrors');
        const serverErrors = errorsElement ? JSON.parse(errorsElement.textContent) : {};
        root.querySelectorAll('form').forEach(form => {
            if (form.dataset.uiEnhanced) return;
            form.dataset.uiEnhanced = 'true';
            form.noValidate = true;
            const fields = Array.from(form.querySelectorAll('input:not([type=hidden]),select,textarea'));
            fields.forEach(field => {
                if (!field.id) field.id = 'ui-field-' + (++sequence);
                const group = field.closest('.form-group');
                const label = group && group.querySelector('label');
                if (label && !label.htmlFor) label.htmlFor = field.id;
                const hasAssociatedLabel = field.labels && field.labels.length > 0;
                if (!label && !hasAssociatedLabel && !field.getAttribute('aria-label') && field.name) field.setAttribute('aria-label', field.name === 'work_unit' ? 'Satuan kerja' : field.name);
                const serverError = group && group.querySelector('.invalid-feedback,span[style*="danger"]');
                if (serverError) {
                    serverError.id = field.id + '-server-error';
                    field.setAttribute('aria-invalid', 'true');
                    field.setAttribute('aria-describedby', serverError.id);
                }
                const serverKey = field.name.replace(/\[([^\]]+)\]/g, '.$1').replace(/\[\]$/, '');
                const errorKey = serverErrors[serverKey] ? serverKey : (field.type === 'file' ? Object.keys(serverErrors).find(key => key.startsWith(serverKey + '.')) : null);
                if (errorKey) fieldError(field, serverErrors[errorKey][0]);
                field.addEventListener('input', () => {
                    if (serverError) serverError.hidden = true;
                    if (field.getAttribute('aria-invalid') === 'true') fieldError(field, messageFor(field));
                });
            });
            form.addEventListener('submit', event => {
                if (form.dataset.busy === 'true') { event.preventDefault(); return; }
                let firstInvalid = null;
                Array.from(form.querySelectorAll('input:not([type=hidden]),select,textarea')).forEach(field => {
                    if (field.disabled || !field.willValidate) return;
                    const message = messageFor(field);
                    fieldError(field, message);
                    if (message && !firstInvalid) firstInvalid = field;
                });
                if (firstInvalid) { event.preventDefault(); firstInvalid.focus(); return; }
                if ((form.dataset.confirmTitle || (form.querySelector('input[name="_method"][value="DELETE"]') && form.action.includes('/meetings/'))) && form.dataset.confirmed !== 'true') {
                    event.preventDefault(); confirmSubmit(form, event.submitter); return;
                }
                if (event.defaultPrevented || form.method.toLowerCase() !== 'post') return;
                form.dataset.busy = 'true';
                form.setAttribute('aria-busy', 'true');
                const submitStatus = form.querySelector('[data-form-status]');
                if (submitStatus) submitStatus.textContent = form.dataset.busyMessage || 'Sedang diproses…';
                const submitter = event.submitter;
                // Keep named button values in the POST when disabling controls.
                if (submitter && submitter.name) {
                    const value = document.createElement('input');
                    value.type = 'hidden'; value.name = submitter.name; value.value = submitter.value;
                    value.dataset.submitterValue = '';
                    form.append(value);
                }
                form.querySelectorAll('button[type=submit],button:not([type])').forEach(button => { button.dataset.uiDisabled = ''; button.disabled = true; });
            });
        });
    }

    function enhanceFields() {
        document.querySelectorAll('input[type=password]').forEach(field => {
            if (field.parentElement.classList.contains('password-field')) return;
            if (!field.autocomplete) field.autocomplete = field.name === 'password' && location.pathname.endsWith('/login') ? 'current-password' : 'new-password';
            const wrapper = document.createElement('div');
            wrapper.className = 'password-field';
            field.replaceWith(wrapper); wrapper.append(field);
            const toggle = document.createElement('button');
            toggle.type = 'button'; toggle.className = 'password-toggle';
            const secretLabel = field.dataset.secretLabel || 'kata sandi';
            toggle.textContent = 'Lihat'; toggle.setAttribute('aria-label', 'Tampilkan ' + secretLabel); toggle.setAttribute('aria-pressed', 'false');
            toggle.addEventListener('click', () => {
                const reveal = field.type === 'password'; field.type = reveal ? 'text' : 'password';
                toggle.textContent = reveal ? 'Sembunyi' : 'Lihat';
                toggle.setAttribute('aria-label', (reveal ? 'Sembunyikan ' : 'Tampilkan ') + secretLabel);
                toggle.setAttribute('aria-pressed', String(reveal));
            });
            wrapper.append(toggle);
        });
        document.querySelectorAll('textarea').forEach(field => {
            const grow = () => { field.style.height = 'auto'; field.style.height = Math.max(120, Math.min(field.scrollHeight + 2, 340)) + 'px'; };
            field.addEventListener('input', grow); grow();
        });
        document.querySelectorAll('#globalSearch,#serviceSearch,[data-clear-search]').forEach(field => {
            const clear = document.createElement('button');
            clear.type = 'button'; clear.className = 'search-clear'; clear.textContent = '×'; clear.hidden = !field.value;
            clear.setAttribute('aria-label', 'Hapus pencarian');
            clear.addEventListener('click', () => {
                field.value = ''; field.dispatchEvent(new Event('input', { bubbles: true })); field.focus();
                if (field.hasAttribute('data-clear-submit') && field.form) field.form.requestSubmit();
            });
            field.parentElement.append(clear);
            field.addEventListener('input', () => { clear.hidden = !field.value; });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        enhanceForms(); enhanceFields();
        const serverSummary = document.querySelector('.operations-errors');
        if (serverSummary) serverSummary.focus();
        document.querySelectorAll('.nav-item.active').forEach(link => link.setAttribute('aria-current', 'page'));
        const reopening = document.querySelector('[data-reopen=true]');
        if (reopening) openModal(reopening);
    });
    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-dialog-open]');
        if (trigger) openModal(document.getElementById(trigger.dataset.dialogOpen), trigger);
        if (event.target.closest('[data-dialog-close]') || event.target === activeModal) closeModal();
    });
    document.addEventListener('keydown', event => {
        if (!activeModal) {
            const notification = document.getElementById('notifDropdown');
            if (event.key === 'Escape' && notification && notification.classList.contains('show')) {
                notification.classList.remove('show');
                const trigger = document.getElementById('notifBtn'); trigger.setAttribute('aria-expanded', 'false'); trigger.focus();
            }
            return;
        }
        if (event.key === 'Escape') { event.preventDefault(); closeModal(); return; }
        if (event.key === 'Tab') {
            const items = Array.from(activeModal.querySelectorAll(focusable)).filter(element => element.getClientRects().length);
            const first = items[0], last = items[items.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('form[data-busy=true]').forEach(form => {
            delete form.dataset.busy; delete form.dataset.confirmed; form.removeAttribute('aria-busy');
            const submitStatus = form.querySelector('[data-form-status]');
            if (submitStatus) submitStatus.textContent = '';
            form.querySelectorAll('[data-ui-disabled]').forEach(button => { button.disabled = false; delete button.dataset.uiDisabled; });
            form.querySelectorAll('[data-submitter-value]').forEach(field => field.remove());
        });
    });
    window.PTSPUI = { status, openModal, closeModal, enhanceForms };
})();
