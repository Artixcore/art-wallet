import $ from 'jquery';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { apiGetJson, apiPostJson, apiPutJson } from './lib/artWalletAjax.js';
import { applyFieldErrors, clearFieldErrors } from './lib/artWalletValidation.js';
import { showToast } from './lib/artWalletUi.js';

const csrf = document.querySelector('meta[name="csrf-token"]');
if (csrf) {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrf.getAttribute('content') },
    });
}

/** @type {string|null} */
let stepUpToken = null;

function showPanel(name) {
    $('.settings-panel').addClass('hidden');
    $(`#settings-panel-${name}`).removeClass('hidden');
    $('.settings-tab')
        .removeClass('bg-indigo-100 dark:bg-indigo-900/50 text-indigo-900 dark:text-indigo-100')
        .addClass('text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800');
    $(`.settings-tab[data-settings-tab="${name}"]`)
        .removeClass('text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800')
        .addClass('bg-indigo-100 dark:bg-indigo-900/50 text-indigo-900 dark:text-indigo-100');
}

$('.settings-tab').on('click', function tabClick() {
    const name = $(this).data('settings-tab');
    showPanel(name);
    if (name === 'audit') {
        void loadAudit();
    }
});

/**
 * @param {Record<string, unknown>} snap
 */
function applySnapshot(snap) {
    const u = /** @type {Record<string, unknown>} */ (snap.user || {});
    $('#set-theme').val(String(u.theme || 'system'));
    $('#set-locale').val(u.locale != null ? String(u.locale) : '');
    $('#set-tz').val(u.timezone != null ? String(u.timezone) : '');
    $('#set-user-version').val(String(u.settings_version ?? 1));

    const sec = /** @type {Record<string, unknown>} */ (snap.security_policy || {});
    $('#set-idle').val(String(sec.idle_timeout_minutes ?? 60));
    if (sec.max_session_duration_minutes != null) {
        $('#set-max-sess').val(String(sec.max_session_duration_minutes));
    } else {
        $('#set-max-sess').val('');
    }
    $('#set-notify-dev').prop('checked', sec.notify_new_device_login !== false);
    $('#set-security-version').val(String(sec.settings_version ?? 1));

    const msg = /** @type {Record<string, unknown>} */ (snap.messaging_privacy || {});
    $('#set-rr').prop('checked', msg.read_receipts_enabled !== false);
    $('#set-typing').prop('checked', msg.typing_indicators_enabled !== false);
    $('#set-att').val(String(msg.max_attachment_mb ?? 10));
    $('#set-safe').prop('checked', msg.safety_warnings_enabled !== false);
    $('#set-messaging-version').val(String(msg.settings_version ?? 1));

    const risk = /** @type {Record<string, unknown>} */ (snap.risk_thresholds || {});
    $('#set-large-tx').val(risk.large_tx_alert_fiat != null ? String(risk.large_tx_alert_fiat) : '');
    $('#set-large-cur').val(String(risk.large_tx_alert_currency ?? 'USD'));
    $('#set-risk-version').val(String(risk.settings_version ?? 1));
}

async function loadSettings() {
    try {
        const env = await apiGetJson('/ajax/settings');
        const data = /** @type {Record<string, unknown>} */ (env.data || {});
        applySnapshot(data);
    } catch {
        // envelope handled
    }
}

async function loadAudit() {
    const $ul = $('#settings-audit-list').empty();
    try {
        const env = await apiGetJson('/ajax/settings/audit');
        const data = /** @type {Record<string, unknown>} */ (env.data || {});
        const logs = /** @type {unknown[]} */ (data.logs || []);
        if (!logs.length) {
            $ul.append('<li class="py-2 text-gray-500">No entries yet.</li>');
            return;
        }
        logs.forEach((row) => {
            const r = /** @type {Record<string, unknown>} */ (row);
            const line = `${r.created_at || ''} [${r.scope}] ${r.setting_key}`;
            $ul.append(
                `<li class="py-2"><span class="text-gray-600 dark:text-gray-400">${line}</span></li>`,
            );
        });
    } catch {
        $ul.append('<li class="py-2 text-red-600">Could not load audit log.</li>');
    }
}

async function promptPasswordAndStepUp() {
    const r = await Swal.fire({
        title: 'Verify password',
        input: 'password',
        inputLabel: 'Current password',
        showCancelButton: true,
        focusCancel: true,
        reverseButtons: true,
        confirmButtonText: 'Verify',
    });
    if (!r.isConfirmed || !r.value) {
        return;
    }
    try {
        const env = await apiPostJson('/ajax/settings/step-up', { password: r.value });
        const data = /** @type {Record<string, unknown>} */ (env.data || {});
        stepUpToken = typeof data.step_up_token === 'string' ? data.step_up_token : null;
        showToast({
            title: 'Verified',
            text: 'You can save sensitive changes for a short time.',
            severity: 'success',
            timer: 5000,
            dedupe_key: 'settings_step_up_ok',
        });
    } catch {
        stepUpToken = null;
    }
}

