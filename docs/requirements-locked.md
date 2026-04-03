# Locked product requirements (Phase 1)

Decisions below align with the Phase 1 blueprint: BIP39 compatibility, network clarity, and separation of account auth from wallet encryption.

| Topic | Decision | Rationale |
|-------|----------|-----------|
| **Mnemonic length** | **24 words** (BIP39) | Interoperable with standard wallets; 20 words is non-BIP39 and breaks import/export expectations. |
| **USDT in v1** | **ERC-20 (Ethereum)** and **TRC-20 (Tron)** as distinct `network`/`asset` rows | Same ticker, different chains, addresses, and fee assets (ETH vs TRX). Omni deferred. |
| **Password UX** | **Dual password**: account password (Laravel auth only) + wallet password (client KEK for vault) | Meets “separate authentication from wallet encryption keys”; single-password mode is explicitly out of scope for v1. |

## Implications

- UI copy must state that **resetting the account password does not recover** the wallet without mnemonic or encrypted backup.
- `assets` / `wallet_addresses` must key USDT as e.g. `USDT_ERC20` and `USDT_TRC20` (or `code` + `network` composite).
- Wallet creation flow generates a **24-word** mnemonic; validation uses the BIP39 wordlist.

## Change control

Any change to these locks requires updating this file and the vault/message envelope spec version notes.
