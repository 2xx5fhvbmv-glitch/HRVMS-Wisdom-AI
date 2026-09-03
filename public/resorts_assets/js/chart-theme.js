/*
 * WaiChart — shared Chart.js theming helper (Phase 3 of dark mode).
 *
 * Canvas is a bitmap: Chart.js's fillStyle/strokeStyle values are handed
 * straight to Canvas 2D, which never resolves CSS custom properties (a
 * fillStyle of "var(--teal)" is simply invalid and silently ignored). So
 * charts can't theme via CSS the way the rest of the app now does — they
 * need their colours read from the live SSOT tokens in JS, at the moment
 * each chart is built AND again every time the theme changes.
 *
 * Usage per chart, at the point you'd normally call `new Chart(ctx, cfg)`:
 *
 *   var p = WaiChart.palette();
 *   var cfg = { type: 'bar', data: { datasets: [{
 *       backgroundColor: p.ramp[0], borderColor: p.ramp[0]
 *   }] }, options: {...} };
 *   var chart = new Chart(ctx, cfg);
 *   WaiChart.registerForTheme(chart, function (chart, p) {
 *       chart.data.datasets[0].backgroundColor = p.ramp[0];
 *       chart.data.datasets[0].borderColor = p.ramp[0];
 *   });
 *
 * The recolor callback is deliberately per-chart, not automatic — every
 * chart's dataset shape is different (a doughnut's backgroundColor is an
 * array of per-slice colours, a line chart's is one flat colour, a
 * "series 2 is always red because it's the over-budget line" chart needs
 * a semantic token, not a ramp slot) — only the chart's own migration
 * knows which of its colour properties map to which role.
 */
(function () {
    'use strict';

    function cssVar(name, fallback) {
        var v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        return v || fallback || '';
    }

    // Categorical ramp — six roles built entirely from tokens the SSOT
    // already defines (no new tokens added this phase): teal, aqua,
    // positive/ok, warning/brass, muted/grey, rejected (a genuine muted
    // brick-red — the closest existing token to "oxblood").
    function palette() {
        return {
            ramp: [
                cssVar('--teal', '#014653'),
                cssVar('--aqua', '#2EACB3'),
                cssVar('--positive', '#1F9D6B'),
                cssVar('--warning', '#D98A00'),
                cssVar('--muted', '#5D6F75'),
                cssVar('--rejected', '#C23A3A')
            ],
            teal: cssVar('--teal', '#014653'),
            tealSoft: cssVar('--teal-soft', '#F5F8F8'),
            aqua: cssVar('--aqua', '#2EACB3'),
            faint: cssVar('--faint', '#93A4A9'),
            ink: cssVar('--ink', '#14232A'),
            darkblack: cssVar('--darkblack', '#222222'),
            card: cssVar('--card', '#FFFFFF'),
            lime: cssVar('--lime', '#E0FF02'),
            positive: cssVar('--positive', '#1F9D6B'),
            positiveBg: cssVar('--positive-bg', '#E9F7F0'),
            warning: cssVar('--warning', '#D98A00'),
            warningBg: cssVar('--warning-bg', '#FBF0DC'),
            critical: cssVar('--critical', '#FF2400'),
            criticalBg: cssVar('--critical-bg', '#FFDED9'),
            error: cssVar('--error', '#E5573F'),
            errorBg: cssVar('--error-bg', '#FDEEEB'),
            rejected: cssVar('--rejected', '#C23A3A'),
            neutralBg: cssVar('--neutral-bg', '#DEDEDE'),
            // Axis/grid/label/tooltip — the colours that actually make a
            // chart LOOK dark, not just its bars: gridlines, tick labels,
            // titles, tooltip surface.
            grid: cssVar('--line', '#E2EBEC'),
            lineSoft: cssVar('--line-2', '#EEF4F4'),
            ticks: cssVar('--muted', '#5D6F75'),
            title: cssVar('--ink', '#14232A'),
            tooltipBg: cssVar('--ink', '#14232A'),
            tooltipText: cssVar('--card', '#FFFFFF')
        };
    }

    function applyDefaults() {
        if (!window.Chart) return;
        var p = palette();
        Chart.defaults.color = p.ticks;
        Chart.defaults.borderColor = p.grid;
        Chart.defaults.plugins = Chart.defaults.plugins || {};
        Chart.defaults.plugins.tooltip = Chart.defaults.plugins.tooltip || {};
        Chart.defaults.plugins.tooltip.backgroundColor = p.tooltipBg;
        Chart.defaults.plugins.tooltip.titleColor = p.tooltipText;
        Chart.defaults.plugins.tooltip.bodyColor = p.tooltipText;
        Chart.defaults.scale = Chart.defaults.scale || {};
        Chart.defaults.scale.grid = Chart.defaults.scale.grid || {};
        Chart.defaults.scale.grid.color = p.grid;
        Chart.defaults.scale.ticks = Chart.defaults.scale.ticks || {};
        Chart.defaults.scale.ticks.color = p.ticks;
    }

    var registry = [];

    // recolor(chart, palette) — optional. Called on every retheme with a
    // fresh palette so the caller can reassign whichever of its own
    // dataset colour properties need it. Omit it if a chart only needs
    // the shared axis/grid/tooltip retheming applied below.
    function registerForTheme(chart, recolor) {
        registry.push({ chart: chart, recolor: recolor });
    }

    function retheme() {
        applyDefaults();
        var p = palette();
        registry = registry.filter(function (entry) {
            return entry.chart && !entry.chart.destroyed && entry.chart.canvas && entry.chart.canvas.isConnected !== false;
        });
        registry.forEach(function (entry) {
            var chart = entry.chart;
            try {
                if (entry.recolor) entry.recolor(chart, p);

                if (chart.options && chart.options.scales) {
                    Object.keys(chart.options.scales).forEach(function (axisKey) {
                        var axis = chart.options.scales[axisKey];
                        if (!axis) return;
                        if (axis.grid) axis.grid.color = p.grid;
                        if (axis.ticks) axis.ticks.color = p.ticks;
                        if (axis.title) axis.title.color = p.title;
                        if (axis.angleLines) axis.angleLines.color = p.grid;
                        if (axis.pointLabels) axis.pointLabels.color = p.ticks;
                    });
                }
                if (chart.options && chart.options.plugins) {
                    if (chart.options.plugins.tooltip) {
                        chart.options.plugins.tooltip.backgroundColor = p.tooltipBg;
                        chart.options.plugins.tooltip.titleColor = p.tooltipText;
                        chart.options.plugins.tooltip.bodyColor = p.tooltipText;
                    }
                    if (chart.options.plugins.legend) {
                        chart.options.plugins.legend.labels = chart.options.plugins.legend.labels || {};
                        chart.options.plugins.legend.labels.color = p.ticks;
                    }
                }
                chart.update();
            } catch (e) {
                if (window.console) console.warn('WaiChart: retheme failed for one chart, continuing', e);
            }
        });
    }

    document.addEventListener('wai:theme-change', retheme);

    // Fallback: catches any path that sets data-theme without going
    // through the picker's apply() (e.g. a future entry point), so charts
    // never silently miss a theme switch.
    if (window.MutationObserver) {
        new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                if (mutations[i].attributeName === 'data-theme') { retheme(); break; }
            }
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    }

    applyDefaults();

    window.WaiChart = {
        palette: palette,
        registerForTheme: registerForTheme,
        retheme: retheme
    };
})();
