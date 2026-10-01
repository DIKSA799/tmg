const VIEW_HEIGHT = 900;
const PADDING = 6;
const NO_DATA_FILL = 'rgba(255, 255, 255, 0.09)';
const LOW_RGB = [74, 17, 22];
const HIGH_RGB = [255, 59, 65];

const numberFormat = new Intl.NumberFormat('en-GB');
const cache = new Map();

/**
 * Mirrors Str::slug() on the server so boundary names and database names hash
 * to the same key.
 */
function slug(value) {
    return String(value ?? '')
        .normalize('NFD')
        .replace(/\p{Diacritic}/gu, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function escapeAttr(value) {
    return String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[character]);
}

function eachRing(geometry, callback) {
    if (geometry.type === 'Polygon') {
        geometry.coordinates.forEach(callback);
    } else if (geometry.type === 'MultiPolygon') {
        geometry.coordinates.forEach((polygon) => polygon.forEach(callback));
    }
}

function projector(features) {
    let minLon = Infinity;
    let minLat = Infinity;
    let maxLon = -Infinity;
    let maxLat = -Infinity;

    features.forEach((feature) => eachRing(feature.geometry, (ring) => ring.forEach(([lon, lat]) => {
        if (lon < minLon) minLon = lon;
        if (lon > maxLon) maxLon = lon;
        if (lat < minLat) minLat = lat;
        if (lat > maxLat) maxLat = lat;
    })));

    const kx = Math.cos((((minLat + maxLat) / 2) * Math.PI) / 180);
    const spanX = (maxLon - minLon) * kx || 1;
    const spanY = maxLat - minLat || 1;
    const scale = (VIEW_HEIGHT - PADDING * 2) / spanY;

    return {
        width: spanX * scale + PADDING * 2,
        height: VIEW_HEIGHT,
        project: ([lon, lat]) => [
            PADDING + (lon - minLon) * kx * scale,
            PADDING + (maxLat - lat) * scale,
        ],
    };
}

function pathFor(geometry, project) {
    let path = '';

    eachRing(geometry, (ring) => {
        ring.forEach((point, index) => {
            const [x, y] = project(point);
            path += `${index === 0 ? 'M' : 'L'}${x.toFixed(1)},${y.toFixed(1)}`;
        });
        path += 'Z';
    });

    return path;
}

function fillFor(value, max) {
    if (value <= 0) {
        return NO_DATA_FILL;
    }

    const ratio = Math.sqrt(value / Math.max(1, max));
    const channel = (index) => Math.round(LOW_RGB[index] + (HIGH_RGB[index] - LOW_RGB[index]) * ratio);

    return `rgb(${channel(0)}, ${channel(1)}, ${channel(2)})`;
}

function countFor(feature, level, heat) {
    // heat is the whole __GEO_MAP payload: { states: {...}, lgas: {...} }.
    const index = (level === 'lgas' ? heat.lgas : heat.states) ?? {};
    const names = feature.properties.db ?? [];
    const scope = level === 'lgas' ? `${slug(feature.properties.state ?? '')}|` : '';

    return names.reduce((total, name) => total + Number(index[scope + slug(name)] ?? 0), 0);
}

function buildSvg(features, level, heat, total) {
    const projection = projector(features);
    const counts = features.map((feature) => countFor(feature, level, heat));
    const max = Math.max(1, ...counts);

    const shapes = features.map((feature, index) => {
        const value = counts[index];
        const share = total > 0 ? ((value / total) * 100).toFixed(1) : '0.0';

        return `<path
            d="${pathFor(feature.geometry, projection.project)}"
            fill="${fillFor(value, max)}"
            data-geo-name="${escapeAttr(feature.properties.name)}"
            data-geo-state="${escapeAttr(feature.properties.state ?? '')}"
            data-geo-value="${value}"
            data-geo-share="${share}"
        />`;
    }).join('');

    return {
        max,
        markup: `<svg viewBox="0 0 ${projection.width.toFixed(0)} ${projection.height}" role="img" aria-label="Registration heat map of Nigeria by ${level === 'lgas' ? 'local government area' : 'state'}">${shapes}</svg>`,
    };
}

function renderLegend(element, max) {
    if (!element) {
        return;
    }

    const steps = [0, 0.25, 0.5, 0.75, 1]
        .map((step) => fillFor(Math.round(max * step * step), max))
        .map((color) => `<span style="background:${color}"></span>`)
        .join('');

    element.innerHTML = `
        <span class="geo-map-legend-label">0</span>
        <span class="geo-map-scale">${steps}</span>
        <span class="geo-map-legend-label">${numberFormat.format(max)}</span>
        <span class="geo-map-legend-empty"><i style="background:${NO_DATA_FILL}"></i> no records</span>
    `;
}

export function initGeoMap() {
    const root = document.querySelector('[data-geo-map]');
    const canvas = root?.querySelector('[data-geo-canvas]');

    if (!root || !canvas) {
        return;
    }

    const legend = root.querySelector('[data-geo-legend]');
    const status = document.querySelector('[data-geo-status]');
    const heat = window.__GEO_MAP ?? {};
    const sources = { states: root.dataset.states, lgas: root.dataset.lgas };
    const buttons = Array.from(root.parentElement?.querySelectorAll('[data-map-level]') ?? []);

    const tip = document.createElement('div');
    tip.className = 'geo-tip';
    tip.hidden = true;
    root.appendChild(tip);

    let level = 'states';

    const say = (text) => {
        if (status) {
            status.textContent = text;
        }
    };

    async function load(nextLevel) {
        if (cache.has(nextLevel)) {
            return cache.get(nextLevel);
        }

        const response = await fetch(sources[nextLevel], { headers: { Accept: 'application/json' } });

        if (!response.ok) {
            throw new Error(String(response.status));
        }

        const data = await response.json();
        cache.set(nextLevel, data.features ?? []);

        return cache.get(nextLevel);
    }

    function hideTip() {
        tip.hidden = true;
    }

    function bindTooltip() {
        canvas.addEventListener('pointermove', (event) => {
            const path = event.target.closest('path[data-geo-name]');

            if (!path) {
                hideTip();
                return;
            }

            const state = path.dataset.geoState;
            const value = Number(path.dataset.geoValue ?? 0);
            const share = path.dataset.geoShare ?? '0.0';

            tip.innerHTML = `<strong>${escapeAttr(path.dataset.geoName)}</strong>
                ${state ? `<span class="geo-tip-state">${escapeAttr(state)} State</span>` : ''}
                <span class="geo-tip-value">${numberFormat.format(value)} record${value === 1 ? '' : 's'}</span>
                <span class="geo-tip-share">${share}% of the register</span>`;

            const bounds = root.getBoundingClientRect();
            tip.hidden = false;
            tip.style.left = `${Math.min(event.clientX - bounds.left + 14, bounds.width - tip.offsetWidth - 8)}px`;
            tip.style.top = `${Math.max(event.clientY - bounds.top - tip.offsetHeight - 12, 8)}px`;
        });

        canvas.addEventListener('pointerleave', hideTip);
        canvas.addEventListener('click', hideTip);
    }

    async function show(nextLevel) {
        level = nextLevel;

        buttons.forEach((button) => button.classList.toggle('is-active', button.dataset.mapLevel === level));

        try {
            const features = await load(level);
            const total = features.reduce((sum, feature) => sum + countFor(feature, level, heat), 0);
            const { max, markup } = buildSvg(features, level, heat, total);

            canvas.innerHTML = markup;
            renderLegend(legend, max);

            const covered = features.filter((feature) => countFor(feature, level, heat) > 0).length;

            say(`${numberFormat.format(covered)} of ${numberFormat.format(features.length)} ${level === 'lgas' ? 'LGAs' : 'states'} have registrations · ${numberFormat.format(total)} records mapped.`);
        } catch {
            say('Could not load the boundary data. Please refresh.');
        }
    }

    buttons.forEach((button) => button.addEventListener('click', () => {
        if (button.dataset.mapLevel !== level) {
            hideTip();
            show(button.dataset.mapLevel);
        }
    }));

    bindTooltip();
    show(level);
}
