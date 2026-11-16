/**
 * WAM katılımcılar tablosu: satır sonundaki tek "İşlemler" menüsü.
 *
 * Menü içeriği sunucuda üretilir (user/classes/table/participants.php, templates/participants_actions.mustache),
 * yani öğeler mevcut yetkilere göre gelir. Bu betik yalnızca açma/kapama, klavye ve konumlandırmayı yönetir.
 *
 *  - Düğme: Enter/Space/ArrowDown menüyü açar, ilk öğeye odaklanır; ArrowUp son öğeye gider.
 *  - Menü: ArrowUp/ArrowDown/Home/End dolaşır, Escape kapatıp odağı düğmeye döndürür, Tab menüyü kapatır.
 *  - Konum: position:fixed; düğmenin sağ kenarına hizalanır, taşarsa yukarı açılır ve görünüm alanına sıkıştırılır.
 *    Tablonun yatay kaydırma alanı tarafından kırpılmaz.
 *  - Moodle'un kayıt işlemleri (bilgi, düzenle, kaldır + onay) core_user/status_field ile çalışmaya devam eder:
 *    menü öğeleri aynı data-action özniteliklerini ve data-* kapsayıcısını taşır.
 *  - Rol/grup düzenleme, satırdaki Moodle satır içi düzenleyicisini tetikler (core/inplace_editable).
 */
