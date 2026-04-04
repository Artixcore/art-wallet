/**
 * ArtWallet API v1 client (Bearer + device header).
 * Uses fetch; responses follow AjaxEnvelope.
 */

export async function apiV1Request(path, options = {}) {
    const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-ArtWallet-Device-Id': options.deviceId ?? '',
        ...options.headers,
    };
    if (options.token) {
        headers.Authorization = `Bearer ${options.token}`;
    }

    const res = await fetch(`/api${path.startsWith('/') ? path : `/${path}`}`, {
        method: options.method ?? 'GET',
        headers,
        body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
        credentials: options.credentials ?? 'same-origin',
    });

    const json = await res.json().catch(() => ({}));

    return { ok: res.ok, status: res.status, json };
}
