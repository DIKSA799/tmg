import Chart from 'chart.js/auto';

const COLORS = {
    accent: '#ff3b41',
    blue: '#5b8cff',
    amber: '#ffb020',
    green: '#2fd07f',
    violet: '#a97bff',
    grey: '#8b8b93',
    teal: '#3fd0c9',
    rose: '#ff8fa3',
    grid: 'rgba(255, 255, 255, 0.07)',
    text: '#c6b9b9',
};

const SERIES = [COLORS.accent, COLORS.blue, COLORS.amber, COLORS.green, COLORS.violet, COLORS.teal, COLORS.rose, COLORS.grey];

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const charts = new Map();

const fmt = (value) => new Intl.NumberFormat('en-GB').format(Number(value ?? 0));

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[character]);
}

function paint(element, value, suffix) {
    element.textContent = suffix === '%' ? `${value.toFixed(1)}%` : fmt(Math.round(value));
}

function tween(element, from, to, suffix) {
    if (reducedMotion) {
        paint(element, to, suffix);
        return;
    }

    const duration = 900;
    const start = performance.now();

    const step = (now) => {
        const progress = Math.min(1, (now - start) / duration);
        const eased = 1 - (1 - progress) ** 3;
        paint(element, from + (to - from) * eased, suffix);

        if (progress < 1) {
            requestAnimationFrame(step);
        }
    };

    requestAnimationFrame(step);
}

function tooltip() {
    return {
        backgroundColor: 'rgba(10, 8, 8, 0.95)',
        borderColor: 'rgba(255, 255, 255, 0.14)',
        borderWidth: 1,
        padding: 10,
        cornerRadius: 10,
        titleColor: '#ffffff',
        bodyColor: COLORS.text,
        displayColors: true,
        usePointStyle: true,
    };
}

function axes({ horizontal = false } = {}) {
    if (horizontal) {
        return {
            x: { beginAtZero: true, grid: { color: COLORS.grid }, ticks: { color: COLORS.text, precision: 0 } },
            y: { grid: { display: false }, ticks: { color: COLORS.text } },
        };
    }

    return {
        x: { grid: { display: false }, ticks: { color: COLORS.text, maxRotation: 0, autoSkipPadding: 14 } },
        y: { beginAtZero: true, grid: { color: COLORS.grid }, ticks: { color: COLORS.text, precision: 0 } },
    };
}

function lineConfig(data) {
    const canvas = document.getElementById('chart-trend');
    const height = canvas?.parentElement?.clientHeight ?? 240;
    const gradient = canvas?.getContext('2d').createLinearGradient(0, 0, 0, height);

    gradient?.addColorStop(0, 'rgba(255, 59, 65, 0.45)');
    gradient?.addColorStop(1, 'rgba(255, 59, 65, 0)');

    return {
        type: 'line',
        data: {
            labels: data.series.labels,
            datasets: [{
                label: 'Records',
                data: data.series.values,
                borderColor: COLORS.accent,
                backgroundColor: gradient ?? 'rgba(255, 59, 65, 0.2)',
                borderWidth: 2,
                fill: true,
                tension: 0.38,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: COLORS.accent,
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 900, easing: 'easeOutQuart' },
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false }, tooltip: tooltip() },
            scales: axes(),
        },
    };
}

function doughnutConfig(labels, values, { cutout = '62%' } = {}) {
    const total = values.reduce((sum, value) => sum + value, 0);

    return {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: SERIES,
                borderColor: 'rgba(11, 9, 9, 0.92)',
                borderWidth: 2,
                hoverOffset: 10,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout,
            animation: { animateRotate: true, animateScale: true, duration: 900, easing: 'easeOutQuart' },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: COLORS.text, usePointStyle: true, pointStyle: 'circle', boxWidth: 8, boxHeight: 8, padding: 12 },
                },
                tooltip: {
                    ...tooltip(),
                    callbacks: {
                        label: (context) => {
                            const value = Number(context.parsed ?? 0);
                            const share = total === 0 ? 0 : (value / total) * 100;
                            return ` ${context.label}: ${fmt(value)} (${share.toFixed(1)}%)`;
                        },
                    },
                },
            },
        },
    };
}

function barConfig(labels, values, { horizontal = false, color = COLORS.accent } = {}) {
    return {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Records',
                data: values,
                backgroundColor: horizontal ? values.map((_, index) => SERIES[index % SERIES.length]) : color,
                borderRadius: 8,
                borderSkipped: false,
                maxBarThickness: 34,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: horizontal ? 'y' : 'x',
            animation: { duration: 800, easing: 'easeOutQuart' },
            plugins: { legend: { display: false }, tooltip: tooltip() },
            scales: axes({ horizontal }),
        },
    };
}

function renderChart(id, config) {
    const canvas = document.getElementById(id);
    if (!canvas) {
        return;
    }

    const existing = charts.get(id);

    if (existing) {
        existing.data.labels = config.data.labels;
        existing.data.datasets = config.data.datasets;
        existing.update();
        return;
    }

    charts.set(id, new Chart(canvas, config));
}

function renderKpis(kpis, fromZero) {
    document.querySelectorAll('[data-kpi]').forEach((element) => {
        const key = element.dataset.kpi;
        const suffix = element.dataset.suffix ?? '';
        const target = Number(kpis[key] ?? 0);
        const current = fromZero ? 0 : Number(String(element.textContent).replace(/[^0-9.]/g, '')) || 0;

        tween(element, current, target, suffix);
    });
}

