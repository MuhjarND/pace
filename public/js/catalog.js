(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('serviceSearch');
        if (!input) return;
        const cards = Array.from(document.querySelectorAll('#servicesGrid .service-card'));
        const pills = Array.from(document.querySelectorAll('[data-category-filter]'));
        const parameters = new URLSearchParams(location.search);
        let category = parameters.get('category') || '';
        if (!pills.some(button => button.dataset.categoryFilter === category)) category = '';
        input.value = parameters.get('q') || '';
        function filter(updateUrl) {
            const query = input.value.trim().toLocaleLowerCase('id-ID');
            let count = 0;
            cards.forEach(card => {
                const visible = (!category || card.dataset.category === category) && card.dataset.name.includes(query);
                card.hidden = !visible;
                if (visible) count++;
            });
            pills.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.categoryFilter === category)));
            document.getElementById('serviceResultCount').textContent = count + ' dari ' + cards.length + ' layanan';
            document.getElementById('serviceNoResults').hidden = count > 0;
            if (updateUrl) {
                const url = new URL(location.href);
                if (query) url.searchParams.set('q', input.value.trim()); else url.searchParams.delete('q');
                if (category) url.searchParams.set('category', category); else url.searchParams.delete('category');
                history.replaceState(null, '', url);
            }
        }
        input.addEventListener('input', event => { if (!event.isComposing) filter(true); });
        input.addEventListener('compositionend', () => filter(true));
        pills.forEach(button => button.addEventListener('click', () => { category = button.dataset.categoryFilter; filter(true); }));
        document.querySelector('[data-reset-catalog]').addEventListener('click', () => {
            input.value = ''; category = ''; input.dispatchEvent(new Event('input', {bubbles:true})); input.focus();
        });
        filter(false);
        input.dispatchEvent(new Event('input', {bubbles:true}));
    });
})();
