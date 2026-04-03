import * as ed from '@noble/ed25519';
import { sha512 } from '@noble/hashes/sha2.js';
import { uint8ToB64, b64ToUint8 } from './base64.js';

ed.hashes.sha512 = sha512;

const STORAGE_PREFIX = 'artwallet_login_device_sk:';

function storageKey(userId) {
    return `${STORAGE_PREFIX}${userId}`;
}

/**
 * @param {number|string} userId
 * @returns {{ publicKeyB64: string, privateKeyB64: string }}
 */
export function getOrCreateDeviceKeyPair(userId) {
    const key = storageKey(userId);
    const existing = localStorage.getItem(key);
    if (existing) {
        try {
            const sk = b64ToUint8(existing);
            if (sk.length === 32) {
                const pk = ed.getPublicKey(sk);
                return { privateKeyB64: existing, publicKeyB64: uint8ToB64(pk) };
            }
        } catch {
            localStorage.removeItem(key);
        }
    }
    const { secretKey, publicKey } = ed.keygen();
    const skB64 = uint8ToB64(secretKey);
    localStorage.setItem(key, skB64);
    return { privateKeyB64: skB64, publicKeyB64: uint8ToB64(publicKey) };
}

/**
 * @param {string} messageUtf8
 * @param {string} privateKeyB64
 * @returns {string} base64 signature
 */
export function signChallengeMessage(messageUtf8, privateKeyB64) {
    const sk = b64ToUint8(privateKeyB64);
    const msg = new TextEncoder().encode(messageUtf8);
    const sig = ed.sign(msg, sk);
    return uint8ToB64(sig);
}
