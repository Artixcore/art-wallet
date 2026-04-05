import $ from 'jquery';
import Swal from 'sweetalert2';
import {
    buildOnboardingVaultPayload,
    computePassphraseVerifierHmacHex,
    generate24WordMnemonic,
    normalizeMnemonic,
} from './lib/onboardingCrypto.js';

const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

function cfg() {
    const el = document.getElementById('onboarding-config');
    if (!el?.textContent) {
        return null;
    }
    return JSON.parse(el.textContent);
}

function setProgress(step) {
    $('#onboarding-progress li').each(function () {
        const n = Number($(this).data('step'));
        $(this).removeClass('bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-200');
        if (n === step) {
            $(this).addClass('bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-200');
        }
    });
}

function showStep(n) {
    $('#step-signup, #step-encrypt, #step-reveal, #step-confirm').addClass('hidden');
    if (n === 1) {
        $('#step-signup').removeClass('hidden');
    }
    if (n === 2) {
        $('#step-encrypt').removeClass('hidden');
    }
    if (n === 3) {
        $('#step-reveal').removeClass('hidden');
    }
    if (n === 4) {
        $('#step-confirm').removeClass('hidden');
    }
    setProgress(n);
}

async function ajaxJson(url, body, method = 'POST') {
    const res = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: body === undefined ? undefined : JSON.stringify(body),
    });
    const data = await res.json().catch(() => ({}));
    return { res, data };
}

function handleEnvelope(data) {
    if (data.toast && data.toast.title) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: data.toast.timer ?? 4000,
            icon: data.severity === 'success' ? 'success' : 'info',
            title: data.toast.title,
            text: data.toast.text,
        });
    }
    if (data.modal && data.modal.title) {
        return Swal.fire({
            icon: data.modal.icon ?? 'warning',
            title: data.modal.title,
            text: data.modal.text,
            showCancelButton: !!data.modal.showCancelButton,
            confirmButtonText: data.modal.confirmButtonText ?? 'OK',
        });
    }
    if (data.redirect) {
        window.location.href = data.redirect;
        return true;
    }
    return false;
}

let mnemonicHold = '';
let stepTokenHold = '';

