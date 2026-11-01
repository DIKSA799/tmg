const SEGMENT_LENGTH = 160;

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

function format(value) {
    return new Intl.NumberFormat('en-GB').format(Number(value ?? 0));
}

function numberFrom(element) {
    return Number(String(element?.textContent ?? '').replace(/[^0-9]/g, '')) || 0;
}

// Mirrors App\Support\PhoneNumber::normalize so the live count matches what the
// server will accept. Returns bare national digits, or null when invalid.
function normaliseNumber(value) {
    let digits = String(value ?? '').replace(/\D+/g, '');

    if (digits === '') {
        return null;
    }

    if (digits.startsWith('00')) {
        digits = digits.slice(2);
    }

    if (digits.startsWith('234')) {
        digits = digits.slice(3);
    }

    digits = digits.replace(/^0+/, '');

    return /^[789]\d{9}$/.test(digits) ? digits : null;
}

function parseNumbers(raw) {
    const seen = new Set();
    const numbers = [];

    String(raw ?? '').split(/[\s,;]+/).forEach((part) => {
        const normalised = normaliseNumber(part);

        if (normalised !== null && ! seen.has(normalised)) {
            seen.add(normalised);
            numbers.push(normalised);
        }
    });

    return numbers;
}

async function requestJson(url, { method = 'GET', body = null } = {}) {
    const options = {
        method,
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    };

    if (method !== 'GET') {
        options.headers['Content-Type'] = 'application/json';
        options.headers['X-CSRF-TOKEN'] = csrfToken();
        options.body = JSON.stringify(body ?? {});
    }

    const response = await fetch(url, options);
    const payload = await response.json().catch(() => ({}));

    if (! response.ok) {
        const firstError = payload.errors ? Object.values(payload.errors)[0]?.[0] : null;

        throw new Error(firstError || payload.message || `Request failed (${response.status})`);
    }

    return payload;
}

function paintProgress(ui, progress) {
    if (ui.sent) {
        ui.sent.textContent = format(progress.sent);
    }

    if (ui.failed) {
        ui.failed.textContent = format(progress.failed);
    }

    if (ui.remaining) {
        ui.remaining.textContent = format(progress.remaining);
    }

    if (ui.bar) {
        const total = Number(progress.total ?? 0);
        const done = total === 0 ? 1 : (total - Number(progress.remaining ?? 0)) / total;

        ui.bar.style.width = `${Math.max(0, Math.min(1, done)) * 100}%`;
    }
}

async function runBatches(processUrl, ui) {
    while (true) {
        const progress = await requestJson(processUrl, { method: 'POST' });
        paintProgress(ui, progress);

        if (progress.remaining <= 0) {
            return progress;
        }

        if (progress.processed === 0) {
            throw new Error('Termii returned no progress for the next batch; stopping to avoid a loop.');
        }
    }
}

