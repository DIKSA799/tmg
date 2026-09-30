const STORAGE_KEY = 'tmg.theme';

function storedTheme() {
    try {
        const value = window.localStorage.getItem(STORAGE_KEY);
        return value === 'light' || value === 'dark' ? value : null;
    } catch {
        return null;
    }
}

// Light is the default; only an explicit choice by the visitor is remembered.
function currentTheme() {
    return document.documentElement.getAttribute('data-theme') ?? storedTheme() ?? 'light';
}

export function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', theme === 'dark' ? '#0b0909' : '#f1eeee');

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
}

export function initContrastToggle() {
    const buttons = document.querySelectorAll('[data-contrast-toggle]');
    if (buttons.length === 0) {
        return;
    }

    buttons.forEach((button) => {
        const sync = () => {
            button.setAttribute('aria-pressed', String(document.documentElement.classList.contains('contrast-boost')));
        };

        button.addEventListener('click', () => {
            document.documentElement.classList.toggle('contrast-boost');
            sync();
        });

        sync();
    });
}
