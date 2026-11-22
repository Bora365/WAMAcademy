/**
 * Sekme / seçici gibi görünen ama Moodle’da ayrı sayfaya giden alanlar için
 * yumuşak geçiş. AJAX içerik değiştirmez; URL, form, AMD ve yetkiler aynı kalır.
 *
 * Kapsam:
 * - .secondary-navigation sekmeleri (Eğitim / Katılımcılar / Notlar…)
 * - Klasik tabtree / nav-tabs linkleri (data-bs-toggle=tab olmayan)
 * - Tertiary select_menu (Notlar Görüntüle, katılımcılar seçici, taşan ayarlar)
 * - View-as / benzeri gizli input URL seçicileri
 * - Admin yan liste linkleri (gerçek Bootstrap tab olmayanlar)
 */
(function () {
    'use strict';

    if (document.body.dataset.wamSoftNavReady === '1') {
        return;
    }
    document.body.dataset.wamSoftNavReady = '1';

    function sameOrigin(url) {
        try {
            return new URL(url, window.location.href).origin === window.location.origin;
        } catch (e) {
            return false;
        }
    }

    function isNavigableUrl(href) {
        if (!href || href.charAt(0) === '#') {
            return false;
        }
        if (href.indexOf('javascript:') === 0) {
            return false;
        }
        return sameOrigin(href);
    }

    function normalizeHref(href) {
        try {
            return new URL(href, window.location.href).href;
        } catch (e) {
            return href;
        }
    }

    function softNavigate(href) {
        var next = normalizeHref(href);
        if (!isNavigableUrl(next) || next === window.location.href) {
            return;
        }
        // Aynı belge startViewTransition + assign, MPA geçişini bozup flaş yapıyordu.
        // Çapraz belge geçişi @view-transition { navigation: auto } ile yapılır.
        window.location.assign(next);
    }

    function isSoftNavAnchor(anchor) {
        if (!anchor || !anchor.getAttribute('href')) {
            return false;
        }
        if (anchor.target && anchor.target !== '_self') {
            return false;
        }
        if (anchor.hasAttribute('download')) {
            return false;
        }
        // Gerçek Bootstrap / hash sekmeleri: reload yok, dokunma.
        if (anchor.getAttribute('data-bs-toggle') === 'tab' ||
                anchor.getAttribute('data-toggle') === 'tab') {
            return false;
        }
        if (!isNavigableUrl(anchor.getAttribute('href'))) {
            return false;
        }

        if (anchor.closest('.secondary-navigation')) {
            return true;
        }
        // Klasik Moodle tabtree (görünüş tab, gerçek link).
        if (anchor.closest('.tabtree')) {
            return true;
        }
        // Admin kategori şeridi: data-bs-toggle yoksa link.
        if (anchor.classList.contains('wam-adm-nav__link') &&
                anchor.getAttribute('data-bs-toggle') !== 'tab') {
            return true;
        }
        return false;
    }

    function isNavSelectInput(input) {
        if (!input || input.tagName !== 'INPUT') {
            return false;
        }
        if ((input.type || '').toLowerCase() !== 'hidden') {
            return false;
        }
        if (!input.closest('.select-menu, .tertiary-navigation-selector, .view-user-selector, .tertiary-navigation')) {
            return false;
        }
        var value = (input.value || '').trim();
        if (!value) {
            return false;
        }
        // Yalnızca URL benzeri değerler (rapor / katılımcı seçicileri).
        if (value.charAt(0) === '/' || /^https?:\/\//i.test(value)) {
            return isNavigableUrl(value);
        }
        return false;
    }

    function onClick(event) {
        if (event.defaultPrevented || event.button !== 0) {
            return;
        }
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }
        var anchor = event.target.closest('a');
        if (!isSoftNavAnchor(anchor)) {
            return;
        }
        var href = anchor.href;
        if (normalizeHref(href) === window.location.href) {
            event.preventDefault();
            return;
        }
        if (anchor.classList.contains('active') ||
                anchor.getAttribute('aria-current') === 'true' ||
                anchor.getAttribute('aria-selected') === 'true') {
            event.preventDefault();
            return;
        }
        event.preventDefault();
        softNavigate(href);
    }

    function onChange(event) {
        var input = event.target;
        if (!isNavSelectInput(input)) {
            return;
        }
        var next = normalizeHref(input.value);
        if (next === window.location.href) {
            event.stopImmediatePropagation();
            return;
        }
        // Moodle’ın window.location.href atamasını engelle; soft geçiş bizde.
        event.stopImmediatePropagation();
        softNavigate(next);
    }

    document.addEventListener('click', onClick, true);
    document.addEventListener('change', onChange, true);
})();
