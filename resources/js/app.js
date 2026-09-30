import { initCaptureForm } from './capture-form';
import { initCounters, initNavSpy, initReveal } from './motion';
import { initThemeToggle } from './theme';

function boot() {
    initThemeToggle();
    initReveal();
    initCounters();
    initNavSpy();
    initCaptureForm();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}
