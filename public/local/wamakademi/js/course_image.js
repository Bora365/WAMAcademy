/**
 * Eğitim görseli önizlemesi: Eğitimler kartındaki kapakla aynı kırpım.
 */
(function () {
    'use strict';

    function frame() {
        return document.querySelector('[data-wam-course-image]');
    }

    function manager() {
        return document.getElementById('id_overviewfiles_filemanager')
            || document.querySelector('[data-fieldtype="filemanager"] .filemanager');
    }

    function imageUrl(root) {
        if (!root) {
            return '';
        }
        var img = root.querySelector('.fp-thumbnail img, img.realpreview, .fp-filename-icon img');
        if (img && img.getAttribute('src')) {
            return img.src;
        }
        var painted = root.querySelector('.fp-thumbnail');
        if (painted) {
            var bg = window.getComputedStyle(painted).backgroundImage || '';
            var match = bg.match(/url\(["']?([^"')]+)["']?\)/);
            if (match) {
                return match[1];
            }
        }
        return '';
    }

    function paint() {
        var box = frame();
        var root = manager();
        if (!box) {
            return;
        }
        var img = box.querySelector('img');
        var empty = box.querySelector('[data-wam-course-image-empty]');
        var url = imageUrl(root);
        if (url) {
            img.src = url;
            img.hidden = false;
            if (empty) {
                empty.hidden = true;
            }
        } else {
            img.removeAttribute('src');
            img.hidden = true;
            if (empty) {
                empty.hidden = false;
            }
        }
    }

    function watch() {
        paint();
        var root = manager();
        if (!root || root.getAttribute('data-wam-image-watch') === '1' || typeof MutationObserver === 'undefined') {
            return;
        }
        root.setAttribute('data-wam-image-watch', '1');
        var observer = new MutationObserver(paint);
        observer.observe(root, {childList: true, subtree: true, attributes: true, attributeFilter: ['src', 'style']});
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', watch);
    } else {
        watch();
    }
    window.setTimeout(watch, 600);
    window.setTimeout(watch, 1600);
})();
