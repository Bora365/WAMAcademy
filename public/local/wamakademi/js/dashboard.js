/**
 * WAM Akademi — ana sayfa özet kartları ile Eğitimlerim filtresi.
 */
(function () {
    'use strict';

    function init(root) {
        if (!root || root.getAttribute('data-wam-dash-ready') === '1') {
            return;
        }
        root.setAttribute('data-wam-dash-ready', '1');

        var filterButtons = Array.prototype.slice.call(
            root.querySelectorAll('[data-wam-dash-filter]')
        );
        var cards = Array.prototype.slice.call(
            root.querySelectorAll('.wam-course-card[data-status]')
        );
        var grid = root.querySelector('[data-wam-dash-grid]');
        var filterEmpty = root.querySelector('[data-wam-dash-empty]');
        var activeFilter = 'all';

        if (!filterButtons.length || !cards.length) {
            return;
        }

        function setFilter(next) {
            // Aynı karta tekrar tıklanınca tümünü göster.
            if (next === activeFilter) {
                activeFilter = 'all';
            } else {
                activeFilter = next;
            }

            filterButtons.forEach(function (button) {
                var value = button.getAttribute('data-wam-dash-filter') || '';
                var pressed = value === activeFilter;
                button.setAttribute('aria-pressed', pressed ? 'true' : 'false');
                button.classList.toggle('is-active', pressed);
            });

            applyFilters();
        }

        function applyFilters() {
            var visibleCount = 0;

            cards.forEach(function (card) {
                var status = card.getAttribute('data-status') || '';
                var show = activeFilter === 'all' || status === activeFilter;
                if (show) {
                    card.removeAttribute('hidden');
                    visibleCount += 1;
                } else {
                    card.setAttribute('hidden', 'hidden');
                }
            });

            if (grid) {
                if (visibleCount === 0) {
                    grid.setAttribute('hidden', 'hidden');
                } else {
                    grid.removeAttribute('hidden');
                }
            }

            if (filterEmpty) {
                if (visibleCount === 0) {
                    filterEmpty.removeAttribute('hidden');
                } else {
                    filterEmpty.setAttribute('hidden', 'hidden');
                }
            }
        }

        filterButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                setFilter(button.getAttribute('data-wam-dash-filter') || 'all');
            });
        });
    }

    function boot() {
        var root = document.querySelector('.wam-dashboard');
        if (root) {
            init(root);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
