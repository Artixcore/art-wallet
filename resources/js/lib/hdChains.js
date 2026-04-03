import * as bitcoin from 'bitcoinjs-lib';
import { ECPairFactory } from 'ecpair';
import * as ecc from '@bitcoinerlab/secp256k1';
import { HDKey } from '@scure/bip32';
import { mnemonicToSeedSync } from '@scure/bip39';
import { keccak_256 } from '@noble/hashes/sha3.js';
import { getPublicKey as secp256k1GetPublicKey } from '@noble/secp256k1';
import * as ed from '@noble/ed25519';
import { derivePath } from 'ed25519-hd-key';
import bs58 from 'bs58';
import bs58check from 'bs58check';

bitcoin.initEccLib(ecc);
const ECPair = ECPairFactory(ecc);

function seedToHex(seed) {
    return [...seed].map((b) => b.toString(16).padStart(2, '0')).join('');
}

function ethAddressFromPriv(privateKey32) {
    const uncompressed = secp256k1GetPublicKey(privateKey32, false);
    const hash = keccak_256(uncompressed.slice(1));
    const last = hash.slice(-20);
    return `0x${[...last].map((b) => b.toString(16).padStart(2, '0')).join('')}`;
}

function tronAddressFromPriv(privateKey32) {
    const uncompressed = secp256k1GetPublicKey(privateKey32, false);
    const hash = keccak_256(uncompressed.slice(1));
    const last = hash.slice(-20);
    const payload = new Uint8Array(21);
    payload[0] = 0x41;
    payload.set(last, 1);
    return bs58check.encode(Buffer.from(payload));
}

/**
 * Build per-chain material for encrypted vault plaintext (`chains` object).
 * Private material is stored only inside the AES-GCM–encrypted vault blob.
 *
 * @param {string} mnemonic — BIP39 phrase (24 words)
 * @returns {object} chains map for vault v1
 */
export function buildChainsFromMnemonic(mnemonic) {
    const seed = mnemonicToSeedSync(mnemonic);
    const root = HDKey.fromMasterSeed(seed);

    const btcPath = "m/84'/0'/0'/0/0";
    const btcNode = root.derive(btcPath);
    const btcPair = ECPair.fromPrivateKey(btcNode.privateKey);
    const btcPay = bitcoin.payments.p2wpkh({
        pubkey: btcPair.publicKey,
        network: bitcoin.networks.bitcoin,
    });
    const btcAddress = btcPay.address;

    const ethPath = "m/44'/60'/0'/0/0";
    const ethNode = root.derive(ethPath);
    const ethPriv = ethNode.privateKey;
    const ethAddress = ethAddressFromPriv(ethPriv);

    const solPath = "m/44'/501'/0'/0'";
    const solDerived = derivePath(solPath, seedToHex(seed));
    const solSecret = new Uint8Array(solDerived.key);
    const solPub = ed.getPublicKey(solSecret);
    const solAddress = bs58.encode(solPub);

    const tronPath = "m/44'/195'/0'/0/0";
    const tronNode = root.derive(tronPath);
    const tronPriv = tronNode.privateKey;
    const tronAddress = tronAddressFromPriv(tronPriv);

    return {
        btc: {
            path: btcPath,
            address: btcAddress,
            privHex: [...btcNode.privateKey].map((b) => b.toString(16).padStart(2, '0')).join(''),
        },
        eth: {
            path: ethPath,
            address: ethAddress,
            privHex: [...ethPriv].map((b) => b.toString(16).padStart(2, '0')).join(''),
        },
        sol: {
            path: solPath,
            address: solAddress,
            secretKeyB64: btoa(String.fromCharCode(...solSecret)),
        },
        tron: {
            path: tronPath,
            address: tronAddress,
            privHex: [...tronPriv].map((b) => b.toString(16).padStart(2, '0')).join(''),
        },
    };
}
