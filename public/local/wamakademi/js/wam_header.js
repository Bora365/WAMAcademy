/**
 * WAM ortak üst çubuk: aşağı kaydırınca gizle, yukarı kaydırınca tekrar göster.
 *
 * Davranış WAM için tasarlanmıştır; başka bir sitenin davranışı ölçülüp kopyalanmamıştır.
 * Kurallar:
 *  - Sayfa başında ve üst çubuk yüksekliği geçilene kadar görünür.
 *  - Yön değişimi için eşik kullanılır (küçük kaydırmalarda titreme yok).
 *  - Açık menü, üst çubuk içinde klavye odağı, etkin arama, açık mobil panel veya modal varken gizlenmez.
 *  - Üst çubuğa klavye odağı gelirse hemen görünür.
 *  - prefers-reduced-motion: reduce iken üst çubuk her zaman görünür kalır.
 *  - JavaScript çalışmazsa üst çubuk sabit ve görünür kalır (CSS yalnızca .wam-header-hidden sınıfına tepki verir).
 */
(function () {
    'use strict';

    var nav = document.querySelector('nav.navbar.fixed-top');
    if (!nav || !document.body || !document.body.classList.contains('wam-ui')) {
        return;
    }

    var HIDE_THRESHOLD = 12; // Aşağı yönde birikmesi gereken px.
    var SHOW_THRESHOLD = 8; // Yukarı yönde birikmesi gereken px.
    var CLASS_HIDDEN = 'wam-header-hidden';

    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    var small = window.matchMedia('(max-width: 991.98px)');
    var lastY = window.pageYOffset || 0;
    var accumulated = 0;
    var hidden = false;
    var ticking = false;

    function headerHeight() {
        return nav.offsetHeight || 64;
    }

    function setHidden(value) {
        if (value === hidden) {
            return;
        }
        hidden = value;
        document.body.classList.toggle(CLASS_HIDDEN, value);
    }

    /**
     * Üst çubuğun görünür kalması gereken bir etkileşim var mı?
     *
     * @return {boolean}
     */
    function mustStayVisible() {
        var active = document.activeElement;
        if (active && active !== document.body) {
            if (nav.contains(active)) {
                return true;
            }
            if (active.matches('input[type="search"], [role="search"] input, input[name="query"]')) {
                return true;
            }
        }
        if (nav.querySelector('[aria-expanded="true"], .dropdown-menu.show, .popover-region:not(.collapsed)')) {
            return true;
        }
        if (document.body.classList.contains('modal-open')) {
            return true;
        }
        // Dar ekranlarda açık çekmece (yönetim menüsü, birincil menü) üst çubuğu görünür tutar.
        if (small.matches && document.querySelector('[data-region="fixed-drawer"].show')) {
            return true;
        }
        return false;
    }

    function update() {
        ticking = false;
        var y = Math.max(window.pageYOffset || 0, 0);
        var delta = y - lastY;
        lastY = y;

        if (reduced.matches || mustStayVisible() || y <= headerHeight()) {
            accumulated = 0;
            setHidden(false);
            return;
        }
        if (delta === 0) {
            return;
        }
        // Yön değiştiyse biriktirmeyi sıfırla.
        if ((delta > 0) !== (accumulated > 0)) {
            accumulated = 0;
        }
        accumulated += delta;

        if (accumulated >= HIDE_THRESHOLD) {
            setHidden(true);
            accumulated = 0;
        } else if (accumulated <= -SHOW_THRESHOLD) {
            setHidden(false);
            accumulated = 0;
        }
    }

    function onScroll() {
        if (!ticking) {
            ticking = true;
            window.requestAnimationFrame(update);
        }
    }

    window.addEventListener('scroll', onScroll, {passive: true});

    // Klavye odağı üst çubuğa gelince hemen göster.
    nav.addEventListener('focusin', function () {
        accumulated = 0;
        setHidden(false);
    });

    // Başka bir WAM bileşeni (ör. yan panel kapanınca odağı hamburger düğmesine döndürmek) üst çubuğu görünür istiyorsa.
    document.addEventListener('wam:header-show', function () {
        accumulated = 0;
        setHidden(false);
    });

    // Menü açıldığında / çekmece açıldığında hemen göster.
    document.addEventListener('shown.bs.dropdown', function () {
        setHidden(false);
    });
    document.addEventListener('click', function (e) {
        if (e.target && e.target.closest && e.target.closest('[data-toggler="drawers"]')) {
            setHidden(false);
        }
    });

    var onPreferenceChange = function () {
        if (reduced.matches) {
            setHidden(false);
        }
    };
    if (reduced.addEventListener) {
        reduced.addEventListener('change', onPreferenceChange);
    } else if (reduced.addListener) {
        reduced.addListener(onPreferenceChange);
    }

    window.addEventListener('resize', function () {
        accumulated = 0;
        update();
    });

    update();
})();
