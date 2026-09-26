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
})();
