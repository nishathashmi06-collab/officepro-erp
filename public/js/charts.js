/* ==========================================================================
   OfficePro — Chart.js helpers (theme aware). Charts are declared in Blade
   with <canvas data-chart='{...json...}'> and rendered here.
   ========================================================================== */
(function () {
    'use strict';
    if (typeof Chart === 'undefined') return;

    const palette = {
        primary: '#6366f1', success: '#10b981', warning: '#f59e0b', danger: '#ef4444',
        info: '#0ea5e9', secondary: '#94a3b8', purple: '#8b5cf6', pink: '#ec4899', teal: '#14b8a6', orange: '#f97316',
    };
    const series = ['#6366f1', '#10b981', '#f59e0b', '#0ea5e9', '#ec4899', '#8b5cf6', '#14b8a6', '#ef4444', '#f97316', '#94a3b8'];
    const instances = [];

    function themeColors() {
        const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        return {
            text: dark ? '#8b95ad' : '#6b7489',
            grid: dark ? 'rgba(148,163,184,.10)' : 'rgba(15,23,42,.06)',
            surface: dark ? '#121a2c' : '#ffffff',
            tooltip: dark ? '#1f2a44' : '#0f172a',
        };
    }

    function color(name, i) { return palette[name] || name || series[i % series.length]; }
    function alpha(hex, a) {
        const n = parseInt(hex.slice(1), 16);
        return 'rgba(' + (n >> 16) + ',' + ((n >> 8) & 255) + ',' + (n & 255) + ',' + a + ')';
    }

    function build(canvas) {
        const cfg = JSON.parse(canvas.dataset.chart);
        const c = themeColors();
        const type = cfg.type || 'bar';
        const round = type === 'doughnut' || type === 'pie' || type === 'polarArea';
        const ctx = canvas.getContext('2d');

        const datasets = cfg.datasets.map(function (ds, i) {
            const base = color(ds.color, i);
            const out = Object.assign({}, ds);
            delete out.color;
            if (round) {
                out.backgroundColor = (ds.colors || cfg.labels.map(function (_, j) { return series[j % series.length]; })).map(function (x, j) { return color(x, j); });
                out.borderColor = c.surface;
                out.borderWidth = 3;
                out.hoverOffset = 6;
            } else if (type === 'line') {
                const g = ctx.createLinearGradient(0, 0, 0, canvas.parentElement.clientHeight || 280);
                g.addColorStop(0, alpha(base, .28));
                g.addColorStop(1, alpha(base, 0));
                out.borderColor = base;
                out.backgroundColor = ds.fill === false ? base : g;
                out.fill = ds.fill !== false;
                out.tension = .4;
                out.borderWidth = 2.5;
                out.pointRadius = 3;
                out.pointHoverRadius = 6;
                out.pointBackgroundColor = c.surface;
                out.pointBorderWidth = 2;
            } else {
                out.backgroundColor = base;
                out.hoverBackgroundColor = alpha(base, .85);
                out.borderRadius = 6;
                out.borderSkipped = false;
                out.maxBarThickness = cfg.stacked ? 34 : 26;
            }
            return out;
        });

        const money = cfg.money ? (canvas.dataset.currency || '') : null;
        const fmt = function (v) { return money !== null ? money + Number(v).toLocaleString(undefined, { maximumFractionDigits: 0 }) : Number(v).toLocaleString(); };

        const chart = new Chart(canvas, {
            type: type,
            data: { labels: cfg.labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 900, easing: 'easeOutQuart' },
                cutout: type === 'doughnut' ? '70%' : undefined,
                interaction: { mode: round ? 'nearest' : 'index', intersect: round },
                plugins: {
                    legend: {
                        display: cfg.legend !== false,
                        position: round ? 'bottom' : 'top',
                        align: round ? 'center' : 'end',
                        labels: { color: c.text, usePointStyle: true, pointStyle: 'circle', boxWidth: 8, boxHeight: 8, padding: 16, font: { size: 12 } },
                    },
                    tooltip: {
                        backgroundColor: c.tooltip, padding: 12, cornerRadius: 10, titleFont: { weight: '600' },
                        usePointStyle: true, boxPadding: 4,
                        callbacks: { label: function (item) { return ' ' + (item.dataset.label || item.label) + ': ' + fmt(item.raw); } },
                    },
                },
                scales: round ? {} : {
                    x: { stacked: !!cfg.stacked, grid: { display: false }, border: { display: false }, ticks: { color: c.text, font: { size: 11 } } },
                    y: {
                        stacked: !!cfg.stacked, beginAtZero: true, border: { display: false },
                        grid: { color: c.grid }, ticks: { color: c.text, font: { size: 11 }, precision: 0, callback: fmt, maxTicksLimit: 6 },
                    },
                },
            },
        });
        instances.push({ canvas: canvas, chart: chart });
    }

    function renderAll() {
        instances.splice(0).forEach(function (i) { i.chart.destroy(); });
        document.querySelectorAll('canvas[data-chart]').forEach(build);
    }

    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    renderAll();
    document.addEventListener('op:theme', renderAll);
})();
