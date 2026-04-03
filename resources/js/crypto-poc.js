import $ from 'jquery';
import { generateMnemonic } from '@scure/bip39';
import { wordlist } from '@scure/bip39/wordlists/english.js';
import { encryptWalletVault, decryptWalletVault } from './lib/walletVault.js';
import { buildChainsFromMnemonic } from './lib/hdChains.js';

const csrf = document.querySelector('meta[name="csrf-token"]');
if (csrf) {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrf.getAttribute('content') },
    });
}

function setStatus(msg) {
    $('#poc-status').text(msg);
}

function setOutput(obj) {
    $('#poc-output').text(JSON.stringify(obj, null, 2));
}

let lastEnvelope = null;

$('#poc-gen-mnemonic').on('click', () => {
    const m = generateMnemonic(wordlist, 256);
    $('#poc-mnemonic').text(m);
    setStatus('Mnemonic generated (display once in real flows).');
    lastEnvelope = null;
    setOutput({});
});

$('#poc-encrypt').on('click', async () => {
    const password = $('#poc-wallet-password').val();
    if (!password) {
        setStatus('Enter wallet password.');
        return;
    }
    const mnemonic = $('#poc-mnemonic').text().trim();
    setStatus('Deriving keys and encrypting (Argon2id)…');
    try {
        const chains = mnemonic ? buildChainsFromMnemonic(mnemonic) : {};
        const plaintext = {
            v: 1,
            chains,
            created_at: new Date().toISOString(),
        };
        lastEnvelope = await encryptWalletVault(plaintext, String(password));
        setStatus('Encrypted in browser (HD chains embedded when mnemonic present).');
        setOutput({ ...lastEnvelope, chains_preview: Object.keys(chains) });
    } catch (e) {
        setStatus('Encrypt failed.');
        setOutput({ error: String(e) });
        lastEnvelope = null;
    }
});

$('#poc-decrypt').on('click', async () => {
    if (!lastEnvelope) {
        setStatus('Encrypt first.');
        return;
    }
    const password = $('#poc-wallet-password').val();
    if (!password) {
        setStatus('Enter wallet password.');
        return;
    }
    setStatus('Deriving key…');
    try {
        const json = await decryptWalletVault(lastEnvelope, String(password));
        setStatus('Decrypted OK.');
        setOutput({ decrypted: json });
    } catch {
        setStatus('Decrypt failed (wrong password or corrupt blob).');
        setOutput({ error: true });
    }
});

$('#poc-save-wallet').on('click', async () => {
    if (!lastEnvelope) {
        setStatus('Encrypt first.');
        return;
    }
    setStatus('Saving ciphertext to server…');
    const payload = {
        label: 'PoC wallet',
        public_wallet_id: crypto.randomUUID(),
        vault_version: '1',
        wallet_vault: lastEnvelope,
    };
    try {
        const res = await fetch('/ajax/wallets', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf?.getAttribute('content') ?? '',
            },
            body: JSON.stringify(payload),
        });
        const body = await res.json().catch(() => ({}));
        if (!res.ok) {
            setStatus(`Save failed: ${res.status}`);
            setOutput(body);
            return;
        }
        setStatus('Wallet ciphertext stored (server cannot decrypt).');
        setOutput(body);
    } catch (e) {
        setStatus('Save failed (network).');
        setOutput({ error: String(e) });
    }
});

$('#poc-ajax-health').on('click', () => {
    setStatus('Calling /ajax/health…');
    $.getJSON('/ajax/health')
        .done((data) => {
            setStatus('AJAX OK.');
            setOutput(data);
        })
        .fail((xhr) => {
            setStatus(`AJAX failed: ${xhr.status}`);
            setOutput({ status: xhr.status, body: xhr.responseText });
        });
});
