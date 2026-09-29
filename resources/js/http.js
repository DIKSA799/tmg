export class ApiError extends Error {
    constructor(status, body) {
        super(`Request failed with status ${status}`);
        this.name = 'ApiError';
        this.status = status;
        this.body = body;
    }
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export async function requestJson(url, { method = 'GET', body } = {}) {
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
        headers['X-CSRF-TOKEN'] = csrfToken();
    }

    const response = await fetch(url, {
        method,
        headers,
        body,
        credentials: 'same-origin',
    });

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
        throw new ApiError(response.status, payload);
    }

    return payload;
}
