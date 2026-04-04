import $ from 'jquery';
import { applyFieldErrors, clearFieldErrors } from './artWalletValidation.js';
import { showModalFromEnvelope, showToast } from './artWalletUi.js';

export class AjaxEnvelopeError extends Error {
    /**
     * @param {Record<string, unknown>} envelope
     */
    constructor(envelope) {
        super(typeof envelope.message === 'string' ? envelope.message : 'Request failed');
        this.name = 'AjaxEnvelopeError';
        /** @type {Record<string, unknown>} */
        this.envelope = envelope;
        /** @type {string|undefined} */
        this.code = typeof envelope.code === 'string' ? envelope.code : undefined;
    }
}

/**
 * @param {unknown} body
 * @returns {body is Record<string, unknown>}
 */
function isRecord(body) {
    return body !== null && typeof body === 'object' && !Array.isArray(body);
}

/**
 * @param {unknown} parsed
 */
export function isUnifiedEnvelope(parsed) {
    return isRecord(parsed) && typeof parsed.success === 'boolean';
}

/**
 * Apply toast/modal from a successful or error envelope.
 * @param {Record<string, unknown>} envelope
 * @param {{ skipToast?: boolean, skipModal?: boolean }} opts
 */
export function applyEnvelope(envelope, opts = {}) {
    if (envelope.success) {
        clearFieldErrors();
    }
    if (!opts.skipToast && envelope.toast && typeof envelope.toast === 'object') {
        showToast(/** @type {Parameters<typeof showToast>[0]} */ (envelope.toast));
    }
    if (!opts.skipModal && envelope.modal && typeof envelope.modal === 'object') {
        void showModalFromEnvelope(envelope.modal);
    }
    if (!envelope.success && envelope.errors && typeof envelope.errors === 'object') {
        const err = /** @type {Record<string, unknown>} */ (envelope.errors);
        if (Object.keys(err).length > 0) {
            applyFieldErrors(/** @type {Record<string, string[]|string>} */ (envelope.errors));
        }
    }
}

/**
 * @param {Record<string, unknown>|null} envelope
 */
export function getEnvelopeData(envelope) {
    if (!envelope || !isRecord(envelope.data)) {
        return {};
    }

    return /** @type {Record<string, unknown>} */ (envelope.data);
}

/**
 * @param {unknown} parsed
 * @returns {Record<string, unknown>|null}
 */
export function normalizeResponse(parsed) {
    if (isUnifiedEnvelope(parsed)) {
        return /** @type {Record<string, unknown>} */ (parsed);
    }

    return null;
}

/**
 * @param {string} url
 * @param {Record<string, unknown>} [ajaxOptions]
 * @returns {Promise<Record<string, unknown>>}
 */
export async function apiGetJson(url, ajaxOptions = {}) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url,
            method: 'GET',
            dataType: 'json',
            ...ajaxOptions,
        })
            .done((data) => {
                const parsed = /** @type {unknown} */ (data);
                const env = normalizeResponse(parsed);
                if (env) {
                    if (!env.success) {
                        applyEnvelope(env);
                        reject(new AjaxEnvelopeError(env));

                        return;
                    }
                    applyEnvelope(env);

                    resolve(env);
                } else {
                    resolve(isRecord(parsed) ? /** @type {Record<string, unknown>} */ (parsed) : {});
                }
            })
            .fail((xhr) => {
                handleFail(xhr, reject);
            });
    });
}

/**
 * @param {string} url
 * @param {unknown} [data]
 * @param {Record<string, unknown>} [ajaxOptions]
 * @returns {Promise<Record<string, unknown>>}
 */
export async function apiPostJson(url, data, ajaxOptions = {}) {
    return new Promise((resolve, reject) => {
        const headers = { ...ajaxOptions.headers };
        $.ajax({
            url,
            method: 'POST',
            contentType: 'application/json',
            data: data !== undefined ? JSON.stringify(data) : undefined,
            dataType: 'json',
            headers,
            ...ajaxOptions,
        })
            .done((body) => {
                const parsed = /** @type {unknown} */ (body);
                const env = normalizeResponse(parsed);
                if (env) {
                    if (!env.success) {
                        applyEnvelope(env);
                        reject(new AjaxEnvelopeError(env));

                        return;
                    }
                    applyEnvelope(env);

                    resolve(env);
                } else {
                    resolve(isRecord(parsed) ? /** @type {Record<string, unknown>} */ (parsed) : {});
                }
            })
            .fail((xhr) => {
                handleFail(xhr, reject);
            });
    });
}

/**
 * @param {import('jquery').jqXHR} xhr
 * @param {(e: Error) => void} reject
 */
function handleFail(xhr, reject) {
    const parsed = xhr.responseJSON;
    const env = normalizeResponse(parsed);
    if (env) {
        applyEnvelope(env);
        reject(new AjaxEnvelopeError(env));

        return;
    }
    const msg =
        isRecord(parsed) && typeof parsed.message === 'string'
            ? parsed.message
            : xhr.statusText || 'Request failed';
    showToast({
        title: 'Error',
        text: msg,
        severity: 'danger',
        timer: 6000,
        dedupe_key: `http_fail:${xhr.status}:${msg.slice(0, 40)}`,
    });
    reject(new Error(msg));
}
