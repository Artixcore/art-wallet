import { generateMnemonic } from '@scure/bip39';
import { wordlist } from '@scure/bip39/wordlists/english.js';
import { encryptWalletVault } from './walletVault.js';
import { buildChainsFromMnemonic } from './hdChains.js';

export function normalizeMnemonic(phrase) {
    const t = phrase.trim().toLowerCase();
    const nk = t.normalize('NFKC');
    return nk.split(/\s+/).filter(Boolean).join(' ');
}

export function generate24WordMnemonic() {
    return generateMnemonic(wordlist, 256);
}

export async function computePassphraseVerifierHmacHex(saltHex, normalizedMnemonic) {
    const pairs = saltHex.match(/.{1,2}/g);
    if (!pairs || pairs.length !== 32) {
        throw new Error('Invalid verifier salt.');
    }
    const keyBytes = new Uint8Array(pairs.map((byte) => parseInt(byte, 16)));
    const key = await crypto.subtle.importKey(
        'raw',
        keyBytes,
        { name: 'HMAC', hash: 'SHA-256' },
        false,
        ['sign'],
    );
    const sig = await crypto.subtle.sign('HMAC', key, new TextEncoder().encode(normalizedMnemonic));
    return [...new Uint8Array(sig)].map((b) => b.toString(16).padStart(2, '0')).join('');
}

/**
 * @param {string} mnemonic
 * @param {string} password Account password (re-entered on /onboarding for local encryption only).
 */
export async function buildOnboardingVaultPayload(mnemonic, password) {
    const chains = buildChainsFromMnemonic(mnemonic);
    const plaintext = { v: 1, chains, created_at: new Date().toISOString() };
    const envelope = await encryptWalletVault(plaintext, password);
    return {
        envelope,
        addresses: [
            { chain: 'BTC', address: chains.btc.address, derivation_path: chains.btc.path },
            { chain: 'ETH', address: chains.eth.address, derivation_path: chains.eth.path },
            { chain: 'SOL', address: chains.sol.address, derivation_path: chains.sol.path },
        ],
    };
}
