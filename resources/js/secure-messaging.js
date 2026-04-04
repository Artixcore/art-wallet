import $ from 'jquery';
import Swal from 'sweetalert2';
import { showToast, showModalFromEnvelope } from './lib/artWalletUi.js';
import * as MessagingCrypto from './lib/messagingCrypto.js';

const csrf = document.querySelector('meta[name="csrf-token"]');
if (csrf) {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrf.getAttribute('content') },
    });
}

/** @type {number | null} */
let currentConversationId = null;

function ajaxBase() {
    const root = document.getElementById('secure-messaging-root');
    return root?.dataset?.messagingEndpoint || '/ajax';
}

function currentUserId() {
    const root = document.getElementById('secure-messaging-root');
    const raw = root?.dataset?.currentUserId;
    return raw ? Number(raw) : null;
}

/**
 * @param {string} url
 * @param {RequestInit} [opts]
 */
async function fetchEnvelope(url, opts = {}) {
    const r = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(opts.body instanceof FormData ? {} : { 'Content-Type': 'application/json' }),
            ...opts.headers,
        },
        ...opts,
    });
    const json = await r.json().catch(() => null);
    return { ok: r.ok, status: r.status, json };
}

/**
 * @param {unknown[]} rows
 */
function renderConversationList(rows) {
    const q = String($('#messaging-conv-filter').val() || '').trim().toLowerCase();
    const el = $('#messaging-conversation-list');
    el.empty();
    const filtered = q
        ? rows.filter((c) => String(c.conversation_id).includes(q) || String(c.public_id || '').toLowerCase().includes(q))
        : rows;
    if (!filtered.length) {
        el.append(`<p class="text-gray-500 text-sm p-2">${q ? 'No matches.' : 'No conversations yet.'}</p>`);
        return;
    }
    filtered.forEach((c) => {
        const unread = c.unread_count > 0 ? ` <span class="ml-1 inline-flex min-w-[1.25rem] justify-center rounded-full bg-indigo-600 px-1.5 text-xs font-semibold text-white">${c.unread_count}</span>` : '';
        const active =
            currentConversationId !== null && Number(c.conversation_id) === Number(currentConversationId)
                ? ' ring-2 ring-indigo-500 bg-indigo-50 dark:bg-indigo-950/40'
                : '';
        el.append(
            `<button type="button" class="w-full text-left rounded-lg px-3 py-2.5 mb-1 transition hover:bg-gray-100 dark:hover:bg-gray-700/80${active}" data-conv="${c.conversation_id}">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-medium text-gray-900 dark:text-gray-100 text-sm truncate">#${c.conversation_id}</span>
                    ${unread}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 truncate font-mono">${c.public_id || ''}</div>
            </button>`,
        );
    });
}

/** @type {unknown[]} */
let lastConversations = [];

async function loadConversations() {
    const { ok, json } = await fetchEnvelope(`${ajaxBase()}/conversations`);
    if (!ok || !json?.success) {
        showToast({
            title: json?.message || 'Could not load conversations',
            severity: 'danger',
            dedupe_key: 'messaging:list-fail',
        });
        return;
    }
    lastConversations = json.data?.conversations || [];
    renderConversationList(lastConversations);
    if (json.modal) {
        await showModalFromEnvelope(json.modal);
    }
}

/**
 * @param {number} id
 */
async function openConversation(id) {
    currentConversationId = id;
    $('#messaging-thread-title').text(`Conversation #${id}`);
    $('#messaging-thread-sub').text('');

    const { ok, json } = await fetchEnvelope(`${ajaxBase()}/conversations/${id}/messages`);
    if (!ok || !json?.success) {
        showToast({
            title: json?.message || 'Failed to load messages',
            severity: 'danger',
        });
        return;
    }
    const msgs = json.data?.messages || [];
    const me = currentUserId();
    const inner = $('#messaging-chat-inner');
    const lines = msgs
        .map((m) => {
            const mine = me !== null && Number(m.sender_id) === me;
            const bubble = mine
                ? 'ml-auto bg-indigo-600 text-white'
                : 'mr-auto bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border border-gray-200 dark:border-gray-600';
            const ct = typeof m.ciphertext === 'string' ? m.ciphertext.slice(0, 36) : '';
            return `<div class="flex w-full mb-3 ${mine ? 'justify-end' : 'justify-start'}">
                <div class="max-w-[85%] rounded-2xl px-3 py-2 text-xs shadow-sm ${bubble}">
                    <div class="font-mono break-all opacity-90">idx=${m.message_index} · ct=${ct}…</div>
                    <div class="text-[10px] mt-1 opacity-70">sender=${m.sender_id} · ${m.delivery?.state || 'n/a'}</div>
                </div>
            </div>`;
        })
        .join('');
    inner.html(`<div class="space-y-1">${lines || '<p class="text-sm text-gray-500">No messages yet.</p>'}</div>`);
    if (json.modal) {
        await showModalFromEnvelope(json.modal);
    }

    renderConversationList(lastConversations);

    const last = msgs.length ? msgs[msgs.length - 1] : null;
    if (last && last.id) {
        void fetchEnvelope(`${ajaxBase()}/conversations/${id}/read`, {
            method: 'POST',
            body: JSON.stringify({ last_read_message_id: last.id }),
        });
    }
}

