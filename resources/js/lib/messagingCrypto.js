import { x25519 } from '@noble/curves/ed25519';
import { hkdf } from '@noble/hashes/hkdf.js';
import { sha256 } from '@noble/hashes/sha2.js';
import { uint8ToB64, b64ToUint8 } from './base64.js';

const AAD_WRAP = new TextEncoder().encode('wrap-v1');

/**
 * @returns {{ privateKey: Uint8Array, publicKey: Uint8Array }}
 */
export function generateX25519IdentityKeypair() {
    const privateKey = x25519.utils.randomPrivateKey();
    const publicKey = x25519.getPublicKey(privateKey);
    return { privateKey, publicKey };
}

export function publicKeyToB64(pub) {
    return uint8ToB64(pub);
}

export function wrapInfo(conversationPublicId, recipientUserId) {
    return `wrap-v1|${conversationPublicId}|${recipientUserId}`;
}

export function messageKdfInfo(messageIndex) {
    return `msg-v1|${messageIndex}`;
}

export function messageAad(conversationId, messageIndex, senderUserId) {
    return `v1|${conversationId}|${messageIndex}|${senderUserId}`;
}

/**
 * @param {Uint8Array} conversationKey — 32 bytes
 * @param {string} recipientPublicKeyB64
 * @param {string} conversationPublicId — UUID chosen before POST /conversations
 * @param {number} recipientUserId
 */
export async function wrapConversationKey(conversationKey, recipientPublicKeyB64, conversationPublicId, recipientUserId) {
    const ephemeralPriv = x25519.utils.randomPrivateKey();
    const ephemeralPub = x25519.getPublicKey(ephemeralPriv);
    const recipientPub = b64ToUint8(recipientPublicKeyB64);
    const shared = x25519.getSharedSecret(ephemeralPriv, recipientPub);
    const info = new TextEncoder().encode(wrapInfo(conversationPublicId, recipientUserId));
    const wk = hkdf(sha256, shared, new Uint8Array(0), info, 32);
    const key = await crypto.subtle.importKey('raw', wk, { name: 'AES-GCM' }, false, ['encrypt']);
    const nonce = crypto.getRandomValues(new Uint8Array(12));
    const ct = await crypto.subtle.encrypt(
        { name: 'AES-GCM', iv: nonce, additionalData: AAD_WRAP },
        key,
        conversationKey
    );
    return {
        format: 'artwallet-wrap-v1',
        alg: 'AES-256-GCM',
        ephemeral_pub: uint8ToB64(ephemeralPub),
        nonce: uint8ToB64(nonce),
        ciphertext: uint8ToB64(new Uint8Array(ct)),
        info: wrapInfo(conversationId, recipientUserId),
    };
}

/**
 * @param {object} wrap — validated artwallet-wrap-v1
 * @param {Uint8Array} recipientPrivateKey
 */
export async function unwrapConversationKey(wrap, recipientPrivateKey) {
    const ephemeralPub = b64ToUint8(wrap.ephemeral_pub);
    const shared = x25519.getSharedSecret(recipientPrivateKey, ephemeralPub);
    const info = new TextEncoder().encode(wrap.info);
    const wk = hkdf(sha256, shared, new Uint8Array(0), info, 32);
    const key = await crypto.subtle.importKey('raw', wk, { name: 'AES-GCM' }, false, ['decrypt']);
    const iv = b64ToUint8(wrap.nonce);
    const ct = b64ToUint8(wrap.ciphertext);
    const pt = await crypto.subtle.decrypt({ name: 'AES-GCM', iv, additionalData: AAD_WRAP }, key, ct);
    return new Uint8Array(pt);
}

/**
 * @param {Uint8Array} conversationKey
 * @param {number} messageIndex
 */
export function deriveMessageKey(conversationKey, messageIndex) {
    const info = new TextEncoder().encode(messageKdfInfo(messageIndex));
    return hkdf(sha256, conversationKey, new Uint8Array(0), info, 32);
}

/**
 * @param {Uint8Array} conversationKey
 * @param {number} conversationId
 * @param {number} messageIndex
 * @param {number} senderUserId
 * @param {string} plaintextUtf8
 */
export async function encryptMessageContent(conversationKey, conversationId, messageIndex, senderUserId, plaintextUtf8) {
    const mk = deriveMessageKey(conversationKey, messageIndex);
    const key = await crypto.subtle.importKey('raw', mk, { name: 'AES-GCM' }, false, ['encrypt']);
    const nonce = crypto.getRandomValues(new Uint8Array(12));
    const aad = new TextEncoder().encode(messageAad(conversationId, messageIndex, senderUserId));
    const pt = new TextEncoder().encode(plaintextUtf8);
    const ct = await crypto.subtle.encrypt({ name: 'AES-GCM', iv: nonce, additionalData: aad }, key, pt);
    return {
        ciphertext: uint8ToB64(new Uint8Array(ct)),
        nonce: uint8ToB64(nonce),
        alg: 'AES-256-GCM',
        version: '1',
    };
}

/**
 * @param {Uint8Array} conversationKey
 * @param {number} conversationId
 * @param {number} messageIndex
 * @param {number} senderUserId
 * @param {string} ciphertextB64
 * @param {string} nonceB64
 */
export async function decryptMessageContent(
    conversationKey,
    conversationId,
    messageIndex,
    senderUserId,
    ciphertextB64,
    nonceB64
) {
    const mk = deriveMessageKey(conversationKey, messageIndex);
    const key = await crypto.subtle.importKey('raw', mk, { name: 'AES-GCM' }, false, ['decrypt']);
    const iv = b64ToUint8(nonceB64);
    const ct = b64ToUint8(ciphertextB64);
    const aad = new TextEncoder().encode(messageAad(conversationId, messageIndex, senderUserId));
    const pt = await crypto.subtle.decrypt({ name: 'AES-GCM', iv, additionalData: aad }, key, ct);
    return new TextDecoder().decode(pt);
}
