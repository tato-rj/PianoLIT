(function () {
    'use strict';
    var root = document.querySelector('[data-storefront]');
    if (!root) return;

    root.querySelectorAll('[data-shop-category]').forEach(function (section) {
        var track = section.querySelector('[data-category-track]');
        var controls = section.querySelector('[data-category-controls]');
        var previous = section.querySelector('[data-category-step="-1"]');
        var next = section.querySelector('[data-category-step="1"]');
        var frame = null;
        function maximum() { return Math.max(0, track.scrollWidth - track.clientWidth); }
        function sync() {
            previous.disabled = track.scrollLeft <= 2;
            next.disabled = track.scrollLeft >= maximum() - 2;
        }
        function queueSync() {
            if (frame !== null) return;
            frame = requestAnimationFrame(function () { frame = null; sync(); });
        }
        function move(direction) {
            var gap = parseFloat(getComputedStyle(track).columnGap) || 0;
            var left = Math.max(0, Math.min(maximum(), track.scrollLeft + direction * (track.clientWidth + gap)));
            track.scrollTo({left: left, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'});
        }
        previous.addEventListener('click', function () { move(-1); });
        next.addEventListener('click', function () { move(1); });
        track.addEventListener('scroll', queueSync, {passive: true});
        track.addEventListener('keydown', function (event) {
            if (event.target !== track) return;
            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                event.preventDefault();
                move(event.key === 'ArrowLeft' ? -1 : 1);
            } else if (event.key === 'Home' || event.key === 'End') {
                event.preventDefault();
                track.scrollTo({left: event.key === 'Home' ? 0 : maximum(), behavior: 'auto'});
            }
        });
        if (window.ResizeObserver) new ResizeObserver(queueSync).observe(track);
        else window.addEventListener('resize', queueSync);
        controls.hidden = false;
        sync();
    });

    root.querySelectorAll('[data-quantity]').forEach(function (control) {
        var input = control.querySelector('input');
        var buttons = control.querySelectorAll('[data-quantity-change]');
        function sync() {
            buttons.forEach(function (button) {
                button.disabled = Number(button.dataset.quantityChange) < 0 ? Number(input.value) <= 1 : Number(input.value) >= 10;
            });
        }
        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                input.value = Math.max(1, Math.min(10, (Number(input.value) || 1) + Number(button.dataset.quantityChange)));
                sync();
                if (control.closest('[data-bag-quantity]')) control.closest('form').requestSubmit();
            });
        });
        input.addEventListener('input', sync);
        var bagForm = control.closest('[data-bag-quantity]');
        if (bagForm) {
            bagForm.querySelector('[data-quantity-save]').hidden = true;
            input.addEventListener('change', function () { bagForm.requestSubmit(); });
        }
        sync();
    });

    root.querySelectorAll('[data-shop-gallery]').forEach(function (gallery) {
        var slides = Array.from(gallery.querySelectorAll('[data-gallery-slide]'));
        var count = root.querySelector('[data-gallery-count]');
        var current = 0;
        function move(step) {
            if (!slides.length) return;
            current = (current + step + slides.length) % slides.length;
            slides.forEach(function (slide, index) { slide.hidden = index !== current; });
            if (count) count.textContent = (current + 1) + ' / ' + slides.length;
        }
        gallery.querySelectorAll('[data-gallery-step]').forEach(function (button) {
            button.addEventListener('click', function () { move(Number(button.dataset.galleryStep)); });
        });
        gallery.addEventListener('keydown', function (event) {
            if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
            event.preventDefault();
            move(event.key === 'ArrowLeft' ? -1 : 1);
        });
    });

    function renderBagButton(form) {
        var selected = form.querySelector('[name="variant_id"]').selectedOptions[0];
        var quantity = selected ? Number(selected.dataset.inBag) || 0 : 0;
        form.querySelector('[data-bag-label]').textContent = quantity ? 'In bag (' + quantity + ') · Add more' : 'Add to bag';
    }

    root.querySelectorAll('[data-product-options]').forEach(function (form) {
        var select = form.querySelector('[name="variant_id"]');
        var variants = Array.from(select.options);
        var colors = form.querySelector('[data-option-group="color"]');
        var sizes = form.querySelector('[data-option-group="size"]');
        var selected = select.selectedOptions[0];
        function render() {
            select.value = selected.value;
            renderBagButton(form);
            var price = root.querySelector('[data-product-price]');
            if (price) price.textContent = selected.dataset.price;
            [colors, sizes].forEach(function (group) {
                var key = group.dataset.optionGroup;
                group.querySelectorAll('[data-option-value]').forEach(function (button) {
                    button.setAttribute('aria-pressed', String(button.dataset.optionValue === selected.dataset[key]));
                    button.disabled = key === 'size' && !variants.some(function (variant) { return variant.dataset.color === selected.dataset.color && variant.dataset.size === button.dataset.optionValue; });
                });
            });
        }
        [colors, sizes].forEach(function (group) {
            group.querySelectorAll('[data-option-value]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var key = group.dataset.optionGroup;
                    var matches = variants.filter(function (variant) { return variant.dataset[key] === button.dataset.optionValue; });
                    var next = matches.find(function (variant) { return key === 'color' ? variant.dataset.size === selected.dataset.size : variant.dataset.color === selected.dataset.color; });
                    selected = next || matches[0];
                    render();
                });
            });
        });
        select.addEventListener('change', function () {
            selected = select.selectedOptions[0];
            if (selected) render();
        });
        if (selected) {
            form.querySelector('[data-variant-fallback]').hidden = true;
            form.querySelector('[data-option-pickers]').hidden = false;
            render();
        }
    });

    root.querySelectorAll('[data-add-to-bag]').forEach(function (form) {
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (root.dataset.pending === 'true') return;
            if (!form.reportValidity()) return;
            var button = form.querySelector('[type="submit"]');
            var error = form.querySelector('.shop-form-error');
            root.dataset.pending = 'true';
            var submitters = root.querySelectorAll('[data-add-to-bag] [type="submit"]');
            submitters.forEach(function (submitter) { submitter.disabled = true; });
            button.setAttribute('aria-busy', 'true');
            error.hidden = true;
            try {
                var response = await fetch(form.action, {method: 'POST', credentials: 'same-origin', body: new FormData(form), headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}});
                var payload = await response.json().catch(function () { return {}; });
                if (!response.ok) {
                    var messages = Object.values(payload.errors || {}).flat();
                    throw new Error(messages[0] || (response.status === 419 ? 'Please reload this page and try again.' : 'Your bag could not be updated. Please try again.'));
                }
                root.querySelectorAll('[data-bag-count]').forEach(function (count) { count.textContent = payload.count; });
                if (payload.item) {
                    root.querySelectorAll('[data-product-options]').forEach(function (productForm) {
                        if (productForm.querySelector('[name="product_id"]').value !== String(payload.item.product_id)) return;
                        Array.from(productForm.querySelector('[name="variant_id"]').options).forEach(function (option) {
                            if (option.value === String(payload.item.variant_id)) option.dataset.inBag = payload.item.quantity;
                        });
                        renderBagButton(productForm);
                    });
                }
                var notice = root.querySelector('[data-shop-notice]');
                notice.querySelector('[data-shop-notice-text]').textContent = payload.message;
                notice.hidden = false;
                clearTimeout(root.shopNoticeTimer);
                root.shopNoticeTimer = setTimeout(function () { notice.hidden = true; }, 7000);
            } catch (failure) {
                error.textContent = failure.message || 'Your bag could not be updated. Please try again.';
                error.hidden = false;
            } finally {
                root.dataset.pending = 'false';
                submitters.forEach(function (submitter) { submitter.disabled = false; });
                button.removeAttribute('aria-busy');
            }
        });
    });
}());
