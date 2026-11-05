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
