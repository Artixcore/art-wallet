import $ from 'jquery';
import { generateMnemonic, validateMnemonic } from '@scure/bip39';
import { wordlist } from '@scure/bip39/wordlists/english.js';
import { apiPostJson } from './lib/artWalletAjax.js';
import { confirmDanger, showToast } from './lib/artWalletUi.js';
import { getOrCreateDeviceKeyPair, signChallengeMessage } from './lib/deviceLoginTrust.js';
import { encryptRecoveryKit } from './lib/recoveryKit.js';

const csrf = document.querySelector('meta[name="csrf-token"]');
if (csrf) {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrf.getAttribute('content') },
    });
}

const meta = document.getElementById('sec-page-meta');
const USER_ID = meta ? Number(meta.dataset.userId) : 0;

const PURPOSE_NEW_DEVICE = 'new_device';

function showPanel(name) {
    $('.sec-panel').addClass('hidden');
    $(`#sec-panel-${name}`).removeClass('hidden');
    $('.sec-tab').removeClass('bg-indigo-100 dark:bg-indigo-900/50 text-indigo-900 dark:text-indigo-100').addClass('text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800');
    $(`.sec-tab[data-sec-tab="${name}"]`).removeClass('text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800').addClass('bg-indigo-100 dark:bg-indigo-900/50 text-indigo-900 dark:text-indigo-100');
}

$('.sec-tab').on('click', function tabClick() {
    const name = $(this).data('sec-tab');
    showPanel(name);
    if (name === 'trusted') {
        refreshDevices();
    }
});

$('#sec-gen-mnemonic').on('click', () => {
    const m = generateMnemonic(wordlist, 256);
    $('#sec-mnemonic-display').text(m);
    $('#sec-backup-msg').text('');
});

$('#sec-verify-mnemonic').on('click', () => {
    const expected = $('#sec-mnemonic-display').text().trim();
    const got = $('#sec-mnemonic-confirm').val().trim().replace(/\s+/g, ' ');
    if (!expected) {
        $('#sec-backup-msg').text('Generate a mnemonic first.');
        return;
    }
    if (got !== expected) {
        $('#sec-backup-msg').text('Words do not match. Double-check your backup.');
        return;
    }
    if (!validateMnemonic(got, wordlist)) {
        $('#sec-backup-msg').text('Invalid BIP39 mnemonic.');
        return;
    }
    $.post('/ajax/security/backup-state', { mnemonic_verified: true })
        .done(() => {
            $('#sec-backup-msg').text('Recorded: mnemonic verification timestamp stored (no words sent).');
        })
        .fail(() => {
            $('#sec-backup-msg').text('Could not save state.');
        });
});

function buildChallengeMessage(nonce, code, userId, purpose, trustVersion) {
    return `artwallet-device-challenge-v1|${nonce}|${code}|${userId}|${purpose}|${trustVersion}`;
}

function refreshDevices() {
    return $.getJSON('/ajax/security/trusted-devices').done((res) => {
        const $ul = $('#sec-device-list').empty();
        if (!res.devices || !res.devices.length) {
            $ul.append('<li class="py-2 text-gray-500">No devices yet.</li>');
            return;
        }
        res.devices.forEach((d) => {
            const revoked = d.revoked_at ? ' (revoked)' : '';
            const $li = $('<li class="py-3 flex justify-between gap-2 items-center"></li>');
            $li.append(`<span class="font-mono text-xs break-all">${d.public_key.slice(0, 24)}…${revoked}</span>`);
            if (!d.revoked_at) {
                const $b = $('<button type="button" class="shrink-0 text-red-600 text-sm font-medium">Revoke</button>');
                $b.on('click', () => {
                    $.ajax({ url: `/ajax/security/trusted-devices/${d.id}`, type: 'DELETE' }).done(() => refreshDevices());
                });
                $li.append($b);
            }
            $ul.append($li);
        });
    });
}

$('#sec-register-device').on('click', () => {
    const { publicKeyB64 } = getOrCreateDeviceKeyPair(USER_ID);
    $.post('/ajax/security/trusted-devices', {
        public_key: publicKeyB64,
        fingerprint_signals_json: { source: 'security-center' },
    })
        .done(() => {
            refreshDevices();
        })
        .fail((xhr) => {
            const err = xhr.responseJSON?.error;
            showToast({
                title: err === 'device_already_registered' ? 'Already registered' : 'Registration failed',
                text:
                    err === 'device_already_registered'
                        ? 'This browser key is already registered.'
                        : 'Could not register this device.',
                severity: 'danger',
                timer: 6000,
            });
        });
});

let lastChallenge = null;

$('#sec-create-challenge').on('click', () => {
    $.post('/ajax/security/challenges', { purpose: PURPOSE_NEW_DEVICE })
        .done((res) => {
            lastChallenge = res.challenge;
            $('#sec-challenge-new').text(JSON.stringify(res.challenge, null, 2));
        })
        .fail(() =>
            showToast({ title: 'Challenge failed', text: 'Could not create challenge.', severity: 'danger' }),
        );
});

$('#sec-poll-status').on('click', () => {
    $.getJSON('/ajax/security/challenges/status')
        .done((res) => {
            $('#sec-challenge-new').append(`\n\nstatus: ${JSON.stringify(res.challenge)}`);
        })
        .fail(() => showToast({ title: 'Status failed', severity: 'danger' }));
});

