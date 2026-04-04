import $ from 'jquery';
import QRCode from 'qrcode';
import { ethers } from 'ethers';
import { apiGetJson, apiPostJson, getEnvelopeData } from './lib/artWalletAjax.js';
import { decryptWalletVault } from './lib/walletVault.js';
import { signOutgoingEvmIntent } from './lib/evmSign.js';

const csrf = document.querySelector('meta[name="csrf-token"]');
if (csrf) {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrf.getAttribute('content') },
    });
}

let networks = [];
let selectedWalletId = null;
let decryptedVault = null;
let networkIdBySlug = new Map();

function setStatus(el, msg, isError = false) {
    $(el).text(msg).toggleClass('text-red-600', isError).toggleClass('text-gray-600', !isError);
}

async function apiGet(url) {
    return apiGetJson(url);
}

async function apiPost(url, data, idempotencyKey = null) {
    const ajaxOptions = idempotencyKey ? { headers: { 'Idempotency-Key': idempotencyKey } } : {};

    return apiPostJson(url, data, ajaxOptions);
}

function chainToSlug(chain) {
    const m = { BTC: 'BTC_MAINNET', ETH: 'ETH_MAINNET', SOL: 'SOL_MAINNET', TRON: 'TRON_MAINNET' };
    return m[chain] || null;
}

async function loadNetworks() {
    const data = await apiGet('/ajax/networks');
    networks = data.networks || [];
    networkIdBySlug = new Map(networks.map((n) => [n.slug, n.id]));
}

async function syncAddressesFromVault(walletId, vault) {
    const chains = vault.chains || {};
    const rows = [];
    const mapping = [
        ['BTC', chains.btc?.address, chains.btc?.path, 0],
        ['ETH', chains.eth?.address, chains.eth?.path, 0],
        ['SOL', chains.sol?.address, chains.sol?.path, 0],
        ['TRON', chains.tron?.address, chains.tron?.path, 0],
    ];
    for (const [chain, address, path, idx] of mapping) {
        if (!address) {
            continue;
        }
        const slug = chainToSlug(chain);
        const nid = slug ? networkIdBySlug.get(slug) : null;
        if (!nid) {
            continue;
        }
        rows.push({
            supported_network_id: nid,
            address,
            derivation_path: path || null,
            derivation_index: idx,
            is_change: false,
        });
    }
    if (!rows.length) {
        return;
    }
    await apiPost(`/ajax/wallets/${walletId}/addresses`, { addresses: rows });
}

async function refreshReceiveQr(address) {
    const canvas = document.getElementById('wt-qr');
    if (!canvas || !address) {
        return;
    }
    await QRCode.toCanvas(canvas, address, { width: 200, margin: 2 });
}

$('#wt-load').on('click', async () => {
    const wid = $('#wt-wallet').val();
    const pwd = $('#wt-wallet-password').val();
    setStatus('#wt-unlock-status', '');
    if (!wid || !pwd) {
        setStatus('#wt-unlock-status', 'Select a wallet and enter wallet password.', true);
        return;
    }
    selectedWalletId = wid;
    try {
        const envelope = await apiGet(`/ajax/wallets/${wid}/vault`);
        decryptedVault = await decryptWalletVault(envelope.wallet_vault, pwd);
        await syncAddressesFromVault(wid, decryptedVault);
        setStatus('#wt-unlock-status', 'Vault unlocked. Addresses synced.');
        $('#wt-panel').removeClass('hidden');
        populateReceiveNetworkSelect();
    } catch (e) {
        decryptedVault = null;
        setStatus('#wt-unlock-status', e.message || 'Unlock failed', true);
        $('#wt-panel').addClass('hidden');
    }
});

function populateReceiveNetworkSelect() {
    const sel = $('#wt-receive-network');
    sel.empty();
    networks.forEach((n) => {
        sel.append($('<option></option>').attr('value', n.id).text(`${n.display_name} (${n.slug})`));
    });
    sel.trigger('change');
}

