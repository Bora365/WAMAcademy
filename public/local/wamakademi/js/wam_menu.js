(function () {
    'use strict';

    if (document.body.dataset.wamMenuReady === '1') {
        return;
    }

    var MAX_RETRIES = 25;
    var retryCount = 0;

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
            profileurl: root + '/user/profile.php',
            active: '',
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
                    id: 'assignments',
                    label: 'Ödevler',
                    url: root + '/local/wamakademi/assignments.php',
                },
                {
                    id: 'quizzes',
                    label: 'Sınavlar',
                    url: root + '/local/wamakademi/quizzes.php',
                },
            ],
            groups: [
                {
                    id: 'preferences',
                    label: 'Tercihler',
                    items: [
                        {
                            id: 'preferences',
                            label: 'Tercihler',
                            url: root + '/user/preferences.php',
                        },
                    ],
                },
            ],
            settings: [],
            banner: '',
            unreadConversationsTemplate: '{$a} okunmamış konuşma',
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

    /**
     * Profil ikonunun açılır menüsünü sadece Profil / Şu role geç / Çıkış yap öğeleriyle doldurur.
     * Açma-kapama Moodle'un Bootstrap dropdown'ı tarafından yönetilmeye devam eder.
     */
    function buildUserMenu(cfg) {
        // Moodle'un core/usermenu betiği karusel yapısını bekler; yalnızca ana öğenin içeriği değişir.
        var main = document.getElementById('carousel-item-main');
        var items = cfg.usermenu;
        if (!main || !items || !items.length) {
            return;
        }

        var html = '<div class="wam-usermenu">';
        if (cfg.userfullname) {
            html += '<div class="wam-usermenu__name">' + escapeHtml(cfg.userfullname) + '</div>';
        }
        items.forEach(function (item) {
            if (item.id === 'logout') {
                html += '<div class="dropdown-divider" role="separator"></div>';
            }
            html += '<a class="dropdown-item wam-usermenu__item wam-usermenu__item--' +
                escapeHtml(item.id) + '" role="menuitem" href="' + escapeHtml(item.url) + '">' +
                escapeHtml(item.label) + '</a>';
        });
        html += '</div>';

        main.innerHTML = html;

        // Alt menüleri (Dil, Tercihler vb.) kaldır; bunlar zaten çark menüsünde.
        main.parentNode.querySelectorAll('.carousel-item.submenu').forEach(function (el) {
            el.remove();
        });
        document.body.classList.add('wam-usermenu-custom');
        tidyUserMenuToggle();
    }

    /**
     * Üst çubuk profil tetikleyicisini sadeleştirir: rol metnini gizler, dropdown okunu kaldırır.
     * Menü açılması data-bs-toggle="dropdown" ile sürer.
     */
    function tidyUserMenuToggle() {
        var toggle = document.getElementById('user-menu-toggle');
        if (!toggle) {
            return;
        }
        toggle.classList.remove('dropdown-toggle');
        toggle.querySelectorAll('.usertext').forEach(function (el) {
            el.hidden = true;
            el.setAttribute('aria-hidden', 'true');
        });
    }

    function buildGearMenu(cfg, gearMenu) {
        gearMenu.innerHTML = '';

        if (cfg.banner) {
            var banner = document.createElement('div');
            banner.className = 'wam-gear__banner';
            banner.textContent = cfg.banner;
            gearMenu.appendChild(banner);
        }

        var groups = cfg.groups;
        if (!groups || !groups.length) {
            // Eski düz settings listesine düş.
            (cfg.settings || []).forEach(function (item) {
                var a = document.createElement('a');
                a.href = item.url;
                a.setAttribute('role', 'menuitem');
                a.textContent = item.label;
                gearMenu.appendChild(a);
            });
            return;
        }

        groups.forEach(function (group) {
            var hasItems = (group.items && group.items.length) ||
                (group.actions && group.actions.length) ||
                (group.languages && group.languages.length);
            if (!hasItems) {
                return;
            }

            var wrap = document.createElement('div');
            wrap.className = 'wam-gear__group';
            wrap.setAttribute('role', 'group');
            wrap.setAttribute('aria-label', group.label || group.id);
            if (group.id) {
                wrap.setAttribute('data-wam-group', group.id);
            }

            if (group.label) {
                var title = document.createElement('div');
                title.className = 'wam-gear__group-title';
                title.textContent = group.label;
                wrap.appendChild(title);
            }

            (group.items || []).forEach(function (item) {
                var a = document.createElement('a');
                a.href = item.url;
                a.setAttribute('role', 'menuitem');
                a.className = 'wam-gear__item';
                a.textContent = item.label;
                wrap.appendChild(a);
            });

            if (group.languages && group.languages.length) {
                var langTitle = document.createElement('div');
                langTitle.className = 'wam-gear__group-title';
                langTitle.textContent = group.languageLabel || 'Dil';
                wrap.appendChild(langTitle);

                var langList = document.createElement('div');
                langList.className = 'wam-gear__langs';
                group.languages.forEach(function (lang) {
                    var a = document.createElement('a');
                    a.href = lang.url || '#';
                    a.setAttribute('role', 'menuitem');
                    a.textContent = lang.label;
                    if (lang.active) {
                        a.classList.add('is-active');
                        a.setAttribute('aria-current', 'true');
                    }
                    langList.appendChild(a);
                });
                wrap.appendChild(langList);
            }

            if (group.actions && group.actions.length) {
                if ((group.items && group.items.length) || (group.languages && group.languages.length)) {
                    var hr = document.createElement('hr');
                    hr.className = 'wam-gear__divider';
                    wrap.appendChild(hr);
                }
                group.actions.forEach(function (item) {
                    var a = document.createElement('a');
                    a.href = item.url;
                    a.setAttribute('role', 'menuitem');
                    a.className = 'wam-gear__item wam-gear__item--action';
                    a.textContent = item.label;
                    wrap.appendChild(a);
                });
            }

            gearMenu.appendChild(wrap);
        });
    }

    function setupGearKeyboard(gearBtn, gearMenu) {
        gearMenu.addEventListener('keydown', function (event) {
            var items = Array.prototype.slice.call(
                gearMenu.querySelectorAll('[role="menuitem"]')
            );
            if (!items.length) {
                return;
            }
            var index = items.indexOf(document.activeElement);
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                items[(index + 1) % items.length].focus();
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                items[(index - 1 + items.length) % items.length].focus();
            } else if (event.key === 'Home') {
                event.preventDefault();
                items[0].focus();
            } else if (event.key === 'End') {
                event.preventDefault();
                items[items.length - 1].focus();
            } else if (event.key === 'Tab') {
                gearMenu.hidden = true;
                gearBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function protectMessageCountA11y(cfg) {
        var template = cfg.unreadConversationsTemplate || '{$a} okunmamış konuşma';

        function fixContainer(container) {
            if (!container || container.classList.contains('hidden')) {
                return;
            }
            var visible = container.querySelector('[aria-hidden="true"]');
            var sr = container.querySelector('.visually-hidden');
            var raw = (visible ? visible.textContent : container.textContent || '').trim();
            var count = parseInt(raw, 10);
            if (isNaN(count)) {
                return;
            }

            if (!visible || !sr) {
                var id = sr ? sr.id : '';
                if (!id) {
                    var toggle = container.closest('a, button');
                    id = toggle ? toggle.getAttribute('aria-describedby') : '';
                    if (!id) {
                        id = 'unread-messages-count-wam';
                    }
                }
                container.innerHTML =
                    '<span aria-hidden="true">' +
                    escapeHtml(String(count)) +
                    '</span>' +
                    '<span class="visually-hidden" id="' +
                    escapeHtml(id) +
                    '">' +
                    escapeHtml(template.replace('{$a}', String(count))) +
                    '</span>';
                return;
            }

            if (visible.textContent.trim() !== String(count)) {
                visible.textContent = String(count);
            }
            var expected = template.replace('{$a}', String(count));
            if (sr.textContent.trim() !== expected) {
                sr.textContent = expected;
            }
        }

        function scan() {
            document
                .querySelectorAll(
                    '[data-region="popover-region-messages"] [data-region="count-container"]'
                )
                .forEach(fixContainer);
        }

        scan();

        var root = document.querySelector('[data-region="popover-region-messages"]');
        if (!root || typeof MutationObserver === 'undefined') {
            return;
        }
        var observer = new MutationObserver(function () {
            scan();
        });
        observer.observe(root, {
            childList: true,
            subtree: true,
            characterData: true,
        });
    }

    /**
     * Site yönetimi her zaman "Daha fazla" altında kalsın; üst şeritte Kontrol paneli + WAM linkleri.
     * moremenu.js init'ten sonra da çalışır; düğümü manuel taşır.
     */
    function keepSiteAdminInMoreMenu() {
        function move() {
            var menu = document.querySelector('.primary-navigation ul.more-nav');
            if (!menu) {
                return false;
            }
            var moreButton = menu.querySelector('[data-region="morebutton"]');
            var moreDropdown = menu.querySelector('[data-region="moredropdown"]');
            var item = menu.querySelector(':scope > [data-key="siteadminnode"]') ||
                document.querySelector('.primary-navigation [data-key="siteadminnode"]');
            if (!moreButton || !moreDropdown || !item) {
                return false;
            }
            // Zaten Daha fazla içindeyse yalnızca kilidi koru.
            if (item.parentNode === moreDropdown) {
                item.dataset.forceintomoremenu = 'true';
                moreButton.classList.remove('d-none');
                return true;
            }
            item.dataset.forceintomoremenu = 'true';
            var navLink = item.querySelector('a.nav-link, a');
            var dropdownToggle = moreButton.querySelector('[data-bs-toggle="dropdown"]');
            if (navLink) {
                if (navLink.classList.contains('active') && dropdownToggle) {
                    dropdownToggle.classList.add('active');
                    dropdownToggle.setAttribute('tabindex', '0');
                    navLink.setAttribute('tabindex', '-1');
                    navLink.setAttribute('aria-current', 'true');
                }
                navLink.setAttribute('role', 'menuitem');
                navLink.classList.remove('nav-link');
                navLink.classList.add('dropdown-item');
            }
            moreDropdown.appendChild(item);
            moreButton.classList.remove('d-none');
            return true;
        }

        if (move()) {
            return;
        }
        // moremenu henüz hazır değilse kısa süre yeniden dene.
        var attempts = 0;
        var timer = window.setInterval(function () {
            attempts += 1;
            if (move() || attempts > 20) {
                window.clearInterval(timer);
            }
        }, 100);
    }

    /**
     * Öğrenci görünümünde yönetim düğmelerini DOM'dan kaldırır.
     */
    function stripStudentInappropriateNav() {
        var selectors = [
            '.editmode-switch-form',
            '.primary-navigation [data-key="siteadminnode"]',
            '.primary-navigation [data-key="home"]',
            '.primary-navigation [data-key="myhome"]',
            '.primary-navigation [data-key="sitesettingsnode"]',
        ];
        selectors.forEach(function (sel) {
            document.querySelectorAll(sel).forEach(function (el) {
                el.remove();
            });
        });
    }

    function init() {
        var brand = document.querySelector('.navbar .navbar-brand');
        var nav = document.querySelector('.navbar .container-fluid');
        if (!brand || !nav) {
            retryCount += 1;
            if (retryCount > MAX_RETRIES) {
                return;
            }
            setTimeout(init, 200);
            return;
        }

        document.body.dataset.wamMenuReady = '1';
        document.body.classList.add('wam-menu-ready');

        var cfg = getConfig();

        // Logo her zaman WAM ana sayfasına gider (/my/ veya defaulthomepage değil).
        if (cfg.homeurl) {
            brand.setAttribute('href', cfg.homeurl);
        }

        if (cfg.studentView) {
            document.body.classList.add('wam-student-view');
            stripStudentInappropriateNav();
        }

        // Üst Moodle primary nav: site yönetimi yetkisi olanlarda kalsın (Kontrol paneli + Daha fazla).
        // Öğrenci görünümünde veya yönetim bağlantısı yoksa gizlenir.
        if (cfg.studentView || !(cfg.adminLinks && cfg.adminLinks.length)) {
            document.body.classList.add('wam-hide-primary-nav');
        } else {
            keepSiteAdminInMoreMenu();
        }

        // Hamburger düğmesi: WAM logosunun solunda, yan menüyü açar.
        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'wam-menu-toggle wam-hamburger aabtn';
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-controls', 'wam-side-menu');
        toggle.setAttribute('aria-label', 'Ana menü');
        toggle.setAttribute('aria-haspopup', 'dialog');
        toggle.innerHTML =
            '<span class="wam-hamburger__bars" aria-hidden="true">' +
            '<span></span><span></span><span></span>' +
            '</span>';

        nav.insertBefore(toggle, nav.firstChild);

        // Mobildeki Moodle birincil çekmece düğmesi gizlenir; bağlantıları yan menüye de eklenir.
        document.body.classList.add('wam-hide-bootstrap-toggler-active');

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

        function linkMarkup(link) {
            var current =
                cfg.active && link.id === cfg.active
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
        }

        var linksHtml = (cfg.links || []).map(linkMarkup).join('');

        var adminHtml = '';
        if (cfg.adminLinks && cfg.adminLinks.length) {
            adminHtml =
                '<div class="wam-menu-panel__section" role="group" aria-label="Yönetim">' +
                '<div class="wam-menu-panel__section-label">Yönetim</div>' +
                cfg.adminLinks.map(linkMarkup).join('') +
                '</div>';
        }

        var sitename = cfg.sitename || 'WAM';
        var brandHtml = cfg.logourl
            ? '<img class="wam-menu-panel__logo" src="' + escapeHtml(cfg.logourl) +
                '" width="134" height="32" alt="' + escapeHtml(sitename) + '">'
            : '<span class="wam-menu-panel__wordmark">' + escapeHtml(sitename) + '</span>';

        panel.innerHTML =
            '<div class="wam-menu-panel__head">' +
            '<div class="wam-menu-panel__brand">' + brandHtml + '</div>' +
            '<button type="button" class="wam-menu-close" aria-label="Menüyü kapat">' +
            '<svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" ' +
            'stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"></path></svg>' +
            '</button>' +
            '</div>' +
            '<nav class="wam-menu-panel__nav" aria-label="Ana gezinme">' +
            linksHtml +
            adminHtml +
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
            '<div class="wam-gear__menu" hidden role="menu" aria-label="Hesap ayarları"></div>';

        var gearMenu = gearWrap.querySelector('.wam-gear__menu');
        buildGearMenu(cfg, gearMenu);

        if (userNav) {
            userNav.insertBefore(gearWrap, userNav.firstChild);
        } else {
            nav.appendChild(gearWrap);
        }

        buildUserMenu(cfg);
        tidyUserMenuToggle();
        protectMessageCountA11y(cfg);

        var gearBtn = gearWrap.querySelector('.wam-gear__btn');
        setupGearKeyboard(gearBtn, gearMenu);

        var closeBtn = panel.querySelector('.wam-menu-close');
        var lastFocus = null;

        function getFocusable() {
            return Array.prototype.slice.call(
                panel.querySelectorAll(
                    'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
                )
            ).filter(function (el) {
                return !el.hasAttribute('disabled') && el.offsetParent !== null;
            });
        }

        var isOpen = false;
        var hideTimer = null;
        var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        var pushMq = window.matchMedia('(min-width: 1024px)');
        var CLOSE_MS = 220;
        var inertedNodes = [];

        function isPushMode() {
            return pushMq.matches;
        }

        // Overlay (mobil): arka içerik inert. Push (masaüstü): içerik etkileşimli kalır.
        function setBackgroundInert(on) {
            if (on) {
                Array.prototype.forEach.call(document.body.children, function (node) {
                    if (node === panel || node === overlay || /^(SCRIPT|STYLE|LINK|TEMPLATE)$/.test(node.tagName)) {
                        return;
                    }
                    if (!node.hasAttribute('inert')) {
                        node.setAttribute('inert', '');
                        inertedNodes.push(node);
                    }
                });
            } else {
                inertedNodes.forEach(function (node) {
                    node.removeAttribute('inert');
                });
                inertedNodes = [];
            }
        }

        function applyOpenMode() {
            var push = isPushMode();
            document.body.classList.toggle('wam-menu-push', push);
            overlay.hidden = push;
            panel.setAttribute('aria-modal', push ? 'false' : 'true');
            panel.setAttribute('role', push ? 'navigation' : 'dialog');
            toggle.setAttribute('aria-haspopup', push ? 'true' : 'dialog');
            if (push) {
                setBackgroundInert(false);
            } else {
                setBackgroundInert(true);
            }
        }

        function openMenu() {
            if (isOpen) {
                return;
            }
            isOpen = true;
            lastFocus = document.activeElement;
            window.clearTimeout(hideTimer);
            panel.hidden = false;
            // Başlangıç konumu çizilsin, sonra animasyon sınıfı eklensin.
            void panel.offsetWidth;
            document.body.classList.add('wam-menu-open');
            applyOpenMode();
            toggle.setAttribute('aria-expanded', 'true');
            closeBtn.focus({preventScroll: true});
        }

        function closeMenu() {
            if (!isOpen) {
                return;
            }
            isOpen = false;
            document.body.classList.remove('wam-menu-open', 'wam-menu-push');
            toggle.setAttribute('aria-expanded', 'false');
            setBackgroundInert(false);
            panel.setAttribute('aria-modal', 'true');
            panel.setAttribute('role', 'dialog');
            toggle.setAttribute('aria-haspopup', 'dialog');
            // Animasyon bitince panel tamamen gizlenir (klavye/okuyucu erişimi dışı kalır).
            hideTimer = window.setTimeout(function () {
                panel.hidden = true;
                overlay.hidden = true;
            }, reducedMotion.matches ? 0 : CLOSE_MS);
            // Odak, menüyü açan düğmeye döner.
            // Fare ile tıklanan düğme bazı tarayıcılarda odak almaz (activeElement body olur); bu durumda hamburger düğmesi odaklanır.
            var returnTo = lastFocus && lastFocus !== document.body && document.contains(lastFocus) &&
                typeof lastFocus.focus === 'function' ? lastFocus : toggle;
            // Üst çubuk kaydırma nedeniyle gizliyse (görünmez öğe odak alamaz) önce görünür yapılır.
            document.dispatchEvent(new CustomEvent('wam:header-show'));
            returnTo.focus({preventScroll: true});
        }

        function toggleMenu() {
            if (isOpen) {
                closeMenu();
            } else {
                openMenu();
            }
        }

        function closeGear() {
            gearMenu.hidden = true;
            gearBtn.setAttribute('aria-expanded', 'false');
        }

        function openGear() {
            gearMenu.hidden = false;
            gearBtn.setAttribute('aria-expanded', 'true');
            var first = gearMenu.querySelector('[role="menuitem"]');
            if (first) {
                first.focus();
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
            // Yeni sekmede açma (Ctrl/Cmd/Shift/orta tuş) paneli kapatmaz; gerçek URL'ler olduğu gibi çalışır.
            if (link && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey && event.button === 0) {
                closeMenu();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                if (isOpen) {
                    event.preventDefault();
                    closeMenu();
                }
                if (!gearMenu.hidden) {
                    closeGear();
                    gearBtn.focus();
                }
            }

            // Yan menü odak kapanı yalnızca overlay (modal) modunda.
            if (event.key === 'Tab' && isOpen && !isPushMode()) {
                var focusable = getFocusable();
                if (!focusable.length) {
                    return;
                }
                var first = focusable[0];
                var last = focusable[focusable.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        });

        function onPushMqChange() {
            // Genişlik değişince overlay / overflow / inert push veya modal moda göre güncellenir.
            if (isOpen) {
                applyOpenMode();
                if (isPushMode()) {
                    overlay.hidden = true;
                }
            } else {
                document.body.classList.remove('wam-menu-push');
                overlay.hidden = true;
                setBackgroundInert(false);
            }
        }
        if (typeof pushMq.addEventListener === 'function') {
            pushMq.addEventListener('change', onPushMqChange);
        } else if (typeof pushMq.addListener === 'function') {
            pushMq.addListener(onPushMqChange);
        }

        // RF1 F-05: navbar içeriği sığmıyorsa üst WAM linklerini gizle (yan menüde kalır).
        var navbarEl = document.querySelector('.navbar.wam-navbar');
        var compactTimer = null;
        var editSwitchHome = null; // {parent, next} — RF3: compact'ta kurs not/katılımcıda taşı
        function ensureCourseEditHost() {
            var host = document.getElementById('wam-course-edit-host');
            if (host) {
                return host;
            }
            host = document.createElement('div');
            host.id = 'wam-course-edit-host';
            host.className = 'wam-course-edit-host';
            var anchor = document.querySelector('.tertiary-navigation') ||
                document.querySelector('#page-header') ||
                document.querySelector('#region-main');
            if (!anchor) {
                return null;
            }
            if (anchor.classList.contains('tertiary-navigation') || anchor.id === 'page-header') {
                anchor.insertAdjacentElement('afterend', host);
            } else {
                anchor.insertAdjacentElement('afterbegin', host);
            }
            return host;
        }
        function syncCourseEditSwitch() {
            var form = document.querySelector('.editmode-switch-form');
            if (!form) {
                return;
            }
            var onCourseTable = document.body.classList.contains('wam-course-grades') ||
                document.body.classList.contains('wam-course-participants');
            var compact = document.body.classList.contains('wam-nav-compact');
            if (compact && onCourseTable) {
                if (!editSwitchHome) {
                    editSwitchHome = {parent: form.parentNode, next: form.nextSibling};
                }
                var host = ensureCourseEditHost();
                if (host && form.parentNode !== host) {
                    host.appendChild(form);
                }
            } else if (editSwitchHome && editSwitchHome.parent) {
                if (editSwitchHome.next && editSwitchHome.next.parentNode === editSwitchHome.parent) {
                    editSwitchHome.parent.insertBefore(form, editSwitchHome.next);
                } else {
                    editSwitchHome.parent.appendChild(form);
                }
            }
        }
        function updateNavCompact() {
            if (!navbarEl) {
                return;
            }
            var row = navbarEl.querySelector('.container-fluid') || navbarEl;
            // Ölçüm öncesi anahtarı navbar'a geri al (compact taşınmış olabilir).
            document.body.classList.remove('wam-nav-compact');
            if (editSwitchHome && editSwitchHome.parent) {
                var formBack = document.querySelector('.editmode-switch-form');
                if (formBack && formBack.parentNode !== editSwitchHome.parent) {
                    if (editSwitchHome.next && editSwitchHome.next.parentNode === editSwitchHome.parent) {
                        editSwitchHome.parent.insertBefore(formBack, editSwitchHome.next);
                    } else {
                        editSwitchHome.parent.appendChild(formBack);
                    }
                }
            }
            // Ölçümü geniş (non-compact) halde al; sığmazsa compact uygula.
            var overflow = row.scrollWidth - row.clientWidth > 2;
            document.body.classList.toggle('wam-nav-compact', overflow);
            syncCourseEditSwitch();
        }
        function scheduleNavCompact() {
            window.clearTimeout(compactTimer);
            compactTimer = window.setTimeout(updateNavCompact, 80);
        }
        updateNavCompact();
        if (typeof ResizeObserver === 'function' && navbarEl) {
            var navRo = new ResizeObserver(scheduleNavCompact);
            navRo.observe(navbarEl);
            var rowEl = navbarEl.querySelector('.container-fluid');
            if (rowEl) {
                navRo.observe(rowEl);
            }
        }
        window.addEventListener('resize', scheduleNavCompact);

        gearBtn.addEventListener('click', function (event) {
            event.stopPropagation();
            if (gearMenu.hidden) {
                openGear();
            } else {
                closeGear();
            }
        });

        document.addEventListener('click', function (event) {
            if (!gearWrap.contains(event.target)) {
                closeGear();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