function bind(config) {
    stepTokenHold = config.stepToken;

    $('#ob-saved-check').on('change', function () {
        $('#ob-ack-btn').prop('disabled', !this.checked);
    });

    $('#ob-signup-btn').on('click', async () => {
        $('#ob-username-err').addClass('hidden').text('');
        const username = String($('#ob-username').val() || '').trim();
        const password = String($('#ob-password').val() || '');
        const password_confirmation = String($('#ob-password2').val() || '');
        const { res, data } = await ajaxJson(config.routes.signup, {
            username,
            password,
            password_confirmation,
        });
        if (!res.ok) {
            if (data.errors) {
                const u = data.errors.username?.[0] || data.errors.password?.[0] || data.message;
                $('#ob-username-err').removeClass('hidden').text(u || __('Sign up failed'));
            }
            await Swal.fire({
                icon: 'error',
                title: __('Sign up failed'),
                text: data.message || '',
            });
            return;
        }
        if (data.toast) {
            handleEnvelope(data);
        }
        window.location.href = config.routes.onboarding;
    });

    $('#ob-vault-btn').on('click', async () => {
        const pwd = String($('#ob-enc-password').val() || '');
        if (!pwd) {
            await Swal.fire({ icon: 'warning', title: __('Password required'), text: __('Enter your password to encrypt locally.') });
            return;
        }
        $('#ob-vault-status').text(__('Working…'));
        try {
            mnemonicHold = generate24WordMnemonic();
            const normalized = normalizeMnemonic(mnemonicHold);
            const hmacHex = await computePassphraseVerifierHmacHex(config.verifierSaltHex, normalized);
            const { envelope, addresses } = await buildOnboardingVaultPayload(mnemonicHold, pwd);
            const { res, data } = await ajaxJson(config.routes.vault, {
                step_token: stepTokenHold,
                public_wallet_id: crypto.randomUUID(),
                vault_version: '1',
                wallet_vault: envelope,
                passphrase_verifier_hmac_hex: hmacHex,
                addresses,
            });
            if (!res.ok) {
                $('#ob-vault-status').text('');
                await Swal.fire({
                    icon: 'error',
                    title: __('Wallet setup failed'),
                    text: data.message || '',
                });
                return;
            }
            if (data.data?.step_token) {
                stepTokenHold = data.data.step_token;
            }
            const words = mnemonicHold.split(/\s+/);
            const grid = words.map((w, i) => `<span class="inline-block mr-2 mb-1"><span class="text-gray-400 text-xs">${i + 1}.</span> ${w}</span>`).join('');
            $('#ob-mnemonic-grid').html(grid);
            $('#ob-vault-status').text('');
            showStep(3);
            await Swal.fire({
                icon: 'warning',
                title: __('Copy warning'),
                text: __(
                    'Clipboard history and cloud sync can expose your phrase. Prefer writing on paper. If you copy, clear the clipboard afterward.',
                ),
            });
        } catch (e) {
            $('#ob-vault-status').text('');
            await Swal.fire({ icon: 'error', title: __('Error'), text: String(e) });
        }
    });

    $('#ob-copy-mnemonic').on('click', async () => {
        const r = await Swal.fire({
            icon: 'warning',
            title: __('Copy to clipboard?'),
            text: __('Clipboard is readable by other apps on this device.'),
            showCancelButton: true,
            confirmButtonText: __('Copy'),
            cancelButtonText: __('Cancel'),
        });
        if (!r.isConfirmed) {
            return;
        }
        await navigator.clipboard.writeText(mnemonicHold);
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: __('Copied'), showConfirmButton: false, timer: 2000 });
    });

    $('#ob-ack-btn').on('click', async () => {
        const { res, data } = await ajaxJson(config.routes.acknowledge, { step_token: stepTokenHold });
        if (!res.ok) {
            await Swal.fire({ icon: 'error', title: __('Could not continue'), text: data.message || '' });
            return;
        }
        if (data.data?.step_token) {
            stepTokenHold = data.data.step_token;
        }
        showStep(4);
    });

    $('#ob-confirm-btn').on('click', async () => {
        $('#ob-confirm-err').addClass('hidden').text('');
        const phrase = String($('#ob-mnemonic-confirm').val() || '');
        const { res, data } = await ajaxJson(config.routes.confirm, {
            step_token: stepTokenHold,
            mnemonic: phrase,
        });
        if (!res.ok) {
            const msg = data.message || data.errors?.mnemonic?.[0] || __('Confirmation failed');
            $('#ob-confirm-err').removeClass('hidden').text(msg);
            await Swal.fire({ icon: 'error', title: __('Does not match'), text: msg });
            return;
        }
        handleEnvelope(data);
        if (data.redirect) {
            window.location.href = data.redirect;
        }
    });
}

function __(s) {
    return s;
}

$(() => {
    const config = cfg();
    if (!config) {
        return;
    }
    bind(config);

    if (config.guestSignup) {
        showStep(1);
        return;
    }

    switch (config.onboardingState) {
        case 'awaiting_vault_upload':
            showStep(2);
            break;
        case 'awaiting_passphrase_ack':
            showStep(3);
            if (!mnemonicHold) {
                $('#ob-mnemonic-grid').html(
                    `<p class="text-amber-800 dark:text-amber-200 text-sm">${__(
                        'Your recovery phrase cannot be shown again in this browser session. If you did not write it down, sign out immediately and start over. If you saved it safely, confirm below to continue.',
                    )}</p>`,
                );
            }
            break;
        case 'awaiting_passphrase_confirm':
            showStep(4);
            break;
        default:
            showStep(2);
    }
});
