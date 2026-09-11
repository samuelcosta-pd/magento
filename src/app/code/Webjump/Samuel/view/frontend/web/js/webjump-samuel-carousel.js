/**
 * Copyright © Webjump. All rights reserved.
 *
 * Carrossel de produtos — Webjump_Samuel
 *
 * Inicializado via x-magento-init no template home.phtml.
 * Recebe: { visibleCount: 4 }
 */
define([], function () {
    'use strict';

    return function (config, element) {
        var baseVisibleCount = config.visibleCount || 4;
        var trackWrapper     = element.querySelector('.carousel-track-wrapper');
        var track            = element.querySelector('.carousel-track');
        var cards            = track ? Array.from(track.querySelectorAll('.product-card')) : [];
        var btnPrev          = element.querySelector('.carousel-prev');
        var btnNext          = element.querySelector('.carousel-next');

        if (!trackWrapper || !track || cards.length === 0 || !btnPrev || !btnNext) {
            return;
        }

        var totalCards   = cards.length;
        var currentPage  = 0;
        var visibleCount = baseVisibleCount;
        var totalPages   = Math.ceil(totalCards / visibleCount);

        function getGap() {
            return window.innerWidth <= 768 ? 12 : 20;
        }

        function getVisibleCount() {
            var width = window.innerWidth;
            if (width < 640) {
                return 1;
            } else if (width < 768) {
                return 2;
            } else if (width < 1024) {
                return Math.min(3, baseVisibleCount);
            }
            return baseVisibleCount;
        }

        function setCardWidths() {
            var gap = getGap();
            var wrapperWidth = trackWrapper.offsetWidth;
            if (!wrapperWidth) {
                return;
            }
            var cardWidth = (wrapperWidth - (visibleCount - 1) * gap) / visibleCount;

            cards.forEach(function (card) {
                card.style.width      = cardWidth + 'px';
                card.style.minWidth   = cardWidth + 'px';
                card.style.maxWidth   = cardWidth + 'px';
                card.style.flex       = '0 0 ' + cardWidth + 'px';
                card.style.boxSizing  = 'border-box';
            });
        }

        function render() {
            var gap = getGap();
            var offset = currentPage * (trackWrapper.offsetWidth + gap);
            track.style.transform = 'translateX(-' + offset + 'px)';

            btnPrev.classList.toggle('hidden', currentPage === 0);
            btnNext.classList.toggle('hidden', currentPage >= totalPages - 1);
        }

        function update() {
            visibleCount = getVisibleCount();
            totalPages   = Math.ceil(totalCards / visibleCount);
            if (currentPage >= totalPages) {
                currentPage = Math.max(0, totalPages - 1);
            }
            setCardWidths();
            render();
        }

        btnPrev.addEventListener('click', function () {
            if (currentPage > 0) {
                currentPage--;
                render();
            }
        });

        btnNext.addEventListener('click', function () {
            if (currentPage < totalPages - 1) {
                currentPage++;
                render();
            }
        });

        var resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(update, 100);
        });

        // Inicialização
        update();
    };
});
