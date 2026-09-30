const STORAGE_KEY = 'tmg.theme';

function storedTheme() {
    try {
        const value = window.localStorage.getItem(STORAGE_KEY);
        return value === 'light' || value === 'dark' ? value : null;
    } catch {
        return null;
    }
}

function systemTheme() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function currentTheme() {
    return document.documentElement.getAttribute('data-theme') ?? systemTheme();
}

export function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        const label = theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode';
        button.setAttribute('aria-pressed', String(theme === 'dark'));
        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
    });
}

export function initThemeToggle() {
    const buttons = document.querySelectorAll('[data-theme-toggle]');
    if (buttons.length === 0) {
        return;
    }

    applyTheme(currentTheme());

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            const next = currentTheme() === 'dark' ? 'light' : 'dark';

            try {
                window.localStorage.setItem(STORAGE_KEY, next);
            } catch {
                // Storage can be unavailable in private browsing; the theme still applies for this page.
            }

            applyTheme(next);
        });
    });

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (storedTheme() === null) {
            applyTheme(systemTheme());
        }
    });
}
