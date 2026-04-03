import { argon2id } from 'hash-wasm';
import { uint8ToB64, b64ToUint8 } from './base64.js';

export const AAD_VAULT = 'vault-v1';

/** OWASP-ish interactive defaults; tune per deployment. */
export const DEFAULT_ARGON2_PARAMS = Object.freeze({
    iterations: 3,
    memoryKiB: 32768,
    parallelism: 1,
    hashLength: 32,
});

export function stableJsonStringify(value) {
    if (value === null || typeof value !== 'object') {
        return JSON.stringify(value);
    }
    if (Array.isArray(value)) {
        return `[${value.map((v) => stableJsonStringify(v)).join(',')}]`;
    }
    const keys = Object.keys(value).sort();
    return `{${keys.map((k) => `${JSON.stringify(k)}:${stableJsonStringify(value[k])}`).join(',')}}`;
}

/**
 * @param {object} plaintextPayload — e.g. { v, chains, created_at }
 * @param {string} walletPassword
 * @returns {Promise<object>} artwallet-vault-v1 envelope
 */
export async function encryptWalletVault(plaintextPayload, walletPassword) {
    const salt = crypto.getRandomValues(new Uint8Array(16));
    const derived = await argon2id({
        password: String(walletPassword),
        salt,
        parallelism: DEFAULT_ARGON2_PARAMS.parallelism,
        iterations: DEFAULT_ARGON2_PARAMS.iterations,
        memorySize: DEFAULT_ARGON2_PARAMS.memoryKiB,
        hashLength: DEFAULT_ARGON2_PARAMS.hashLength,
        outputType: 'binary',
    });
    const rawKey = derived instanceof Uint8Array ? derived : new Uint8Array(derived);
    const key = await crypto.subtle.importKey('raw', rawKey, { name: 'AES-GCM' }, false, ['encrypt']);
    const nonce = crypto.getRandomValues(new Uint8Array(12));
    const plaintext = new TextEncoder().encode(stableJsonStringify(plaintextPayload));
    const aad = new TextEncoder().encode(AAD_VAULT);
    const ciphertext = await crypto.subtle.encrypt({ name: 'AES-GCM', iv: nonce, additionalData: aad }, key, plaintext);
    const ctBytes = new Uint8Array(ciphertext);
    return {
        format: 'artwallet-vault-v1',
        alg: 'AES-256-GCM',
        kdf: 'argon2id',
        kdf_params: {
            salt: uint8ToB64(salt),
            iterations: DEFAULT_ARGON2_PARAMS.iterations,
            memoryKiB: DEFAULT_ARGON2_PARAMS.memoryKiB,
            parallelism: DEFAULT_ARGON2_PARAMS.parallelism,
            hashLength: DEFAULT_ARGON2_PARAMS.hashLength,
        },
        nonce: uint8ToB64(nonce),
        ciphertext: uint8ToB64(ctBytes),
        aad_hint: AAD_VAULT,
    };
}

/**
 * @param {object} envelope
 * @param {string} walletPassword
 * @returns {Promise<object>} parsed plaintext vault JSON
 */
export async function decryptWalletVault(envelope, walletPassword) {
    const kp = envelope.kdf_params;
    const salt = b64ToUint8(kp.salt);
    const derived = await argon2id({
        password: String(walletPassword),
        salt,
        parallelism: kp.parallelism,
        iterations: kp.iterations,
        memorySize: kp.memoryKiB,
        hashLength: kp.hashLength,
        outputType: 'binary',
    });
    const rawKey = derived instanceof Uint8Array ? derived : new Uint8Array(derived);
    const key = await crypto.subtle.importKey('raw', rawKey, { name: 'AES-GCM' }, false, ['decrypt']);
    const iv = b64ToUint8(envelope.nonce);
    const ct = b64ToUint8(envelope.ciphertext);
    const aad = new TextEncoder().encode(AAD_VAULT);
    const pt = await crypto.subtle.decrypt({ name: 'AES-GCM', iv, additionalData: aad }, key, ct);
    return JSON.parse(new TextDecoder().decode(pt));
}
