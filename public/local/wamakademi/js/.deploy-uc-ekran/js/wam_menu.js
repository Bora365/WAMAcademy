(function () {
    'use strict';

    if (document.body.dataset.wamMenuReady === '1') {
        return;
    }

    function getConfig() {
        if (typeof window.wamMenuConfig === 'object' && window.wamMenuConfig) {
            return window.wamMenuConfig;
        }
        var root =
            typeof M !== 'undefined' && M.cfg && M.cfg.wwwroot
                ? M.cfg.wwwroot
                : '';
        return {
            wwwroot: root,
            homeurl: root + '/local/wamakademi/index.php',
            active: 'home',
            links: [
                {
                    id: 'home',
                    label: 'Ana Sayfa',
                    url: root + '/local/wamakademi/index.php',
                },
                {
                    id: 'catalog',
                    label: 'Eğitimler',
                    url: root + '/local/wamakademi/catalog.php',
                },
                {
                    id: 'mycourses',
                    label: 'Eğitimlerim',
                    url: root + '/local/wamakademi/mycourses.php',
                },
                {
                    id: 'calendar',
                    label: 'Takvim',
                    url: root + '/local/wamakademi/calendar.php',
                },
                {
                    id: 'profile',
                    label: 'Profil',
                    url: root + '/user/profile.php',
                },
            ],
            settings: [
                {
                    id: 'preferences',
                    label: 'Tercihler',
                    url: root + '/user/preferences.php',
                },
            ],
        };
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function init() {
        var brand = document.querySelector('.navbar .navbar-brand');
        var nav = document.querySelector('.navbar .container-fluid');
        if (!brand || !nav) {
            setTimeout(init, 200);
            return;
        }

        document.body.dataset.wamMenuReady = '1';
        document.body.classList.add('wam-menu-ready');

        var cfg = getConfig();
        var brandHtml = brand.innerHTML;

        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'navbar-brand wam-menu-toggle aabtn m-0 me-4 p-0';
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-controls', 'wam-side-menu');
        toggle.setAttribute('aria-label', 'Menüyü aç');
        toggle.innerHTML = brandHtml;

        brand.replaceWith(toggle);

        var toggler = document.querySelector('.navbar-toggler');
        if (toggler) {
            toggler.classList.add('wam-hide-bootstrap-toggler');
            toggler.setAttribute('hidden', 'hidden');
        }

        var overlay = document.createElement('div');
        overlay.className = 'wam-menu-overlay';
        overlay.hidden = true;

        var panel = document.createElement('aside');
        panel.id = 'wam-side-menu';
        panel.className = 'wam-menu-panel';
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-modal', 'true');
        panel.setAttribute('aria-label', 'WAM menü');
        panel.hidden = true;

        var linksHtml = (cfg.links || [])
            .map(function (link) {
                var current =
                    link.id === cfg.active
                        ? ' aria-current="page" class="is-active"'
                        : '';
                return (
                    '<a href="' +
                    escapeHtml(link.url) +
                    '"' +
                    current +
                    '>' +
                    escapeHtml(link.label) +
                    '</a>'
                );
            })
            .join('');

        panel.innerHTML =
            '<div class="wam-menu-panel__head">' +
            '<div class="wam-menu-panel__brand">WAM</div>' +
            '<button type="button" class="wam-menu-close" aria-label="Menüyü kapat">×</button>' +
            '</div>' +
            '<nav class="wam-menu-panel__nav">' +
            linksHtml +
            '</nav>';

        document.body.appendChild(overlay);
        document.body.appendChild(panel);

        var userNav = document.querySelector('#usernavigation');
        var gearWrap = document.createElement('div');
        gearWrap.className = 'wam-gear';
        gearWrap.innerHTML =
            '<button type="button" class="wam-gear__btn" aria-expanded="false" aria-haspopup="true" aria-label="Ayarlar">' +
            '<svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8">' +
            '<circle cx="12" cy="12" r="3"></circle>' +
            '<path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9c.1.7.7 1.2 1.5 1.2H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"></path>' +
            '</svg></button>' +
            '<div class="wam-gear__menu" hidden role="menu"></div>';

        var gearMenu = gearWrap.querySelector('.wam-gear__menu');
        (cfg.settings || []).forEach(function (item) {
            var a = document.createElement('a');
            a.href = item.url;
            a.setAttribute('role', 'menuitem');
            a.textContent = item.label;
            gearMenu.appendChild(a);
        });

        if (userNav) {
            userNav.insertBefore(gearWrap, userNav.firstChild);
        } else {
            nav.appendChild(gearWrap);
        }

        var gearBtn = gearWrap.querySelector('.wam-gear__btn');
        var closeBtn = panel.querySelector('.wam-menu-close');
        var lastFocus = null;

        function openMenu() {
            lastFocus = document.activeElement;
            panel.hidden = false;
            overlay.hidden = false;
            document.body.classList.add('wam-menu-open');
            toggle.setAttribute('aria-expanded', 'true');
            toggle.setAttribute('aria-label', 'Menüyü kapat');
            closeBtn.focus();
        }

        function closeMenu() {
            panel.hidden = true;
            overlay.hidden = true;
            document.body.classList.remove('wam-menu-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Menüyü aç');
            if (lastFocus && typeof lastFocus.focus === 'function') {
                lastFocus.focus();
            } else {
                toggle.focus();
            }
        }

        function toggleMenu() {
            if (panel.hidden) {
                openMenu();
            } else {
                closeMenu();
            }
        }

        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            toggleMenu();
        });

        closeBtn.addEventListener('click', closeMenu);
        overlay.addEventListener('click', closeMenu);

        panel.addEventListener('click', function (event) {
            var link = event.target.closest('a');
            if (link) {
                closeMenu();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                if (!panel.hidden) {
                    closeMenu();
                }
                if (!gearMenu.hidden) {
                    gearMenu.hidden = true;
                    gearBtn.setAttribute('aria-expanded', 'false');
                }
            }
        });

        gearBtn.addEventListener('click', function (event) {
            event.stopPropagation();
            var open = gearMenu.hidden;
            gearMenu.hidden = !open;
            gearBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        document.addEventListener('click', function (event) {
            if (!gearWrap.contains(event.target)) {
                gearMenu.hidden = true;
                gearBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
