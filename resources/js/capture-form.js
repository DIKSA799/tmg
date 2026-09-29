import { ApiError, requestJson } from './http';
import { SearchSelect } from './search-select';

const ENDPOINTS = {
    states: '/geography/states',
    lgas: (stateId) => `/geography/states/${stateId}/lgas`,
    wards: (lgaId) => `/geography/lgas/${lgaId}/wards`,
    pollingUnits: (wardId) => `/geography/wards/${wardId}/polling-units`,
    locate: '/geography/locate',
    submissions: '/submissions',
};

const DEVICE_KEY = 'tmg.device_id';
const SUBMISSION_KEY = 'tmg.submission_key';

const toOption = (item) => ({ value: item.id, label: item.name, code: item.code ?? '' });

const toId = (value) => {
    const parsed = Number.parseInt(value, 10);
    return Number.isNaN(parsed) ? null : parsed;
};

function uuid() {
    return crypto.randomUUID?.() ?? `${Date.now().toString(16)}-${Math.random().toString(16).slice(2)}`;
}

function persistentId(storage, key) {
    let value = storage.getItem(key);
    if (!value) {
        value = uuid();
        storage.setItem(key, value);
    }
    return value;
}

function collectDevice() {
    return {
        device_id: persistentId(window.localStorage, DEVICE_KEY),
        user_agent: navigator.userAgent,
        platform: navigator.userAgentData?.platform ?? navigator.platform ?? null,
        language: navigator.language ?? null,
        languages: (navigator.languages ?? []).slice(0, 5),
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone ?? null,
        screen: `${window.screen.width}x${window.screen.height}`,
        viewport: `${window.innerWidth}x${window.innerHeight}`,
        pixel_ratio: window.devicePixelRatio ?? 1,
        color_scheme: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light',
        touch: 'ontouchstart' in window || navigator.maxTouchPoints > 0,
        cores: navigator.hardwareConcurrency ?? null,
        memory: navigator.deviceMemory ?? null,
        referrer: document.referrer || null,
    };
}

