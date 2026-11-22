/**
 * Yerel geliştirme: admin / eğitmen doldurur; öğrenci sunucu taraflı demo giriş.
 * Yalnızca localhost giriş sayfasında yüklenir.
 */
(function () {
    'use strict';

    var PASSWORD = 'WamLocal#2026x';
    var TEACHER_ACCOUNTS = [
        {label: 'Admin', username: 'admin'},
        {label: 'Eğitmen', username: 'wamtest_egitmen'}
    ];
    var STUDENT_ACCOUNTS = [
        {label: 'Demo Öğrenci 1', username: 'demo_ogrenci1'},
        {label: 'Demo Öğrenci 2', username: 'demo_ogrenci2'},
        {label: 'Demo Öğrenci 3', username: 'demo_ogrenci3'}
    ];

    function getSesskey() {
        if (typeof M !== 'undefined' && M.cfg && M.cfg.sesskey) {
            return M.cfg.sesskey;
        }
        var input = document.querySelector('input[name="sesskey"]');
        return input ? input.value : '';
    }

    function loginFormSubmit(username) {
        var userField = document.getElementById('username');
        var passField = document.getElementById('password');
        var loginForm = document.getElementById('login');
        if (!userField || !passField || !loginForm) {
            return;
        }
        userField.value = username;
        passField.value = PASSWORD;
        HTMLFormElement.prototype.submit.call(loginForm);
    }

    function demoStudentLogin(username) {
        var sesskey = getSesskey();
        if (!sesskey) {
            window.alert('Oturum anahtarı bulunamadı. Sayfayı yenileyin.');
            return;
        }
        var form = document.createElement('form');
        form.method = 'post';
        form.action = M.cfg.wwwroot + '/local/wamakademi/demo_login.php';
        form.style.display = 'none';

        var u = document.createElement('input');
        u.name = 'username';
        u.value = username;
        form.appendChild(u);

        var s = document.createElement('input');
        s.name = 'sesskey';
        s.value = sesskey;
        form.appendChild(s);

        document.body.appendChild(form);
        form.submit();
    }

    function mount() {
        var form = document.getElementById('login');
        if (!form || document.querySelector('.wam-dev-login')) {
            return;
        }

        var wrap = document.createElement('div');
        wrap.className = 'wam-dev-login';

        TEACHER_ACCOUNTS.forEach(function (account) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'wam-dev-login__btn';
            button.textContent = account.label;
            button.addEventListener('click', function () {
                loginFormSubmit(account.username);
            });
            wrap.appendChild(button);
        });

        var studentWrap = document.createElement('div');
        studentWrap.className = 'wam-dev-login__student';

        var studentBtn = document.createElement('button');
        studentBtn.type = 'button';
        studentBtn.className = 'wam-dev-login__btn';
        studentBtn.textContent = 'Öğrenci';
        studentBtn.setAttribute('aria-expanded', 'false');
        studentBtn.setAttribute('aria-haspopup', 'listbox');

        var menu = document.createElement('div');
        menu.className = 'wam-dev-login__menu';
        menu.hidden = true;
        menu.setAttribute('role', 'listbox');

        STUDENT_ACCOUNTS.forEach(function (account) {
            var option = document.createElement('button');
            option.type = 'button';
            option.className = 'wam-dev-login__option';
            option.textContent = account.label;
            option.setAttribute('role', 'option');
            option.addEventListener('click', function () {
                menu.hidden = true;
                studentBtn.setAttribute('aria-expanded', 'false');
                demoStudentLogin(account.username);
            });
            menu.appendChild(option);
        });

        studentBtn.addEventListener('click', function () {
            var open = menu.hidden;
            menu.hidden = !open;
            studentBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        document.addEventListener('click', function (event) {
            if (!studentWrap.contains(event.target)) {
                menu.hidden = true;
                studentBtn.setAttribute('aria-expanded', 'false');
            }
        });

        studentWrap.appendChild(studentBtn);
        studentWrap.appendChild(menu);
        wrap.appendChild(studentWrap);
        form.appendChild(wrap);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mount);
    } else {
        mount();
    }
})();
