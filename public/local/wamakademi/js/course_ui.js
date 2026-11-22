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

    /**
     * Sağ üst master Düzenle / Kaydet ikonları (rol ve grup satır içi düzenleme).
     *
     * @param {HTMLElement} host
     */
    function appendMasterEditBar(host) {
        if (!host || host.querySelector('[data-region="wam-cp-editbar"]')) {
            return;
        }
        var editLabel = str.participantsedit || 'Düzenle';
        var saveLabel = str.participantssave || 'Kaydet';
        var editingLabel = str.participantsediting || '';

        var pencil = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>';
        var check = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';

        var editBtn = el('button', {
            type: 'button',
            className: 'wam-cp-editbar__btn wam-cp-editbar__btn--edit',
            'aria-pressed': 'false',
            'aria-label': editLabel,
            title: editLabel
        });
        editBtn.innerHTML = pencil + '<span class="wam-cp-editbar__text">' + editLabel + '</span>';

        var saveBtn = el('button', {
            type: 'button',
            className: 'wam-cp-editbar__btn wam-cp-editbar__btn--save',
            'aria-label': saveLabel,
            title: saveLabel,
            disabled: 'disabled'
        });
        saveBtn.innerHTML = check + '<span class="wam-cp-editbar__text">' + saveLabel + '</span>';

        var bar = el('div', {
            className: 'wam-cp-editbar',
            'data-region': 'wam-cp-editbar',
            role: 'group',
            'aria-label': editLabel
        }, [editBtn, saveBtn]);
        if (editingLabel) {
            bar.setAttribute('data-editing-label', editingLabel);
        }
        host.appendChild(bar);
    }

    function enhanceParticipants() {
        enhanceEnrolButtons();

        var filterSet = document.querySelector('.filter-group[id^="core-filter"]');
        var filterForm = filterSet && filterSet.closest('form');
        var userlist = document.querySelector('.userlist');
        if (!userlist) {
            return;
        }

        var existingTools = document.querySelector('.wam-cp-tools');
        if (existingTools) {
            appendMasterEditBar(existingTools);
            return;
        }

        if (!filterSet || !filterForm) {
            var fallback = el('div', {className: 'wam-cp-tools wam-cp-tools--editonly'});
            appendMasterEditBar(fallback);
            userlist.parentNode.insertBefore(fallback, userlist);
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
        appendMasterEditBar(tools);

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

    /* ---------- Eğitim sayfasında içerik ekle / ayarlar (yetki PHP'de) ---------- */

    function buildActionUrl(action, sectionNum) {
        var url = String(action.url || '');
        var param = String(action.sectionparam || 'section');
        if (!url) {
            return '';
        }
        var joiner = url.indexOf('?') === -1 ? '?' : '&';
        return url + joiner + encodeURIComponent(param) + '=' + encodeURIComponent(String(sectionNum));
    }

    function injectCourseShortcuts() {
        var editurl = cfg.editurl || '';
        var editlabel = str.menueditcourse || '';
        var addactions = Array.isArray(cfg.addactions) ? cfg.addactions : [];
        var sections = Array.isArray(cfg.sections) ? cfg.sections : [];

        // Yükleme sayfasının kendisinde ve katılımcılarda kısayol gösterme.
        if (body.classList.contains('wam-course-participants') || body.classList.contains('wam-content')) {
            return;
        }
        if ((!editurl || !editlabel) && addactions.length === 0) {
            return;
        }
        if (document.querySelector('[data-region="wam-upload-shortcut"]')) {
            return;
        }
        var host = document.querySelector(
            '.tertiary-navigation .navitem-inner, .tertiary-navigation, #page-header .header-actions-container, #region-main'
        );
        if (!host) {
            return;
        }

        var wrap = el('div', {
            className: 'wam-upload-shortcut',
            'data-region': 'wam-upload-shortcut'
        });

        if (editurl && editlabel) {
            wrap.appendChild(el('a', {
                className: 'btn btn-secondary btn-sm',
                href: editurl,
                text: editlabel
            }));
        }

        if (addactions.length > 0) {
            wrap.appendChild(buildAddContentMenu(addactions, sections));
        }

        if (host.id === 'region-main') {
            host.insertBefore(wrap, host.firstChild);
        } else {
            host.appendChild(wrap);
        }
    }

    function buildAddContentMenu(addactions, sections) {
        var menuId = nextId('wam-add-menu');
        var root = el('div', {className: 'wam-add-content', 'data-region': 'wam-add-content'});
        var toggle = el('button', {
            type: 'button',
            className: 'btn btn-primary btn-sm wam-add-content__toggle',
            'aria-haspopup': 'menu',
            'aria-expanded': 'false',
            'aria-controls': menuId,
            text: '＋ ' + (str.menuaddcontent || 'İçerik ekle')
        });
        var panel = el('div', {
            id: menuId,
            className: 'wam-add-content__panel',
            role: 'menu',
            hidden: 'hidden'
        });

        var actionsView = el('div', {
            className: 'wam-add-content__view',
            'data-view': 'actions'
        });
        var sectionsView = el('div', {
            className: 'wam-add-content__view',
            'data-view': 'sections',
            hidden: 'hidden'
        });

        var pendingAction = null;

        function setOpen(open) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            root.classList.toggle('is-open', open);
            if (open) {
                panel.removeAttribute('hidden');
            } else {
                panel.setAttribute('hidden', 'hidden');
                showActionsView();
            }
        }

        function showActionsView() {
            pendingAction = null;
            sectionsView.setAttribute('hidden', 'hidden');
            actionsView.removeAttribute('hidden');
        }

        function showSectionsView(action) {
            pendingAction = action;
            actionsView.setAttribute('hidden', 'hidden');
            sectionsView.removeAttribute('hidden');
            var first = sectionsView.querySelector('[role="menuitem"]');
            if (first) {
                first.focus();
            }
        }

        function goToAction(action, sectionNum) {
            var target = buildActionUrl(action, sectionNum);
            if (target) {
                window.location.href = target;
            }
        }

        function onActionChoose(action) {
            if (sections.length <= 1) {
                var only = sections.length === 1 ? sections[0].num : 0;
                goToAction(action, only);
                return;
            }
            showSectionsView(action);
        }

        addactions.forEach(function(action) {
            var item = el('button', {
                type: 'button',
                className: 'wam-add-content__item',
                role: 'menuitem',
                text: action.label || ''
            });
            item.addEventListener('click', function() {
                onActionChoose(action);
            });
            actionsView.appendChild(item);
        });

        var prompt = el('div', {
            className: 'wam-add-content__prompt',
            text: str.menuaddsectionprompt || 'Hangi bölüme eklensin?'
        });
        sectionsView.appendChild(prompt);

        var back = el('button', {
            type: 'button',
            className: 'wam-add-content__item wam-add-content__item--back',
            role: 'menuitem',
            text: str.menuaddsectionback || 'Geri'
        });
        back.addEventListener('click', function() {
            showActionsView();
            toggle.focus();
        });
        sectionsView.appendChild(back);

        sections.forEach(function(section) {
            var item = el('button', {
                type: 'button',
                className: 'wam-add-content__item',
                role: 'menuitem',
                text: section.name || ('#' + section.num)
            });
            item.addEventListener('click', function() {
                if (pendingAction) {
                    goToAction(pendingAction, section.num);
                }
            });
            sectionsView.appendChild(item);
        });

        panel.appendChild(actionsView);
        panel.appendChild(sectionsView);
        root.appendChild(toggle);
        root.appendChild(panel);

        toggle.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            setOpen(toggle.getAttribute('aria-expanded') !== 'true');
        });

        document.addEventListener('click', function(event) {
            if (!root.contains(event.target)) {
                setOpen(false);
            }
        });

        root.addEventListener('keydown', function(event) {
            var activeView = sectionsView.hasAttribute('hidden') ? actionsView : sectionsView;
            var items = Array.prototype.slice.call(
                activeView.querySelectorAll('[role="menuitem"]')
            );
            var active = document.activeElement;
            var index = items.indexOf(active);

            if (event.key === 'Escape') {
                event.preventDefault();
                if (!sectionsView.hasAttribute('hidden')) {
                    showActionsView();
                    toggle.focus();
                    return;
                }
                setOpen(false);
                toggle.focus();
                return;
            }

            if (toggle.getAttribute('aria-expanded') !== 'true') {
                if ((event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ')
                        && event.target === toggle) {
                    event.preventDefault();
                    setOpen(true);
                    var first = panel.querySelector('[role="menuitem"]');
                    if (first) {
                        first.focus();
                    }
                }
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (items.length) {
                    items[(index + 1) % items.length].focus();
                }
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (items.length) {
                    items[(index - 1 + items.length) % items.length].focus();
                }
            } else if (event.key === 'Home') {
                event.preventDefault();
                if (items.length) {
                    items[0].focus();
                }
            } else if (event.key === 'End') {
                event.preventDefault();
                if (items.length) {
                    items[items.length - 1].focus();
                }
            }
        });

        return root;
    }

    function run() {
        labelCourseIndexToggle();
        injectCourseShortcuts();
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
