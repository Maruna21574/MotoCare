(function () {
    'use strict';

    // Cookie consent: banner, settings dialog and a "revisit consent" button.
    // Categories: necessary (always on), functional, analytics, advertising.
    // Third-party content waits for consent:
    //   <iframe data-consent="functional" data-src="...">   <script type="text/plain" data-consent="analytics" src="..."></script>
    const NAME = 'motocare_consent';
    const CATEGORIES = ['functional', 'analytics', 'advertising'];

    const read = () => {
        const match = document.cookie.split('; ').find((row) => row.startsWith(NAME + '='));
        if (!match) return null;
        try {
            const data = JSON.parse(decodeURIComponent(match.slice(NAME.length + 1)));
            return data && typeof data === 'object' ? data : null;
        } catch (error) { return null; }
    };
    const write = (choice) => {
        const value = encodeURIComponent(JSON.stringify(choice));
        document.cookie = `${NAME}=${value}; max-age=31536000; path=/; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;
    };

    const $ = (selector, scope = document) => scope.querySelector(selector);
    const banner = $('#cookie-banner');
    const dialog = $('#cookie-dialog');
    const revisit = $('#cookie-revisit');
    if (!banner || !dialog || !revisit) return;

    let state = read();

    const allowed = (category) => !!(state && state[category]);

    const applyConsent = () => {
        document.querySelectorAll('[data-consent]').forEach((el) => {
            const category = el.getAttribute('data-consent');
            if (!allowed(category) || el.dataset.consentDone) return;
            el.dataset.consentDone = '1';
            if (el.tagName === 'IFRAME') {
                el.src = el.getAttribute('data-src');
                const holder = el.closest('[data-consent-holder]');
                if (holder) holder.classList.add('is-allowed');
            } else if (el.tagName === 'SCRIPT') {
                const script = document.createElement('script');
                Array.from(el.attributes).forEach((attr) => {
                    if (attr.name !== 'type' && attr.name !== 'data-consent') script.setAttribute(attr.name, attr.value);
                });
                script.text = el.text;
                el.replaceWith(script);
            }
        });
        document.dispatchEvent(new CustomEvent('motocare:consent', { detail: Object.assign({}, state) }));
    };

    const show = (element) => { element.hidden = false; };
    const hide = (element) => { element.hidden = true; };

    function closeDialog() {
        hide(dialog);
        document.body.classList.remove('cookie-dialog-open');
    }

    const save = (choice) => {
        const previous = state || {};
        state = { necessary: true, functional: !!choice.functional, analytics: !!choice.analytics, advertising: !!choice.advertising };
        write(state);
        hide(banner);
        closeDialog();
        show(revisit);
        // Withdrawn consent: reload so already running third-party content stops.
        if (CATEGORIES.some((category) => previous[category] && !state[category])) { location.reload(); return; }
        applyConsent();
    };

    const openDialog = () => {
        CATEGORIES.forEach((category) => {
            const input = $(`[data-cookie-toggle="${category}"]`, dialog);
            if (input) input.checked = allowed(category);
        });
        show(dialog);
        document.body.classList.add('cookie-dialog-open');
        const first = $('button, input:not(:disabled)', dialog);
        if (first) first.focus();
    };

    const all = (value) => ({ functional: value, analytics: value, advertising: value });
    const dismissDialog = () => { closeDialog(); if (!state) show(banner); };

    document.addEventListener('click', (event) => {
        const target = event.target.closest('button, a');
        if (!target) return;
        if (target.matches('[data-cookie-accept]')) save(all(true));
        else if (target.matches('[data-cookie-reject]')) save(all(false));
        else if (target.matches('[data-cookie-open]')) { event.preventDefault(); hide(banner); openDialog(); }
        else if (target.matches('[data-cookie-settings]')) { event.preventDefault(); openDialog(); }
        else if (target.matches('[data-cookie-close]')) dismissDialog();
        else if (target.matches('[data-cookie-save]')) {
            const choice = {};
            CATEGORIES.forEach((category) => { const input = $(`[data-cookie-toggle="${category}"]`, dialog); choice[category] = !!(input && input.checked); });
            save(choice);
        } else if (target.matches('[data-cookie-allow-category]')) {
            const category = target.getAttribute('data-cookie-allow-category');
            save(Object.assign({}, state || all(false), { [category]: true }));
        }
    });
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dismissDialog(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !dialog.hidden) dismissDialog(); });

    if (state) { show(revisit); applyConsent(); } else { show(banner); }

    window.motoConsent = { get: () => Object.assign({}, state), allowed, open: openDialog };
})();
