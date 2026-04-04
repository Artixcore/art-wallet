import $ from 'jquery';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { apiGetJson, apiPostJson, apiPutJson } from './lib/artWalletAjax.js';
import { showToast } from './lib/artWalletUi.js';

const csrf = document.querySelector('meta[name="csrf-token"]');
if (csrf) {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrf.getAttribute('content') },
    });
}

const root = document.querySelector('[data-agents-editor]');
if (!root) {
    throw new Error('agents editor root missing');
}

const showUrl = root.getAttribute('data-ajax-show-url') || '';
const updateUrl = root.getAttribute('data-ajax-update-url') || '';
const promptUrl = root.getAttribute('data-ajax-prompt-url') || '';
const runUrl = root.getAttribute('data-ajax-run-url') || '';

async function loadAgent() {
    try {
        const env = await apiGetJson(showUrl);
        const data = /** @type {Record<string, unknown>} */ (env.data || {});
        const agent = /** @type {Record<string, unknown>} */ (data.agent || {});
        const prompt = /** @type {Record<string, unknown>|undefined} */ (
            typeof data.latest_prompt === 'object' && data.latest_prompt ? data.latest_prompt : undefined
        );
        $('#agent-name').val(String(agent.name ?? ''));
        $('#agent-description').val(agent.description != null ? String(agent.description) : '');
        if (prompt) {
            $('#agent-system-prompt').val(
                prompt.system_prompt != null ? String(prompt.system_prompt) : '',
            );
        }
    } catch {
        showToast({
            title: 'Agents',
            text: 'Could not load agent.',
            severity: 'danger',
            timer: 6000,
            dedupe_key: 'agent_load_fail',
        });
    }
}

$('#agent-save-meta').on('click', async () => {
    try {
        await apiPutJson(updateUrl, {
            name: String($('#agent-name').val() || ''),
            description: String($('#agent-description').val() || '') || null,
        });
        showToast({
            title: 'Saved',
            text: 'Agent details updated.',
            severity: 'success',
            timer: 4000,
            dedupe_key: 'agent_meta_saved',
        });
    } catch {
        // handled
    }
});

$('#agent-save-prompt').on('click', async () => {
    try {
        await apiPutJson(promptUrl, {
            system_prompt: String($('#agent-system-prompt').val() || ''),
        });
        showToast({
            title: 'Saved',
            text: 'Prompt version updated.',
            severity: 'success',
            timer: 4000,
            dedupe_key: 'agent_prompt_saved',
        });
    } catch {
        // handled
    }
});

$('#agent-run-btn').on('click', async () => {
    const message = String($('#agent-run-input').val() || '').trim();
    if (!message) {
        await Swal.fire({
            icon: 'warning',
            title: 'Message required',
            text: 'Enter a user message to send to the agent.',
        });
        return;
    }
    Swal.fire({
        title: 'Queuing run…',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });
    try {
        const env = await apiPostJson(runUrl, { message });
        Swal.close();
        const data = /** @type {Record<string, unknown>} */ (env.data || {});
        const runId = data.run_id != null ? String(data.run_id) : '';
        showToast({
            title: 'Queued',
            text: 'Run queued. Poll status or refresh shortly.',
            severity: 'success',
            timer: 5000,
            dedupe_key: 'agent_run_queued',
        });
        if (runId) {
            const poll = async () => {
                try {
                    const st = await apiGetJson(`/ajax/agents/runs/${runId}`);
                    const d = /** @type {Record<string, unknown>} */ (st.data || {});
                    const run = /** @type {Record<string, unknown>} */ (d.run || {});
                    const status = String(run.status || '');
                    if (status === 'succeeded' || status === 'failed') {
                        $('#agent-run-output').removeClass('hidden').text(
                            String(run.output_text || run.error_message || run.status || ''),
                        );
                        return;
                    }
                    setTimeout(poll, 1500);
                } catch {
                    // stop
                }
            };
            setTimeout(poll, 2000);
        }
    } catch {
        Swal.close();
    }
});

void loadAgent();
