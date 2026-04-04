import $ from 'jquery';
import Swal from 'sweetalert2';
import { apiGetJson, apiPostJson } from './lib/artWalletAjax.js';
import { showToast } from './lib/artWalletUi.js';

const csrf = document.querySelector('meta[name="csrf-token"]');
if (csrf) {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrf.getAttribute('content') },
    });
}

const meta = document.getElementById('operator-page-meta');
const summaryUrl = meta?.dataset.summaryUrl || '';
const probesUrl = meta?.dataset.probesUrl || '';

/**
 * @param {Record<string, unknown>} env
 */
function applyMetaBanners(env) {
    const m = /** @type {Record<string, unknown>} */ (env.meta || {});
    const stale = m.stale === true;
    const partial = m.partial === true;
    const staleSubs = Array.isArray(m.stale_subsystems) ? m.stale_subsystems : [];

    $('#operator-stale-banner').toggleClass('hidden', !stale);
    $('#operator-partial-banner').toggleClass('hidden', !partial);

    if (stale) {
        $('#operator-stale-text').text(
            staleSubs.length ? ` ${staleSubs.join(', ')}` : ' ' + 'One or more probes exceeded freshness TTL.',
        );
    }
}

/**
 * @param {Record<string, unknown>} data
 */
function renderSummary(data) {
    const summary = /** @type {Record<string, unknown>} */ (data.summary || {});
    const overall = typeof summary.overall === 'string' ? summary.overall : 'unknown';
    $('#operator-overall').text(overall);
    $('#operator-server-time').text(
        typeof summary.server_time === 'string' ? `Server time: ${summary.server_time}` : '',
    );

    const incidents = typeof summary.incidents_open === 'number' ? summary.incidents_open : 0;
    $('#operator-incidents').text(
        incidents === 0
            ? 'No open security incidents recorded (counter only — not a guarantee of safety).'
            : `Open incidents: ${incidents}`,
    );

    const checks = Array.isArray(summary.checks) ? summary.checks : [];
    $('#operator-checks-loading').addClass('hidden');
    const $checks = $('#operator-checks').removeClass('hidden').empty();
    checks.forEach((c) => {
        const row = /** @type {Record<string, unknown>} */ (c);
        const status = typeof row.status === 'string' ? row.status : '?';
        const sub = typeof row.subsystem === 'string' ? row.subsystem : '';
        const key = typeof row.check_key === 'string' ? row.check_key : '';
        const obs = typeof row.observed_at === 'string' ? row.observed_at : '';
        $checks.append(
            `<li class="py-2 flex justify-between gap-4"><span>${sub} / ${key}</span><span class="font-mono text-xs">${status}</span><span class="text-xs text-gray-500">${obs}</span></li>`,
        );
    });
    if (checks.length === 0) {
        $checks.append('<li class="py-2 text-gray-500">No health checks yet — run probes or wait for the scheduler.</li>');
    }

    const rpc = Array.isArray(summary.rpc) ? summary.rpc : [];
    $('#operator-rpc-loading').addClass('hidden');
    const $rpc = $('#operator-rpc').removeClass('hidden').empty();
    rpc.forEach((r) => {
        const row = /** @type {Record<string, unknown>} */ (r);
        const chain = typeof row.chain === 'string' ? row.chain : '';
        const status = typeof row.status === 'string' ? row.status : '?';
        const obs = typeof row.observed_at === 'string' ? row.observed_at : '';
        $rpc.append(
            `<li class="py-2 flex justify-between gap-4"><span>${chain}</span><span class="font-mono text-xs">${status}</span><span class="text-xs text-gray-500">${obs}</span></li>`,
        );
    });
    if (rpc.length === 0) {
        $rpc.append('<li class="py-2 text-gray-500">No RPC probes yet.</li>');
    }
}

async function loadSummary() {
    $('#operator-checks-loading').removeClass('hidden');
    $('#operator-checks').addClass('hidden').empty();
    $('#operator-rpc-loading').removeClass('hidden');
    $('#operator-rpc').addClass('hidden').empty();

    try {
        const env = await apiGetJson(summaryUrl);
        applyMetaBanners(env);
        renderSummary(/** @type {Record<string, unknown>} */ (env.data || {}));
    } catch {
        // apiGetJson already toasts via applyEnvelope
    }
}

async function confirmRunProbes() {
    const r = await Swal.fire({
        title: 'Run health probes?',
        text: 'This executes RPC and database checks from the server. Continue?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Run probes',
        cancelButtonText: 'Cancel',
        focusCancel: true,
    });
    if (!r.isConfirmed) {
        return;
    }

    try {
        const env = await apiPostJson(probesUrl, {});
        applyMetaBanners(env);
        renderSummary(/** @type {Record<string, unknown>} */ (env.data || {}));
        showToast({
            title: 'Probes',
            text: typeof env.message === 'string' ? env.message : 'Updated.',
            severity: 'success',
        });
    } catch {
        /* handled */
    }
}

$('#operator-refresh').on('click', () => {
    void loadSummary();
});

$('#operator-run-probes').on('click', () => {
    void confirmRunProbes();
});

void loadSummary();