(function () {
    'use strict';

    var GAP = 4;
    var MARGIN = 8;

    var openState = null; // {trigger, menu}
    var ignoreScrollUntil = 0;

    function items(menu) {
        return Array.prototype.slice.call(menu.querySelectorAll('[role="menuitem"]'));
    }

    function position(trigger, menu) {
        var rect = trigger.getBoundingClientRect();
        var vw = document.documentElement.clientWidth;
        var vh = window.innerHeight;

        menu.style.maxHeight = (vh - 2 * MARGIN) + 'px';
        menu.style.left = '0px';
        menu.style.top = '0px';

        var w = menu.offsetWidth;
        var h = menu.offsetHeight;

        // Düğmenin sağ kenarına hizala, görünüm alanına sıkıştır (dar ekranlarda sola kayar).
        var left = rect.right - w;
        left = Math.max(MARGIN, Math.min(left, vw - w - MARGIN));

        // Aşağıda yer yoksa ve yukarıda varsa yukarı aç.
        var top = rect.bottom + GAP;
        if (top + h > vh - MARGIN && rect.top - GAP - h >= MARGIN) {
            top = rect.top - GAP - h;
        }
        top = Math.max(MARGIN, Math.min(top, vh - h - MARGIN));

        menu.style.left = Math.round(left) + 'px';
        menu.style.top = Math.round(top) + 'px';
    }

    function close(returnFocus) {
        if (!openState) {
            return;
        }
        var state = openState;
        openState = null;
        state.menu.hidden = true;
        state.trigger.setAttribute('aria-expanded', 'false');
        if (returnFocus && document.contains(state.trigger)) {
            state.trigger.focus();
        }
    }

    function open(trigger, focusWhich) {
        var menu = document.getElementById(trigger.getAttribute('aria-controls'));
        if (!menu) {
            return;
        }
        close(false);

        menu.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        openState = {trigger: trigger, menu: menu};
        position(trigger, menu);

        // Odak taşıması sayfayı kaydırıp menüyü hemen kapatmasın.
        ignoreScrollUntil = Date.now() + 150;
        var list = items(menu);
        if (list.length) {
            var target = focusWhich === 'last' ? list[list.length - 1] : list[0];
            target.focus({preventScroll: true});
        }
    }

    function runWamAction(item, trigger) {
        var row = trigger.closest('tr');
        if (!row) {
            return;
        }
        var selector = item.getAttribute('data-wam-action') === 'edit-roles'
            ? '[data-itemtype="user_roles"] [data-inplaceeditablelink]'
            : '[data-itemtype="user_groups"] [data-inplaceeditablelink]';
        var link = row.querySelector(selector);
        if (link) {
            link.click();
        }
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target || !target.closest) {
            return;
        }

        var trigger = target.closest('.wam-row-actions__trigger');
        if (trigger) {
            event.preventDefault();
            if (openState && openState.trigger === trigger) {
                close(true);
            } else {
                open(trigger, 'first');
            }
            return;
        }

        var item = target.closest('.wam-row-actions__menu [role="menuitem"]');
        if (item && openState) {
            var current = openState;
            if (item.tagName === 'A' && item.getAttribute('href') === '#') {
                event.preventDefault();
            }
            // Menüyü gizle (öğeler DOM'da kalır; Moodle'un işleyicileri öğeye ve kapsayıcısına ihtiyaç duyar).
            close(true);
            if (item.hasAttribute('data-wam-action')) {
                runWamAction(item, current.trigger);
            }
            return;
        }

        // Menü dışına tıklama.
        if (openState && !target.closest('.wam-row-actions__menu')) {
            close(false);
        }
    });

    document.addEventListener('keydown', function (event) {
        var target = event.target;
        if (!target || !target.closest) {
            return;
        }

        var trigger = target.closest('.wam-row-actions__trigger');
        if (trigger) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                open(trigger, 'first');
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                open(trigger, 'last');
            }
            return;
        }

        if (!openState) {
            return;
        }
        var menu = target.closest('.wam-row-actions__menu');
        if (!menu || menu !== openState.menu) {
            return;
        }

        var list = items(menu);
        var index = list.indexOf(document.activeElement);

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                list[(index + 1) % list.length].focus({preventScroll: true});
                break;
            case 'ArrowUp':
                event.preventDefault();
                list[(index - 1 + list.length) % list.length].focus({preventScroll: true});
                break;
            case 'Home':
                event.preventDefault();
                list[0].focus({preventScroll: true});
                break;
            case 'End':
                event.preventDefault();
                list[list.length - 1].focus({preventScroll: true});
                break;
            case 'Escape':
                event.preventDefault();
                event.stopPropagation();
                close(true);
                break;
            case 'Tab':
                // Menü kapanır; odak düğmeden devam eder (Tab ileri, Shift+Tab geri).
                close(true);
                break;
            case ' ':
                // Bağlantı öğeleri Space ile de etkinleşsin.
                if (document.activeElement && document.activeElement.tagName === 'A') {
                    event.preventDefault();
                    document.activeElement.click();
                }
                break;
            default:
                break;
        }
    }, true);

    // Kaydırma ve yeniden boyutlandırmada menü düğmesinden ayrılmasın diye kapanır.
    window.addEventListener('scroll', function (event) {
        if (!openState || Date.now() < ignoreScrollUntil) {
            return;
        }
        if (event.target && event.target.nodeType === 1 && openState.menu.contains(event.target)) {
            return;
        }
        close(false);
    }, true);

    // Yalnızca genişlik değişince kapanır (mobilde adres çubuğu yüksekliği değiştirir, menü kapanmasın).
    var lastWidth = window.innerWidth;
    window.addEventListener('resize', function () {
        if (window.innerWidth !== lastWidth) {
            lastWidth = window.innerWidth;
            close(false);
        }
    });

    // Satır içi düzenleme bağlantıları yalnızca menüden tetiklenir: sekme sırasından ve erişilebilirlik ağacından çıkar.
    function normalizeInplaceLinks(root) {
        (root || document).querySelectorAll('#participants [data-inplaceeditablelink]').forEach(function (link) {
            if (link.getAttribute('tabindex') !== '-1') {
                link.setAttribute('tabindex', '-1');
            }
            if (link.getAttribute('role') !== 'presentation') {
                link.setAttribute('role', 'presentation');
            }
        });
    }

    var scheduled = false;
    function schedule() {
        if (scheduled) {
            return;
        }
        scheduled = true;
        window.requestAnimationFrame(function () {
            scheduled = false;
            normalizeInplaceLinks(document);
            // Tablo yeniden çizildiyse açık menü artık belgede değildir.
            if (openState && !document.contains(openState.menu)) {
                openState = null;
            }
        });
    }

    function start() {
        normalizeInplaceLinks(document);
        if (typeof MutationObserver !== 'undefined') {
            new MutationObserver(schedule).observe(document.body, {childList: true, subtree: true});
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