export function initCaptureForm() {
    const form = document.querySelector('[data-capture-form]');
    if (!form) {
        return;
    }

    const progress = document.querySelector('[data-top-progress]');
    const successPanel = document.querySelector('[data-success]');
    const geoButton = document.querySelector('[data-geolocate]');
    const geoStatus = document.querySelector('[data-geo-status]');
    const submitButton = form.querySelector('[data-submit]');
    const submitLabel = form.querySelector('[data-submit-label]');
    const submitSpinner = form.querySelector('[data-submit-spinner]');
    const formError = form.querySelector('[data-form-error]');
    const idempotencyInput = form.querySelector('[data-idempotency]');
    const latitudeInput = form.querySelector('[data-latitude]');
    const longitudeInput = form.querySelector('[data-longitude]');

    let idempotencyKey = persistentId(window.sessionStorage, SUBMISSION_KEY);
    idempotencyInput.value = idempotencyKey;

    const boxes = new Map();
    let busy = 0;

    const startBusy = () => {
        busy += 1;
        progress?.classList.add('is-active');
    };

    const stopBusy = () => {
        busy = Math.max(0, busy - 1);
        if (busy === 0) {
            progress?.classList.remove('is-active');
        }
    };

    const load = async (url) => {
        startBusy();
        try {
            const { data } = await requestJson(url);
            return data ?? [];
        } finally {
            stopBusy();
        }
    };

    const box = (name) => boxes.get(name);

    async function cascadeFromState(stateId) {
        box('lga_id').setLoading();
        box('ward_id').clear({ disable: true }).setMessage('Select an LGA first.');
        box('polling_unit_id').clear({ disable: true }).setMessage('Select a ward first.');

        const lgas = await load(ENDPOINTS.lgas(stateId));
        box('lga_id').setDisabled(false).setOptions(lgas.map(toOption));
    }

    async function cascadeFromLga(lgaId) {
        box('ward_id').setLoading();
        box('polling_unit_id').clear({ disable: true }).setMessage('Select a ward first.');

        const wards = await load(ENDPOINTS.wards(lgaId));
        box('ward_id').setDisabled(false).setOptions(wards.map(toOption));
    }

    async function cascadeFromWard(wardId) {
        box('polling_unit_id').setLoading();

        const units = await load(ENDPOINTS.pollingUnits(wardId));
        box('polling_unit_id').setDisabled(false).setOptions(units.map(toOption));
    }

    const handlers = {
        state_id: (value) => cascadeFromState(value),
        lga_id: (value) => cascadeFromLga(value),
        ward_id: (value) => cascadeFromWard(value),
        polling_unit_id: () => {},
    };

    document.querySelectorAll('[data-combobox]').forEach((root) => {
        const name = root.dataset.name;
        boxes.set(name, new SearchSelect(root, {
            onChange: (value) => handlers[name]?.(value),
        }));
    });

    async function primeStates() {
        box('state_id').setLoading();
        const states = await load(ENDPOINTS.states);
        box('state_id').setOptions(states.map(toOption));
    }

    async function applyDetectedLocation(data) {
        if (box('state_id').options.length === 0) {
            await primeStates();
        }

        box('state_id').select(String(data.state.id), { notify: false });

        const lgas = await load(ENDPOINTS.lgas(data.state.id));
        box('lga_id').setDisabled(false).setOptions(lgas.map(toOption), { autoSelect: false });

        if (!data.lga) {
            box('ward_id').clear({ disable: true }).setMessage('Select an LGA first.');
            box('polling_unit_id').clear({ disable: true }).setMessage('Select a ward first.');
            return;
        }

        box('lga_id').select(String(data.lga.id), { notify: false });

        const wards = await load(ENDPOINTS.wards(data.lga.id));
        box('ward_id').setDisabled(false).setOptions(wards.map(toOption), { autoSelect: false });

        if (!data.ward) {
            box('polling_unit_id').clear({ disable: true }).setMessage('Select a ward first.');
            return;
        }

        box('ward_id').select(String(data.ward.id), { notify: false });

        const units = await load(ENDPOINTS.pollingUnits(data.ward.id));
        box('polling_unit_id').setDisabled(false).setOptions(units.map(toOption));
    }

    geoButton?.addEventListener('click', () => {
        if (!('geolocation' in navigator)) {
            geoStatus.textContent = 'Location is not available on this device.';
            return;
        }

        geoStatus.textContent = 'Requesting your permission…';
        geoButton.disabled = true;

        navigator.geolocation.getCurrentPosition(async (position) => {
            const { latitude, longitude } = position.coords;
            latitudeInput.value = latitude;
            longitudeInput.value = longitude;
            geoStatus.textContent = 'Matching your position to a ward…';

            try {
                const { data } = await requestJson(ENDPOINTS.locate, {
                    method: 'POST',
                    body: JSON.stringify({ latitude, longitude }),
                });

                if (!data?.matched || !data.state) {
                    geoStatus.textContent = 'We could not match your position. Please choose your location manually.';
                    return;
                }

                await applyDetectedLocation(data);

                geoStatus.textContent = data.ward
                    ? `Location set to ${data.ward.name}.`
                    : `State set to ${data.state.name}. Continue to your ward.`;
            } catch {
                geoStatus.textContent = 'Location lookup failed. Please choose manually.';
            } finally {
                geoButton.disabled = false;
            }
        }, (error) => {
            geoButton.disabled = false;
            geoStatus.textContent = error.code === error.PERMISSION_DENIED
                ? 'Location permission denied. Choose your location manually.'
                : 'Unable to read your location. Choose it manually.';
        }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 300000 });
    });

    document.querySelectorAll('[data-other-trigger]').forEach((select) => {
        const field = form.querySelector(`[data-other-field="${select.name}"]`);
        const input = field?.querySelector('input');

        const sync = () => {
            const isOther = select.value === 'other';
            field?.classList.toggle('hidden', !isOther);
            if (input) {
                input.disabled = !isOther;
                if (!isOther) {
                    input.value = '';
                }
            }
        };

        select.addEventListener('change', sync);
        sync();
    });

    function clearErrors() {
        form.querySelectorAll('[data-error-for]').forEach((element) => {
            element.hidden = true;
            element.textContent = '';
        });
        form.querySelectorAll('[aria-invalid="true"]').forEach((element) => element.removeAttribute('aria-invalid'));
        formError.hidden = true;
        formError.textContent = '';
    }

    function showValidationErrors(errors) {
        let firstInvalid = null;

        Object.entries(errors).forEach(([field, messages]) => {
            const message = Array.isArray(messages) ? messages[0] : messages;
            const target = form.querySelector(`[data-error-for="${field}"]`);
            if (target) {
                target.textContent = message;
                target.hidden = false;
            }

            const input = form.querySelector(`[name="${field}"]`);
            if (input) {
                input.setAttribute('aria-invalid', 'true');
                firstInvalid ??= input;
            }
        });

        firstInvalid?.focus();
    }

    function buildPayload() {
        const data = new FormData(form);
        const text = (key) => {
            const value = data.get(key);
            return typeof value === 'string' && value.trim() !== '' ? value.trim() : null;
        };

        return {
            idempotency_key: idempotencyInput.value,
            full_name: text('full_name'),
            gender: data.get('gender'),
            age_band: data.get('age_band'),
            phone: text('phone'),
            state_id: toId(data.get('state_id')),
            lga_id: toId(data.get('lga_id')),
            ward_id: toId(data.get('ward_id')),
            polling_unit_id: toId(data.get('polling_unit_id')),
            registered_voter_status: data.get('registered_voter_status'),
            pvc_status: data.get('pvc_status'),
            preferred_language: data.get('preferred_language'),
            preferred_language_other: text('preferred_language_other'),
            preferred_channel: data.get('preferred_channel'),
            preferred_channel_other: text('preferred_channel_other'),
            consent_to_contact: data.get('consent_to_contact') === '1',
            consent_to_data: data.get('consent_to_data') === '1',
            latitude: latitudeInput.value ? Number(latitudeInput.value) : null,
            longitude: longitudeInput.value ? Number(longitudeInput.value) : null,
            captured_at: new Date().toISOString(),
            device: collectDevice(),
        };
    }

    function setSubmitting(isSubmitting) {
        submitButton.disabled = isSubmitting;
        submitButton.setAttribute('aria-busy', isSubmitting ? 'true' : 'false');
        submitLabel.textContent = isSubmitting ? 'Saving…' : 'Submit record';
        submitSpinner.classList.toggle('hidden', !isSubmitting);
    }

    function rotateKey() {
        idempotencyKey = uuid();
        window.sessionStorage.setItem(SUBMISSION_KEY, idempotencyKey);
        idempotencyInput.value = idempotencyKey;
    }

    function showSuccess(payload) {
        form.classList.add('hidden');
        successPanel.hidden = false;
        successPanel.classList.remove('hidden');
        successPanel.querySelector('[data-success-title]').textContent = payload.duplicate ? 'Already captured' : 'Record captured';
        successPanel.querySelector('[data-success-message]').textContent = payload.message;
        successPanel.querySelector('[data-success-reference]').textContent = payload.reference;
        successPanel.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function resetForNext() {
        successPanel.hidden = true;
        successPanel.classList.add('hidden');
        form.classList.remove('hidden');

        ['full_name', 'phone', 'preferred_language_other', 'preferred_channel_other'].forEach((name) => {
            const input = form.querySelector(`[name="${name}"]`);
            if (input) {
                input.value = '';
            }
        });

        ['age_band', 'registered_voter_status', 'pvc_status', 'preferred_language', 'preferred_channel'].forEach((name) => {
            const select = form.querySelector(`[name="${name}"]`);
            if (select) {
                select.value = '';
            }
        });

        form.querySelectorAll('input[type="radio"][name="gender"]').forEach((radio, index) => {
            radio.checked = index === 0;
        });
        form.querySelectorAll('input[type="radio"][name="consent_to_contact"], input[type="radio"][name="consent_to_data"]').forEach((radio) => {
            radio.checked = radio.value === '1';
        });
        form.querySelectorAll('[data-other-field]').forEach((field) => {
            field.classList.add('hidden');
            const input = field.querySelector('input');
            if (input) {
                input.value = '';
                input.disabled = true;
            }
        });

        clearErrors();
        rotateKey();
        form.querySelector('[name="full_name"]')?.focus();
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();
        setSubmitting(true);

        try {
            const payload = await requestJson(ENDPOINTS.submissions, {
                method: 'POST',
                body: JSON.stringify(buildPayload()),
            });
            showSuccess(payload);
        } catch (error) {
            if (error instanceof ApiError && error.status === 422) {
                showValidationErrors(error.body?.errors ?? {});
            } else if (error instanceof ApiError && error.status === 429) {
                formError.textContent = 'Too many submissions from this device. Please wait a moment and try again.';
                formError.hidden = false;
            } else {
                formError.textContent = 'We could not save this record. Please check your connection and try again.';
                formError.hidden = false;
            }
        } finally {
            setSubmitting(false);
        }
    });

    document.querySelector('[data-capture-again]')?.addEventListener('click', resetForNext);

    primeStates().catch(() => {
        box('state_id').setMessage('Could not load states. Please refresh.');
    });
}