$('#btn-step-up, #btn-step-up-msg, #btn-step-up-risk').on('click', () => {
    void promptPasswordAndStepUp();
});

$('#form-user-settings').on('submit', (e) => {
    e.preventDefault();
    clearFieldErrors();
    const payload = {
        theme: String($('#set-theme').val()),
        locale: $('#set-locale').val() ? String($('#set-locale').val()) : null,
        timezone: $('#set-tz').val() ? String($('#set-tz').val()) : null,
        settings_version: Number($('#set-user-version').val()),
    };
    void apiPutJson('/ajax/settings/user', payload).then((env) => {
        const data = /** @type {Record<string, unknown>} */ (env.data || {});
        applySnapshot(data);
    }).catch((err) => {
        if (err && typeof err === 'object' && 'envelope' in err) {
            const env = /** @type {Record<string, unknown>} */ (
                /** @type {{ envelope: Record<string, unknown> }} */ (err).envelope
            );
            if (env.errors && typeof env.errors === 'object') {
                applyFieldErrors(/** @type {Record<string, string[]|string>} */ (env.errors));
            }
        }
    });
});

$('#form-security-policy').on('submit', (e) => {
    e.preventDefault();
    clearFieldErrors();
    const payload = {
        idle_timeout_minutes: Number($('#set-idle').val()),
        notify_new_device_login: $('#set-notify-dev').is(':checked'),
        settings_version: Number($('#set-security-version').val()),
    };
    const maxSess = $('#set-max-sess').val();
    if (maxSess !== '' && maxSess != null) {
        payload.max_session_duration_minutes = Number(maxSess);
    } else {
        payload.max_session_duration_minutes = null;
    }
    if (stepUpToken) {
        payload.step_up_token = stepUpToken;
    }
    void apiPutJson('/ajax/settings/security-policy', payload)
        .then((env) => {
            const data = /** @type {Record<string, unknown>} */ (env.data || {});
            applySnapshot(data);
            stepUpToken = null;
        })
        .catch((err) => {
            if (err && typeof err === 'object' && 'envelope' in err) {
                const env = /** @type {Record<string, unknown>} */ (
                    /** @type {{ envelope: Record<string, unknown> }} */ (err).envelope
                );
                if (env.errors && typeof env.errors === 'object') {
                    applyFieldErrors(/** @type {Record<string, string[]|string>} */ (env.errors));
                }
            }
        });
});

$('#form-messaging-privacy').on('submit', (e) => {
    e.preventDefault();
    clearFieldErrors();
    const payload = {
        read_receipts_enabled: $('#set-rr').is(':checked'),
        typing_indicators_enabled: $('#set-typing').is(':checked'),
        max_attachment_mb: Number($('#set-att').val()),
        safety_warnings_enabled: $('#set-safe').is(':checked'),
        settings_version: Number($('#set-messaging-version').val()),
    };
    if (stepUpToken) {
        payload.step_up_token = stepUpToken;
    }
    void apiPutJson('/ajax/settings/messaging-privacy', payload)
        .then((env) => {
            const data = /** @type {Record<string, unknown>} */ (env.data || {});
            applySnapshot(data);
            stepUpToken = null;
        })
        .catch((err) => {
            if (err && typeof err === 'object' && 'envelope' in err) {
                const env = /** @type {Record<string, unknown>} */ (
                    /** @type {{ envelope: Record<string, unknown> }} */ (err).envelope
                );
                if (env.errors && typeof env.errors === 'object') {
                    applyFieldErrors(/** @type {Record<string, string[]|string>} */ (env.errors));
                }
            }
        });
});

$('#form-risk-thresholds').on('submit', (e) => {
    e.preventDefault();
    clearFieldErrors();
    const fiat = $('#set-large-tx').val();
    const payload = {
        large_tx_alert_fiat: fiat !== '' && fiat != null ? String(fiat) : null,
        large_tx_alert_currency: String($('#set-large-cur').val() || 'USD').toUpperCase().slice(0, 3),
        settings_version: Number($('#set-risk-version').val()),
    };
    if (stepUpToken) {
        payload.step_up_token = stepUpToken;
    }
    void apiPutJson('/ajax/settings/risk-thresholds', payload)
        .then((env) => {
            const data = /** @type {Record<string, unknown>} */ (env.data || {});
            applySnapshot(data);
            stepUpToken = null;
        })
        .catch((err) => {
            if (err && typeof err === 'object' && 'envelope' in err) {
                const env = /** @type {Record<string, unknown>} */ (
                    /** @type {{ envelope: Record<string, unknown> }} */ (err).envelope
                );
                if (env.errors && typeof env.errors === 'object') {
                    applyFieldErrors(/** @type {Record<string, string[]|string>} */ (env.errors));
                }
            }
        });
});

void loadSettings();