function renderCoverage(items) {
    const list = document.querySelector('[data-coverage]');
    if (!list) {
        return;
    }

    list.innerHTML = items.map((item) => {
        const share = item.total > 0 ? (item.covered / item.total) * 100 : 0;

        return `<li>
            <div class="flex items-center justify-between gap-3 text-sm">
                <span class="font-semibold">${escapeHtml(item.label)}</span>
                <span class="num text-[color:var(--ink-mute)]">${fmt(item.covered)} <span class="opacity-60">/ ${fmt(item.total)}</span></span>
            </div>
            <div class="admin-progress mt-2"><span style="width: ${share.toFixed(1)}%"></span></div>
            <p class="mt-1.5 text-[0.7rem] text-[color:var(--ink-mute)]">${share.toFixed(1)}% of the register covered</p>
        </li>`;
    }).join('');
}

function renderLeaderboard(key, items) {
    const list = document.querySelector(`[data-list="${key}"]`);
    if (!list) {
        return;
    }

    if (items.length === 0) {
        list.innerHTML = '<li class="text-sm text-[color:var(--ink-mute)]">No records in this period.</li>';
        return;
    }

    const max = Math.max(...items.map((item) => item.total), 1);

    list.innerHTML = items.map((item, index) => `<li>
        <div class="flex items-center justify-between gap-3 text-sm">
            <span class="flex min-w-0 items-center gap-2">
                <span class="text-[0.65rem] font-bold text-[color:var(--ink-mute)]">${String(index + 1).padStart(2, '0')}</span>
                <span class="truncate">${escapeHtml(item.label)}</span>
            </span>
            <span class="num font-semibold">${fmt(item.total)}</span>
        </div>
        <div class="admin-progress mt-2"><span style="width: ${((item.total / max) * 100).toFixed(1)}%"></span></div>
    </li>`).join('');
}

function renderStates(rows) {
    const body = document.querySelector('[data-state-rows]');
    if (!body) {
        return;
    }

    if (rows.length === 0) {
        body.innerHTML = '<tr><td colspan="6" class="py-8 text-center text-[color:var(--ink-mute)]">No records in this period.</td></tr>';
        return;
    }

    const max = Math.max(...rows.map((row) => row.total), 1);
    const grand = rows.reduce((sum, row) => sum + row.total, 0) || 1;

    body.innerHTML = rows.map((row) => `<tr>
        <td>${escapeHtml(row.name)}</td>
        <td class="num">${fmt(row.total)}</td>
        <td class="num">${fmt(row.lgas)}</td>
        <td class="num">${fmt(row.wards)}</td>
        <td class="num">${fmt(row.units)}</td>
        <td style="min-width: 8rem;">
            <div class="admin-progress"><span style="width: ${((row.total / max) * 100).toFixed(1)}%"></span></div>
            <span class="text-[0.68rem] text-[color:var(--ink-mute)]">${((row.total / grand) * 100).toFixed(1)}% of period</span>
        </td>
    </tr>`).join('');
}

function render(data, { animateKpis = false } = {}) {
    renderKpis(data.kpis, animateKpis);

    renderChart('chart-trend', lineConfig(data));
    renderChart('chart-gender', doughnutConfig(data.gender.labels, data.gender.values));
    renderChart('chart-age', barConfig(data.age_bands.labels, data.age_bands.values));
    renderChart('chart-pvc', doughnutConfig(data.pvc.labels, data.pvc.values, { cutout: '58%' }));
    renderChart('chart-voter', doughnutConfig(data.voter_status.labels, data.voter_status.values, { cutout: '58%' }));
    renderChart('chart-lgas', barConfig(data.top_lgas.map((item) => item.label), data.top_lgas.map((item) => item.total), { horizontal: true }));
    renderChart('chart-languages', barConfig(data.languages.labels, data.languages.values, { color: COLORS.blue }));
    renderChart('chart-channels', barConfig(data.channels.labels, data.channels.values, { color: COLORS.amber }));

    renderCoverage(data.coverage);
    renderLeaderboard('top_wards', data.top_wards);
    renderLeaderboard('top_units', data.top_units);
    renderStates(data.by_state);

    const subtitle = document.querySelector('[data-trend-sub]');
    if (subtitle) {
        const total = data.series.values.reduce((sum, value) => sum + value, 0);
        subtitle.textContent = `${fmt(total)} records across the last ${data.range} days`;
    }

    document.querySelectorAll('[data-range]').forEach((button) => {
        button.classList.toggle('is-active', Number(button.dataset.range) === data.range);
    });
}

function setStatus(text) {
    const label = document.querySelector('[data-live-label]');
    if (label) {
        label.textContent = text;
    }
}

function boot() {
    const bootstrap = window.__ADMIN;
    if (!bootstrap) {
        return;
    }

    Chart.defaults.color = COLORS.text;
    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;

    let currentRange = bootstrap.analytics.range;

    render(bootstrap.analytics, { animateKpis: true });

    const load = async (range) => {
        const url = new URL(bootstrap.dataUrl, window.location.origin);
        url.searchParams.set('range', range);
        setStatus('Syncing…');

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) {
                throw new Error(String(response.status));
            }

            const payload = await response.json();
            currentRange = payload.data.range;
            render(payload.data);
            setStatus('Live');
            const stamp = document.querySelector('[data-live-stamp]');
            if (stamp) {
                stamp.textContent = new Date().toLocaleTimeString();
            }
        } catch {
            setStatus('Offline');
        }
    };

    document.querySelectorAll('[data-range]').forEach((button) => {
        button.addEventListener('click', () => load(Number(button.dataset.range)));
    });

    document.querySelector('[data-refresh]')?.addEventListener('click', () => load(currentRange));

    window.setInterval(() => load(currentRange), 60000);
}

boot();
