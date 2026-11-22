(function () {
    'use strict';

    if (document.body.dataset.wamAccountUi === '1') {
        return;
    }
    document.body.dataset.wamAccountUi = '1';

    function cfg() {
        return (typeof window.wamAccountConfig === 'object' && window.wamAccountConfig)
            ? window.wamAccountConfig
            : {};
    }

    function str(key, fallback) {
        var s = cfg().strings || {};
        return s[key] || fallback || key;
    }

    function qs(root, sel) {
        return (root || document).querySelector(sel);
    }

    function qsa(root, sel) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    }

    function pathName() {
        return ((cfg().path) || (window.location && window.location.pathname) || '').replace(/\\/g, '/');
    }

    function pageKind() {
        var bodyId = document.body.id || '';
        var pagetype = (cfg().pagetype || bodyId.replace(/^page-/, '') || '');
        var path = pathName();

        if (bodyId === 'page-user-profile' || pagetype === 'user-profile' || /\/user\/profile\.php$/.test(path)) {
            return 'profile';
        }
        if (
            bodyId === 'page-user-edit' || pagetype === 'user-edit' || /\/user\/edit\.php$/.test(path) ||
            bodyId === 'page-user-editadvanced' || pagetype === 'user-editadvanced' || /\/user\/editadvanced\.php$/.test(path)
        ) {
            return 'edit';
        }
        if (bodyId === 'page-user-preferences' || pagetype === 'user-preferences' || /\/user\/preferences\.php$/.test(path)) {
            return 'preferences';
        }
        if (bodyId === 'page-grade-report-overview-index' || pagetype === 'grade-report-overview-index' ||
                /\/grade\/report\/overview\//.test(path)) {
            return 'grades';
        }
        if (bodyId === 'page-report-usersessions-user' || pagetype === 'report-usersessions-user' ||
                /\/report\/usersessions\/user\.php$/.test(path)) {
            return 'sessions';
        }
        if (bodyId === 'page-user-files' || pagetype === 'user-files' || /\/user\/files\.php$/.test(path)) {
            return 'files';
        }
        if (
            bodyId === 'page-message-notificationpreferences' ||
            pagetype === 'message-notificationpreferences' ||
            /\/message\/notificationpreferences\.php$/.test(path) ||
            bodyId === 'page-message-edit' ||
            pagetype === 'message-edit' ||
            /\/message\/edit\.php$/.test(path)
        ) {
            return 'notifications';
        }
        if (
            /\/login\/change_password\.php$/.test(path) ||
            /\/user\/(?:language|forum|editor|calendar|managetoken|contentbank|defaulthomepage|contactsitesupport|policy|portfolio|portfoliologs)\.php$/.test(path) ||
            /\/calendar\/(?:managesubscriptions|export)\.php$/.test(path) ||
            /\/admin\/tool\/mfa\/user_preferences\.php$/.test(path) ||
            /\/auth\/oauth2\/linkedlogins\.php$/.test(path) ||
            /\/admin\/tool\/dataprivacy\/(?:mydatarequests|createdatarequest)\.php$/.test(path) ||
            pagetype === 'login-change_password' ||
            pagetype === 'admin-tool-mfa-user_preferences' ||
            pagetype === 'auth-oauth2-linkedlogins' ||
            pagetype === 'admin-tool-dataprivacy-mydatarequests' ||
            pagetype === 'admin-tool-dataprivacy-createdatarequest' ||
            pagetype === 'user-language' ||
            pagetype === 'user-forum' ||
            pagetype === 'user-editor' ||
            pagetype === 'user-calendar' ||
            pagetype === 'user-managetoken' ||
            pagetype === 'calendar-managesubscriptions' ||
            pagetype === 'calendar-export'
        ) {
            return 'form';
        }
        if (
            /\/badges\/(?:preferences|mybackpack)\.php$/.test(path) ||
            pagetype === 'badges-preferences' ||
            pagetype === 'badges-mybackpack'
        ) {
            return 'form';
        }
        if (
            /\/badges\/(?:mybadges|badge)\.php$/.test(path) ||
            pagetype === 'badges-mybadges' ||
            pagetype === 'badges-badge'
        ) {
            return 'table';
        }
        return 'generic';
    }

    function markReady() {
        document.documentElement.classList.add('wam-acc-ready');
        document.body.classList.add('wam-acc-ready', 'wam-acc-shell');
        if (document.body.id) {
            document.body.classList.add('wam-acc-ready');
        }
    }

    function ensureBackLink(href, label) {
        if (!href) {
            return;
        }
        var region = document.getElementById('region-main');
        if (!region || qs(region, '.wam-acc-back')) {
            return;
        }
        var back = document.createElement('a');
        back.className = 'wam-acc-back';
        back.href = href;
        back.innerHTML =
            '<svg class="wam-acc-icon" viewBox="0 0 24 24" aria-hidden="true">' +
            '<path d="M15 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2" ' +
            'stroke-linecap="round" stroke-linejoin="round"/></svg>' +
            '<span></span>';
        back.querySelector('span').textContent = label || str('backtopreferences', 'Tercihlere dön');
        region.insertBefore(back, region.firstChild);
    }

    function wrapOverflowTables(root) {
        var region = root || document.getElementById('region-main') || document;
        qsa(region, 'table.generaltable, table.table, table#overview-grade, .generalbox table').forEach(function (table) {
            if (table.closest('.wam-acc-table-wrap') || table.closest('.filemanager, .fp-content, .file-picker')) {
                return;
            }
            var wrap = document.createElement('div');
            wrap.className = 'wam-acc-table-wrap';
            table.parentNode.insertBefore(wrap, table);
            wrap.appendChild(table);
            table.classList.add('wam-acc-table');
            var headers = qsa(table, 'thead th, thead td');
            if (!headers.length) {
                return;
            }
            var headerTexts = headers.map(function (th) {
                return (th.textContent || '').replace(/\s+/g, ' ').trim();
            });
            qsa(table, 'tbody tr').forEach(function (row) {
                qsa(row, 'td').forEach(function (cell, idx) {
                    if (headerTexts[idx] && !cell.getAttribute('data-label')) {
                        cell.setAttribute('data-label', headerTexts[idx]);
                    }
                });
            });
        });
    }

    function stylePrimaryHeading() {
        var region = document.getElementById('region-main');
        if (!region) {
            return;
        }
        var heading = qs(region, ':scope > h2, :scope > .page-header-headings h1, :scope > .page-header-headings h2, h2:first-of-type');
        if (heading) {
            heading.classList.add('wam-acc-page-title');
        }
    }

    function enhanceShell(options) {
        options = options || {};
        markReady();
        var region = document.getElementById('region-main');
        if (region) {
            region.classList.add('wam-acc-region');
        }
        stylePrimaryHeading();
        if (options.backHref) {
            ensureBackLink(options.backHref, options.backLabel);
        }
        wrapOverflowTables(region);
    }

    function enhanceFormPage() {
        enhanceShell({
            backHref: cfg().preferencesUrl || '',
            backLabel: str('backtopreferences', 'Tercihlere dön'),
        });

        var form = qs(document, 'form.mform');
        if (!form) {
            return;
        }
        form.classList.add('wam-acc-form');

        qsa(form, ':scope > fieldset').forEach(function (fs) {
            if (fs.id === 'fgroup_id_buttonar' || fs.classList.contains('hidden')) {
                return;
            }
            fs.classList.add('wam-acc-form-card', 'wam-acc-fieldset');
        });

        var buttons = document.getElementById('fgroup_id_buttonar')
            || qs(form, '.fitem_actionbuttons, .form-group.fitem_actionbuttons')
            || qs(document, '#sticky-footer #fgroup_id_buttonar, #sticky-footer .fitem_actionbuttons');
        if (buttons) {
            buttons.classList.add('wam-acc-form-actions');
            qsa(buttons, 'button, input[type="submit"], .btn').forEach(function (btn, idx) {
                if (btn.classList.contains('btn-primary') || btn.getAttribute('type') === 'submit' || idx === 0) {
                    btn.classList.add('wam-acc-btn', 'wam-acc-btn--primary');
                } else {
                    btn.classList.add('wam-acc-btn', 'wam-acc-btn--ghost');
                }
            });
        }

        var sticky = document.getElementById('sticky-footer');
        if (sticky) {
            sticky.classList.remove('bg-white');
            sticky.classList.add('wam-acc-form-actions-bar');
        }

        openInvalidFieldsets(form);
        openHashTarget(form);
    }

    function enhanceFilesPage() {
        enhanceShell({
            backHref: cfg().profileUrl || cfg().preferencesUrl || '',
            backLabel: str('backtoprofile', 'Profile dön'),
        });
        var region = document.getElementById('region-main');
        if (!region) {
            return;
        }
        var fm = qs(region, '.filemanager, .fp-navbar, form.mform');
        if (fm) {
            var card = qs(region, '.wam-acc-form-card');
            if (!card) {
                card = document.createElement('section');
                card.className = 'wam-acc-form-card wam-acc-files-card';
                var host = fm.closest('form') || fm.parentNode;
                if (host && host.parentNode) {
                    host.parentNode.insertBefore(card, host);
                    card.appendChild(host);
                }
            }
        }
    }

    function enhanceTablePage() {
        var backHref = cfg().preferencesUrl || '';
        var backLabel = str('backtopreferences', 'Tercihlere dön');
        var path = pathName();
        if (/\/grade\//.test(path) || /\/badges\//.test(path)) {
            backHref = cfg().profileUrl || backHref;
            backLabel = str('backtoprofile', 'Profile dön');
        }
        enhanceShell({
            backHref: backHref,
            backLabel: backLabel,
        });
    }

    function enhanceGenericPage() {
        enhanceShell({
            backHref: cfg().preferencesUrl || cfg().profileUrl || '',
            backLabel: str('backtopreferences', 'Tercihlere dön'),
        });
        if (qs(document, 'form.mform')) {
            enhanceFormPage();
        }
    }

    /* ---------------- Profil ---------------- */

    function enhanceProfile() {
        markReady();
        var page = document.getElementById('page-user-profile');
        if (page) {
            page.classList.add('wam-acc-ready');
        }
        wrapOverflowTables(document.getElementById('region-main'));

        var header = qs(document, '.page-context-header');
        if (header) {
            var actions = qs(header, '.wam-acc-header-actions');
            if (!actions) {
                actions = document.createElement('div');
                actions.className = 'wam-acc-header-actions';
                header.appendChild(actions);
            }

            // Profili düzenle — çekirdek bağlantıyı taşı (yetki korunur).
            var editLi = qs(document, '.userprofile .profile_tree li.editprofile a');
            if (editLi && !qs(actions, '.wam-acc-btn--edit')) {
                var editBtn = document.createElement('a');
                editBtn.className = 'wam-acc-btn wam-acc-btn--primary wam-acc-btn--edit';
                editBtn.href = editLi.getAttribute('href');
                editBtn.textContent = str('editprofiletitle', 'Profili düzenle');
                actions.appendChild(editBtn);
                var li = editLi.closest('li');
                if (li) {
                    li.classList.add('wam-acc-hidden');
                }
            }

            // Başlıktaki diğer düğmeleri koru (Mesaj / Rehbere ekle).
            var btnGroup = qs(header, '.header-button-group');
            if (btnGroup && btnGroup.parentNode !== actions) {
                actions.appendChild(btnGroup);
            }

            // Bölüm satırı.
            var headings = qs(header, '.page-header-headings');
            var deptSrc = qs(document, '[data-wam-department]');
            if (headings && deptSrc && !qs(headings, '.wam-acc-dept')) {
                var dept = document.createElement('div');
                dept.className = 'wam-acc-dept';
                dept.textContent = deptSrc.textContent.trim();
                headings.appendChild(dept);
            }
        }

        // Kendi profilinde Mesaj düğmesini gizle.
        var currentId = parseInt(cfg().currentUserId, 10) || 0;
        var profileId = parseInt(cfg().profileUserId, 10) || 0;
        if (currentId && profileId && currentId === profileId) {
            var msgBtn = document.getElementById('message-user-button');
            if (msgBtn) {
                msgBtn.hidden = true;
                msgBtn.setAttribute('aria-hidden', 'true');
                msgBtn.style.display = 'none';
            }
        }

        alignProfileActions(header);
        splitEmailVisibility();
        markCompactCategories();
        layoutProfileColumns();
        bindProfileColumns();

        // Erişim satırlarında göreli süreyi soluk göster.
        qsa(document, '.userprofile .profile_tree .contentnode dd').forEach(function (dd) {
            var html = dd.innerHTML;
            if (!html || dd.dataset.wamReltime === '1') {
                return;
            }
            var updated = html.replace(
                /&nbsp;\s*\(([^)]+)\)|\s+\(([^)]+)\)\s*$/,
                function (m, a, b) {
                    var t = a || b;
                    return ' <span class="wam-acc-reltime">· ' + t + '</span>';
                }
            );
            if (updated !== html) {
                dd.innerHTML = updated;
                dd.dataset.wamReltime = '1';
            }
        });
    }

    /* ---------------- Tercihler ---------------- */

    function prefsBucket(href) {
        var h = String(href || '').toLowerCase();
        // Hesap ve profil
        if (
            h.indexOf('/user/edit.php') !== -1 ||
            h.indexOf('/user/editadvanced.php') !== -1 ||
            h.indexOf('/user/profile.php') !== -1 ||
            h.indexOf('/user/defaulthomepage.php') !== -1
        ) {
            return 'account';
        }
        // Güvenlik ve oturumlar (kişisel MFA dahil; genel /admin/ değil)
        if (
            h.indexOf('/login/change_password.php') !== -1 ||
            h.indexOf('/report/usersessions/') !== -1 ||
            h.indexOf('/auth/oauth2/') !== -1 ||
            h.indexOf('/admin/tool/mfa/') !== -1 ||
            h.indexOf('/user/managetoken.php') !== -1
        ) {
            return 'security';
        }
        // Dil ve editör
        if (
            h.indexOf('/user/language.php') !== -1 ||
            h.indexOf('/user/editor.php') !== -1
        ) {
            return 'locale';
        }
        // İletişim ve bildirimler
        if (
            h.indexOf('/message/notificationpreferences.php') !== -1 ||
            h.indexOf('/message/edit.php') !== -1 ||
            h.indexOf('/user/forum.php') !== -1 ||
            h.indexOf('/user/contactsitesupport.php') !== -1
        ) {
            return 'comms';
        }
        // Dosyalar ve diğer (kişisel rozet / veri talepleri)
        if (
            h.indexOf('/user/files.php') !== -1 ||
            h.indexOf('/user/calendar.php') !== -1 ||
            h.indexOf('/calendar/') !== -1 ||
            h.indexOf('/badges/mybadges.php') !== -1 ||
            h.indexOf('/badges/badge.php') !== -1 ||
            h.indexOf('/badges/preferences.php') !== -1 ||
            h.indexOf('/badges/backpack') !== -1 ||
            h.indexOf('/user/contentbank.php') !== -1 ||
            h.indexOf('/user/portfolio') !== -1 ||
            h.indexOf('/admin/tool/dataprivacy/') !== -1 ||
            h.indexOf('/user/policy.php') !== -1
        ) {
            return 'files';
        }
        // Yönetim kısayolları (hesap dışı sınır)
        if (
            h.indexOf('/admin/') !== -1 ||
            h.indexOf('/badges/index.php') !== -1 ||
            h.indexOf('/badges/new_badge.php') !== -1 ||
            h.indexOf('/badges/edit.php') !== -1 ||
            h.indexOf('/badges/action.php') !== -1 ||
            h.indexOf('/badges/award.php') !== -1 ||
            h.indexOf('/badges/manage_backpacks.php') !== -1 ||
            h.indexOf('loginas.php') !== -1 ||
            (h.indexOf('assign.php') !== -1 && h.indexOf('role') !== -1) ||
            h.indexOf('permissions.php') !== -1 ||
            h.indexOf('checkpermissions') !== -1
        ) {
            return 'admin';
        }
        return 'files';
    }

    function prefsIcon(bucket) {
        var paths = {
            account: '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="7" r="4" fill="none" stroke="currentColor" stroke-width="2"/>',
            security: '<rect x="3" y="11" width="18" height="11" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" fill="none" stroke="currentColor" stroke-width="2"/>',
            locale: '<circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18" fill="none" stroke="currentColor" stroke-width="2"/>',
            comms: '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M13.73 21a2 2 0 0 1-3.46 0" fill="none" stroke="currentColor" stroke-width="2"/>',
            files: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" fill="none" stroke="currentColor" stroke-width="2"/><path d="M14 2v6h6M8 13h8M8 17h8" fill="none" stroke="currentColor" stroke-width="2"/>',
            admin: '<path d="M12 1l3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1z" fill="none" stroke="currentColor" stroke-width="2"/>'
        };
        return (
            '<svg class="wam-acc-icon" viewBox="0 0 24 24" aria-hidden="true">' +
            (paths[bucket] || paths.files) +
            '</svg>'
        );
    }

    function enhancePreferences() {
        markReady();
        var page = document.getElementById('page-user-preferences');
        if (page) {
            page.classList.add('wam-acc-ready');
        }
        var region = document.getElementById('region-main');
        if (region) {
            region.classList.add('wam-acc-region');
        }
        stylePrimaryHeading();

        var source = qs(document, '.container-fluid.p-sm-0');
        if (!source || source.classList.contains('wam-acc-prefs-built')) {
            return;
        }

        var links = [];
        qsa(source, 'a[href]').forEach(function (a) {
            var href = a.getAttribute('href');
            if (!href || href === '#') {
                return;
            }
            links.push({
                href: href,
                label: (a.textContent || '').trim(),
                title: a.getAttribute('title') || '',
                bucket: prefsBucket(href),
            });
        });

        if (!links.length) {
            return;
        }

        var order = [
            { id: 'account', title: 'Hesap ve profil' },
            { id: 'security', title: 'Güvenlik ve oturumlar' },
            { id: 'locale', title: 'Dil ve editör' },
            { id: 'comms', title: 'İletişim ve bildirimler' },
            { id: 'files', title: 'Dosyalar ve diğer' },
            { id: 'admin', title: 'Yönetim kısayolları', boundary: true },
        ];

        var wrap = document.createElement('div');
        wrap.className = 'wam-acc-prefs';
        wrap.setAttribute('data-region', 'wam-preferences');

        order.forEach(function (group) {
            var items = links.filter(function (l) { return l.bucket === group.id; });
            if (!items.length) {
                return;
            }
            var section = document.createElement('section');
            section.className = 'wam-acc-prefs__section' + (group.boundary ? ' wam-acc-prefs__section--admin' : '');
            section.setAttribute('aria-labelledby', 'wam-pref-' + group.id);
            if (group.boundary) {
                section.setAttribute('data-wam-boundary', 'admin');
            }

            var h = document.createElement('h3');
            h.className = 'wam-acc-prefs__title';
            h.id = 'wam-pref-' + group.id;
            h.textContent = group.title;
            section.appendChild(h);

            var ul = document.createElement('ul');
            ul.className = 'wam-acc-prefs__list';

            items.forEach(function (item) {
                var li = document.createElement('li');
                var a = document.createElement('a');
                a.className = 'wam-acc-prefs__link';
                a.href = item.href;
                a.innerHTML =
                    prefsIcon(group.id) +
                    '<span class="wam-acc-prefs__text">' +
                    '<span class="wam-acc-prefs__label"></span>' +
                    (item.title
                        ? '<span class="wam-acc-prefs__desc"></span>'
                        : '') +
                    '</span>' +
                    '<svg class="wam-acc-prefs__chev wam-acc-icon" viewBox="0 0 24 24" aria-hidden="true">' +
                    '<path d="M9 18l6-6-6-6"/>' +
                    '</svg>';
                a.querySelector('.wam-acc-prefs__label').textContent = item.label;
                var desc = a.querySelector('.wam-acc-prefs__desc');
                if (desc) {
                    desc.textContent = item.title;
                }
                li.appendChild(a);
                ul.appendChild(li);
            });

            section.appendChild(ul);
            wrap.appendChild(section);
        });

        source.classList.add('wam-acc-prefs-source');
        source.classList.add('wam-acc-prefs-built');
        source.hidden = true;
        source.setAttribute('aria-hidden', 'true');
        source.setAttribute('inert', '');
        source.parentNode.insertBefore(wrap, source);
    }

    /* ---------------- Profili düzenle (native Moodle fieldset) ---------------- */

    function isMoodleFieldsetOpen(fs) {
        if (!fs) {
            return false;
        }
        var container = qs(fs, '.fcontainer.collapseable, .fcontainer.collapse, [id$="container"].collapse');
        if (container) {
            if (container.classList.contains('show')) {
                return true;
            }
            // aria/class senkronu bozulmuş olabilir; görünür yüksekliği esas al.
            try {
                if (container.getClientRects().length && container.getBoundingClientRect().height > 0) {
                    return true;
                }
            } catch (e) {
                /* ignore */
            }
            return false;
        }
        var toggler = qs(fs, 'a[data-bs-toggle="collapse"], a[data-toggle="collapse"], legend a.fheader, .ftoggler a');
        if (toggler) {
            if (toggler.classList.contains('collapsed')) {
                return false;
            }
            return toggler.getAttribute('aria-expanded') !== 'false';
        }
        return true;
    }

    function revealMoodleFieldset(fs) {
        if (!fs || isMoodleFieldsetOpen(fs)) {
            return;
        }
        var toggler = qs(fs, 'a[data-bs-toggle="collapse"], a[data-toggle="collapse"], legend a.fheader, .ftoggler a');
        if (!toggler) {
            return;
        }
        // Moodle/Bootstrap collapse; ikinci bir durum makinesi kurma — yalnız gerçekten kapalıysa tıkla.
        toggler.click();
    }

    function openInvalidFieldsets(form) {
        if (!form) {
            return;
        }
        var seen = [];
        qsa(form, '.is-invalid, .invalid-feedback:not(:empty), .error, .text-danger, [aria-invalid="true"]').forEach(function (el) {
            var fs = el.closest('fieldset');
            if (!fs || seen.indexOf(fs) !== -1) {
                return;
            }
            seen.push(fs);
            revealMoodleFieldset(fs);
            try {
                el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            } catch (e) {
                /* ignore */
            }
        });
    }

    function openHashTarget(form) {
        var hash = (window.location.hash || '').replace(/^#/, '');
        if (!hash || !form) {
            return;
        }
        var target = document.getElementById(hash) || qs(form, '[name="' + hash.replace(/"/g, '') + '"]');
        if (!target) {
            return;
        }
        var fs = target.closest('fieldset');
        revealMoodleFieldset(fs);
        try {
            target.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        } catch (e) {
            /* ignore */
        }
    }

    function enhanceEdit() {
        document.body.classList.add('wam-acc-edit');
        markReady();
        var page = document.getElementById('page-user-edit') || document.getElementById('page-user-editadvanced');
        if (page) {
            page.classList.add('wam-acc-ready');
        }
        // Kırıntı gezinmeyi gösterir; ikinci geri bağlantısı eklenmez.
        enhanceShell();

        var contextHeader = qs(document, '.page-context-header');
        if (contextHeader) {
            contextHeader.classList.remove('d-flex');
            contextHeader.hidden = true;
            contextHeader.setAttribute('aria-hidden', 'true');
        }

        var region = document.getElementById('region-main');
        var heading = region && qs(region, '[role="main"] > h2, :scope > h2');
        if (heading) {
            heading.textContent = str('editprofiletitle', 'Profili düzenle');
            heading.classList.add('wam-acc-page-title');
        }

        var form = qs(document, 'form.mform');
        if (!form) {
            return;
        }
        form.classList.add('wam-acc-form');
        if (form.dataset.wamEditReady === '1') {
            placeEditFields(form);
            openInvalidFieldsets(form);
            openHashTarget(form);
            return;
        }
        form.dataset.wamEditReady = '1';

        // Yalnız üst düzey bölüm fieldset'leri kart alır; iç içe filemanager vb. kendi yüzeyini korur.
        qsa(form, ':scope > fieldset').forEach(function (fs) {
            if (fs.id === 'fgroup_id_buttonar' || fs.classList.contains('hidden')) {
                return;
            }
            fs.classList.add('wam-acc-form-card', 'wam-acc-fieldset');
        });

        var buttons = document.getElementById('fgroup_id_buttonar')
            || qs(form, '.fitem_actionbuttons, .form-group.fitem_actionbuttons')
            || qs(document, '#sticky-footer #fgroup_id_buttonar, #sticky-footer .fitem_actionbuttons');
        if (buttons) {
            buttons.classList.add('wam-acc-form-actions');
            qsa(buttons, 'button, input[type="submit"], .btn').forEach(function (btn, idx) {
                if (btn.classList.contains('btn-primary') || btn.getAttribute('type') === 'submit' || idx === 0) {
                    btn.classList.add('wam-acc-btn', 'wam-acc-btn--primary');
                } else {
                    btn.classList.add('wam-acc-btn', 'wam-acc-btn--ghost');
                }
            });
        }

        var sticky = document.getElementById('sticky-footer');
        if (sticky) {
            sticky.classList.remove('bg-white');
            sticky.classList.add('wam-acc-form-actions-bar');
        }

        placeEditFields(form);
        openInvalidFieldsets(form);
        openHashTarget(form);
    }

    function alignProfileActions(header) {
        if (!header) {
            return;
        }
        var actions = qs(header, '.wam-acc-header-actions');
        if (!actions) {
            return;
        }
        var pageHeader = document.getElementById('page-header');
        if (!pageHeader) {
            return;
        }
        qsa(pageHeader, '.ms-auto .singlebutton, .ms-auto .header-button-group').forEach(function (el) {
            if (el.closest('.wam-acc-header-actions, .navbar, .wam-gear')) {
                return;
            }
            actions.appendChild(el);
        });
    }

    function splitEmailVisibility() {
        qsa(document, '.userprofile li.contentnode').forEach(function (li) {
            var link = qs(li, 'a[href^="mailto:"]');
            if (!link || link.dataset.wamMail === '1') {
                return;
            }
            var parent = link.parentNode;
            var note = document.createElement('span');
            note.className = 'wam-acc-mailnote';
            var node = link.nextSibling;
            while (node) {
                var next = node.nextSibling;
                note.appendChild(node);
                node = next;
            }
            var text = (note.textContent || '')
                .replace(/^[\s·•\u00b7(]+/, '')
                .replace(/[\s)]+$/, '')
                .trim();
            if (text) {
                note.textContent = text;
                parent.appendChild(note);
            }
            link.dataset.wamMail = '1';
        });
    }

    function markCompactCategories() {
        qsa(document, '.userprofile .node_category').forEach(function (cat) {
            var items = qsa(cat, '.card-body > ul > li').filter(function (li) {
                if (li.classList.contains('wam-acc-hidden')) {
                    return false;
                }
                if (qs(li, '[data-wam-marker], [data-wam-department]')) {
                    return false;
                }
                return true;
            });
            if (items.length <= 1) {
                cat.classList.add('wam-acc-cat--compact');
            }
        });
    }

    function profileCategoryKey(cat) {
        var marked = cat.getAttribute('data-wam-profile-cat');
        if (marked) {
            return marked;
        }
        var marker = qs(cat, '[data-wam-cat]');
        var key = marker ? marker.getAttribute('data-wam-cat') : '';
        if (!key) {
            var privacy = qsa(cat, 'a[href]').some(function (a) {
                var href = a.getAttribute('href') || '';
                return href.indexOf('/admin/tool/dataprivacy/') !== -1 || href.indexOf('/admin/tool/policy/') !== -1;
            });
            if (privacy) {
                key = 'privacyandpolicies';
            }
        }
        if (key) {
            cat.setAttribute('data-wam-profile-cat', key);
        }
        return key;
    }

    function layoutProfileColumns() {
        var tree = qs(document, '.userprofile .profile_tree');
        if (!tree) {
            return;
        }
        var cats = qsa(tree, '.node_category');
        if (!cats.length) {
            return;
        }
        var mainOrder = ['contact', 'privacyandpolicies', 'reports', 'badges', 'administration'];
        var sideOrder = ['coursedetails', 'miscellaneous', 'loginactivity'];
        var mobileOrder = ['contact', 'coursedetails', 'privacyandpolicies', 'miscellaneous', 'reports', 'loginactivity', 'badges', 'administration'];
        var byKey = {};
        var extra = [];
        cats.forEach(function (cat) {
            var key = profileCategoryKey(cat);
            if (key && !byKey[key]) {
                byKey[key] = cat;
            } else {
                extra.push(cat);
            }
        });
        function collect(keys) {
            var list = [];
            keys.forEach(function (key) {
                if (byKey[key]) {
                    list.push(byKey[key]);
                    delete byKey[key];
                }
            });
            return list;
        }
        var wide = window.matchMedia('(min-width: 769px)').matches;
        if (wide) {
            var main = qs(tree, ':scope > .wam-acc-col--main');
            var side = qs(tree, ':scope > .wam-acc-col--side');
            if (!main) {
                main = document.createElement('div');
                main.className = 'wam-acc-col wam-acc-col--main';
                side = document.createElement('div');
                side.className = 'wam-acc-col wam-acc-col--side';
                tree.appendChild(main);
                tree.appendChild(side);
            }
            var left = collect(mainOrder).concat(extra);
            var right = collect(sideOrder);
            Object.keys(byKey).forEach(function (key) {
                left.push(byKey[key]);
            });
            left.forEach(function (cat) { main.appendChild(cat); });
            right.forEach(function (cat) { side.appendChild(cat); });
            main.hidden = main.children.length === 0;
            side.hidden = side.children.length === 0;
            tree.classList.toggle('wam-acc-cols-1', main.hidden || side.hidden);
        } else {
            var mobile = collect(mobileOrder).concat(extra);
            Object.keys(byKey).forEach(function (key) {
                mobile.push(byKey[key]);
            });
            mobile.forEach(function (cat) { tree.appendChild(cat); });
            qsa(tree, ':scope > .wam-acc-col').forEach(function (col) {
                if (!col.querySelector('.node_category')) {
                    col.remove();
                }
            });
            tree.classList.remove('wam-acc-cols-1');
        }
    }

    function bindProfileColumns() {
        if (document.documentElement.dataset.wamProfileCols === '1') {
            return;
        }
        document.documentElement.dataset.wamProfileCols = '1';
        var mq = window.matchMedia('(min-width: 769px)');
        var onchange = function () { layoutProfileColumns(); };
        if (mq.addEventListener) {
            mq.addEventListener('change', onchange);
        } else if (mq.addListener) {
            mq.addListener(onchange);
        }
    }

    function fieldItem(name) {
        return document.getElementById('fitem_id_' + name);
    }

    function fieldVisible(el) {
        if (!el || el.classList.contains('hidden') || el.classList.contains('d-none')) {
            return false;
        }
        return true;
    }

    function placeEditFields(form) {
        if (!form) {
            return;
        }
        var general = document.getElementById('id_moodlecontainer');
        if (general) {
            [
                ['firstname', 'lastname'],
                ['email', 'maildisplay'],
                ['city', 'country'],
                ['username', 'auth'],
                ['suspended', 'createpassword']
            ].forEach(function (pair) {
                var a = fieldItem(pair[0]);
                var b = fieldItem(pair[1]);
                if (!fieldVisible(a) || !fieldVisible(b) || a.parentElement !== general || b.parentElement !== general) {
                    return;
                }
                a.classList.add('wam-acc-pair-a');
                b.classList.add('wam-acc-pair-b');
            });
            qsa(general, ':scope > .fitem').forEach(function (item) {
                if (item.classList.contains('wam-acc-pair-a') || item.classList.contains('wam-acc-pair-b')) {
                    return;
                }
                item.classList.add('wam-acc-span');
            });
        }
        var picture = document.getElementById('id_moodle_picturecontainer');
        var imagefile = fieldItem('imagefile');
        if (picture && fieldVisible(imagefile) && imagefile.parentElement === picture) {
            picture.classList.add('wam-acc-picture-grid');
            imagefile.classList.add('wam-acc-picture-file');
        }
    }

    /* ---------------- Başarı notları ---------------- */

    function enhanceGrades() {
        markReady();
        var page = document.getElementById('page-grade-report-overview-index');
        if (page) {
            page.classList.add('wam-acc-ready');
        }
        enhanceShell({
            backHref: cfg().profileUrl || '',
            backLabel: str('backtoprofile', 'Profile dön'),
        });

        var region = document.getElementById('region-main') || document;
        var tables = qsa(region, 'table#overview-grade, table.generaltable');
        var studentTable = null;
        var i;

        for (i = 0; i < tables.length; i++) {
            if (tables[i].id === 'overview-grade' || qs(tables[i], 'td.c1, th.c1')) {
                studentTable = tables[i];
                break;
            }
        }

        if (studentTable) {
            studentTable.classList.add('wam-acc-grades-table');
            var headers = qsa(studentTable, 'thead th, thead td');
            var headerTexts = headers.map(function (th) {
                return (th.textContent || '').replace(/\s+/g, ' ').trim();
            });

            qsa(studentTable, 'tbody tr').forEach(function (row) {
                var cells = qsa(row, 'td');
                cells.forEach(function (cell, idx) {
                    if (headerTexts[idx]) {
                        cell.setAttribute('data-label', headerTexts[idx]);
                    }
                });

                // Not sütunu: genelde c1. Yoksa ikinci hücre.
                var gradeCell = qs(row, 'td.c1') || cells[1];
                if (!gradeCell) {
                    return;
                }
                var raw = (gradeCell.textContent || '').replace(/\s+/g, ' ').trim();
                if (raw === '-') {
                    gradeCell.setAttribute('title', '-');
                    gradeCell.classList.add('wam-acc-grade-empty');
                    gradeCell.innerHTML = '';
                    var chip = document.createElement('span');
                    chip.className = 'wam-acc-grade-chip';
                    chip.textContent = str('nogradeyet', 'Henüz not girilmedi');
                    gradeCell.appendChild(chip);
                } else {
                    gradeCell.classList.add('wam-acc-grade-value');
                }
            });
        }

        var hasRows = studentTable && qsa(studentTable, 'tbody tr').length > 0;
        var notes = qsa(region, '.alert, .notifymessage, .notifyproblem');
        var onlyEmptyNotice = !hasRows && notes.some(function (n) {
            var t = (n.textContent || '').toLowerCase();
            return t.indexOf('rapor') !== -1 || t.indexOf('kayıt') !== -1 ||
                t.indexOf('enrol') !== -1 || t.indexOf('report') !== -1 ||
                t.indexOf('not') !== -1;
        });

        if (!hasRows) {
            if (qs(region, '.wam-acc-empty')) {
                return;
            }
            var empty = document.createElement('div');
            empty.className = 'wam-acc-empty wam-acc-section';
            var p = document.createElement('p');
            p.className = 'wam-acc-empty__text';
            p.textContent = str(
                'gradesempty',
                'Henüz notlandırılmış eğitimin yok. Notlar, eğitmen değerlendirme girdikçe burada görünür.'
            );
            empty.appendChild(p);
            var myUrl = cfg().mycoursesUrl || '';
            if (myUrl) {
                var a = document.createElement('a');
                a.className = 'wam-acc-btn wam-acc-btn--primary';
                a.href = myUrl;
                a.textContent = str('mycourses', 'Eğitimlerim');
                empty.appendChild(a);
            }
            var insertAfter = qs(region, 'h2, .page-header-headings, h3') || null;
            if (insertAfter && insertAfter.parentNode) {
                insertAfter.parentNode.insertBefore(empty, insertAfter.nextSibling);
            } else {
                region.appendChild(empty);
            }
            if (onlyEmptyNotice) {
                notes.forEach(function (n) {
                    n.classList.add('wam-acc-hidden');
                });
            }
        }
    }

    /* ---------------- Tarayıcı oturumları ---------------- */

    function enhanceSessions() {
        markReady();
        var page = document.getElementById('page-report-usersessions-user');
        if (page) {
            page.classList.add('wam-acc-ready');
        }
        enhanceShell({
            backHref: cfg().preferencesUrl || cfg().profileUrl || '',
            backLabel: str('backtopreferences', 'Tercihlere dön'),
        });

        var region = document.getElementById('region-main') || document;
        var table = qs(region, 'table.generaltable, table');
        if (!table) {
            return;
        }
        table.classList.add('wam-acc-sessions-table');

        var thisSession = str('thissession', 'Bu oturum');
        var currentLabel = 'Bu oturum';
        var headers = qsa(table, 'thead th, thead td');
        var headerTexts = headers.map(function (th) {
            return (th.textContent || '').replace(/\s+/g, ' ').trim();
        });

        var tbody = qs(table, 'tbody') || table;
        var rows = qsa(tbody, 'tr');
        var currentRow = null;

        function isCurrentSessionText(txt) {
            var t = String(txt || '').toLowerCase();
            return t === String(thisSession).toLowerCase() ||
                t === 'bu oturum' ||
                t.indexOf('mevcut oturum') !== -1 ||
                t.indexOf('mectu oturum') !== -1 ||
                t.indexOf('this session') !== -1;
        }

        rows.forEach(function (row) {
            var cells = qsa(row, 'td');
            cells.forEach(function (cell, idx) {
                if (headerTexts[idx]) {
                    cell.setAttribute('data-label', headerTexts[idx]);
                }
            });

            var lastAccess = cells[1];
            if (lastAccess) {
                var txt = (lastAccess.textContent || '').replace(/\s+/g, ' ').trim();
                if (isCurrentSessionText(txt) || qs(lastAccess, '.wam-acc-session-badge')) {
                    currentRow = row;
                    row.classList.add('wam-acc-session-current');
                    var existing = qs(lastAccess, '.wam-acc-session-badge');
                    if (!existing) {
                        lastAccess.textContent = '';
                        var badge = document.createElement('span');
                        badge.className = 'wam-acc-session-badge wam-acc-badge-current';
                        badge.textContent = currentLabel;
                        lastAccess.appendChild(badge);
                    } else if (existing.textContent !== currentLabel) {
                        existing.textContent = currentLabel;
                    }
                }
            }

            var actionCell = cells[cells.length - 1];
            if (actionCell) {
                var link = qs(actionCell, 'a');
                if (link) {
                    link.classList.add('wam-acc-btn', 'wam-acc-btn--danger-outline', 'wam-acc-session-logout');
                }
            }
        });

        if (currentRow && currentRow.parentNode && currentRow !== currentRow.parentNode.firstElementChild) {
            currentRow.parentNode.insertBefore(currentRow, currentRow.parentNode.firstElementChild);
        }

        // "Diğer tüm oturumlardan çıkış yap" düğmesini ayrı eylem satırına al.
        var logoutAll =
            qs(region, 'form[action*="usersessions"] .singlebutton, .singlebutton form, .singlebutton');
        if (logoutAll && !qs(region, '.wam-acc-session-actions')) {
            var wrap = document.createElement('div');
            wrap.className = 'wam-acc-session-actions';
            var formEl = logoutAll.closest('form') || logoutAll;
            if (formEl.parentNode) {
                formEl.parentNode.insertBefore(wrap, formEl);
                wrap.appendChild(formEl);
            }
            var btn = qs(wrap, 'button, input[type="submit"], .btn');
            if (btn) {
                btn.classList.add('wam-acc-btn', 'wam-acc-btn--danger-outline');
            }
        }
    }

    function decorateNotificationsRegion(region) {
        if (!region) {
            return;
        }
        region.classList.add('wam-acc-region', 'wam-acc-notifications');
        wrapOverflowTables(region);
        qsa(region, 'table').forEach(function (table) {
            if (!table.closest('.wam-acc-table-wrap')) {
                return;
            }
            table.classList.add('wam-acc-table');
        });
        // Kanal başlıklarını mobil için görünür tut.
        qsa(region, '.preference-table thead th, table thead th').forEach(function (th) {
            th.classList.add('wam-acc-notif-channel');
        });
    }

    function enhanceNotifications() {
        markReady();
        enhanceShell({
            backHref: cfg().preferencesUrl || '',
            backLabel: str('backtopreferences', 'Tercihlere dön'),
        });
        var region = document.getElementById('region-main');
        decorateNotificationsRegion(region);
        stylePrimaryHeading();

        if (region && region.getAttribute('data-wam-notif-obs') !== '1') {
            region.setAttribute('data-wam-notif-obs', '1');
            var timer = null;
            var obs = new MutationObserver(function () {
                if (timer) {
                    window.clearTimeout(timer);
                }
                timer = window.setTimeout(function () {
                    decorateNotificationsRegion(region);
                }, 80);
            });
            obs.observe(region, { childList: true, subtree: true });
        }
    }

    function init() {
        if (document.body.getAttribute('data-wam-acc-init') === '1') {
            return;
        }
        document.body.setAttribute('data-wam-acc-init', '1');

        var kind = pageKind();

        if (kind === 'profile') {
            enhanceProfile();
        } else if (kind === 'edit') {
            enhanceEdit();
        } else if (kind === 'preferences') {
            enhancePreferences();
        } else if (kind === 'grades') {
            enhanceGrades();
        } else if (kind === 'sessions') {
            enhanceSessions();
        } else if (kind === 'files') {
            enhanceFilesPage();
        } else if (kind === 'notifications') {
            enhanceNotifications();
        } else if (kind === 'form') {
            enhanceFormPage();
        } else if (kind === 'table') {
            enhanceTablePage();
        } else {
            enhanceGenericPage();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
