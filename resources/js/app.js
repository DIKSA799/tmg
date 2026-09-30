import { initCaptureForm } from './capture-form';
import { initLiveInfo } from './live-info';
import { initModals } from './modals';
import { initCounters, initNavSpy, initReveal, initScramble } from './motion';
import { initContrastToggle, initThemeToggle } from './theme';

function boot() {
    initThemeToggle();
    initContrastToggle();
    initModals();
    initReveal();
    initCounters();
    initScramble();
    initNavSpy();
    initLiveInfo();
    initCaptureForm();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}
