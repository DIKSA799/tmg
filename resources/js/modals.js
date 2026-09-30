const FOCUSABLE = 'button:not([disabled]), input:not([disabled]), a[href], select, textarea, [tabindex]:not([tabindex="-1"])';

export function initModals() {
    const modals = Array.from(document.querySelectorAll('[data-modal]'));
    if (modals.length === 0) {
        return;
    }

    let active = null;
    let lastFocus = null;

    const focusables = (root) => Array.from(root.querySelectorAll(FOCUSABLE)).filter((el) => el.offsetParent !== null);

    const close = () => {
        if (active === null) {
            return;
        }

        active.classList.remove('is-open');
        active = null;

        if (lastFocus instanceof HTMLElement) {
            lastFocus.focus();
        }
    };

    const open = (id) => {
        const modal = document.getElementById(id);
        if (modal === null) {
            return;
        }

        if (active !== null) {
            active.classList.remove('is-open');
        }

        lastFocus = document.activeElement;
        active = modal;
        modal.classList.add('is-open');

        focusables(modal)[0]?.focus();
    };

    document.querySelectorAll('[data-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => open(trigger.dataset.open));
    });

    document.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', close);
    });

    modals.forEach((modal) => {
        modal.addEventListener('mousedown', (event) => {
            if (event.target === modal) {
                close();
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (active === null) {
            return;
        }

        if (event.key === 'Escape') {
            close();
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const items = focusables(active);
        if (items.length === 0) {
            return;
        }

        const first = items[0];
        const last = items[items.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
}
