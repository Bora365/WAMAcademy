/**
 * Site ana sayfası ikincil sekmeleri: forceintomoremenu ile “Daha fazla”ya
 * taşınan düğümleri ana şeride geri alır; yatay kaydırma CSS ile sağlanır.
 * Yalnız .secondary-navigation; birincil üst menüye dokunmaz.
 */
(function () {
    'use strict';

    var body = document.body;
    if (!body || !body.classList.contains('wam-sitefront')) {
        return;
    }

    var menu = document.querySelector('.secondary-navigation ul.more-nav');
    if (!menu) {
        return;
    }

    var moreButton = menu.querySelector('[data-region="morebutton"]');
    var moreDropdown = menu.querySelector('[data-region="moredropdown"]');
    if (!moreButton || !moreDropdown) {
        return;
    }

    var isTabList = menu.getAttribute('role') === 'tablist';
    var ticking = false;

    /**
     * core/moremenu moveOutOfMoreDropdown ile aynı sınıf/ARIA dönüşümü.
     *
     * @param {HTMLElement} navNode
     */
    function moveOut(navNode) {
        var dropdownToggle = moreButton.querySelector('[data-bs-toggle="dropdown"]');
        var navLink = navNode.querySelector('a.dropdown-item, a.nav-link, a');
        if (!navLink) {
            menu.insertBefore(navNode, moreButton);
            return;
        }

        if (isTabList) {
            navLink.setAttribute('role', 'tab');
        } else {
            navLink.setAttribute('role', 'menuitem');
        }

        if (navLink.classList.contains('active') && dropdownToggle) {
            dropdownToggle.classList.remove('active');
            dropdownToggle.setAttribute('tabindex', '-1');
            navLink.setAttribute('tabindex', '0');
            if (isTabList) {
                navLink.removeAttribute('aria-current');
                navLink.setAttribute('aria-selected', 'true');
            }
        }

        navLink.classList.remove('dropdown-item');
        navLink.classList.add('nav-link');
        // Yeniden “Daha fazla”ya kilitlenmesin; yatay kaydırma taşmayı çözer.
        navNode.setAttribute('data-forceintomoremenu', 'false');
        if (navNode.dataset) {
            navNode.dataset.forceintomoremenu = 'false';
        }
        menu.insertBefore(navNode, moreButton);
    }

    function hideMoreIfEmpty() {
        var hasChildren = moreDropdown.querySelector(':scope > *') !== null;
        if (hasChildren) {
            moreButton.classList.remove('d-none');
            moreButton.classList.add('wam-sf-more-visible');
            return;
        }
        moreButton.classList.add('d-none');
        moreButton.classList.remove('wam-sf-more-visible');
        var toggle = moreButton.querySelector('.dropdown-toggle');
        if (toggle) {
            toggle.classList.remove('active');
            toggle.setAttribute('aria-expanded', 'false');
        }
    }

    function restoreAll() {
        var nodes = Array.prototype.slice.call(moreDropdown.children);
        nodes.forEach(function (node) {
            if (node && node.nodeType === 1) {
                moveOut(node);
            }
        });
        hideMoreIfEmpty();
    }

    function scrollActiveIntoView() {
        var active = menu.querySelector(
            'a.nav-link.active, a.nav-link[aria-current="true"], a[aria-selected="true"]'
        );
        if (active && typeof active.scrollIntoView === 'function') {
            active.scrollIntoView({block: 'nearest', inline: 'nearest', behavior: 'smooth'});
        }
    }

    function run() {
        ticking = false;
        restoreAll();
        scrollActiveIntoView();
    }

    function schedule() {
        if (ticking) {
            return;
        }
        ticking = true;
        window.requestAnimationFrame(function () {
            // moremenu.js init/resize sonrası.
            window.setTimeout(run, 0);
        });
    }

    schedule();
    window.addEventListener('load', schedule);
    window.addEventListener('resize', schedule);

    if (typeof MutationObserver === 'function') {
        var observer = new MutationObserver(function () {
            if (moreDropdown.children.length > 0) {
                schedule();
            }
        });
        observer.observe(moreDropdown, {childList: true});
    }

    // AMD moremenu yüklendikten sonra bir kez daha dene.
    var tries = 0;
    var timer = window.setInterval(function () {
        tries += 1;
        if (moreDropdown.children.length > 0 || tries >= 20) {
            schedule();
        }
        if (tries >= 20) {
            window.clearInterval(timer);
        }
    }, 50);

    menu.addEventListener('focusin', function (event) {
        var link = event.target && event.target.closest && event.target.closest('a.nav-link');
        if (!link) {
            return;
        }
        window.requestAnimationFrame(function () {
            link.scrollIntoView({block: 'nearest', inline: 'nearest', behavior: 'smooth'});
        });
    });
})();
