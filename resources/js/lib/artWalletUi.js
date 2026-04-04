import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

/** @type {Map<string, number>} */
const dedupeTimestamps = new Map();

const DEFAULT_DEDUPE_TTL_MS = 10000;

function iconForSeverity(severity) {
    switch (severity) {
        case 'success':
            return 'success';
        case 'warning':
            return 'warning';
        case 'danger':
        case 'critical':
            return 'error';
        case 'info':
        default:
            return 'info';
    }
}

/**
 * @param {string} key
 * @param {number} ttlMs
 * @returns {boolean} true if duplicate (skip show)
 */
export function isDedupeKeyRecent(key, ttlMs = DEFAULT_DEDUPE_TTL_MS) {
    const now = Date.now();
    const last = dedupeTimestamps.get(key);
    if (last !== undefined && now - last < ttlMs) {
        return true;
    }
    dedupeTimestamps.set(key, now);
    if (dedupeTimestamps.size > 500) {
        const cutoff = now - ttlMs * 2;
        for (const [k, t] of dedupeTimestamps) {
            if (t < cutoff) {
                dedupeTimestamps.delete(k);
            }
        }
    }

    return false;
}

/**
 * @param {{ title?: string, text?: string, severity?: string, timer?: number|null, dedupe_key?: string|null, silent?: boolean }} payload
 */
export function showToast(payload) {
    if (payload.silent) {
        return;
    }
    if (payload.dedupe_key && isDedupeKeyRecent(payload.dedupe_key)) {
        return;
    }
    const icon = iconForSeverity(payload.severity || 'info');
    Swal.fire({
        toast: true,
        position: 'bottom-end',
        icon,
        title: payload.title || '',
        text: payload.text || undefined,
        showConfirmButton: false,
        timer: payload.timer === undefined ? 4000 : payload.timer,
        timerProgressBar: true,
    });
}

/**
 * @param {{ title?: string, text?: string, severity?: string, html?: string, confirmButtonText?: string, allowOutsideClick?: boolean }} payload
 */
export async function showBlockingModal(payload) {
    const icon = iconForSeverity(payload.severity || 'warning');
    await Swal.fire({
        icon,
        title: payload.title || '',
        text: payload.text,
        html: payload.html,
        confirmButtonText: payload.confirmButtonText || 'OK',
        allowOutsideClick: payload.allowOutsideClick !== false,
    });
}

/**
 * @param {{ title: string, text?: string, confirmButtonText?: string, cancelButtonText?: string }} opts
 * @returns {Promise<boolean>}
 */
export async function confirmDanger(opts) {
    const r = await Swal.fire({
        icon: 'warning',
        title: opts.title,
        text: opts.text,
        showCancelButton: true,
        confirmButtonText: opts.confirmButtonText || 'Confirm',
        cancelButtonText: opts.cancelButtonText || 'Cancel',
        focusCancel: true,
        reverseButtons: true,
    });

    return Boolean(r.isConfirmed);
}

/**
 * @param {Record<string, unknown>|null|undefined} modalPayload
 */
export async function showModalFromEnvelope(modalPayload) {
    if (!modalPayload || typeof modalPayload !== 'object') {
        return;
    }
    const title = String(modalPayload.title || '');
    const text = modalPayload.text ? String(modalPayload.text) : undefined;
    const html = modalPayload.html ? String(modalPayload.html) : undefined;
    const severity = String(modalPayload.severity || 'warning');
    await showBlockingModal({
        title,
        text: html ? undefined : text,
        html: html || undefined,
        severity,
        confirmButtonText: modalPayload.confirmButtonText ? String(modalPayload.confirmButtonText) : 'OK',
        allowOutsideClick: modalPayload.allowOutsideClick !== false,
    });
}
