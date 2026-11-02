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
    summary.innerHTML = `<span class="num">${escapeHtml(result.recipient)}</span>`
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

// Mirrors App\Support\PhoneNumber::normalize. Returns bare national digits.
function normalisePhone(value) {
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

function normaliseEmail(value) {
    const email = String(value ?? '').trim().toLowerCase();

    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) ? email : null;
}

function parseRecipients(channel, raw) {
    const normalise = channel === 'email' ? normaliseEmail : normalisePhone;
    const seen = new Set();
    const values = [];

    String(raw ?? '').split(/[\s,;]+/).forEach((part) => {
        const normalised = normalise(part);

        if (normalised !== null && ! seen.has(normalised)) {
            seen.add(normalised);
            values.push(normalised);
        }
    });

    return values;
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
    const root = document.querySelector('[data-msg-compose]');

    if (! root) {
        return;
    }

    const channelSelect = root.querySelector('[data-msg-channel]');
    const providerSelect = root.querySelector('[data-msg-provider]');
    const senderHint = root.querySelector('[data-msg-sender]');
    const providerWarning = root.querySelector('[data-msg-provider-warning]');
    const smsField = root.querySelector('[data-msg-sms-field]');
    const emailField = root.querySelector('[data-msg-email-field]');
    const stateSelect = root.querySelector('[data-msg-state]');
    const lgaSelect = root.querySelector('[data-msg-lga]');
    const count = root.querySelector('[data-msg-count]');
    const testInput = root.querySelector('[data-msg-test-recipients]');
    const testLabel = root.querySelector('[data-msg-test-label]');
    const testCount = root.querySelector('[data-msg-test-count]');
    const manualInput = root.querySelector('[data-msg-manual-recipients]');
    const manualLabel = root.querySelector('[data-msg-manual-label]');
    const manualCount = root.querySelector('[data-msg-manual-count]');
    const pauseAfterTest = root.querySelector('[data-msg-pause-after-test]');
    const skipRecent = root.querySelector('[data-msg-skip-recent]');
    const skipDays = root.querySelector('[data-msg-skip-days]');
    const subjectWrap = root.querySelector('[data-msg-subject-wrap]');
    const subject = root.querySelector('[data-msg-subject]');
    const message = root.querySelector('[data-msg-message]');
    const chars = root.querySelector('[data-msg-chars]');
    const segments = root.querySelector('[data-msg-segments]');
    const segmentsWrap = root.querySelector('[data-msg-segments-wrap]');
    const sendButton = root.querySelector('[data-msg-send]');
    const status = root.querySelector('[data-msg-status]');
    const progressBox = root.querySelector('[data-msg-progress]');
    const logContainer = root.querySelector('[data-msg-log]');
    const detail = root.querySelector('[data-msg-detail]');
    const continueButton = root.querySelector('[data-msg-continue]');
    const totalSpan = root.querySelector('[data-msg-total]');
    const modeButtons = Array.from(root.querySelectorAll('[data-msg-mode]'));
    const panels = {
        records: root.querySelector('[data-msg-panel="records"]'),
        list: root.querySelector('[data-msg-panel="list"]'),
    };

    const ui = {
        sent: root.querySelector('[data-msg-sent]'),
        failed: root.querySelector('[data-msg-failed]'),
        remaining: root.querySelector('[data-msg-remaining]'),
        bar: root.querySelector('[data-msg-bar]'),
    };

    const logTo = logContainer ? (result) => appendLogRow(logContainer, result) : null;

    let mode = 'records';
    let sending = false;

    const channelInfo = () => (config.channels ?? []).find((channel) => channel.value === channelSelect.value) ?? null;
    const isEmail = () => channelSelect.value === 'email';

    const providerInfo = () => (config.providers ?? []).find((provider) => provider.value === providerSelect.value) ?? null;

    const configuredFor = () => (isEmail() ? config.emailConfigured === true : (providerInfo()?.configured ?? false));

    const refreshSendState = () => {
        sendButton.disabled = sending || ! configuredFor();
    };

    const setMode = (next) => {
        mode = next;
        modeButtons.forEach((button) => button.classList.toggle('is-active', button.dataset.msgMode === next));
        setPanel(panels.records, next === 'records');
        setPanel(panels.list, next === 'list');
    };

    const refreshProvider = () => {
        const info = providerInfo();

        if (senderHint) {
            senderHint.textContent = info?.senderId ?? '—';
        }

        setPanel(providerWarning, ! (info?.configured ?? false));
        refreshSendState();
    };

    const refreshChannel = () => {
        const info = channelInfo();
        const email = isEmail();

        setPanel(smsField, ! email);
        setPanel(emailField, email);
        setPanel(subjectWrap, email);
        setPanel(segmentsWrap, ! email);

        if (testLabel) {
            testLabel.textContent = `Also send to these ${(info?.fieldLabel ?? 'phone numbers').toLowerCase()} (optional test recipients)`;
        }

        if (manualLabel) {
            manualLabel.textContent = info?.fieldLabel ?? 'Phone numbers';
        }

        refreshProvider();
        refreshCount();
        refreshTestCount();
        refreshManualCount();
        refreshMessage();
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

    // Warn before a likely duplicate: the same channel/location in the last day.
    const confirmDuplicate = async () => {
        try {
            const url = new URL(config.recentUrl, window.location.origin);
            url.searchParams.set('channel', channelSelect.value);

            if (stateSelect.value) {
                url.searchParams.set('state_id', stateSelect.value);
            }

            if (lgaSelect.value) {
                url.searchParams.set('lga_id', lgaSelect.value);
            }

            const info = await requestJson(url.toString());

            if (! info.recent) {
                return true;
            }

            return window.confirm(`This audience was already messaged ${info.sent_at} (${format(info.recipients)} recipients). Send again anyway?`);
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
            // Leave the LGA list empty; the nationwide/state count still works.
        }
    };

    const refreshCount = async () => {
        const url = new URL(config.countUrl, window.location.origin);
        url.searchParams.set('channel', channelSelect.value);

        if (stateSelect.value) {
            url.searchParams.set('state_id', stateSelect.value);
        }

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

    const refreshTestCount = () => {
        testCount.textContent = format(parseRecipients(channelSelect.value, testInput.value).length);
    };

    const refreshManualCount = () => {
        manualCount.textContent = format(parseRecipients(channelSelect.value, manualInput.value).length);
    };

    const refreshMessage = () => {
        const length = message.value.length;
        chars.textContent = format(length);
        segments.textContent = format(Math.max(1, Math.ceil(length / SEGMENT_LENGTH)));
    };

    channelSelect.addEventListener('change', refreshChannel);
    providerSelect.addEventListener('change', refreshProvider);
    stateSelect.addEventListener('change', async () => {
        await loadLgas();
        await refreshCount();
    });
    lgaSelect.addEventListener('change', refreshCount);
    testInput.addEventListener('input', refreshTestCount);
    manualInput.addEventListener('input', refreshManualCount);
    message.addEventListener('input', refreshMessage);
    modeButtons.forEach((button) => button.addEventListener('click', () => setMode(button.dataset.msgMode)));

    continueButton?.addEventListener('click', async () => {
        const url = continueButton.dataset.processUrl;

        setPanel(continueButton, false);
        status.textContent = 'Continuing to the full list…';

        try {
            const progress = await sendLoop(url, ui, logTo, { resume: true });

            if (progress.paused) {
                status.textContent = 'Still paused after the test recipients.';
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
        const email = isEmail();
        const channel = channelSelect.value;
        const body = { channel, mode, message: message.value };

        if (email) {
            body.subject = subject.value;
        } else {
            body.provider = providerSelect.value;
        }

        const testNumberCount = parseRecipients(channel, testInput.value).length;

        if (mode === 'records') {
            if (stateSelect.value) {
                body.state_id = Number(stateSelect.value);

                if (lgaSelect.value) {
                    body.lga_id = Number(lgaSelect.value);
                }
            }

            if (testNumberCount > 0) {
                body.test_recipients = testInput.value;
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
            if (parseRecipients(channel, manualInput.value).length === 0) {
                status.textContent = email ? 'Add at least one valid email address.' : 'Add at least one valid phone number.';

                return;
            }

            body.manual_recipients = manualInput.value;
        }

        sending = true;
        refreshSendState();
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
                status.textContent = `Paused after the test recipients — ${format(progress.sent)} sent. Review above, then continue.`;

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
            sending = false;
            refreshSendState();
        }
    });

    setMode('records');
    refreshChannel();
}

function initResume(config) {
    const root = document.querySelector('[data-msg-resume]');

    if (! root) {
        return;
    }

    const button = root.querySelector('[data-msg-resume-run]');
    const retryButton = root.querySelector('[data-msg-retry-run]');
    const status = root.querySelector('[data-msg-status]');
    const logContainer = root.querySelector('[data-msg-log]');
    const total = Number(root.dataset.total ?? 0);
    const resumeFlag = root.dataset.resume === '1';

    const logTo = logContainer ? (result) => appendLogRow(logContainer, result) : null;

    const ui = {
        sent: document.querySelector('[data-msg-sent]'),
        failed: document.querySelector('[data-msg-failed]'),
        remaining: root.querySelector('[data-msg-remaining]'),
        bar: root.querySelector('[data-msg-bar]'),
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
                status.textContent = 'Still paused after the test recipients.';
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

export function initMessaging() {
    const config = window.__MSG;

    if (! config) {
        return;
    }

    if (config.mode === 'resume') {
        initResume(config);
    } else {
        initCompose(config);
    }
}
