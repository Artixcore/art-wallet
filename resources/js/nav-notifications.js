import $ from 'jquery';
import { apiGetJson, apiPostJson } from './lib/artWalletAjax.js';

const csrf = document.querySelector('meta[name="csrf-token"]');
if (csrf) {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrf.getAttribute('content') },
    });
}

const $bell = $('#nav-notif-bell');
const $badge = $('#nav-notif-badge');
const $panel = $('#nav-notif-panel');
const $list = $('#nav-notif-list');

function setBadge(count) {
    if (!$badge.length) {
        return;
    }
    if (count > 0) {
        $badge.text(count > 99 ? '99+' : String(count)).removeClass('hidden');
    } else {
        $badge.addClass('hidden').text('');
    }
}

async function refreshBadge() {
    try {
        const env = await apiGetJson('/ajax/notifications/dropdown?limit=1');
        const c = typeof env.meta?.unread_count === 'number' ? env.meta.unread_count : 0;
        setBadge(c);
    } catch {
        /* ignore on nav */
    }
}

async function loadDropdown() {
    if (!$list.length) {
        return;
    }
    $list.html('<li class="px-3 py-4 text-sm text-gray-500">Loading…</li>');
    try {
        const env = await apiGetJson('/ajax/notifications/dropdown?limit=8');
        const data = env.data && typeof env.data === 'object' ? env.data : {};
        const raw = data.notifications;
        const items = Array.isArray(raw) ? raw : [];
        setBadge(typeof env.meta?.unread_count === 'number' ? env.meta.unread_count : 0);
        if (!items.length) {
            $list.html('<li class="px-3 py-4 text-sm text-gray-500">No notifications</li>');

            return;
        }
        $list.empty();
        items.forEach((n) => {
            const t = $('<div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate"></div>').text(n.title);
            const time = $('<div class="text-xs text-gray-400 mt-0.5"></div>').text(n.created_at || '');
            const li = $('<li class="px-3 py-2 border-b border-gray-100 dark:border-gray-700 last:border-0"></li>');
            if (!n.read_at) {
                li.addClass('bg-indigo-50/40 dark:bg-indigo-950/20');
            }
            li.append(t);
            li.append(time);
            if (n.action_url) {
                li.on('click', () => {
                    window.open(n.action_url, '_blank', 'noopener,noreferrer');
                });
                li.addClass('cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50');
            }
            $list.append(li);
        });
    } catch {
        $list.html('<li class="px-3 py-4 text-sm text-red-600">Could not load</li>');
    }
}

if ($bell.length) {
    let open = false;
    $bell.on('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        open = !open;
        if (open) {
            $panel.removeClass('hidden');
            void loadDropdown();
        } else {
            $panel.addClass('hidden');
        }
    });
    $(document).on('click', () => {
        if (open) {
            open = false;
            $panel.addClass('hidden');
        }
    });
    $panel.on('click', (e) => e.stopPropagation());

    $('#nav-notif-mark-read').on('click', async (e) => {
        e.preventDefault();
        try {
            await apiPostJson('/ajax/notifications/read-all', {});
            await refreshBadge();
            $panel.addClass('hidden');
        } catch {
            /* ignore */
        }
    });

    void refreshBadge();
    setInterval(() => {
        void refreshBadge();
    }, 60000);
}
