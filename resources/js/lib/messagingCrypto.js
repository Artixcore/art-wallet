/**
 * Browser-side helpers for ArtWallet E2E messaging (Web Crypto API).
 * Conversation keys are managed by the app; this module only performs AES-256-GCM with a raw key.
 */

const AES_GCM = 'AES-GCM';

/**
 * @param {number} bytes
 * @returns {string} base64 (no padding normalization)
 */
function randomBytesBase64(bytes) {
    const u8 = crypto.getRandomValues(new Uint8Array(bytes));
    return btoa(String.fromCharCode(...u8));
}

/**
 * Import a 32-byte raw AES key from base64.
 * @param {string} keyB64
 */
async function importAesKey(keyB64) {
    const raw = Uint8Array.from(atob(keyB64), (c) => c.charCodeAt(0));
    if (raw.byteLength !== 32) {
        throw new Error('AES key must be 32 bytes');
    }
    return crypto.subtle.importKey('raw', raw, { name: AES_GCM, length: 256 }, false, ['encrypt', 'decrypt']);
}

/**
 * @param {string} utf8
 * @param {string} keyB64 32-byte AES key (base64)
 * @returns {Promise<{ ciphertext: string, nonce: string, alg: string, version: string }>}
 */
export async function encryptMessageUtf8(utf8, keyB64) {
    const key = await importAesKey(keyB64);
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const enc = new TextEncoder();
    const pt = enc.encode(utf8);
    const ct = await crypto.subtle.encrypt({ name: AES_GCM, iv }, key, pt);
    const ctU8 = new Uint8Array(ct);
    return {
        ciphertext: btoa(String.fromCharCode(...ctU8)),
        nonce: btoa(String.fromCharCode(...iv)),
        alg: 'AES-256-GCM',
        version: '1',
    };
}

/**
 * @param {string} ciphertextB64
 * @param {string} nonceB64
 * @param {string} keyB64
 * @returns {Promise<string>} UTF-8 plaintext
 */
export async function decryptMessageUtf8(ciphertextB64, nonceB64, keyB64) {
    const key = await importAesKey(keyB64);
    const iv = Uint8Array.from(atob(nonceB64), (c) => c.charCodeAt(0));
    const ct = Uint8Array.from(atob(ciphertextB64), (c) => c.charCodeAt(0));
    const pt = await crypto.subtle.decrypt({ name: AES_GCM, iv }, key, ct);
    return new TextDecoder().decode(pt);
}

/**
 * Generate a random 32-byte key for demos / local testing (not registered on the server).
 * @returns {string} base64
 */
export function generateLocalConversationKey() {
    return randomBytesBase64(32);
}