function showNewMessageModal() {
    void Swal.fire({
        title: 'New message',
        html:
            '<p class="text-sm text-gray-600 dark:text-gray-300 text-left mb-2">Paste a <strong>verified</strong> Solana address. Only users who synced that address to ArtWallet can be found.</p>' +
            '<textarea id="swal-sol-input" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm font-mono p-2" rows="3" placeholder="Solana address…"></textarea>' +
            '<p id="swal-sol-err" class="text-red-600 text-xs text-left mt-1 hidden"></p>',
        showCancelButton: true,
        confirmButtonText: 'Find contact',
        cancelButtonText: 'Cancel',
        focusConfirm: false,
        didOpen: () => {
            document.getElementById('swal-sol-input')?.focus();
        },
        preConfirm: () => {
            const v = String(document.getElementById('swal-sol-input')?.value || '').trim();
            if (!v) {
                Swal.showValidationMessage('Enter a Solana address.');
                return false;
            }
            return v;
        },
    }).then((result) => {
        if (result.isConfirmed && typeof result.value === 'string') {
            void resolveSolAddress(result.value);
        }
    });
}

/**
 * @param {string} solAddress
 */
async function resolveSolAddress(solAddress) {
    Swal.fire({
        title: 'Checking address…',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        },
    });

    const { ok, json, status } = await fetchEnvelope(`${ajaxBase()}/messaging/resolve-sol-address`, {
        method: 'POST',
        body: JSON.stringify({ sol_address: solAddress }),
    });

    Swal.close();

    if (status === 429) {
        await Swal.fire({
            icon: 'warning',
            title: 'Too many attempts',
            text: json?.message || 'Please wait and try again.',
        });
        return;
    }

    if (!json) {
        showToast({ title: 'Unexpected response', severity: 'danger' });
        return;
    }

    if (json.modal) {
        await showModalFromEnvelope(json.modal);
    }

    const data = json.data || {};
    const crs = data.contact_resolution_status;

    if (json.success && crs === 'resolved_artwallet_user') {
        const existing = data.existing_conversation;
        if (existing?.conversation_id) {
            await Swal.fire({
                icon: 'success',
                title: 'Contact found',
                text: 'Opening your existing conversation.',
                timer: 2000,
                showConfirmButton: false,
            });
            await openConversation(Number(existing.conversation_id));
            await loadConversations();
            return;
        }

        await Swal.fire({
            icon: 'info',
            title: 'Contact found',
            html:
                '<p class="text-sm text-left">Recipient user ID: <code>' +
                (data.recipient?.user_id ?? '') +
                '</code></p>' +
                '<p class="text-sm text-left mt-2">Starting a <strong>new</strong> encrypted chat requires generating a conversation key and wraps in your browser (E2E). Register your messaging identity key in Settings if you have not already.</p>',
        });
        return;
    }

    if (json.success && crs === 'not_found') {
        await Swal.fire({
            icon: 'info',
            title: 'No contact found',
            text: json.message || 'We could not find a matching ArtWallet user for this address.',
        });
        return;
    }

    if (!json.success) {
        const code = json.code || '';
        if (code === 'MESSAGING_SELF_CONVERSATION') {
            await Swal.fire({ icon: 'warning', title: 'Your address', text: json.message });
            return;
        }
        if (code === 'MESSAGING_KEY_REQUIRED') {
            await Swal.fire({
                icon: 'warning',
                title: 'Messaging not ready',
                text: json.message || 'The other user must register a messaging key.',
            });
            return;
        }
        if (code === 'MESSAGING_DM_REQUIRES_APPROVAL') {
            await Swal.fire({
                icon: 'info',
                title: 'Approval required',
                text: json.message || 'This user does not accept new direct messages without approval.',
            });
            return;
        }
        if (code === 'SOL_ADDRESS_INVALID') {
            await Swal.fire({ icon: 'error', title: 'Invalid address', text: json.message });
            return;
        }
        showToast({
            title: json.message || 'Could not resolve address',
            severity: 'danger',
        });
    }
}

$(async () => {
    await loadConversations();

    $('#messaging-conv-filter').on('input', () => {
        renderConversationList(lastConversations);
    });

    $('#messaging-conversation-list').on('click', '[data-conv]', async function () {
        const id = Number($(this).data('conv'));
        await openConversation(id);
    });

    $('#messaging-new-message, #messaging-empty-new').on('click', () => {
        showNewMessageModal();
    });

    $('#secure-messaging-root').on('click', '#messaging-demo-encrypt', async () => {
        const key = MessagingCrypto.generateLocalConversationKey();
        const { ciphertext, nonce, alg, version } = await MessagingCrypto.encryptMessageUtf8('Hello from browser E2E demo', key);
        await Swal.fire({
            icon: 'info',
            title: 'Local encrypt demo',
            html: `<p class="text-left text-sm">This shows AES-256-GCM in the browser. Your real app unwraps <code>conv_key</code> from the server-stored wrap.</p>
            <pre class="text-xs text-left mt-2 overflow-auto max-h-40">ct: ${ciphertext.slice(0, 24)}…\nnonce: ${nonce.slice(0, 24)}…\nalg: ${alg} v${version}</pre>`,
        });
    });
});
