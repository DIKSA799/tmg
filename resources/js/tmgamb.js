import { initModals } from './modals';
import { initThemeToggle } from './theme';

function initUppercaseInputs() {
    document.querySelectorAll('[data-uppercase]').forEach((input) => {
        const sync = () => {
            const start = input.selectionStart;
            const end = input.selectionEnd;
            const upper = input.value.toUpperCase();

            if (input.value === upper) {
                return;
            }

            input.value = upper;

            if (start !== null && end !== null) {
                input.setSelectionRange(start, end);
            }
        };

        input.addEventListener('input', sync);
        sync();
    });
}

function initPasswordToggles() {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);

        if (input === null) {
            return;
        }

        const sync = () => {
            const hidden = input.type === 'password';

            button.setAttribute('aria-pressed', String(!hidden));
            button.setAttribute('aria-label', hidden ? 'Show password' : 'Hide password');
            button.querySelector('[data-icon-show]')?.classList.toggle('hidden', !hidden);
            button.querySelector('[data-icon-hide]')?.classList.toggle('hidden', hidden);
        };

        button.addEventListener('click', () => {
            input.type = input.type === 'password' ? 'text' : 'password';
            sync();
        });

        sync();
    });
}

function initRevealSecrets() {
    document.querySelectorAll('[data-reveal]').forEach((button) => {
        const target = document.getElementById(button.dataset.reveal);

        if (target === null) {
            return;
        }

        const secret = target.dataset.secret;
        const mask = target.dataset.mask ?? '••••••••••••••••';

        if (secret !== undefined) {
            target.textContent = mask;

            button.addEventListener('click', () => {
                const revealed = button.dataset.revealed === '1';

                target.textContent = revealed ? mask : secret;
                button.textContent = revealed ? 'Reveal' : 'Hide';
                button.dataset.revealed = revealed ? '0' : '1';
                button.setAttribute('aria-expanded', String(!revealed));
            });

            return;
        }

        button.addEventListener('click', () => {
            const hidden = target.hasAttribute('hidden');

            target.toggleAttribute('hidden', !hidden);
            button.textContent = hidden ? 'Hide' : 'Reveal';
            button.setAttribute('aria-expanded', String(hidden));
        });
    });
}

function initCopyable() {
    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const value = button.dataset.copy;
            const label = button.querySelector('[data-copy-label]');
            const original = label?.textContent ?? 'Copy link';

            try {
                await navigator.clipboard.writeText(value);
                if (label) {
                    label.textContent = 'Copied';
                }
            } catch {
                if (label) {
                    label.textContent = 'Copy failed';
                }
            }

            window.setTimeout(() => {
                if (label) {
                    label.textContent = original;
                }
            }, 1800);
        });
    });
}

function initNativeShare() {
    document.querySelectorAll('[data-native-share]').forEach((button) => {
        if (!navigator.share) {
            button.hidden = true;

            return;
        }

        button.addEventListener('click', () => {
            navigator.share({
                title: button.dataset.shareTitle ?? document.title,
                text: button.dataset.shareText ?? '',
                url: button.dataset.nativeShare,
            }).catch(() => {});
        });
    });
}

function initConfirmations(modals) {
    const modal = document.getElementById('confirm-modal');

    if (modal === null) {
        return;
    }

    const title = modal.querySelector('[data-confirm-title]');
    const message = modal.querySelector('[data-confirm-message]');
    const confirmButton = modal.querySelector('[data-confirm-submit]');

    let pendingForm = null;

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            // The confirm button re-submits the form with this flag set.
            if (form.dataset.confirmed === '1') {
                delete form.dataset.confirmed;

                return;
            }

            event.preventDefault();

            pendingForm = form;

            if (title) {
                title.textContent = form.dataset.confirmTitle ?? 'Please confirm';
            }

            if (message) {
                message.textContent = form.dataset.confirm ?? '';
            }

            if (confirmButton) {
                confirmButton.textContent = form.dataset.confirmLabel ?? 'Confirm';
            }

            modals.open('confirm-modal');
        });
    });

    confirmButton?.addEventListener('click', () => {
        if (pendingForm === null) {
            return;
        }

        const form = pendingForm;
        pendingForm = null;
        form.dataset.confirmed = '1';

        modals.close();
        form.requestSubmit();
    });
}

function initPhotoPreviews() {
    document.querySelectorAll('[data-photo-input]').forEach((input) => {
        const preview = document.querySelector(input.dataset.photoInput);

        if (preview === null) {
            return;
        }

        input.addEventListener('change', () => {
            const file = input.files?.[0];

            if (!file) {
                return;
            }

            const url = URL.createObjectURL(file);
            preview.src = url;
            preview.hidden = false;
        });
    });
}

function initAutoModals(modals) {
    // The modal system shows one dialog at a time. Opening only the first
    // acknowledgement means terms -> notice is a clean two-step sequence
    // instead of the second modal replacing the first before it is read.
    const first = document.querySelector('[data-auto-open]');

    if (first !== null) {
        modals.open(first.id);
    }
}

function boot() {
    initThemeToggle();

    const modals = initModals();
    initAutoModals(modals);

    initPasswordToggles();
    initUppercaseInputs();
    initRevealSecrets();
    initCopyable();
    initNativeShare();
    initConfirmations(modals);
    initPhotoPreviews();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}
