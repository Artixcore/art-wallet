import { ethers } from 'ethers';

/**
 * Sign an outgoing EIP-1559 transaction matching server intent + construction payload.
 *
 * @param {object} opts
 * @param {string} opts.privHex — 32-byte secp256k1 private key hex (no 0x required)
 * @param {object} opts.intent — API intent object (from_address, to_address, amount_atomic string, asset)
 * @param {object} opts.construction — server construction_payload_json
 * @param {object} opts.asset — asset object with asset_type, contract_address
 * @returns {Promise<string>} raw signed tx hex (0x-prefixed)
 */
export async function signOutgoingEvmIntent({ privHex, intent, construction, asset }) {
    const key = String(privHex).startsWith('0x') ? String(privHex) : `0x${privHex}`;
    const wallet = new ethers.Wallet(key);
    const chainId = BigInt(construction.chain_id);
    const tx = {
        type: 2,
        chainId,
        nonce: construction.nonce,
        maxPriorityFeePerGas: BigInt(construction.max_priority_fee_per_gas),
        maxFeePerGas: BigInt(construction.max_fee_per_gas),
        gasLimit: BigInt(construction.gas),
    };
    if (asset.asset_type === 'native') {
        tx.to = intent.to_address;
        tx.value = BigInt(intent.amount_atomic);
        tx.data = '0x';
    } else if (asset.asset_type === 'erc20') {
        const iface = new ethers.Interface(['function transfer(address to, uint256 amount)']);
        tx.to = asset.contract_address;
        tx.value = 0n;
        tx.data = iface.encodeFunctionData('transfer', [intent.to_address, BigInt(intent.amount_atomic)]);
    } else {
        throw new Error('Unsupported EVM asset type for client signing.');
    }
    return wallet.signTransaction(tx);
}
