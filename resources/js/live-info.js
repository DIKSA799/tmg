const WEATHER_CACHE_KEY = 'tmg.abuja.weather';
const WEATHER_CACHE_TTL = 900000;
const WEATHER_URL = 'https://api.open-meteo.com/v1/forecast?latitude=9.0765&longitude=7.3986&current=temperature_2m,apparent_temperature,weather_code&daily=temperature_2m_max,temperature_2m_min,precipitation_probability_max&timezone=Africa%2FLagos&forecast_days=1';

const WEATHER_CODES = {
    0: 'Clear', 1: 'Mainly clear', 2: 'Partly cloudy', 3: 'Overcast',
    45: 'Fog', 48: 'Rime fog', 51: 'Light drizzle', 53: 'Drizzle', 55: 'Heavy drizzle',
    61: 'Light rain', 63: 'Rain', 65: 'Heavy rain', 80: 'Rain showers', 81: 'Rain showers',
    82: 'Heavy showers', 95: 'Thunderstorm', 96: 'Thunderstorm', 99: 'Thunderstorm',
};

function readCache() {
    try {
        const cached = JSON.parse(window.sessionStorage.getItem(WEATHER_CACHE_KEY) ?? 'null');
        return cached && typeof cached.t === 'number' && Date.now() - cached.t < WEATHER_CACHE_TTL ? cached.d : null;
    } catch {
        return null;
    }
}

function writeCache(data) {
    try {
        window.sessionStorage.setItem(WEATHER_CACHE_KEY, JSON.stringify({ t: Date.now(), d: data }));
    } catch {
        // Storage can be unavailable in private browsing; the weather still renders.
    }
}

function renderWeather(valueEl, minorEl, data) {
    const current = data?.current ?? {};
    const daily = data?.daily ?? {};

    if (!Number.isFinite(current.temperature_2m)) {
        throw new Error('weather');
    }

    const condition = WEATHER_CODES[current.weather_code] ?? 'Current conditions';
    valueEl.textContent = `${Math.round(current.temperature_2m)}°C · ${condition}`;

    const high = daily.temperature_2m_max?.[0];
    const low = daily.temperature_2m_min?.[0];
    const rain = daily.precipitation_probability_max?.[0];
    const parts = [];

    if (Number.isFinite(current.apparent_temperature)) {
        parts.push(`Feels ${Math.round(current.apparent_temperature)}°`);
    }
    if (Number.isFinite(high)) {
        parts.push(`H ${Math.round(high)}°`);
    }
    if (Number.isFinite(low)) {
        parts.push(`L ${Math.round(low)}°`);
    }
    if (Number.isFinite(rain)) {
        parts.push(`Rain ${rain}%`);
    }

    minorEl.textContent = parts.join(' · ') || 'Abuja, Nigeria';
}

function initClock() {
    const clock = document.querySelector('[data-clock]');
    if (clock === null) {
        return;
    }

    const date = document.querySelector('[data-date]');

    const tick = () => {
        const now = new Date();
        clock.textContent = new Intl.DateTimeFormat('en-GB', {
            timeZone: 'Africa/Lagos', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true,
        }).format(now);

        if (date !== null) {
            const label = new Intl.DateTimeFormat('en-GB', {
                timeZone: 'Africa/Lagos', weekday: 'short', day: '2-digit', month: 'short', year: 'numeric',
            }).format(now);

            date.textContent = `${label.toUpperCase()} · ABUJA`;
        }
    };

    tick();
    window.setInterval(tick, 1000);
}

function initWeather() {
    const value = document.querySelector('[data-weather]');
    const minor = document.querySelector('[data-weather-minor]');

    if (value === null || minor === null) {
        return;
    }

    const cached = readCache();
    if (cached !== null) {
        try {
            renderWeather(value, minor, cached);
            return;
        } catch {
            // Fall through and refetch when the cached payload is unusable.
        }
    }

    fetch(WEATHER_URL, { headers: { Accept: 'application/json' } })
        .then((response) => (response.ok ? response.json() : Promise.reject(new Error('weather'))))
        .then((data) => {
            writeCache(data);
            renderWeather(value, minor, data);
        })
        .catch(() => {
            value.textContent = 'Weather temporarily unavailable';
            minor.textContent = 'Abuja, Nigeria';
        });
}

export function initLiveInfo() {
    initClock();
    initWeather();
}
