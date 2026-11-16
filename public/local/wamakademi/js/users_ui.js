/**
 * WAM Akademi — kullanıcı ekranları arayüz katmanı (User R1).
 *
 * Yalnızca görünüm ve etiket düzenler; Moodle'ın yetki denetimini, sesskey'ini, onay pencerelerini
 * ve form gönderimini değiştirmez. Sayfa türü (liste/yeni/düzenle) PHP tarafından wamUsersConfig.kind ile verilir.
 *
 *  - list : satır sonundaki Moodle eylem menüsü tetikleyicisi "İşlemler" etiketli düğme olur; silme öğesi "Hesabı sil".
 *           Rapor AJAX ile yeniden çizildiği için MutationObserver ile tekrar uygulanır.
 *  - new / edit : Kaydet/İptal çubuğu form yüzeyinin sol kenarıyla hizalanır.
 *           ("Yeni şifre" iki adımlı kalır: Moodle passwordunmask bileşeni düzenlemeyi odağa bağlar,
 *           odaksız açmak desteklenmez; yalnızca CSS ile alan gibi görünür.)
 */
(function() {
    'use strict';

    var cfg = window.wamUsersConfig || {};
    var str = cfg.strings || {};
    var body = document.body;
    if (!body || body.dataset.wamUsersUi === '1') {
        return;
    }
    body.dataset.wamUsersUi = '1';

    function rowName(trigger) {
        var row = trigger.closest('tr');
        var link = row ? row.querySelector('td.c1 a') : null;
        if (!link) {
            return '';
        }
        // Avatar baş harfleri (.userinitials) ad metnine karışmasın: yalnızca doğrudan metin düğümleri.
        var text = '';
        link.childNodes.forEach(function(node) {
            if (node.nodeType === Node.TEXT_NODE) {
                text += node.textContent;
            }
        });
        return text.replace(/\s+/g, ' ').trim();
    }

    /** Satır eylem menüsü tetikleyicisi: üç nokta yerine "İşlemler" + ok. */
    function decorateReport(root) {
        var label = str.participantsactions || '';
        if (!label) {
            return;
        }

        root.querySelectorAll('.reportbuilder-table .action-menu-trigger > .dropdown > a.dropdown-toggle:not([data-wam-actions])')
            .forEach(function(trigger) {
                trigger.setAttribute('data-wam-actions', '1');
                var name = rowName(trigger);
                var accessible = name ? label + ': ' + name : label;
                trigger.setAttribute('aria-label', accessible);
                trigger.setAttribute('title', accessible);
                // Üç nokta simgesi, gizli "Eylem" metni ve Moodle caret'i çıkarılır; yerine görünür etiket gelir.
                trigger.textContent = '';
                var span = document.createElement('span');
                span.className = 'wam-actions-label';
                span.textContent = label;
                trigger.appendChild(span);
            });

        var header = root.querySelector('.reportbuilder-table thead th.c4');
        if (header && !header.hasAttribute('data-wam-actions')) {
            header.setAttribute('data-wam-actions', '1');
            header.textContent = label;
        }

        // Hesap silme (sistem hesabı); eğitimden kayıt kaldırma ayrı ekrandadır (Katılımcılar).
        var deleteLabel = str.usersdeleteaccount || '';
        if (deleteLabel) {
            root.querySelectorAll('.reportbuilder-table .action-menu .dropdown-item[data-modal="confirmation"]:not([data-wam-actions])')
                .forEach(function(item) {
                    item.setAttribute('data-wam-actions', '1');
                    var text = item.querySelector('.menu-action-text');
                    if (text) {
                        text.textContent = deleteLabel;
                    }
                    item.setAttribute('aria-label', deleteLabel);
                });
        }
    }

    function initList() {
        var main = document.getElementById('region-main') || body;
        decorateReport(main);
        var pending = false;
        new MutationObserver(function() {
            if (pending) {
                return;
            }
            pending = true;
            window.requestAnimationFrame(function() {
                pending = false;
                decorateReport(main);
            });
        }).observe(main, {childList: true, subtree: true});
    }

    /** Kaydet/İptal çubuğunu form yüzeyinin sol kenarına hizalar (çekmece açık/kapalı ve yeniden boyutlandırmada). */
    function alignFooter() {
        var form = document.querySelector('#region-main form.mform');
        var footer = document.getElementById('sticky-footer');
        if (!form || !footer) {
            return;
        }
        var apply = function() {
            footer.style.setProperty('--wam-users-footer-left', Math.max(0, Math.round(form.getBoundingClientRect().left)) + 'px');
        };
        apply();
        window.addEventListener('resize', apply);
        if (window.ResizeObserver) {
            new ResizeObserver(apply).observe(document.getElementById('region-main') || form);
        }
    }

    function initForm() {
        alignFooter();
    }

    function start() {
        if (cfg.kind === 'list') {
            initList();
        } else if (cfg.kind === 'new' || cfg.kind === 'edit') {
            initForm();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
}());
