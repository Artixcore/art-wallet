import $ from 'jquery';
import { apiGetJson, apiPostJson } from './lib/artWalletAjax.js';
import { showToast } from './lib/artWalletUi.js';

const csrf = document.querySelector('meta[name="csrf-token"]');
if (csrf) {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrf.getAttribute('content') },
    });
}

function severityClass(sev) {
    switch (sev) {
        case 'danger':
        case 'critical':
            return 'text-red-700 dark:text-red-300';
        case 'warning':
            return 'text-amber-800 dark:text-amber-200';
        case 'success':
            return 'text-emerald-700 dark:text-emerald-300';
        default:
            return 'text-gray-800 dark:text-gray-200';
    }
}

function renderList(items) {
    const $ul = $('#notif-list').empty();
    if (!items.length) {
        $ul.append(
            `<li class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">${'No notifications yet.'}</li>`,
        );

        return;
    }
    items.forEach((n) => {
        const unread = !n.read_at;
        const ack = n.requires_ack && !n.acknowledged_at;
        const title = $('<div class="font-medium"></div>').text(n.title);
        title.addClass(severityClass(n.severity));
        const body = n.body ? $('<p class="mt-1 text-sm text-gray-600 dark:text-gray-400"></p>').text(n.body) : null;
        const meta = $('<p class="mt-1 text-xs text-gray-400"></p>').text(n.created_at);
        const actions = $('<div class="mt-2 flex flex-wrap gap-2"></div>');
        if (unread) {
            const $read = $('<button type="button" class="text-sm font-medium text-indigo-600 dark:text-indigo-400"></button>').text(
                'Mark read',
            );
            $read.on('click', async () => {
                try {
                    await apiPostJson(`/ajax/notifications/${n.id}/read`, {});
                    await loadPage();
                } catch (e) {
                    showToast({ title: e instanceof Error ? e.message : 'Failed', severity: 'danger' });
                }
            });
            actions.append($read);
        }
        if (ack) {
            const $ack = $('<button type="button" class="text-sm font-medium text-red-600 dark:text-red-400"></button>').text(
                'Acknowledge',
            );
            $ack.on('click', async () => {
                try {
                    await apiPostJson(`/ajax/notifications/${n.id}/acknowledge`, {});
                    await loadPage();
                } catch (e) {
                    showToast({ title: e instanceof Error ? e.message : 'Failed', severity: 'danger' });
                }
            });
            actions.append($ack);
        }
        if (n.action_url) {
            const $a = $('<a class="text-sm font-medium text-indigo-600 dark:text-indigo-400"></a>')
                .attr('href', n.action_url)
                .attr('target', '_blank')
                .attr('rel', 'noopener noreferrer')
                .text('Open link');
            actions.append($a);
        }
        const li = $('<li class="px-4 py-4"></li>');
        if (unread) {
            li.addClass('bg-indigo-50/50 dark:bg-indigo-950/20');
        }
        li.append(title);
        if (body) {
            li.append(body);
        }
        li.append(meta);
        li.append(actions);
        $ul.append(li);
    });
}

async function loadPage() {
    try {
        const env = await apiGetJson('/ajax/notifications?per_page=50');
        const data = env.data && typeof env.data === 'object' ? env.data : {};
        const items = /** @type {unknown} */ (data.notifications);
        const list = Array.isArray(items) ? items : [];
        renderList(list);
        const unread = typeof env.meta?.unread_count === 'number' ? env.meta.unread_count : 0;
        $('#notif-unread-label').text(unread ? `${unread} unread` : 'All caught up');
    } catch (e) {
        $('#notif-list').html(
            `<li class="px-4 py-8 text-center text-sm text-red-600">${e instanceof Error ? e.message : 'Error'}</li>`,
        );
    }
}

$('#notif-mark-all-read').on('click', async () => {
    try {
        await apiPostJson('/ajax/notifications/read-all', {});
        await loadPage();
    } catch (e) {
        showToast({ title: e instanceof Error ? e.message : 'Failed', severity: 'danger' });
    }
});

void loadPage();
