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

// Toggle visibility with both the attribute and inline display, so a Tailwind
// display utility (grid/flex) on the element can never keep it visible.
function setPanel(element, visible) {
    if (! element) {
        return;
    }

    element.hidden = ! visible;
    element.style.display = visible ? '' : 'none';
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    })[character]);
}

function pretty(value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (typeof value === 'string') {
        try {
            return JSON.stringify(JSON.parse(value), null, 2);
        } catch {
            return value;
        }
    }

    return JSON.stringify(value, null, 2);
}

function exchangeBlock(label, value) {
    const wrap = document.createElement('div');

    const heading = document.createElement('span');
    heading.className = 'admin-kpi-label';
    heading.textContent = label;

    const pre = document.createElement('pre');
    pre.className = 'sms-pre';
    pre.textContent = pretty(value);

    wrap.append(heading, pre);

    return wrap;
}

// Append one live request/response entry to the send log.
function appendLogRow(container, result) {
    const details = document.createElement('details');
    details.className = 'sms-log-row';
    details.open = result.status !== 'sent';

    const summary = document.createElement('summary');
    summary.className = 'flex cursor-pointer items-center gap-2 text-xs';
    summary.innerHTML = `<span class="num">${escapeHtml(result.phone)}</span>`
        + `<span class="admin-pill">${escapeHtml(result.status)}</span>`
        + `<span class="truncate text-[color:var(--ink-mute)]">${escapeHtml(result.provider_message_id || result.provider_status || result.error || '')}</span>`;

    const body = document.createElement('div');
    body.className = 'mt-2 grid gap-2';
    body.append(
        exchangeBlock('Request URL', result.request_url),
        exchangeBlock('Request headers', result.request_headers),
        exchangeBlock('Request body', result.request_body),
        exchangeBlock('Response status', result.response_status),
        exchangeBlock('Response headers', result.response_headers),
        exchangeBlock('Response body', result.response_body),
    );

    details.append(summary, body);
    container.append(details);
    container.scrollTop = container.scrollHeight;
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

/**
 * Drive the sender one batch at a time, streaming every exchange to onResults.
 * Returns early when the campaign is paused for a test review, or when it is done.
 *
 * @param {{resume?: boolean}} options  resume lifts a pause on the first call.
 */
async function sendLoop(processUrl, ui, onResults, { resume = false } = {}) {
    let liftPause = resume;

    while (true) {
        const progress = await requestJson(processUrl, { method: 'POST', body: liftPause ? { resume: true } : {} });
        liftPause = false;

        paintProgress(ui, progress);

        if (onResults && Array.isArray(progress.results)) {
            progress.results.forEach((result) => onResults(result));
        }

        if (progress.paused) {
            return progress;
        }

        if (progress.remaining <= 0) {
            return progress;
        }

        if (progress.processed === 0) {
            throw new Error('The provider returned no progress for the next batch; stopping to avoid a loop.');
        }
    }
}

function initCompose(config) {
    const root = document.querySelector('[data-sms-compose]');

    if (! root) {
        return;
    }

    const providerSelect = root.querySelector('[data-sms-provider]');
    const senderHint = root.querySelector('[data-sms-sender]');
    const providerWarning = root.querySelector('[data-sms-provider-warning]');
    const stateSelect = root.querySelector('[data-sms-state]');
    const lgaSelect = root.querySelector('[data-sms-lga]');
    const count = root.querySelector('[data-sms-count]');
    const numbersInput = root.querySelector('[data-sms-numbers]');
    const numberCount = root.querySelector('[data-sms-number-count]');
    const testNumbersInput = root.querySelector('[data-sms-test-numbers]');
    const testCount = root.querySelector('[data-sms-test-count]');
    const pauseAfterTest = root.querySelector('[data-sms-pause-after-test]');
    const skipRecent = root.querySelector('[data-sms-skip-recent]');
    const skipDays = root.querySelector('[data-sms-skip-days]');
    const message = root.querySelector('[data-sms-message]');
    const chars = root.querySelector('[data-sms-chars]');
    const segments = root.querySelector('[data-sms-segments]');
    const sendButton = root.querySelector('[data-sms-send]');
    const status = root.querySelector('[data-sms-status]');
    const progressBox = root.querySelector('[data-sms-progress]');
    const logContainer = root.querySelector('[data-sms-log]');
    const detail = root.querySelector('[data-sms-detail]');
    const continueButton = root.querySelector('[data-sms-continue]');
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

    const logTo = logContainer ? (result) => appendLogRow(logContainer, result) : null;

    let mode = 'location';

    const setMode = (next) => {
        mode = next;
        modeButtons.forEach((button) => button.classList.toggle('is-active', button.dataset.smsMode === next));
        setPanel(panels.location, next === 'location');
        setPanel(panels.manual, next === 'manual');
    };

    const providerInfo = () => (config.providers ?? []).find((provider) => provider.value === providerSelect.value) ?? null;

    const refreshProvider = () => {
        const info = providerInfo();
        senderHint.textContent = info?.senderId ?? '—';
        setPanel(providerWarning, ! (info?.configured ?? false));
        sendButton.disabled = ! (info?.configured ?? false);
    };

    const finish = (campaignId, progress) => {
        status.textContent = progress.failed > 0
            ? `Done — ${format(progress.sent)} sent, ${format(progress.failed)} failed.`
            : `Done — ${format(progress.sent)} sent.`;

        if (detail) {
            detail.href = config.detailUrl.replace('__ID__', campaignId);
        }

        setPanel(detail, true);
    };

    // Warn before a likely duplicate: the same location broadcast in the last day.
    const confirmDuplicate = async () => {
        try {
            const url = new URL(config.recentUrl, window.location.origin);
            url.searchParams.set('state_id', stateSelect.value);

            if (lgaSelect.value) {
                url.searchParams.set('lga_id', lgaSelect.value);
            }

            const info = await requestJson(url.toString());

            if (! info.recent) {
                return true;
            }

            return window.confirm(`This location was already messaged ${info.sent_at} (${format(info.recipients)} recipients). Send again anyway?`);
        } catch {
            return true;
        }
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

    const refreshTestCount = () => {
        testCount.textContent = format(parseNumbers(testNumbersInput.value).length);
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
    testNumbersInput.addEventListener('input', refreshTestCount);
    message.addEventListener('input', refreshMessage);
    modeButtons.forEach((button) => button.addEventListener('click', () => setMode(button.dataset.smsMode)));
    providerSelect.addEventListener('change', refreshProvider);

    continueButton?.addEventListener('click', async () => {
        const url = continueButton.dataset.processUrl;

        setPanel(continueButton, false);
        status.textContent = 'Continuing to the full list…';

        try {
            const progress = await sendLoop(url, ui, logTo, { resume: true });

            if (progress.paused) {
                status.textContent = 'Still paused after the test numbers.';
                setPanel(continueButton, true);

                return;
            }

            finish(Number(continueButton.dataset.campaignId), progress);
        } catch (error) {
            status.textContent = error.message;
            setPanel(continueButton, true);
        }
    });

    sendButton.addEventListener('click', async () => {
        const body = { provider: providerSelect.value, mode, message: message.value };
        const testNumberCount = parseNumbers(testNumbersInput.value).length;

        if (mode === 'location') {
            if (! stateSelect.value) {
                status.textContent = 'Select a state first.';

                return;
            }

            body.state_id = Number(stateSelect.value);

            if (lgaSelect.value) {
                body.lga_id = Number(lgaSelect.value);
            }

            if (testNumberCount > 0) {
                body.test_numbers = testNumbersInput.value;
            }

            if (pauseAfterTest?.checked && testNumberCount > 0) {
                body.pause_after_test = 1;
            }

            if (skipRecent?.checked) {
                body.skip_recent = 1;
                body.skip_days = Math.min(365, Math.max(1, Number(skipDays?.value || 30)));
            }

            status.textContent = 'Checking for a recent broadcast…';

            if (! await confirmDuplicate()) {
                status.textContent = 'Cancelled.';

                return;
            }
        } else {
            if (parseNumbers(numbersInput.value).length === 0) {
                status.textContent = 'Add at least one valid phone number.';

                return;
            }

            body.manual_numbers = numbersInput.value;
        }

        sendButton.disabled = true;
        setPanel(progressBox, true);
        setPanel(detail, false);
        setPanel(continueButton, false);
        logContainer?.replaceChildren();
        status.textContent = 'Creating campaign…';
        paintProgress(ui, { sent: 0, failed: 0, remaining: 0, total: 0 });

        try {
            const created = await requestJson(config.storeUrl, { method: 'POST', body });

            if (totalSpan) {
                totalSpan.textContent = format(created.total);
            }

            paintProgress(ui, { sent: 0, failed: 0, remaining: created.total, total: created.total });
            status.textContent = `Sending to ${format(created.total)} recipient(s)…`;

            const url = config.processUrl.replace('__ID__', created.campaign_id);
            const progress = await sendLoop(url, ui, logTo);

            if (progress.paused) {
                status.textContent = `Paused after the test numbers — ${format(progress.sent)} sent. Review above, then continue.`;

                if (continueButton) {
                    continueButton.dataset.processUrl = url;
                    continueButton.dataset.campaignId = String(created.campaign_id);
                    setPanel(continueButton, true);
                }

                return;
            }

            finish(created.campaign_id, progress);
        } catch (error) {
            status.textContent = error.message;
        } finally {
            refreshProvider();
        }
    });

    setMode('location');
    refreshMessage();
    refreshProvider();
    refreshTestCount();
}

function initResume(config) {
    const root = document.querySelector('[data-sms-resume]');

    if (! root) {
        return;
    }

    const button = root.querySelector('[data-sms-resume-run]');
    const retryButton = root.querySelector('[data-sms-retry-run]');
    const status = root.querySelector('[data-sms-status]');
    const logContainer = root.querySelector('[data-sms-log]');
    const total = Number(root.dataset.total ?? 0);
    const resumeFlag = root.dataset.resume === '1';

    const logTo = logContainer ? (result) => appendLogRow(logContainer, result) : null;

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

    button?.addEventListener('click', async () => {
        button.disabled = true;
        button.textContent = 'Sending…';
        status.textContent = 'Sending the remaining messages…';

        try {
            const progress = await sendLoop(config.processUrl, ui, logTo, { resume: resumeFlag });

            if (progress.paused) {
                status.textContent = 'Still paused after the test numbers.';
                button.disabled = false;

                return;
            }

            status.textContent = 'Finished. Refreshing…';
            window.location.reload();
        } catch (error) {
            status.textContent = error.message;
            button.disabled = false;
            button.textContent = resumeFlag ? 'Continue sending' : 'Resume sending';
        }
    });

    retryButton?.addEventListener('click', async () => {
        retryButton.disabled = true;
        status.textContent = 'Re-queuing the failed messages…';

        try {
            await requestJson(root.dataset.retryUrl, { method: 'POST', body: {} });
            status.textContent = 'Retrying…';

            await sendLoop(config.processUrl, ui, logTo, { resume: true });

            status.textContent = 'Finished. Refreshing…';
            window.location.reload();
        } catch (error) {
            status.textContent = error.message;
            retryButton.disabled = false;
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
