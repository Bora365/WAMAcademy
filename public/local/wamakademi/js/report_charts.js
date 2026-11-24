/**
 * WAM İlerleme raporu — Tablo / Pasta / İleri grafik + seri alt döngüsü.
 */
(function () {
    'use strict';

    var SERIES = ['bars', 'line', 'both'];
    var COLORS = {
        assigned: '#94a3b8',
        active: '#2563eb',
        completed: '#15803d',
        barA: '#2563eb',
        barB: '#f97316',
        lineA: '#16a34a',
    };

    function $(sel, root) {
        return (root || document).querySelector(sel);
    }

    function $all(sel, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    }

    function parseData(root) {
        var raw = root.getAttribute('data-chart');
        if (!raw) {
            return null;
        }
        try {
            return JSON.parse(raw);
        } catch (e) {
            return null;
        }
    }

    function shortLabel(text, max) {
        max = max || 22;
        text = String(text || '');
        if (text.length <= max) {
            return text;
        }
        return text.slice(0, max - 1) + '…';
    }

    function drawPie(canvas, slices) {
        var ctx = canvas.getContext('2d');
        var dpr = window.devicePixelRatio || 1;
        var cssW = canvas.clientWidth || 420;
        var cssH = canvas.clientHeight || 300;
        canvas.width = Math.floor(cssW * dpr);
        canvas.height = Math.floor(cssH * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        ctx.clearRect(0, 0, cssW, cssH);

        var total = slices.reduce(function (s, x) { return s + (x.value || 0); }, 0);
        if (total <= 0) {
            ctx.fillStyle = '#64748b';
            ctx.font = '14px system-ui,sans-serif';
            ctx.fillText('Veri yok', 24, 40);
            return;
        }

        var cx = cssW * 0.42;
        var cy = cssH * 0.48;
        var r = Math.min(cssW, cssH) * 0.34;
        var angle = -Math.PI / 2;

        slices.forEach(function (slice) {
            var portion = (slice.value || 0) / total;
            var next = angle + portion * Math.PI * 2;
            ctx.beginPath();
            ctx.moveTo(cx, cy);
            ctx.arc(cx, cy, r, angle, next);
            ctx.closePath();
            ctx.fillStyle = slice.color || COLORS.active;
            ctx.fill();
            angle = next;
        });

        ctx.beginPath();
        ctx.arc(cx, cy, r * 0.48, 0, Math.PI * 2);
        ctx.fillStyle = '#fff';
        ctx.fill();

        var lx = cssW * 0.72;
        var ly = Math.max(28, cy - slices.length * 14);
        ctx.font = '12px system-ui,sans-serif';
        slices.forEach(function (slice, i) {
            var y = ly + i * 24;
            ctx.fillStyle = slice.color || COLORS.active;
            ctx.fillRect(lx, y - 9, 11, 11);
            ctx.fillStyle = '#0f2747';
            ctx.fillText(slice.label + ' (' + slice.value + ')', lx + 18, y);
        });
    }

    function renderPieList(listEl, titleEl, data) {
        if (titleEl && data.listtitle) {
            titleEl.textContent = data.listtitle;
        }
        if (!listEl) {
            return;
        }
        listEl.innerHTML = '';
        var items = data.list || [];
        if (!items.length) {
            var empty = document.createElement('li');
            empty.className = 'wam-report__pie-list-empty';
            empty.textContent = '—';
            listEl.appendChild(empty);
            return;
        }
        items.forEach(function (item) {
            var li = document.createElement('li');
            li.className = 'wam-report__pie-list-item';

            var top = document.createElement('div');
            top.className = 'wam-report__pie-list-top';

            var name = document.createElement('strong');
            name.className = 'wam-report__pie-list-name';
            name.textContent = item.name || '';
            name.title = item.name || '';
            top.appendChild(name);

            if (item.progress !== null && item.progress !== undefined) {
                var pct = document.createElement('span');
                pct.className = 'wam-report__pie-list-pct';
                pct.textContent = String(item.progress) + '%';
                top.appendChild(pct);
            } else if (item.status) {
                var st = document.createElement('span');
                st.className = 'wam-report__pie-list-pct';
                st.textContent = item.status;
                top.appendChild(st);
            }
            li.appendChild(top);

            if (item.progress !== null && item.progress !== undefined) {
                var bar = document.createElement('div');
                bar.className = 'wam-report__pie-list-bar';
                bar.setAttribute('aria-hidden', 'true');
                var fill = document.createElement('span');
                fill.style.width = Math.max(0, Math.min(100, item.progress || 0)) + '%';
                fill.style.background = item.color || COLORS.active;
                bar.appendChild(fill);
                li.appendChild(bar);
            }

            if (item.detail) {
                var detail = document.createElement('span');
                detail.className = 'wam-report__pie-list-detail';
                detail.textContent = item.detail;
                li.appendChild(detail);
            }

            listEl.appendChild(li);
        });
    }

    /**
     * Yatay ileri grafik: bars | line | both.
     */
    function drawAdvancedHorizontal(canvas, payload, seriesMode) {
        var ctx = canvas.getContext('2d');
        var dpr = window.devicePixelRatio || 1;
        var cats = payload.categories || [];
        var barA = payload.barA || [];
        var barB = payload.barB || [];
        var lineA = payload.lineA || [];
        var n = cats.length;
        var rowH = 36;
        var pad = { t: 36, r: 72, b: 48, l: 168 };
        var cssW = canvas.clientWidth || 720;
        var cssH = Math.max(220, pad.t + pad.b + Math.max(1, n) * rowH);

        canvas.style.height = cssH + 'px';
        canvas.width = Math.floor(cssW * dpr);
        canvas.height = Math.floor(cssH * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        ctx.clearRect(0, 0, cssW, cssH);

        if (!n) {
            ctx.fillStyle = '#64748b';
            ctx.font = '14px system-ui,sans-serif';
            ctx.fillText('Veri yok', 24, 40);
            return;
        }

        var plotW = cssW - pad.l - pad.r;
        var plotH = cssH - pad.t - pad.b;
        var showBars = seriesMode === 'bars' || seriesMode === 'both';
        var showLine = seriesMode === 'line' || seriesMode === 'both';

        var maxBar = 1;
        for (var i = 0; i < n; i++) {
            maxBar = Math.max(maxBar, (barA[i] || 0) + (barB[i] || 0), barA[i] || 0);
        }
        maxBar = Math.ceil(maxBar * 1.1) || 1;

        // Grid + scales
        ctx.strokeStyle = '#e2e8f0';
        ctx.lineWidth = 1;
        ctx.fillStyle = '#64748b';
        ctx.font = '11px system-ui,sans-serif';
        for (var g = 0; g <= 4; g++) {
            var gx = pad.l + plotW * (g / 4);
            ctx.beginPath();
            ctx.moveTo(gx, pad.t);
            ctx.lineTo(gx, pad.t + plotH);
            ctx.stroke();
            ctx.textAlign = 'center';
            if (showBars) {
                var barVal = Math.round(maxBar * (g / 4) * 10) / 10;
                ctx.fillText(String(barVal), gx, pad.t - 12);
            }
            if (showLine && !showBars) {
                ctx.fillText(Math.round(100 * (g / 4)) + '%', gx, pad.t - 12);
            }
            if (showLine && showBars) {
                ctx.fillText(Math.round(100 * (g / 4)) + '%', gx, cssH - 16);
            }
        }

        var barH = Math.min(18, rowH * 0.45);

        for (var j = 0; j < n; j++) {
            var cy = pad.t + rowH * (j + 0.5);

            // Y label
            ctx.fillStyle = '#0f2747';
            ctx.font = '12px system-ui,sans-serif';
            ctx.textAlign = 'right';
            ctx.textBaseline = 'middle';
            ctx.fillText(shortLabel(cats[j], 24), pad.l - 12, cy);

            if (showBars) {
                var a = barA[j] || 0;
                var b = barB[j] || 0;
                var wA = (a / maxBar) * plotW;
                var wB = (b / maxBar) * plotW;
                var by = cy - barH / 2;
                ctx.fillStyle = COLORS.barA;
                ctx.fillRect(pad.l, by, Math.max(0, wA), barH);
                ctx.fillStyle = COLORS.barB;
                ctx.fillRect(pad.l + wA, by, Math.max(0, wB), barH);
            }

            if (showLine) {
                var pct = Math.max(0, Math.min(100, lineA[j] || 0));
                var px = pad.l + (pct / 100) * plotW;
                ctx.strokeStyle = COLORS.lineA;
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(pad.l, cy);
                ctx.lineTo(px, cy);
                ctx.stroke();
                ctx.beginPath();
                ctx.fillStyle = COLORS.lineA;
                ctx.arc(px, cy, 5, 0, Math.PI * 2);
                ctx.fill();
                ctx.strokeStyle = '#fff';
                ctx.lineWidth = 1.5;
                ctx.stroke();
                ctx.fillStyle = '#0f2747';
                ctx.font = '11px system-ui,sans-serif';
                ctx.textAlign = 'left';
                ctx.textBaseline = 'middle';
                ctx.fillText(Math.round(pct) + '%', px + 10, cy);
            }
        }

        // Legend
        var legend = payload.legend || [];
        var filtered = legend.filter(function (item) {
            if (item.type === 'bar') {
                return showBars;
            }
            if (item.type === 'line') {
                return showLine;
            }
            return true;
        });
        var lx = pad.l;
        var ly = cssH - 18;
        if (showBars && showLine) {
            ly = cssH - 32;
        }
        ctx.font = '12px system-ui,sans-serif';
        ctx.textBaseline = 'alphabetic';
        filtered.forEach(function (item) {
            ctx.fillStyle = item.color;
            if (item.type === 'line') {
                ctx.fillRect(lx, ly - 5, 16, 3);
                ctx.beginPath();
                ctx.arc(lx + 8, ly - 3.5, 3, 0, Math.PI * 2);
                ctx.fill();
            } else {
                ctx.fillRect(lx, ly - 10, 12, 12);
            }
            ctx.fillStyle = '#0f2747';
            ctx.textAlign = 'left';
            ctx.fillText(item.label, lx + 18, ly);
            lx += ctx.measureText(item.label).width + 36;
        });
    }

    function seriesHint(mode, labels) {
        if (mode === 'bars') {
            return labels.toLine;
        }
        if (mode === 'line') {
            return labels.toBoth;
        }
        return labels.toBars;
    }

    function init(root) {
        var data = parseData(root);
        if (!data) {
            return;
        }

        var viewBtns = $all('[data-action="set-view"]', root);
        var seriesBtn = $('[data-action="cycle-series"]', root);
        var tablePanel = $('[data-panel="table"]', root);
        var chartPanel = $('[data-panel="chart"]', root);
        var pieLayout = $('[data-pie-layout]', root);
        var advancedWrap = $('[data-advanced-wrap]', root);
        var pieCanvas = $('[data-chart-canvas-pie]', root);
        var advCanvas = $('[data-chart-canvas-advanced]', root);
        var titleEl = $('[data-chart-title]', root);
        var listEl = $('[data-pie-list]', root);
        var listTitleEl = $('[data-pie-list-title]', root);

        if (!viewBtns.length || !tablePanel || !chartPanel) {
            return;
        }

        var seriesLabels = {
            bars: (seriesBtn && seriesBtn.getAttribute('data-label-bars')) || 'Sütun',
            line: (seriesBtn && seriesBtn.getAttribute('data-label-line')) || 'Çizgi',
            both: (seriesBtn && seriesBtn.getAttribute('data-label-both')) || 'Birlikte',
            toBars: (seriesBtn && seriesBtn.getAttribute('data-next-bars')) || 'Sütun',
            toLine: (seriesBtn && seriesBtn.getAttribute('data-next-line')) || 'Çizgi',
            toBoth: (seriesBtn && seriesBtn.getAttribute('data-next-both')) || 'Birlikte',
        };

        var view = 'table';
        var seriesIdx = 0;

        function setActiveViewButtons() {
            viewBtns.forEach(function (btn) {
                var on = btn.getAttribute('data-view') === view;
                btn.classList.toggle('is-active', on);
                btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
        }

        function render() {
            setActiveViewButtons();

            if (view === 'table') {
                tablePanel.hidden = false;
                chartPanel.hidden = true;
                if (seriesBtn) {
                    seriesBtn.hidden = true;
                }
                return;
            }

            tablePanel.hidden = true;
            chartPanel.hidden = false;

            if (view === 'pie') {
                var pieLabelBtn = viewBtns.filter(function (b) {
                    return b.getAttribute('data-view') === 'pie';
                })[0];
                if (titleEl) {
                    titleEl.textContent = (pieLabelBtn && pieLabelBtn.textContent.trim()) || 'Pasta grafik';
                }
                if (seriesBtn) {
                    seriesBtn.hidden = true;
                }
                if (pieLayout) {
                    pieLayout.hidden = false;
                }
                if (advancedWrap) {
                    advancedWrap.hidden = true;
                }
                if (pieCanvas) {
                    drawPie(pieCanvas, data.pie || []);
                }
                renderPieList(listEl, listTitleEl, data);
                return;
            }

            // advanced
            var advLabelBtn = viewBtns.filter(function (b) {
                return b.getAttribute('data-view') === 'advanced';
            })[0];
            if (titleEl) {
                titleEl.textContent = (advLabelBtn && advLabelBtn.textContent.trim()) || 'İleri grafik';
            }
            if (pieLayout) {
                pieLayout.hidden = true;
            }
            if (advancedWrap) {
                advancedWrap.hidden = false;
            }
            if (seriesBtn) {
                seriesBtn.hidden = false;
                var sm = SERIES[seriesIdx];
                seriesBtn.setAttribute('data-series', sm);
                seriesBtn.textContent = seriesHint(sm, seriesLabels);
                seriesBtn.setAttribute('aria-label', seriesHint(sm, seriesLabels));
            }
            if (advCanvas) {
                drawAdvancedHorizontal(advCanvas, data.combo || {}, SERIES[seriesIdx]);
            }
        }

        viewBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                view = btn.getAttribute('data-view') || 'table';
                render();
            });
        });

        if (seriesBtn) {
            seriesBtn.addEventListener('click', function () {
                seriesIdx = (seriesIdx + 1) % SERIES.length;
                render();
            });
        }

        window.addEventListener('resize', function () {
            if (view !== 'table') {
                render();
            }
        });

        render();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-region="wam-report-charts"]').forEach(init);
    });
})();
