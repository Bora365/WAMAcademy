/**
 * WAM Akademi — eğitim detay ve yönetim ekranları (Kurs R1).
 *
 * Yalnızca lib.php'deki local_wamakademi_course_page_kind() eşleşen sayfalarda yüklenir.
 * Moodle'ın gezinme, filtre ve form davranışlarını değiştirmez; mevcut bileşenleri
 * (datafilter, dinamik tablo, kayıt penceresi) belgelenmiş veri öznitelikleri üzerinden kullanır.
 */
(function() {
    'use strict';

    var body = document.body;
    if (!body || body.dataset.wamCourseUi === '1') {
        return;
    }
    body.dataset.wamCourseUi = '1';

    var cfg = window.wamCourseConfig || {};
    var str = cfg.strings || {};
    var uid = 0;

    function nextId(prefix) {
        uid += 1;
        return prefix + '-' + uid;
    }

    function el(tag, attrs, children) {
        var node = document.createElement(tag);
        Object.keys(attrs || {}).forEach(function(key) {
            if (key === 'text') {
                node.textContent = attrs[key];
            } else if (key === 'className') {
                node.className = attrs[key];
            } else {
                node.setAttribute(key, attrs[key]);
            }
        });
        (children || []).forEach(function(child) {
            node.appendChild(child);
        });
        return node;
    }

    function waitFor(test, timeout) {
        return new Promise(function(resolve) {
            var started = Date.now();
            (function poll() {
                var value = test();
                if (value || Date.now() - started > (timeout || 4000)) {
                    resolve(value || null);
                    return;
                }
                window.setTimeout(poll, 60);
            })();
        });
    }

    /* ---------- Mobil kurs dizini düğmesi: görünür etiket ---------- */

    function labelCourseIndexToggle() {
        var button = document.querySelector('.drawer-toggles .drawer-left-toggle .btn');
        if (!button || button.querySelector('.wam-course-toggle__label') || !str.courseindexshort) {
            return;
        }
        // Erişilebilir ad Moodle'ın gizli metninden gelir; görünür etiket yalnızca görsel tekrarıdır.
        var label = el('span', {className: 'wam-course-toggle__label', 'aria-hidden': 'true', text: str.courseindexshort});
        button.insertBefore(label, button.firstChild);
    }

    /* ---------- Katılımcılar ---------- */

    var FILTER = {
        row: '[data-filterregion="filter"]',
        type: '[data-filterfield="type"]',
        values: '[data-filterregion="value"]',
        remove: '[data-filteraction="remove"]',
        add: '[data-filteraction="add"]',
        apply: '[data-filteraction="apply"]'
    };

    function withJquery(callback) {
        if (typeof window.require === 'function') {
            window.require(['jquery'], callback);
        }
    }

    function activeRows(filterSet) {
        return Array.prototype.filter.call(filterSet.querySelectorAll(FILTER.row), function(row) {
            var type = row.dataset.filterType || '';
            return type !== '' && type !== 'courseid' && !row.querySelector('[data-required="1"]');
        });
    }

    function keywordRow(filterSet) {
        return filterSet.querySelector(FILTER.row + '[data-filter-type="keywords"]');
    }

    function keywordValues(filterSet) {
        var row = keywordRow(filterSet);
        var select = row && row.querySelector(FILTER.values + ' select');
        if (!select) {
            return [];
        }
        return Array.prototype.map.call(select.querySelectorAll(':checked'), function(option) {
            return option.value;
        });
    }

    async function ensureKeywordRow(filterSet) {
        var row = keywordRow(filterSet);
        if (row) {
            return row;
        }
        var empty = filterSet.querySelector(FILTER.row + '[data-filter-type=""]');
        if (!empty) {
            var add = filterSet.querySelector(FILTER.add);
            if (!add) {
                return null;
            }
            add.click();
            empty = await waitFor(function() {
                return filterSet.querySelector(FILTER.row + '[data-filter-type=""]');
            });
        }
        var type = empty && empty.querySelector(FILTER.type);
        if (!type || !type.querySelector('option[value="keywords"]')) {
            return null;
        }
        type.value = 'keywords';
        type.dispatchEvent(new Event('change', {bubbles: true}));
        return waitFor(function() {
            var r = keywordRow(filterSet);
            return r && r.querySelector(FILTER.values + ' input[data-fieldtype="autocomplete"]') ? r : null;
        });
    }

    async function applyKeyword(filterSet, term) {
        var row = await ensureKeywordRow(filterSet);
        if (!row) {
            return false;
        }
        var select = row.querySelector(FILTER.values + ' select');
        var input = row.querySelector(FILTER.values + ' input[data-fieldtype="autocomplete"]');
        if (!select || !input) {
            return false;
        }
        Array.prototype.slice.call(select.options).forEach(function(option) {
            option.remove();
        });
        await new Promise(function(resolve) {
            withJquery(function($) {
                input.value = term;
                // form-autocomplete: Enter ile serbest anahtar kelime ekler (tags modu).
                $(input).trigger($.Event('keydown', {keyCode: 13, which: 13}));
                resolve();
            });
        });
        await waitFor(function() {
            return keywordValues(filterSet).indexOf(term) !== -1;
        }, 2000);
        var apply = filterSet.querySelector(FILTER.apply);
        if (apply) {
            apply.click();
        }
        return true;
    }

    function clearKeyword(filterSet) {
        var row = keywordRow(filterSet);
        var remove = row && row.querySelector(FILTER.remove);
        if (remove) {
            remove.click();
        }
    }

    function enhanceEnrolButtons() {
        if (!str.courseaddparticipants) {
            return;
        }
        document.querySelectorAll('.enrolusersbutton.enrol_manual_plugin').forEach(function(wrap) {
            var control = wrap.querySelector('input[type="submit"], button[type="submit"]');
            if (!control || control.dataset.wamLabelled === '1') {
                return;
            }
            control.dataset.wamLabelled = '1';
            if (control.tagName === 'INPUT') {
                control.value = str.courseaddparticipants;
            } else {
                control.textContent = str.courseaddparticipants;
            }
        });
    }

    function enhanceParticipants() {
        enhanceEnrolButtons();

        var filterSet = document.querySelector('.filter-group[id^="core-filter"]');
        var filterForm = filterSet && filterSet.closest('form');
        var userlist = document.querySelector('.userlist');
        if (!filterSet || !filterForm || !userlist || document.querySelector('.wam-cp-tools')) {
            return;
        }

        var searchId = nextId('wam-cp-search');
        var advancedId = nextId('wam-cp-advanced');

        var input = el('input', {
            type: 'search',
            id: searchId,
            className: 'form-control',
            autocomplete: 'off',
            placeholder: str.coursesearchplaceholder || ''
        });
        var submit = el('button', {type: 'submit', className: 'btn btn-primary', text: str.coursesearchsubmit || ''});
        var clear = el('button', {type: 'button', className: 'btn btn-secondary', text: str.coursesearchclear || ''});
        clear.hidden = true;

        var search = el('form', {className: 'wam-cp-search', role: 'search'}, [
            el('div', {className: 'wam-cp-search__field'}, [
                el('label', {'for': searchId, text: str.coursesearchlabel || ''}),
                input
            ]),
            submit,
            clear
        ]);

        var countBadge = el('span', {className: 'wam-cp-toggle__count'});
        countBadge.hidden = true;
        var advancedToggle = el('button', {
            type: 'button',
            className: 'wam-cp-toggle',
            'aria-expanded': 'false',
            'aria-controls': advancedId
        }, [
            el('span', {text: str.courseadvancedfilters || ''}),
            countBadge,
            el('i', {className: 'icon fa fa-chevron-down fa-fw wam-cp-toggle__icon', 'aria-hidden': 'true'})
        ]);

        var initialsToggle = el('button', {type: 'button', className: 'wam-cp-toggle', 'aria-expanded': 'false'}, [
            el('span', {text: str.courseinitials || ''}),
            el('i', {className: 'icon fa fa-chevron-down fa-fw wam-cp-toggle__icon', 'aria-hidden': 'true'})
        ]);

        var tools = el('div', {className: 'wam-cp-tools'}, [search, advancedToggle, initialsToggle]);
        var advanced = el('div', {className: 'wam-cp-advanced', id: advancedId});

        filterForm.parentNode.insertBefore(tools, filterForm);
        tools.parentNode.insertBefore(advanced, tools.nextSibling);
        advanced.appendChild(filterForm);

        function setAdvanced(open) {
            advanced.hidden = !open;
            advancedToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function setInitials(open) {
            body.classList.toggle('wam-cp-initials-open', open);
            initialsToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function initialsActive() {
            return Array.prototype.some.call(userlist.querySelectorAll('.initialbar'), function(bar) {
                var items = bar.querySelectorAll('.page-item');
                var active = bar.querySelector('.page-item.active');
                return active && items.length && active !== items[0];
            });
        }

        function sync() {
            var count = activeRows(filterSet).filter(function(row) {
                return row.dataset.filterType !== 'keywords';
            }).length;
            countBadge.hidden = count === 0;
            countBadge.textContent = count ? String(count) : '';
            var words = keywordValues(filterSet);
            if (document.activeElement !== input) {
                input.value = words.join(' ');
            }
            clear.hidden = words.length === 0;
            if (initialsActive()) {
                setInitials(true);
            }
        }

        setAdvanced(activeRows(filterSet).some(function(row) {
            return row.dataset.filterType !== 'keywords';
        }));
        setInitials(initialsActive());
        sync();

        advancedToggle.addEventListener('click', function() {
            setAdvanced(advanced.hidden);
        });
        initialsToggle.addEventListener('click', function() {
            setInitials(!body.classList.contains('wam-cp-initials-open'));
        });

        search.addEventListener('submit', function(e) {
            e.preventDefault();
            var term = input.value.trim();
            if (term === '') {
                clearKeyword(filterSet);
                return;
            }
            submit.disabled = true;
            applyKeyword(filterSet, term).then(function(ok) {
                submit.disabled = false;
                if (!ok) {
                    setAdvanced(true);
                }
                sync();
                return ok;
            }).catch(function() {
                submit.disabled = false;
                setAdvanced(true);
            });
        });

        clear.addEventListener('click', function() {
            input.value = '';
            clearKeyword(filterSet);
            input.focus();
        });

        var timer = null;
        var observer = new MutationObserver(function() {
            window.clearTimeout(timer);
            timer = window.setTimeout(function() {
                enhanceEnrolButtons();
                sync();
            }, 120);
        });
        observer.observe(filterSet, {childList: true, subtree: true, attributes: true, attributeFilter: ['data-filter-type']});
        observer.observe(userlist, {childList: true, subtree: true});
    }

    /* ---------- Eğitim sayfasında "Video yükle" kısayolu (yetki PHP'de; URL boşsa yok) ---------- */

    function injectUploadShortcut() {
        var url = cfg.uploadurl || '';
        var label = str.menuuploadvideo || '';
        // Yükleme sayfasının kendisinde ve katılımcılarda kısayol gösterme.
        if (!url || !label || body.classList.contains('wam-course-participants') || body.classList.contains('wam-content')) {
            return;
        }
        if (document.querySelector('[data-region="wam-upload-shortcut"]')) {
            return;
        }
        var host = document.querySelector('.tertiary-navigation .navitem-inner, .tertiary-navigation, #page-header .header-actions-container, #region-main');
        if (!host) {
            return;
        }
        var wrap = el('div', {
            className: 'wam-upload-shortcut',
            'data-region': 'wam-upload-shortcut'
        });
        var link = el('a', {
            className: 'btn btn-secondary btn-sm',
            href: url,
            text: label
        });
        wrap.appendChild(link);
        if (host.id === 'region-main') {
            host.insertBefore(wrap, host.firstChild);
        } else {
            host.appendChild(wrap);
        }
    }

    function run() {
        labelCourseIndexToggle();
        injectUploadShortcut();
        if (body.classList.contains('wam-course-participants')) {
            enhanceParticipants();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
