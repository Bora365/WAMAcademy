/**
 * WAM Site yönetimi: küçük, işlevsel iyileştirmeler.
 *
 * Yalnızca sunum/gezinme ile ilgilidir; ayar, yetki, sesskey veya form gönderimini değiştirmez.
 *  1. Sayfa H1'i ile aynı metinli ilk içerik başlığını gizler (çift başlık).
 *  2. Dar ekranda yönetim menüsünden bir sekme seçilince çekmeceyi kapatır.
 *  3. Dar ekranda açık yönetim menüsü Escape ile kapanır.
 */
(function () {
    'use strict';

    if (!document.body || !document.body.classList.contains('wam-admin')) {
        return;
    }

    function normalise(text) {
        return (text || '').replace(/\s+/g, ' ').trim().toLocaleLowerCase('tr');
    }

    function hideDuplicateHeading() {
        var h1 = document.querySelector('#page-header h1, .page-header-headings h1');
        var main = document.getElementById('region-main');
        if (!h1 || !main) {
            return;
        }
        var heading = main.querySelector('h2, h3');
        if (!heading || heading.closest('.wam-adm-group, .wam-adm-result')) {
            return;
        }
        var headingText = normalise(heading.textContent);
        var titleText = normalise(h1.textContent);
        // Aynı metin ya da "Kategori: <başlık>" gibi başlığı yineleyen kısa önek/sonek.
        var duplicate = headingText === titleText
            || (headingText.indexOf(titleText) !== -1 && headingText.length <= titleText.length + 24
                && !heading.closest('form, fieldset, table, .generalbox'));
        if (duplicate) {
            heading.setAttribute('hidden', 'hidden');
            heading.classList.add('wam-adm-dup-title');
        }
    }

    function closeDrawerOnSmallScreens() {
        var nav = document.querySelector('.wam-adm-nav');
        if (!nav) {
            return;
        }
        nav.addEventListener('shown.bs.tab', function () {
            if (!window.matchMedia('(max-width: 991.98px)').matches) {
                return;
            }
            var close = document.querySelector(
                '[data-action="closedrawer"][data-target="theme_boost-drawers-courseindex"]'
            );
            if (close) {
                close.click();
            }
            window.scrollTo({top: 0});
        });
    }

    /**
     * Dar ekranda açık yönetim menüsü Escape ile kapanır; odak açma düğmesine döner
     * (Boost çekmece kodu kapatırken odağı açma düğmesine taşır).
     */
    function closeDrawerOnEscape() {
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape' || e.defaultPrevented) {
                return;
            }
            if (!window.matchMedia('(max-width: 991.98px)').matches) {
                return;
            }
            var drawer = document.getElementById('theme_boost-drawers-courseindex');
            if (!drawer || !drawer.classList.contains('show')) {
                return;
            }
            var close = drawer.querySelector('[data-action="closedrawer"]');
            if (close) {
                e.preventDefault();
                close.click();
            }
        });
    }

    hideDuplicateHeading();
    closeDrawerOnSmallScreens();
    closeDrawerOnEscape();
})();
