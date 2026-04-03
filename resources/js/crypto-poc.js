import $ from 'jquery';
import { generateMnemonic } from '@scure/bip39';
import { wordlist } from '@scure/bip39/wordlists/english.js';
import { argon2id } from 'hash-wasm';

const csrf = document.querySelector('meta[name="csrf-token"]');
if (csrf) {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrf.getAttribute('content') },
    });
}

const AAD_VAULT = 'vault-v1';
const VAULT_PLAINTEXT = {
    v: 1,
    chains: {},
    created_at: new Date().toISOString(),
};

function uint8ToB64(bytes) {
    let bin = '';
    bytes.forEach((b) => {
        bin += String.fromCharCode(b);
    });
    return btoa(bin);
}

function b64ToUint8(b64) {
    const bin = atob(b64);
    const out = new Uint8Array(bin.length);
    for (let i = 0; i < bin.length; i += 1) {
        out[i] = bin.charCodeAt(i);
    }
    return out;
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
    const salt = crypto.getRandomValues(new Uint8Array(16));
    setStatus('Deriving key (Argon2id)…');
    const derived = await argon2id({
        password: String(password),
        salt,
        parallelism: 1,
        iterations: 3,
        memorySize: 32768,
        hashLength: 32,
        outputType: 'binary',
    });
    const rawKey = derived instanceof Uint8Array ? derived : new Uint8Array(derived);
    const key = await crypto.subtle.importKey('raw', rawKey, { name: 'AES-GCM' }, false, ['encrypt', 'decrypt']);
    const nonce = crypto.getRandomValues(new Uint8Array(12));
    const plaintext = new TextEncoder().encode(JSON.stringify({ ...VAULT_PLAINTEXT, created_at: new Date().toISOString() }));
    const aad = new TextEncoder().encode(AAD_VAULT);
    const ciphertext = await crypto.subtle.encrypt({ name: 'AES-GCM', iv: nonce, additionalData: aad }, key, plaintext);
    const ctBytes = new Uint8Array(ciphertext);
    lastEnvelope = {
        format: 'artwallet-vault-v1',
        alg: 'AES-256-GCM',
        kdf: 'argon2id',
        kdf_params: {
            salt: uint8ToB64(salt),
            iterations: 3,
            memoryKiB: 32768,
            parallelism: 1,
            hashLength: 32,
        },
        nonce: uint8ToB64(nonce),
        ciphertext: uint8ToB64(ctBytes),
        aad_hint: AAD_VAULT,
    };
    setStatus('Encrypted in browser.');
    setOutput(lastEnvelope);
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
    const { kdf_params: kp, nonce, ciphertext: ctB64 } = lastEnvelope;
    const salt = b64ToUint8(kp.salt);
    setStatus('Deriving key…');
    const derived = await argon2id({
        password: String(password),
        salt,
        parallelism: kp.parallelism,
        iterations: kp.iterations,
        memorySize: kp.memoryKiB,
        hashLength: kp.hashLength,
        outputType: 'binary',
    });
    const rawKey = derived instanceof Uint8Array ? derived : new Uint8Array(derived);
    const key = await crypto.subtle.importKey('raw', rawKey, { name: 'AES-GCM' }, false, ['decrypt']);
    const iv = b64ToUint8(nonce);
    const ct = b64ToUint8(ctB64);
    const aad = new TextEncoder().encode(AAD_VAULT);
    try {
        const pt = await crypto.subtle.decrypt({ name: 'AES-GCM', iv, additionalData: aad }, key, ct);
        const json = JSON.parse(new TextDecoder().decode(pt));
        setStatus('Decrypted OK.');
        setOutput({ decrypted: json });
    } catch {
        setStatus('Decrypt failed (wrong password or corrupt blob).');
        setOutput({ error: true });
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
