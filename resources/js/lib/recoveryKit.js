import { argon2id } from 'hash-wasm';
import { stableJsonStringify, DEFAULT_ARGON2_PARAMS } from './walletVault.js';
import { uint8ToB64, b64ToUint8 } from './base64.js';

/**
 * @param {number|string} userId
 * @param {number} kitVersion
 */
export function buildRecoveryKitAadHint(userId, kitVersion) {
    return `artwallet-recovery-kit-v1|${userId}|${kitVersion}`;
}

/**
 * @param {object} plaintextPayload — e.g. { v: 1, mnemonic: string }
 * @param {string} kitPassphrase
 * @param {number|string} userId
 * @param {number} kitVersion
 */
export async function encryptRecoveryKit(plaintextPayload, kitPassphrase, userId, kitVersion) {
    const salt = crypto.getRandomValues(new Uint8Array(16));
    const derived = await argon2id({
        password: String(kitPassphrase),
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
    const aadHint = buildRecoveryKitAadHint(userId, kitVersion);
    const aad = new TextEncoder().encode(aadHint);
    const ciphertext = await crypto.subtle.encrypt({ name: 'AES-GCM', iv: nonce, additionalData: aad }, key, plaintext);
    const ctBytes = new Uint8Array(ciphertext);
    return {
        format: 'artwallet-recovery-kit-v1',
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
        aad_hint: aadHint,
        kit_version: kitVersion,
    };
}

/**
 * @param {object} envelope
 * @param {string} kitPassphrase
 * @param {number|string} userId
 */
export async function decryptRecoveryKit(envelope, kitPassphrase, userId) {
    const kp = envelope.kdf_params;
    const salt = b64ToUint8(kp.salt);
    const derived = await argon2id({
        password: String(kitPassphrase),
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
    const aadHint = envelope.aad_hint;
    const parts = String(aadHint).split('|');
    if (parts.length !== 3 || parts[0] !== 'artwallet-recovery-kit-v1' || String(parts[1]) !== String(userId)) {
        throw new Error('aad_mismatch');
    }
    const aad = new TextEncoder().encode(aadHint);
    const pt = await crypto.subtle.decrypt({ name: 'AES-GCM', iv, additionalData: aad }, key, ct);
    return JSON.parse(new TextDecoder().decode(pt));
}