$('#wt-receive-network').on('change', async function () {
    const nid = $(this).val();
    const net = networks.find((n) => String(n.id) === String(nid));
    if (!net || !decryptedVault?.chains) {
        $('#wt-receive-address').text('');
        return;
    }
    const chain = net.chain;
    const map = { BTC: 'btc', ETH: 'eth', SOL: 'sol', TRON: 'tron' };
    const key = map[chain];
    const addr = key ? decryptedVault.chains[key]?.address : '';
    $('#wt-receive-address').text(addr || '');
    $('#wt-receive-warning').toggleClass('hidden', !addr);
    if (addr) {
        await refreshReceiveQr(addr);
    }
    const label = net.assets?.length
        ? net.assets.map((a) => a.display_label).join(', ')
        : '';
    $('#wt-receive-assets').text(label);
});

function populateSendAssetSelect() {
    const sel = $('#wt-send-asset');
    sel.empty();
    networks.forEach((n) => {
        (n.assets || []).forEach((a) => {
            const label = `${a.display_label} — ${n.display_name}`;
            sel.append(
                $('<option></option>')
                    .attr('value', a.id)
                    .attr('data-chain', n.chain)
                    .text(label),
            );
        });
    });
}

$('#wt-tab-receive').on('click', (e) => {
    e.preventDefault();
    $('.wt-tab').removeClass('border-indigo-600 text-indigo-600').addClass('border-transparent text-gray-500');
    $('#wt-tab-receive').removeClass('border-transparent text-gray-500').addClass('border-indigo-600 text-indigo-600');
    $('.wt-panel-section').addClass('hidden');
    $('#wt-section-receive').removeClass('hidden');
});

$('#wt-tab-send').on('click', (e) => {
    e.preventDefault();
    $('.wt-tab').removeClass('border-indigo-600 text-indigo-600').addClass('border-transparent text-gray-500');
    $('#wt-tab-send').removeClass('border-transparent text-gray-500').addClass('border-indigo-600 text-indigo-600');
    $('.wt-panel-section').addClass('hidden');
    $('#wt-section-send').removeClass('hidden');
    populateSendAssetSelect();
});

$('#wt-tab-history').on('click', async (e) => {
    e.preventDefault();
    $('.wt-tab').removeClass('border-indigo-600 text-indigo-600').addClass('border-transparent text-gray-500');
    $('#wt-tab-history').removeClass('border-transparent text-gray-500').addClass('border-indigo-600 text-indigo-600');
    $('.wt-panel-section').addClass('hidden');
    $('#wt-section-history').removeClass('hidden');
    if (!selectedWalletId) {
        return;
    }
    try {
        const data = await apiGet(`/ajax/wallets/${selectedWalletId}/blockchain-transactions`);
        const tbody = $('#wt-history-body');
        tbody.empty();
        (data.transactions || []).forEach((t) => {
            const tr = $('<tr></tr>');
            tr.append($('<td></td>').text(t.txid.slice(0, 18) + '…'));
            tr.append($('<td></td>').text(t.direction));
            tr.append($('<td></td>').text(t.status));
            tr.append($('<td></td>').text(t.network_slug || ''));
            const link = t.explorer_url
                ? $('<a></a>').attr('href', t.explorer_url).attr('target', '_blank').attr('rel', 'noopener').text('Explorer')
                : '';
            tr.append($('<td></td>').append(link));
            tbody.append(tr);
        });
    } catch (err) {
        setStatus('#wt-history-status', err.message, true);
    }
});

$('#wt-fetch-fee').on('click', async () => {
    const opt = $('#wt-send-asset option:selected');
    const assetId = opt.val();
    const net = networks.find((n) => (n.assets || []).some((a) => String(a.id) === String(assetId)));
    if (!net) {
        return;
    }
    try {
        const q = await apiGet(`/ajax/fee-estimates?supported_network_id=${net.id}&asset_id=${assetId}`);
        $('#wt-fee-panel').text(JSON.stringify(q.tiers, null, 2));
    } catch (e) {
        $('#wt-fee-panel').text(e.message);
    }
});

