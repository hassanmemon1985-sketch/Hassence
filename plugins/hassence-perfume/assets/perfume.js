/* Hassence Perfume Mode — small frontend fixes that the theme hardcodes. */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') fn();
        else document.addEventListener('DOMContentLoaded', fn);
    }

    ready(function () {
        document.documentElement.classList.add('hassence-perfume');

        // Perfume search placeholder
        document.querySelectorAll('.search input[type="search"]').forEach(function (input) {
            input.placeholder = 'Search perfumes, oud, attar…';
        });

        // Footer description
        document.querySelectorAll('footer p').forEach(function (p) {
            if (/fragrances,\s*fashion/i.test(p.textContent)) {
                p.textContent = 'Hassence is a perfume house in Karachi, Pakistan. Men’s, women’s and unisex fragrances with Cash on Delivery.';
            }
        });

        // Remove marketplace / Daraz / app links
        document.querySelectorAll(
            '.sellbtn, .sellnav, .sell, .dz-strip, .dz-logo, .get-app, ' +
            'footer a[href*="/daraz"], footer a[href*="/bulk-upload"], ' +
            '.dokan-dashboard, .dokan-seller-listing, .dokan-store'
        ).forEach(function (el) {
            el.style.display = 'none';
        });

        // Remove empty footer paragraphs left behind by hidden links
        document.querySelectorAll('footer p').forEach(function (p) {
            if (!p.textContent.trim() && !p.querySelector('a, button, img')) p.remove();
        });

        // Flash sale countdown. The end time comes from WordPress, so it uses
        // the store timezone rather than the visitor's computer clock.
        var countdown = document.querySelector('[data-hsp-countdown]');
        if (countdown) {
            var h = countdown.querySelector('[data-hsp-h]');
            var m = countdown.querySelector('[data-hsp-m]');
            var s = countdown.querySelector('[data-hsp-s]');
            var end = parseInt(countdown.getAttribute('data-end'), 10) * 1000;

            // Next midnight in Pakistan (UTC+5). This keeps the timer working
            // even when LiteSpeed serves a cached page after the stored time.
            function nextStoreMidnight() {
                var now = new Date();
                var storeNow = new Date(now.getTime() + (now.getTimezoneOffset() + 300) * 60000);
                return Date.UTC(
                    storeNow.getUTCFullYear(),
                    storeNow.getUTCMonth(),
                    storeNow.getUTCDate() + 1
                ) - 5 * 60 * 60 * 1000;
            }

            if (!isFinite(end) || end <= Date.now()) end = nextStoreMidnight();

            function pad(n) {
                return n < 10 ? '0' + n : String(n);
            }

            function tick() {
                var diff = end - Date.now();
                if (diff <= 0) {
                    end = nextStoreMidnight();
                    diff = end - Date.now();
                }
                var total = Math.floor(diff / 1000);
                h.textContent = pad(Math.floor(total / 3600));
                m.textContent = pad(Math.floor((total % 3600) / 60));
                s.textContent = pad(total % 60);
            }

            tick();
            setInterval(tick, 1000);
        }

        // Show only the payment box for the selected gateway. The live checkout
        // can leave every gateway description open after switching methods.
        function syncPaymentBoxes() {
            var methods = document.querySelectorAll('.woocommerce-checkout-payment .wc_payment_method');
            methods.forEach(function (item) {
                var radio = item.querySelector('input[name="payment_method"]');
                var box = item.querySelector('.payment_box');
                if (!radio || !box) return;
                box.style.display = radio.checked ? '' : 'none';
            });
        }
        document.addEventListener('change', function (e) {
            if (e.target && e.target.name === 'payment_method') syncPaymentBoxes();
        });
        if (window.jQuery) {
            window.jQuery(document.body).on('updated_checkout', syncPaymentBoxes);
        }
        syncPaymentBoxes();

        // Smooth scroll for the homepage anchor
        document.querySelectorAll('a[href^="#hsp-"]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                var target = document.querySelector(link.getAttribute('href'));
                if (!target) return;
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    });
})();