function initCompose(config) {
    const root = document.querySelector('[data-sms-compose]');

    if (! root) {
        return;
    }

    const stateSelect = root.querySelector('[data-sms-state]');
    const lgaSelect = root.querySelector('[data-sms-lga]');
    const count = root.querySelector('[data-sms-count]');
    const numbersInput = root.querySelector('[data-sms-numbers]');
    const numberCount = root.querySelector('[data-sms-number-count]');
    const message = root.querySelector('[data-sms-message]');
    const chars = root.querySelector('[data-sms-chars]');
    const segments = root.querySelector('[data-sms-segments]');
    const sendButton = root.querySelector('[data-sms-send]');
    const status = root.querySelector('[data-sms-status]');
    const progressBox = root.querySelector('[data-sms-progress]');
    const detail = root.querySelector('[data-sms-detail]');
    const totalSpan = root.querySelector('[data-sms-total]');
    const modeButtons = Array.from(root.querySelectorAll('[data-sms-mode]'));
    const panels = {
        location: root.querySelector('[data-sms-panel="location"]'),
        manual: root.querySelector('[data-sms-panel="manual"]'),
    };

    const ui = {
        sent: root.querySelector('[data-sms-sent]'),
        failed: root.querySelector('[data-sms-failed]'),
        remaining: root.querySelector('[data-sms-remaining]'),
        bar: root.querySelector('[data-sms-bar]'),
    };

    let mode = 'location';

    const setMode = (next) => {
        mode = next;
        modeButtons.forEach((button) => button.classList.toggle('is-active', button.dataset.smsMode === next));
        panels.location.hidden = next !== 'location';
        panels.manual.hidden = next !== 'manual';
    };

    const loadLgas = async () => {
        lgaSelect.innerHTML = '<option value="">All LGAs in the state</option>';

        if (! stateSelect.value) {
            return;
        }

        try {
            const response = await fetch(stateSelect.dataset.lgasUrl.replace('__STATE__', stateSelect.value), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();

            (payload.data ?? []).forEach((lga) => {
                const option = document.createElement('option');
                option.value = lga.id;
                option.textContent = lga.name;
                lgaSelect.append(option);
            });
        } catch {
            // Leave the LGA list empty; the state-wide count still works.
        }
    };

    const refreshCount = async () => {
        if (! stateSelect.value) {
            count.textContent = '0';

            return;
        }

        const url = new URL(config.countUrl, window.location.origin);
        url.searchParams.set('state_id', stateSelect.value);

        if (lgaSelect.value) {
            url.searchParams.set('lga_id', lgaSelect.value);
        }

        try {
            const payload = await requestJson(url.toString());
            count.textContent = format(payload.total);
        } catch {
            count.textContent = '0';
        }
    };

    const refreshManualCount = () => {
        numberCount.textContent = format(parseNumbers(numbersInput.value).length);
    };

    const refreshMessage = () => {
        const length = message.value.length;
        chars.textContent = format(length);
        segments.textContent = format(Math.max(1, Math.ceil(length / SEGMENT_LENGTH)));
    };

    stateSelect.addEventListener('change', async () => {
        await loadLgas();
        await refreshCount();
    });
    lgaSelect.addEventListener('change', refreshCount);
    numbersInput.addEventListener('input', refreshManualCount);
    message.addEventListener('input', refreshMessage);
    modeButtons.forEach((button) => button.addEventListener('click', () => setMode(button.dataset.smsMode)));

    sendButton.addEventListener('click', async () => {
        const body = { mode, message: message.value };

        if (mode === 'location') {
            if (! stateSelect.value) {
                status.textContent = 'Select a state first.';

                return;
            }

            body.state_id = Number(stateSelect.value);

            if (lgaSelect.value) {
                body.lga_id = Number(lgaSelect.value);
            }
        } else {
            if (parseNumbers(numbersInput.value).length === 0) {
                status.textContent = 'Add at least one valid phone number.';

                return;
            }

            body.manual_numbers = numbersInput.value;
        }

        sendButton.disabled = true;
        progressBox.hidden = false;
        detail.hidden = true;
        status.textContent = 'Creating campaign…';
        paintProgress(ui, { sent: 0, failed: 0, remaining: 0, total: 0 });

        try {
            const created = await requestJson(config.storeUrl, { method: 'POST', body });

            if (totalSpan) {
                totalSpan.textContent = format(created.total);
            }

            paintProgress(ui, { sent: 0, failed: 0, remaining: created.total, total: created.total });
            status.textContent = `Sending to ${format(created.total)} recipient(s)…`;

            await runBatches(config.processUrl.replace('__ID__', created.campaign_id), ui);

            status.textContent = 'Done.';
            detail.href = config.detailUrl.replace('__ID__', created.campaign_id);
            detail.hidden = false;
        } catch (error) {
            status.textContent = error.message;
        } finally {
            sendButton.disabled = false;
        }
    });

    setMode('location');
    refreshMessage();
}

function initResume(config) {
    const root = document.querySelector('[data-sms-resume]');

    if (! root) {
        return;
    }

    const button = root.querySelector('[data-sms-resume-run]');
    const status = root.querySelector('[data-sms-status]');
    const total = Number(root.dataset.total ?? 0);

    const ui = {
        sent: document.querySelector('[data-sms-sent]'),
        failed: document.querySelector('[data-sms-failed]'),
        remaining: root.querySelector('[data-sms-remaining]'),
        bar: root.querySelector('[data-sms-bar]'),
    };

    paintProgress(ui, {
        sent: numberFrom(ui.sent),
        failed: numberFrom(ui.failed),
        remaining: numberFrom(ui.remaining),
        total,
    });

    button.addEventListener('click', async () => {
        button.disabled = true;
        button.textContent = 'Sending…';
        status.textContent = 'Sending the remaining messages…';

        try {
            await runBatches(config.processUrl, ui);

            status.textContent = 'Finished. Refreshing…';
            window.location.reload();
        } catch (error) {
            status.textContent = error.message;
            button.disabled = false;
            button.textContent = 'Resume sending';
        }
    });
}

export function initSms() {
    const config = window.__SMS;

    if (! config) {
        return;
    }

    if (config.mode === 'resume') {
        initResume(config);
    } else {
        initCompose(config);
    }
}
