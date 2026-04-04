import $ from 'jquery';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { apiGetJson, apiPostJson } from './lib/artWalletAjax.js';
import { showToast } from './lib/artWalletUi.js';

const csrf = document.querySelector('meta[name="csrf-token"]');
if (csrf) {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrf.getAttribute('content') },
    });
}

const root = document.querySelector('[data-agents-dashboard]');
if (!root) {
    throw new Error('agents dashboard root missing');
}

const dashboardUrl = root.getAttribute('data-ajax-dashboard-url') || '/ajax/agents/dashboard';
const agentsUrl = root.getAttribute('data-ajax-agents-url') || '/ajax/agents';
const credentialsUrl = root.getAttribute('data-ajax-credentials-url') || '/ajax/agents/credentials';

async function loadDashboard() {
    try {
        const env = await apiGetJson(dashboardUrl);
        const data = /** @type {Record<string, unknown>} */ (env.data || {});
        const w = /** @type {Record<string, unknown>} */ (data.widgets || {});
        $('[data-metric="active_agents"]').text(String(w.active_agents ?? '—'));
        $('[data-metric="runs_7d"]').text(String(w.runs_7d ?? '—'));
        $('[data-metric="credentials_count"]').text(String(w.credentials_count ?? '—'));
        $('[data-metric="degraded_providers"]').text(String(w.degraded_providers ?? '—'));

        const runs = /** @type {unknown[]} */ (data.recent_runs || []);
        const $runs = $('#agents-recent-runs').empty();
        if (!runs.length) {
            $runs.append('<li class="px-4 py-6 text-sm text-gray-500 text-center">No runs yet.</li>');
        } else {
            runs.forEach((row) => {
                const r = /** @type {Record<string, unknown>} */ (row);
                const agent = /** @type {Record<string, unknown>|undefined} */ (
                    typeof r.agent === 'object' && r.agent ? r.agent : undefined
                );
                const name = agent && typeof agent.name === 'string' ? agent.name : '—';
                const line = `${String(r.created_at || '')} · ${name} · ${String(r.status || '')}`;
                $runs.append(
                    `<li class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200 flex justify-between gap-4"><span>${line}</span></li>`,
                );
            });
        }
    } catch {
        showToast({
            title: 'Agents',
            text: 'Could not load dashboard.',
            severity: 'danger',
            timer: 6000,
            dedupe_key: 'agents_dash_fail',
        });
    }
}

async function loadAgentsTable() {
    try {
        const env = await apiGetJson(agentsUrl);
        const data = /** @type {Record<string, unknown>} */ (env.data || {});
        const agents = /** @type {unknown[]} */ (data.agents || []);
        const $tb = $('#agents-table-body').empty();
        if (!agents.length) {
            $tb.append(
                '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No agents yet. Create one to get started.</td></tr>',
            );
            return;
        }
        agents.forEach((row) => {
            const a = /** @type {Record<string, unknown>} */ (row);
            const id = String(a.id ?? '');
            const editHref = `/agents/${id}/edit`;
            $tb.append(`<tr>
                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-gray-100">${String(a.name ?? '')}</td>
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">${String(a.type ?? '')}</td>
                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">${String(a.status ?? '')}</td>
                <td class="px-4 py-3 text-sm text-right">
                    <a href="${editHref}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">Edit</a>
                </td>
            </tr>`);
        });
    } catch {
        $('#agents-table-body').html(
            '<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-red-600">Could not load agents.</td></tr>',
        );
    }
}

$('#agents-btn-refresh').on('click', () => {
    void loadDashboard();
    void loadAgentsTable();
});

$('#agents-btn-create').on('click', async () => {
    const r = await Swal.fire({
        title: 'Create agent',
        html:
            '<label class="block text-left text-sm text-gray-600 mb-1">Name</label>' +
            '<input id="swal-agent-name" class="swal2-input" placeholder="My assistant" maxlength="120">',
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Create',
        preConfirm: () => {
            const name = String(document.getElementById('swal-agent-name')?.value || '').trim();
            if (!name) {
                Swal.showValidationMessage('Enter a name.');
                return false;
            }
            return { name };
        },
    });
    if (!r.isConfirmed || !r.value) {
        return;
    }
    try {
        const env = await apiPostJson(agentsUrl, {
            name: /** @type {{ name: string }} */ (r.value).name,
            type: 'default',
        });
        const data = /** @type {Record<string, unknown>} */ (env.data || {});
        const agent = /** @type {Record<string, unknown>|undefined} */ (
            typeof data.agent === 'object' && data.agent ? data.agent : undefined
        );
        const id = agent && agent.id != null ? String(agent.id) : '';
        showToast({
            title: 'Created',
            text: 'Your agent is ready to configure.',
            severity: 'success',
            timer: 4000,
            dedupe_key: 'agent_created',
        });
        if (id) {
            window.location.href = `/agents/${id}/edit`;
        } else {
            void loadAgentsTable();
        }
    } catch {
        // envelope handled by apiPostJson
    }
});

void loadDashboard();
void loadAgentsTable();
