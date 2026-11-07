(function () {
    'use strict';

    // Ask before destructive actions (cancel, delete).
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });

    // Block form: time inputs only when the block is not for the whole day.
    const wholeDay = document.querySelector('[data-whole-day]');
    const blockTimes = document.querySelector('[data-block-times]');
    if (wholeDay && blockTimes) {
        const sync = () => { blockTimes.hidden = wholeDay.checked; };
        wholeDay.addEventListener('change', sync);
        sync();
    }

    // Opening hours: disable the time inputs of closed days.
    document.querySelectorAll('[data-hours-row]').forEach((row) => {
        const open = row.querySelector('input[type="checkbox"]');
        const sync = () => {
            row.classList.toggle('is-closed', !open.checked);
            row.querySelectorAll('input[type="time"]').forEach((input) => { input.disabled = !open.checked; });
        };
        open.addEventListener('change', sync);
        sync();
    });

    // Booking form: the pickup address is shown only when pickup is checked.
    const pickup = document.querySelector('[data-pickup]');
    const pickupAddress = document.querySelector('[data-pickup-address]');
    if (pickup && pickupAddress) {
        const sync = () => { pickupAddress.hidden = !pickup.checked; };
        pickup.addEventListener('change', sync);
        sync();
    }

    // Booking form: changing the service resets a custom duration so the service length is used.
    const serviceSelect = document.querySelector('[data-service-select]');
    const duration = document.querySelector('[data-duration]');
    if (serviceSelect && duration) {
        serviceSelect.addEventListener('change', () => { duration.value = ''; });
    }

    // Photo preview in the same window (instead of a new tab) with arrows and keyboard.
    const previewLinks = Array.from(document.querySelectorAll('a[data-preview]'));
    if (previewLinks.length) {
        const box = document.createElement('div');
        box.className = 'preview';
        box.hidden = true;
        box.innerHTML = '<div class="preview-box" role="dialog" aria-modal="true" aria-label="Náhľad fotky"><button type="button" class="preview-close" aria-label="Zavrieť">×</button><button type="button" class="preview-nav preview-prev" aria-label="Predchádzajúca">‹</button><img alt=""><button type="button" class="preview-nav preview-next" aria-label="Ďalšia">›</button><p class="preview-count"></p></div>';
        document.body.appendChild(box);
        const image = box.querySelector('img');
        const count = box.querySelector('.preview-count');
        let current = 0;
        const show = (index) => {
            current = (index + previewLinks.length) % previewLinks.length;
            const link = previewLinks[current];
            image.src = link.getAttribute('href');
            const label = link.closest('figure')?.querySelector('.gallery-tile-num')?.textContent.trim() || '';
            count.textContent = `${current + 1} / ${previewLinks.length}${label ? ' — ' + label : ''}`;
        };
        const close = () => { box.hidden = true; image.removeAttribute('src'); document.body.style.overflow = ''; };
        previewLinks.forEach((link, index) => link.addEventListener('click', (event) => {
            event.preventDefault();
            show(index);
            box.hidden = false;
            document.body.style.overflow = 'hidden';
        }));
        box.addEventListener('click', (event) => {
            if (event.target === box || event.target.closest('.preview-close')) close();
            else if (event.target.closest('.preview-prev')) show(current - 1);
            else if (event.target.closest('.preview-next')) show(current + 1);
        });
        document.addEventListener('keydown', (event) => {
            if (box.hidden) return;
            if (event.key === 'Escape') close();
            else if (event.key === 'ArrowLeft') show(current - 1);
            else if (event.key === 'ArrowRight') show(current + 1);
        });
    }
})();