$('#wt-create-intent').on('click', async () => {
    setStatus('#wt-send-status', '');
    if (!selectedWalletId || !decryptedVault) {
        setStatus('#wt-send-status', 'Unlock wallet first.', true);
        return;
    }
    const assetId = $('#wt-send-asset').val();
    const to = $('#wt-send-to').val().trim();
    const amount = $('#wt-send-amount').val().trim();
    if (!assetId || !to || !amount) {
        setStatus('#wt-send-status', 'Asset, recipient, and amount are required.', true);
        return;
    }
    const opt = $('#wt-send-asset option:selected');
    const chain = opt.attr('data-chain');
    const asset = networks.flatMap((n) => n.assets || []).find((a) => String(a.id) === String(assetId));
    try {
        const decimals = Number(asset?.decimals ?? 18);
        const atomic = ethers.parseUnits(amount, decimals).toString();
        const body = {
            asset_id: Number(assetId),
            to_address: to,
            amount_atomic: atomic,
        };
        const res = await apiPost(`/ajax/wallets/${selectedWalletId}/transaction-intents`, body);
        $('#wt-intent-json').text(JSON.stringify(res, null, 2));
        $('#wt-sign-broadcast').data('intentPayload', res);
        $('#wt-sign-broadcast').data('chain', chain);
        $('#wt-sign-broadcast').data('asset', asset);
        setStatus('#wt-send-status', 'Intent created. Review hash, then sign & broadcast (EVM only in this UI).');
    } catch (e) {
        setStatus('#wt-send-status', e.message, true);
    }
});

$('#wt-sign-broadcast').on('click', async () => {
    setStatus('#wt-send-status', '');
    const payload = $('#wt-sign-broadcast').data('intentPayload');
    const chain = $('#wt-sign-broadcast').data('chain');
    const asset = $('#wt-sign-broadcast').data('asset');
    if (!payload?.intent || !payload?.signing_request) {
        setStatus('#wt-send-status', 'Create an intent first.', true);
        return;
    }
    if (chain !== 'ETH') {
        setStatus(
            '#wt-send-status',
            'Browser signing in this screen is implemented for Ethereum (native + ERC-20) only. Use a dedicated tool for other chains.',
            true,
        );
        return;
    }
    const priv = decryptedVault?.chains?.eth?.privHex;
    if (!priv) {
        setStatus('#wt-send-status', 'Missing ETH key material in vault.', true);
        return;
    }
    const btn = $('#wt-sign-broadcast');
    if (btn.prop('disabled')) {
        return;
    }
    btn.prop('disabled', true);
    try {
        const signed = await signOutgoingEvmIntent({
            privHex: priv,
            intent: payload.intent,
            construction: payload.intent.construction_payload,
            asset: payload.intent.asset,
        });
        const idem = crypto.randomUUID();
        const env = await apiPost(
            `/ajax/wallets/${selectedWalletId}/transaction-intents/${payload.intent.id}/broadcast`,
            {
                server_nonce: payload.signing_request.server_nonce,
                signed_tx_hex: signed,
            },
            idem,
        );
        const data = getEnvelopeData(env);
        const txid = typeof data.txid === 'string' ? data.txid : '';
        setStatus('#wt-send-status', `Broadcast ok. Txid: ${txid}`, false);
    } catch (e) {
        setStatus('#wt-send-status', e instanceof Error ? e.message : 'Broadcast failed', true);
    } finally {
        btn.prop('disabled', false);
    }
});

$(async () => {
    try {
        await loadNetworks();
        const wl = await apiGet('/ajax/wallets/list');
        const sel = $('#wt-wallet');
        sel.empty();
        (wl.wallets || []).forEach((w) => {
            sel.append(
                $('<option></option>')
                    .attr('value', w.id)
                    .text(`${w.label || 'Wallet'} (#${w.id})`),
            );
        });
    } catch (e) {
        setStatus('#wt-boot-status', e.message, true);
    }
});