$('#sec-load-pending').on('click', () => {
    $.getJSON('/ajax/security/challenges/pending')
        .done((res) => {
            $('#sec-challenge-trusted').text(JSON.stringify(res.pending, null, 2));
        })
        .fail(() => showToast({ title: 'Could not load pending', severity: 'danger' }));
});

$('#sec-approve-first').on('click', async () => {
    const pendingRes = await $.getJSON('/ajax/security/challenges/pending');
    const first = pendingRes.pending && pendingRes.pending[0];
    if (!first) {
        showToast({ title: 'No pending challenges', severity: 'info' });

        return;
    }
    const devicesRes = await $.getJSON('/ajax/security/trusted-devices');
    const { publicKeyB64, privateKeyB64 } = getOrCreateDeviceKeyPair(USER_ID);
    const device = devicesRes.devices.find((d) => d.public_key === publicKeyB64 && !d.revoked_at);
    if (!device) {
        showToast({
            title: 'Not a trusted device',
            text: 'This browser is not registered as a trusted device.',
            severity: 'warning',
        });

        return;
    }
    const msg = buildChallengeMessage(
        first.nonce,
        first.client_code,
        USER_ID,
        first.purpose,
        device.trust_version,
    );
    const signature = signChallengeMessage(msg, privateKeyB64);
    $.post('/ajax/security/challenges/approve', {
        challenge_public_uuid: first.public_uuid,
        login_trusted_device_id: device.id,
        signature,
    })
        .done(() => {
            $('#sec-challenge-trusted').text('Approved. New device can poll status.');
        })
        .fail(() => showToast({ title: 'Approval failed', severity: 'danger' }));
});

$('#sec-load-sessions').on('click', () => {
    $.getJSON('/ajax/security/sessions')
        .done((res) => {
            const $ul = $('#sec-session-list').empty();
            (res.sessions || []).forEach((s) => {
                const cur = s.is_current ? ' (current)' : '';
                const $li = $(`<li class="py-3 flex justify-between gap-2 items-center"><span>#${s.id}${cur} · ${s.last_seen_at || '—'}</span></li>`);
                if (!s.is_current) {
                    const $b = $('<button type="button" class="text-red-600 text-sm font-medium">Revoke</button>');
                    $b.on('click', () => {
                        $.ajax({ url: `/ajax/security/sessions/${s.id}`, type: 'DELETE' }).done(() => $('#sec-load-sessions').trigger('click'));
                    });
                    $li.append($b);
                }
                $ul.append($li);
            });
        })
        .fail(() => showToast({ title: 'Could not load sessions', severity: 'danger' }));
});

$('#sec-revoke-others').on('click', async () => {
    const ok = await confirmDanger({
        title: 'Log out other sessions?',
        text: 'This will sign out all other browsers using your account.',
        confirmButtonText: 'Revoke others',
    });
    if (!ok) {
        return;
    }
    $.post('/ajax/security/sessions/revoke-others')
        .done(() => $('#sec-load-sessions').trigger('click'))
        .fail(() => showToast({ title: 'Failed to revoke sessions', severity: 'danger' }));
});

const KIT_VERSION = 1;
let lastKitEnvelope = null;

$('#sec-build-kit').on('click', async () => {
    const mnemonic = String($('#sec-kit-mnemonic').val()).trim();
    const pass = String($('#sec-kit-pass').val());
    $('#sec-kit-msg').text('');
    if (!mnemonic || !pass) {
        $('#sec-kit-msg').text('Enter mnemonic and kit passphrase.');
        return;
    }
    if (!validateMnemonic(mnemonic.replace(/\s+/g, ' '), wordlist)) {
        $('#sec-kit-msg').text('Invalid BIP39 mnemonic.');
        return;
    }
    try {
        const payload = { v: 1, kind: 'mnemonic', mnemonic };
        const envelope = await encryptRecoveryKit(payload, pass, USER_ID, KIT_VERSION);
        lastKitEnvelope = envelope;
        const blob = new Blob([JSON.stringify(envelope, null, 2)], { type: 'application/json' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `artwallet-recovery-kit-${USER_ID}.json`;
        a.click();
        URL.revokeObjectURL(a.href);
        $('#sec-kit-msg').text('Download started. Store the file offline.');
    } catch (e) {
        $('#sec-kit-msg').text(`Build failed: ${e}`);
    }
});

$('#sec-upload-kit').on('click', async () => {
    if (!lastKitEnvelope) {
        $('#sec-kit-msg').text('Build a kit first (or re-select file manually in a future version).');
        return;
    }
    try {
        await apiPostJson('/ajax/security/recovery-kit', { recovery_kit: lastKitEnvelope });
        $('#sec-kit-msg').text('Encrypted kit synced to server (server cannot decrypt).');
    } catch (e) {
        $('#sec-kit-msg').text(e instanceof Error ? e.message : 'Upload failed.');
    }
});

$('#sec-load-events').on('click', () => {
    $.getJSON('/ajax/security/events')
        .done((res) => {
            const $ul = $('#sec-event-list').empty();
            (res.events || []).forEach((e) => {
                $ul.append(
                    `<li class="py-2"><span class="text-gray-500">${e.created_at}</span> · ${e.severity} · ${e.event_type}</li>`,
                );
            });
        })
        .fail(() => showToast({ title: 'Could not load events', severity: 'danger' }));
});

