(function () {
    'use strict';

    const $ = (selector, scope = document) => scope.querySelector(selector);
    const $$ = (selector, scope = document) => Array.from(scope.querySelectorAll(selector));
    const CART_KEY = 'motocare_cart';
    const readCart = () => {
        try { const stored = JSON.parse(localStorage.getItem(CART_KEY) || '[]'); return Array.isArray(stored) ? stored : []; }
        catch (error) { return []; }
    };
    let cart = readCart();

    const saveCart = () => {
        localStorage.setItem(CART_KEY, JSON.stringify(cart));
        updateCartCount();
    };
    const cartQuantity = () => cart.reduce((sum, item) => sum + item.quantity, 0);
    const cartAmount = () => cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
    const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (character) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character]));
    const updateCartCount = () => { const counter = $('#cart-count'); if (counter) counter.textContent = cartQuantity(); };
    const toast = (message) => {
        const element = $('#toast');
        if (!element) return;
        element.textContent = message;
        element.classList.add('is-visible');
        window.clearTimeout(element._timer);
        element._timer = window.setTimeout(() => element.classList.remove('is-visible'), 3600);
    };

    const menuToggle = $('#menu-toggle');
    const mobileNav = $('#mobile-nav');
    const setMenu = (open) => {
        mobileNav?.classList.toggle('is-open', open);
        menuToggle?.classList.toggle('is-open', open);
        menuToggle?.setAttribute('aria-expanded', String(open));
    };
    menuToggle?.addEventListener('click', () => setMenu(!mobileNav?.classList.contains('is-open')));
    $$('.mobile-nav a').forEach((link) => link.addEventListener('click', () => setMenu(false)));

    const siteHeader = $('.site-header');
    const scrollToTop = $('.scroll-to-top');
    const updateHeaderState = () => siteHeader?.classList.toggle('is-scrolled', window.scrollY > 20);
    const updateScrollState = () => {
        updateHeaderState();
        scrollToTop?.classList.toggle('is-visible', window.scrollY > 300);
    };
    updateScrollState();
    window.addEventListener('scroll', updateScrollState, { passive: true });

    const revealItems = $$('.reveal');
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries, instance) => {
            entries.forEach((entry) => { if (entry.isIntersecting) { entry.target.classList.add('is-visible'); instance.unobserve(entry.target); } });
        }, { threshold: 0.08 });
        revealItems.forEach((item) => observer.observe(item));
    } else revealItems.forEach((item) => item.classList.add('is-visible'));

    // Before/after sliders and the tabs that switch between them.
    $$('.comparison').forEach((comparison) => {
        const input = $('input', comparison);
        input?.addEventListener('input', () => comparison.style.setProperty('--position', `${input.value}%`));
    });
    const compareTabs = $$('[data-compare-tab]');
    compareTabs.forEach((tab) => tab.addEventListener('click', () => {
        compareTabs.forEach((other) => {
            const active = other === tab;
            other.classList.toggle('is-active', active);
            other.setAttribute('aria-selected', String(active));
            $(`#compare-${other.dataset.compareTab}`).hidden = !active;
        });
    }));

    // Gallery lightbox with keyboard, arrows and a scrollable thumbnail strip.
    const galleryItems = $$('.gallery-item[data-gallery-index]');
    const galleryLightbox = $('#gallery-lightbox');
    const galleryLightboxImage = $('#gallery-lightbox-image');
    const galleryLightboxCounter = $('#gallery-lightbox-counter');
    const galleryLightboxThumbs = $('#gallery-lightbox-thumbs');
    let galleryIndex = 0;
    let galleryTrigger = null;
    const galleryCount = galleryItems.length;
    const renderGalleryLightbox = (index) => {
        if (!galleryCount || !galleryLightboxImage) return;
        galleryIndex = (index + galleryCount) % galleryCount;
        const item = galleryItems[galleryIndex];
        const image = $('img', item);
        galleryLightboxImage.src = image.currentSrc || image.src;
        galleryLightboxImage.alt = image.alt;
        if (galleryLightboxCounter) galleryLightboxCounter.textContent = `${galleryIndex + 1} / ${galleryCount}`;
        $$('[data-lightbox-thumb]', galleryLightboxThumbs).forEach((thumb) => thumb.classList.toggle('is-active', Number(thumb.dataset.lightboxThumb) === galleryIndex));
        const activeThumb = $(`[data-lightbox-thumb="${galleryIndex}"]`, galleryLightboxThumbs);
        activeThumb?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    };
    const openGalleryLightbox = (index, trigger) => {
        if (!galleryLightbox) return;
        galleryTrigger = trigger;
        renderGalleryLightbox(index);
        galleryLightbox.classList.add('is-open');
        galleryLightbox.setAttribute('aria-hidden', 'false');
        document.body.classList.add('no-scroll');
        $('.lb-close', galleryLightbox)?.focus();
    };
    const closeGalleryLightbox = () => {
        if (!galleryLightbox) return;
        galleryLightbox.classList.remove('is-open');
        galleryLightbox.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('no-scroll');
        galleryTrigger?.focus();
        galleryTrigger = null;
    };
    galleryItems.forEach((item) => {
        const open = () => openGalleryLightbox(Number(item.dataset.galleryIndex), item);
        item.addEventListener('click', open);
        item.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); open(); }
        });
    });
    galleryLightbox?.addEventListener('click', (event) => {
        if (event.target.closest('[data-lightbox-close]') || event.target.classList.contains('lb-stage')) closeGalleryLightbox();
        if (event.target.closest('[data-lightbox-prev]')) renderGalleryLightbox(galleryIndex - 1);
        if (event.target.closest('[data-lightbox-next]')) renderGalleryLightbox(galleryIndex + 1);
        const thumb = event.target.closest('[data-lightbox-thumb]');
        if (thumb) renderGalleryLightbox(Number(thumb.dataset.lightboxThumb));
    });
    document.addEventListener('keydown', (event) => {
        if (!galleryLightbox?.classList.contains('is-open')) return;
        if (event.key === 'Escape') closeGalleryLightbox();
        if (event.key === 'ArrowLeft') renderGalleryLightbox(galleryIndex - 1);
        if (event.key === 'ArrowRight') renderGalleryLightbox(galleryIndex + 1);
    });
    // Swipe left/right on touch screens.
    let swipeStartX = null;
    galleryLightboxImage?.addEventListener('touchstart', (event) => { swipeStartX = event.touches[0].clientX; }, { passive: true });
    galleryLightboxImage?.addEventListener('touchend', (event) => {
        if (swipeStartX === null) return;
        const distance = event.changedTouches[0].clientX - swipeStartX;
        swipeStartX = null;
        if (Math.abs(distance) > 40) renderGalleryLightbox(galleryIndex + (distance < 0 ? 1 : -1));
    });

    // Reservation wizard: service → date & time → contact → summary.
    const bookForm = $('#book-form');
    if (bookForm) {
        const services = window.MOTOCARE?.services || {};
        const monthNames = ['Január', 'Február', 'Marec', 'Apríl', 'Máj', 'Jún', 'Júl', 'August', 'September', 'Október', 'November', 'December'];
        const pad = (number) => String(number).padStart(2, '0');
        const [todayYear, todayMonth] = window.MOTOCARE.today.split('-').map(Number);
        const panels = $$('[data-panel]', bookForm);
        const stepItems = $$('#book-steps li');
        const dateInput = $('#book-date');
        const timeInput = $('#book-time');
        const daysGrid = $('#book-days');
        const slotsGrid = $('#book-slots');
        const timesTitle = $('#book-times-title');
        const timesNote = $('#book-times-note');
        const monthLabel = $('#book-month-label');
        const message = $('#book-message');
        let step = 1;
        let view = { year: todayYear, month: todayMonth };
        let selectedSlot = null;
        let autoPick = true;
        let monthRequest = 0;

        const serviceId = () => $('input[name="service"]:checked', bookForm)?.value || '';
        const monthKey = () => `${view.year}-${pad(view.month)}`;
        const setMessage = (text, type = '') => { message.textContent = text; message.className = `form-message${type ? ` is-${type}` : ''}`; };
        const dayTitles = { available: 'voľných časov', full: 'Obsadené', closed: 'Zatvorené', past: '' };

        const showStep = (target) => {
            step = target;
            panels.forEach((panel) => { panel.hidden = panel.dataset.panel !== String(target); });
            stepItems.forEach((item) => {
                const number = Number(item.dataset.step);
                item.classList.toggle('is-active', number === target);
                item.classList.toggle('is-done', target === 'done' || number < target);
            });
            if (target === 2) enterDateStep();
            if (target === 4) renderSummary();
            const steps = $('#book-steps');
            if (steps.getBoundingClientRect().top < 0) steps.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        const validateStep = () => {
            if (step === 1 && !serviceId()) { toast('Vyber si prosím službu.'); return false; }
            if (step === 2 && (!dateInput.value || !timeInput.value)) { toast('Vyber si prosím deň a čas.'); return false; }
            if (step === 3) return $$('input, textarea', $('[data-panel="3"]', bookForm)).every((field) => field.reportValidity());
            return true;
        };

        const resetDate = () => { dateInput.value = ''; timeInput.value = ''; selectedSlot = null; autoPick = true; };

        const renderMonth = (days) => {
            monthLabel.textContent = `${monthNames[view.month - 1]} ${view.year}`;
            $('[data-month="-1"]', bookForm).disabled = view.year === todayYear && view.month === todayMonth;
            const offset = (new Date(view.year, view.month - 1, 1).getDay() + 6) % 7;
            daysGrid.innerHTML = '<span></span>'.repeat(offset);
            Object.entries(days).forEach(([date, info]) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = Number(date.slice(8));
                button.dataset.date = date;
                button.className = `book-day is-${info.state}${date === dateInput.value ? ' is-selected' : ''}`;
                button.disabled = info.state !== 'available';
                button.title = info.state === 'available' ? `${info.free} ${dayTitles.available}` : dayTitles[info.state] || '';
                button.addEventListener('click', () => selectDate(date));
                daysGrid.appendChild(button);
            });
        };

        const loadMonth = async () => {
            const request = ++monthRequest;
            monthLabel.textContent = `${monthNames[view.month - 1]} ${view.year}`;
            daysGrid.innerHTML = '<p class="book-loading">Načítavam dostupnosť…</p>';
            try {
                const response = await fetch(`api.php?action=month&service=${encodeURIComponent(serviceId())}&month=${monthKey()}`, { headers: { Accept: 'application/json' } });
                const result = await response.json();
                if (request !== monthRequest) return;
                if (!result.ok) throw new Error(result.message);
                renderMonth(result.days);
                if (autoPick && !dateInput.value) {
                    const first = Object.entries(result.days).find(([, info]) => info.state === 'available');
                    if (first) { autoPick = false; selectDate(first[0]); }
                    else if (view.year * 12 + view.month < todayYear * 12 + todayMonth + 2) changeMonth(1);
                    else autoPick = false;
                }
            } catch (error) {
                daysGrid.innerHTML = '<p class="book-loading">Kalendár sa nepodarilo načítať. Zavolaj nám prosím.</p>';
            }
        };

        const changeMonth = (delta) => {
            const index = view.year * 12 + (view.month - 1) + delta;
            if (index < todayYear * 12 + todayMonth - 1) return;
            view = { year: Math.floor(index / 12), month: (index % 12) + 1 };
            loadMonth();
        };

        const selectDate = async (date) => {
            dateInput.value = date;
            timeInput.value = '';
            selectedSlot = null;
            $$('.book-day', daysGrid).forEach((day) => day.classList.toggle('is-selected', day.dataset.date === date));
            timesTitle.textContent = 'Načítavam časy…';
            timesNote.textContent = '';
            slotsGrid.innerHTML = '';
            try {
                const response = await fetch(`api.php?action=availability&service=${encodeURIComponent(serviceId())}&date=${date}`, { headers: { Accept: 'application/json' } });
                const result = await response.json();
                if (dateInput.value !== date) return;
                if (!result.ok) throw new Error(result.message);
                const free = result.slots.filter((slot) => slot.available);
                timesTitle.textContent = result.label.charAt(0).toUpperCase() + result.label.slice(1);
                if (result.whole_day) timesNote.textContent = 'Služba zaberie celý deň — motorku privezieš ráno a vyzdvihneš ju po dohode.';
                else timesNote.textContent = free.length ? 'Vyber si čas príchodu.' : 'V tento deň už nie je voľný čas.';
                free.forEach((slot) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'book-slot';
                    button.innerHTML = '<b></b><small></small>';
                    $('b', button).textContent = slot.time;
                    $('small', button).textContent = `do ${slot.end}`;
                    button.addEventListener('click', () => {
                        $$('.book-slot', slotsGrid).forEach((item) => item.classList.remove('is-selected'));
                        button.classList.add('is-selected');
                        timeInput.value = slot.time;
                        selectedSlot = { ...slot, label: timesTitle.textContent };
                    });
                    slotsGrid.appendChild(button);
                });
            } catch (error) {
                timesTitle.textContent = 'Časy sa nepodarilo načítať.';
            }
        };

        const enterDateStep = () => {
            const service = services[serviceId()];
            const picked = $('#book-picked-service');
            picked.innerHTML = service ? 'Služba: <b></b> · <button type="button" data-goto="1">zmeniť</button>' : '';
            if (service) $('b', picked).textContent = `${service.name}, od ${service.price} €`;
            if (!dateInput.value) { timesTitle.textContent = 'Vyber deň v kalendári'; timesNote.textContent = ''; slotsGrid.innerHTML = ''; }
            loadMonth();
        };

        const renderSummary = () => {
            const service = services[serviceId()] || {};
            const data = new FormData(bookForm);
            const rows = [
                ['Služba', `${service.name} (od ${service.price} €)`],
                ['Termín', `${selectedSlot?.label || dateInput.value}, ${timeInput.value} – ${selectedSlot?.end || ''}`],
                ['Meno', data.get('name')],
                ['Telefón', data.get('phone')],
                ['E-mail', data.get('email')],
                ['Motorka', data.get('bike')],
                ['Vyzdvihnutie', data.get('pickup') ? `Áno — ${data.get('pickup_address')}` : ''],
                ['Poznámka', data.get('note')],
            ].filter(([, value]) => value);
            $('#book-summary').innerHTML = rows.map(([label, value]) => `<div><dt>${escapeHtml(label)}</dt><dd>${escapeHtml(value)}</dd></div>`).join('');
            setMessage('');
        };

        bookForm.addEventListener('click', (event) => {
            if (event.target.closest('[data-next]') && validateStep()) showStep(step + 1);
            if (event.target.closest('[data-prev]')) showStep(step - 1);
            const monthButton = event.target.closest('[data-month]');
            if (monthButton) { autoPick = false; changeMonth(Number(monthButton.dataset.month)); }
            const goto = event.target.closest('[data-goto]');
            if (goto) showStep(Number(goto.dataset.goto));
        });
        stepItems.forEach((item) => item.addEventListener('click', () => { if (step !== 'done' && Number(item.dataset.step) < step) showStep(Number(item.dataset.step)); }));
        $$('input[name="service"]', bookForm).forEach((radio) => radio.addEventListener('change', () => { resetDate(); window.setTimeout(() => showStep(2), 180); }));

        // Pickup at the customer's address: the address field appears and becomes required.
        const pickup = $('[data-pickup]', bookForm);
        const pickupAddress = $('[data-pickup-address]', bookForm);
        pickup?.addEventListener('change', () => {
            pickupAddress.hidden = !pickup.checked;
            $('input', pickupAddress).required = pickup.checked;
            if (pickup.checked) $('input', pickupAddress).focus();
        });

        bookForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (step !== 4) return;
            const consent = $('input[name="consent"]', bookForm);
            if (!consent.reportValidity()) return;
            const submit = $('button[type="submit"]', bookForm);
            submit.disabled = true;
            submit.textContent = 'Odosielam…';
            setMessage('');
            try {
                const response = await fetch('api.php?action=book', { method: 'POST', body: new FormData(bookForm), headers: { Accept: 'application/json' } });
                const result = await response.json();
                if (!result.ok) {
                    setMessage(result.message || 'Rezerváciu sa nepodarilo odoslať.', 'error');
                    if (response.status === 409) { resetDate(); autoPick = false; toast(result.message); showStep(2); }
                    return;
                }
                const booking = result.booking;
                $('#book-done-text').textContent = `Rezervácia ${booking.id} na ${booking.label || booking.date} o ${booking.time} je zapísaná. Súhrn sme poslali na ${booking.email}. Termín ti čoskoro potvrdíme.`;
                showStep('done');
            } catch (error) {
                setMessage('Rezerváciu sa nepodarilo odoslať. Skús to znova alebo nám zavolaj.', 'error');
            } finally {
                submit.disabled = false;
                submit.textContent = 'Odoslať rezerváciu';
            }
        });

        showStep(Number(bookForm.dataset.startStep) || 1);
    }

    // Contact form.
    const contactForm = $('#contact-form');
    const contactMessage = $('#contact-message');
    if (contactForm) {
        // Photo picker: keeps chosen files in an array so they can be previewed and removed one by one.
        const photoInput = $('[data-photo-input]', contactForm);
        const photoPreviews = $('[data-photo-previews]', contactForm);
        const photoDrop = $('.ct-drop', contactForm);
        const maxPhotos = 4;
        let photos = [];
        const renderPhotos = () => {
            photoPreviews.querySelectorAll('img').forEach((img) => URL.revokeObjectURL(img.src));
            photoPreviews.innerHTML = photos.map((file, index) => `<li><img src="${URL.createObjectURL(file)}" alt=""><button type="button" data-photo-remove="${index}" aria-label="Odstrániť fotku">×</button></li>`).join('');
            photoDrop.hidden = photos.length >= maxPhotos;
        };
        const addPhotos = (files) => {
            const allowed = /^image\/(jpeg|png|webp|heic|heif)$/;
            let problem = '';
            [...files].forEach((file) => {
                if (!allowed.test(file.type) && !/\.(heic|heif)$/i.test(file.name)) problem = 'Nahrať môžeš iba fotky vo formáte JPG, PNG, WEBP alebo HEIC.';
                else if (file.size > 8 * 1024 * 1024) problem = `Fotka ${file.name} má viac ako 8 MB.`;
                else if (photos.length >= maxPhotos) problem = `Nahrať môžeš najviac ${maxPhotos} fotky.`;
                else photos.push(file);
            });
            contactMessage.textContent = problem;
            contactMessage.className = problem ? 'form-message is-error' : 'form-message';
            renderPhotos();
        };
        photoInput?.addEventListener('change', () => { addPhotos(photoInput.files); photoInput.value = ''; });
        photoPreviews?.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-photo-remove]');
            if (!remove) return;
            photos.splice(Number(remove.dataset.photoRemove), 1);
            renderPhotos();
        });
        ['dragenter', 'dragover'].forEach((type) => photoDrop?.addEventListener(type, (event) => { event.preventDefault(); photoDrop.classList.add('is-over'); }));
        ['dragleave', 'drop'].forEach((type) => photoDrop?.addEventListener(type, () => photoDrop.classList.remove('is-over')));
        photoDrop?.addEventListener('drop', (event) => { event.preventDefault(); addPhotos(event.dataTransfer.files); });

        contactForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!contactForm.checkValidity()) {
                const invalid = $(':invalid', contactForm);
                contactMessage.textContent = invalid?.name === 'consent' ? 'Na odoslanie potrebujeme tvoj súhlas so spracovaním údajov.' : 'Vyplň prosím meno, platný e-mail a správu.';
                contactMessage.className = 'form-message is-error';
                invalid?.focus();
                return;
            }
            const submit = $('button[type="submit"]', contactForm);
            submit.disabled = true;
            submit.innerHTML = 'Odosielam…';
            contactMessage.textContent = '';
            contactMessage.className = 'form-message';
            try {
                const body = new FormData(contactForm);
                photos.forEach((file) => body.append('photos[]', file));
                const response = await fetch('api.php?action=contact', { method: 'POST', body, headers: { Accept: 'application/json' } });
                const result = await response.json().catch(() => ({ ok: false, message: 'Správu sa nepodarilo odoslať. Skús menšie fotky alebo nám zavolaj.' }));
                if (!result.ok) throw new Error(result.message || 'Správu sa nepodarilo odoslať.');
                const email = body.get('email');
                contactForm.reset();
                photos = [];
                if (photoPreviews) renderPhotos();
                const done = $('#contact-done');
                if (done) {
                    $('#contact-done-text').textContent = `Potvrdenie sme ti poslali na ${email}. Zvyčajne odpovedáme do 24 hodín.`;
                    $('#contact-form-wrap').hidden = true;
                    done.hidden = false;
                    done.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    contactMessage.textContent = 'Ďakujeme. Ozveme sa ti čo najskôr.';
                    contactMessage.className = 'form-message is-success';
                }
            } catch (error) {
                contactMessage.textContent = error.message;
                contactMessage.className = 'form-message is-error';
            } finally {
                submit.disabled = false;
                submit.innerHTML = 'Odoslať správu';
            }
        });
        $('#contact-again')?.addEventListener('click', () => {
            $('#contact-done').hidden = true;
            $('#contact-form-wrap').hidden = false;
            $('input[name="name"]', contactForm)?.focus();
        });
    }

    // Product add-to-cart controls on shop and product detail pages.
    const addToCart = (button) => {
        const id = button.dataset.productId;
        const quantityTarget = button.dataset.quantityTarget ? $(`#${button.dataset.quantityTarget}`) : null;
        const quantity = Math.max(1, Math.min(10, Number(quantityTarget?.textContent || button.dataset.quantity || 1)));
        const existing = cart.find((item) => item.id === id);
        if (existing) existing.quantity = Math.min(10, existing.quantity + quantity);
        else cart.push({ id, name: button.dataset.productName, price: Number(button.dataset.productPrice), image: window.MOTOCARE?.products?.[id]?.image || '', quantity });
        saveCart(); toast(`${button.dataset.productName} je v košíku.`);
    };
    $$('.add-product').forEach((button) => button.addEventListener('click', () => addToCart(button)));
    $$('[data-quantity]').forEach((button) => button.addEventListener('click', () => {
        const target = $('#product-quantity'); if (!target) return;
        const value = Math.max(1, Math.min(10, Number(target.textContent) + (button.dataset.quantity === 'plus' ? 1 : -1))); target.textContent = value;
    }));

    // Cart page: item cards with quantity controls and a summary.
    const cartPage = $('#cart-page-items');
    const removeIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14M10 7V5h4v2M7 7l1 12h8l1-12"></path></svg>';
    const renderCartPage = () => {
        if (!cartPage) return;
        const empty = !cart.length;
        $('#cart-layout').hidden = empty;
        $('#cart-empty').hidden = !empty;
        const total = $('#cart-page-total');
        const count = $('#cart-page-count');
        if (total) total.textContent = `${cartAmount()} €`;
        if (count) count.textContent = `${cartQuantity()} ks`;
        if (empty) { cartPage.innerHTML = ''; return; }
        cartPage.innerHTML = cart.map((item) => {
            const product = window.MOTOCARE?.products?.[item.id] || {};
            const name = item.name || product.name || 'Produkt';
            const image = item.image || product.image || '';
            const url = `produkt.php?slug=${encodeURIComponent(item.id)}`;
            const id = escapeHtml(item.id);
            const meta = [`${item.price} € / ks`, product.volume ? `${product.volume} ml` : ''].filter(Boolean).join(' · ');
            return `<article class="cart-item">
                <a class="cart-item-media" href="${url}">${image ? `<img src="${escapeHtml(image)}" alt="">` : ''}</a>
                <div class="cart-item-info">${product.brand ? `<small>${escapeHtml(product.brand)}</small>` : ''}<a class="cart-item-name" href="${url}">${escapeHtml(name)}</a><span>${escapeHtml(meta)}</span></div>
                <div class="cart-item-qty"><button type="button" data-cart-action="minus" data-id="${id}" aria-label="Odobrať kus">−</button><b>${item.quantity}</b><button type="button" data-cart-action="plus" data-id="${id}" aria-label="Pridať kus"${item.quantity >= 10 ? ' disabled' : ''}>+</button></div>
                <strong class="cart-item-total">${item.price * item.quantity} €</strong>
                <button class="cart-item-remove" type="button" data-cart-action="remove" data-id="${id}" aria-label="Odstrániť ${escapeHtml(name)} z košíka">${removeIcon}</button>
            </article>`;
        }).join('');
        $$('[data-cart-action]', cartPage).forEach((button) => button.addEventListener('click', () => {
            const item = cart.find((entry) => entry.id === button.dataset.id);
            if (!item) return;
            const action = button.dataset.cartAction;
            if (action === 'plus') item.quantity = Math.min(10, item.quantity + 1);
            if (action === 'minus') item.quantity -= 1;
            if (action === 'remove' || item.quantity <= 0) cart = cart.filter((entry) => entry.id !== item.id);
            saveCart();
            renderCartPage();
        }));
    };
    updateCartCount(); renderCartPage();

    // Checkout page.
    const checkoutForm = $('#checkout-form');
    const checkoutItems = $('#checkout-items');
    const checkoutTotal = $('#checkout-total');
    const checkoutMessage = $('#checkout-message');
    const renderCheckout = () => {
        if (!checkoutItems) return;
        checkoutItems.innerHTML = cart.length ? cart.map((item) => {
            const product = window.MOTOCARE?.products?.[item.id] || {};
            const name = item.name || product.name || 'Produkt';
            const image = item.image || product.image || '';
            const imageMarkup = image ? `<img src="${escapeHtml(image)}" alt="">` : '';
            return `<a class="co-item" href="produkt.php?slug=${encodeURIComponent(item.id)}"><span class="co-item-image">${imageMarkup}<b>${item.quantity}</b></span><span class="co-item-copy"><strong>${escapeHtml(name)}</strong><small>${item.quantity} × ${item.price} €</small></span><span class="co-item-price">${item.price * item.quantity} €</span></a>`;
        }).join('') : '<div class="co-empty">Košík je prázdny. <a href="shop.php">Prejsť k produktom</a></div>';
        if (checkoutTotal) checkoutTotal.textContent = `${cartAmount()} €`;
    };
    if (checkoutForm) {
        renderCheckout();
        const companyToggle = $('[data-company-toggle]', checkoutForm);
        companyToggle?.addEventListener('change', () => {
            $$('[data-company-field]', checkoutForm).forEach((field) => {
                field.hidden = !companyToggle.checked;
                if (!companyToggle.checked) $('input', field).value = '';
            });
        });
        checkoutForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (!cart.length) { checkoutMessage.textContent = 'Košík je prázdny.'; checkoutMessage.className = 'form-message is-error'; return; }
            const submit = $('button[type="submit"]', checkoutForm);
            submit.disabled = true;
            submit.textContent = 'Odosielam…';
            checkoutMessage.textContent = '';
            try {
                const customer = Object.fromEntries(new FormData(checkoutForm).entries());
                const response = await fetch('api.php?action=shop_order', { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ customer, cart }) });
                const result = await response.json();
                if (!result.ok) throw new Error(result.message || 'Objednávku sa nepodarilo odoslať.');
                $('#checkout-done-text').textContent = `Potvrdenie sme poslali na ${customer.email}. Čoskoro sa ti ozveme kvôli doručeniu.`;
                $('#checkout-done-id').textContent = result.order.id;
                $('#checkout-done-total').textContent = `${result.order.total} €`;
                $('.co')?.classList.add('is-complete');
                checkoutForm.hidden = true;
                $('#checkout-done').hidden = false;
                $('#co-step-details')?.classList.replace('is-active', 'is-done');
                $('#co-step-details span').textContent = '✓';
                $('.co-grid')?.classList.add('is-done');
                $('#co-step-done')?.classList.add('is-active');
                cart = [];
                saveCart();
                $('.co-summary')?.classList.add('is-hidden');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (error) {
                checkoutMessage.textContent = error.message;
                checkoutMessage.className = 'form-message is-error';
            } finally {
                submit.disabled = false;
                submit.textContent = 'Odoslať objednávku na dobierku';
            }
        });
    }
})();
