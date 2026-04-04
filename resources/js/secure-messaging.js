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

function ajaxBase() {
    const root = document.getElementById('secure-messaging-root');
    return root?.dataset?.messagingEndpoint || '/ajax';
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

function renderConversationList(rows) {
    const el = $('#messaging-conversation-list');
    el.empty();
    if (!rows.length) {
        el.append('<p class="text-gray-500 text-sm">No conversations yet.</p>');
        return;
    }
    rows.forEach((c) => {
        const unread = c.unread_count > 0 ? ` <span class="text-red-600 font-semibold">(${c.unread_count})</span>` : '';
        el.append(
            `<div class="py-2 border-b border-gray-100 dark:border-gray-700 last:border-0" data-conv="${c.conversation_id}">
                <span class="font-medium text-gray-800 dark:text-gray-200">#${c.conversation_id}</span>
                <span class="text-xs text-gray-500">${c.public_id || ''}</span>${unread}
            </div>`,
        );
    });
}

function renderMessagesPlaceholder() {
    $('#messaging-chat').html(
        '<p class="text-sm text-gray-500">Ciphertext payloads load here. Decryption requires your conversation key (never sent to the server).</p>',
    );
}

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
    renderConversationList(json.data?.conversations || []);
    if (json.modal) {
        await showModalFromEnvelope(json.modal);
    }
}

$(async () => {
    renderMessagesPlaceholder();
    await loadConversations();

    $('#messaging-conversation-list').on('click', '[data-conv]', async function () {
        const id = $(this).data('conv');
        const { ok, json } = await fetchEnvelope(`${ajaxBase()}/conversations/${id}/messages`);
        if (!ok || !json?.success) {
            showToast({
                title: json?.message || 'Failed to load messages',
                severity: 'danger',
            });
            return;
        }
        const msgs = json.data?.messages || [];
        const lines = msgs
            .map(
                (m) =>
                    `<div class="mb-2 font-mono text-xs break-all">idx=${m.message_index} sender=${m.sender_id} ct=${m.ciphertext?.slice(0, 32)}… <span class="text-gray-400">delivery=${m.delivery?.state || 'n/a'}</span></div>`,
            )
            .join('');
        $('#messaging-chat').html(lines || '<p class="text-gray-500">No messages.</p>');
        if (json.modal) {
            await showModalFromEnvelope(json.modal);
        }
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
