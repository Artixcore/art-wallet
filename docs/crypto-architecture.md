# Cryptographic architecture (design reference)

Normative wire formats remain in [vault-and-message-envelope-spec.md](./vault-and-message-envelope-spec.md). Product locks: [requirements-locked.md](./requirements-locked.md).

## 1. Key management

| Stage | Location | Mechanism |
|-------|----------|-----------|
| Mnemonic | Browser (CSPRNG) | BIP39, **24 words** (English wordlist) |
| Seed | Browser | BIP39 `mnemonicToSeed` (never sent to server) |
| HD master | Browser | BIP32 `HDKey.fromMasterSeed(seed)` (secp256k1 chains) |
| Per-chain keys | Browser | Standard BIP44-style paths (see §5) |
| Persistence | MySQL | **Only** Argon2id-wrapped AES-256-GCM vault blob (`wallet_vault_ciphertext`) |
| Recovery | User | Mnemonic + wallet password; **no** server-side key recovery |

Private keys exist only inside the **decrypted** vault plaintext in browser memory. The server stores ciphertext only.

## 2. Password and KDF

- **Account password** (Laravel): bcrypt/Argon2 via `User` cast; authenticates session only. **Must not** derive the wallet KEK.
- **Wallet password**: UTF-8 input → **Argon2id** with per-wallet random **16-byte salt** (stored in `kdf_params` / envelope) → 32-byte **KEK**. Parameters are versioned in the envelope; defaults target interactive UX on VPS-hosted instances (tunable).
- **Signing**: Uses chain private keys from decrypted vault, not the password directly.
- **Password change**: Re-encrypt vault with new KEK (client downloads ciphertext, decrypts with old KEK, encrypts with new KDF params); server never sees plaintext.

## 3. Encryption model

| Asset | Algorithm | Where | IV / nonce | Integrity |
|-------|-----------|-------|--------------|-----------|
| Wallet vault | AES-256-GCM | Browser | Random 12-byte per encryption | GCM tag |
| Message body | AES-256-GCM | Browser | Random 12-byte per message | GCM tag + AAD binding |
| Conversation key wrap | AES-256-GCM | Browser | Random 12-byte per wrap | GCM tag |
| Attachments (Phase 5) | AES-256-GCM (chunked) | Browser | Per chunk / file nonce strategy | GCM |

**AAD**: Vault uses constant `vault-v1`. Messages use `v1|{conversation_id}|{message_index}|{sender_user_id}` (UTF-8) per envelope spec.

## 4. Client vs server boundaries

**Browser**: Mnemonic generation, seed/HD derivation, Argon2id, AES-GCM encrypt/decrypt, transaction signing (future broadcast payload), message crypto, attachment encryption.

**Laravel**: Authentication, authorization, **opaque** storage of ciphertext, monotonic `message_index`, rate limits, CSRF for session-backed forms/AJAX, structural validation of envelopes (no decryption of user secrets).

**Never on server**: Raw mnemonic, seed, private keys, wallet password, decrypted vault, message plaintext, identity private keys.

**Minimal trust**: User trusts TLS to their VPS, non-malicious server code path, and that the **served JavaScript** matches audited builds (supply-chain / XSS are residual risks).

## 5. Multi-chain (Phase 1)

| Chain | Model | Default path (account 0, index 0) | Notes |
|-------|--------|-------------------------------------|--------|
| BTC | UTXO / segwit | `m/84'/0'/0'/0/0` | P2WPKH mainnet |
| ETH | Account | `m/44'/60'/0'/0/0` | Same key material used for ERC-20 (e.g. USDT ERC-20) |
| SOL | Ed25519 account | `m/44'/501'/0'/0'` | SLIP-0010–style ed25519 HD via `ed25519-hd-key` |
| TRON | Account | `m/44'/195'/0'/0/0` | secp256k1; USDT TRC-20 distinct asset row |

**Libraries (client)**: `@scure/bip39`, `@scure/bip32`, `bitcoinjs-lib` + `ecpair` + `@bitcoinerlab/secp256k1`, `@noble/hashes` (Keccak), `ed25519-hd-key` + `@noble/ed25519`, `bs58check` (Tron address encoding).

**Broadcast**: Signed raw tx from client → server may relay to RPC/indexers without signing capability.

## 6. End-to-end messaging

- **Identity**: Per-user **X25519** keypair; **only public key** stored on server (`users.messaging_x25519_public_key`).
- **Conversation key `CK`**: Random 32 bytes; wrapped per member with ephemeral X25519 ECDH + HKDF-SHA256 + AES-256-GCM (`artwallet-wrap-v1` JSON in `conversation_members.wrapped_conv_key_ciphertext`). HKDF `info` binds a client-chosen conversation `public_id` (UUID) plus `recipient_user_id` so wraps can be produced before the server assigns the numeric `conversations.id`.
- **Message keys**: `MK = HKDF-SHA256(CK, salt=empty, info=UTF-8("msg-v1\|{index}"), length=32)` per envelope spec.
- **Forward secrecy**: Optional future upgrade (e.g. double ratchet); not in v1.

## 7. Database encryption

- Sensitive columns are **application-level ciphertext**; MySQL admins see blobs only.
- **Plaintext** allowed: ids, foreign keys, timestamps, `message_index`, alg/version enums, user email (operational—minimize elsewhere).
- **Indexing**: Search on ciphertext is not supported; use hashed/blind indexes only if product requires lookup (not in v1 core).

## 8. Session and auth

- **Preference**: Encrypted **session cookies** (`httpOnly`, `Secure`, `SameSite`) for browser app—revocable server-side, not exposed to JS. JWT optional for separate API clients; not default for this Blade + Vite app.
- **Sensitive actions**: Re-prompt wallet password before sign/decrypt (client policy); optional step-up later.
- **Inactivity**: Client clears decrypted vault from memory; server session TTL via Laravel config.

## 9. Backup and recovery

- **Mnemonic**: User-written backup; loss + no backup ⇒ **unrecoverable** funds.
- **Wallet password**: Loss ⇒ ciphertext unrecoverable without guessing (Argon2id slows brute force).
- **Account password reset**: Does **not** reset wallet ciphertext; copy must state this clearly.

## 10. Threat model (summary)

| Threat | Mitigation | Residual |
|--------|------------|----------|
| DB breach | Only ciphertext at rest | Metadata leakage, traffic analysis |
| Server compromise | No key material server-side | **Malicious JS** to next visitors |
| MITM | TLS + HSTS | Misconfigured TLS |
| XSS | CSP (tighten over time), sanitize output | Full compromise if script runs |
| CSRF | Session + `X-CSRF-TOKEN` on AJAX | Misconfigured exceptions |
| Phishing / fake UI | User education, bookmark official URL | Social engineering |
| Weak passwords | Argon2id tuning, optional policy | Offline attack on stolen blob |
| Replay | Monotonic `message_index` + AAD | Protocol bugs if mis-implemented |
| Malicious uploads | Validate ciphertext shape; virus scan does not apply to encrypted blobs | Storage abuse via rate limits |

## 11. Failure handling

On **any** decrypt/verify failure: generic error, **no** distinction that leaks oracle information beyond “failed”; no partial key exposure; do not fall back to weaker modes.

## 12. Implementation guidelines

- **Do not** implement custom ciphers or KDFs.
- **Version** envelopes (`vault_version`, `format`, `version`); support decrypt-only for old versions in the client.
- **PHP**: Validate JSON shape and base64 lengths only; use `hash_equals` for constant-time comparisons where comparing secrets.
- **JS**: Web Crypto for AES-GCM; hash-wasm or equivalent for Argon2id; audited micro-libraries for curves.

## 13. Security review (honest)

- **Largest risk**: XSS or compromised build serving exfiltrating wallet code—defeats “server never sees keys.”
- **Hardest**: Secure key lifecycle in JS (memory clearing is best-effort), and convincing users to separate account vs wallet passwords.
- **Audit before production**: Client bundle, envelope parsers, KDF/AEAD parameters, messaging wrap/unwrap, RBAC on ciphertext endpoints.
- **Danger zones**: Any `eval`, dynamic script injection, `dangerouslySetInnerHTML` equivalents, server-side “decrypt for support,” or merging account password with wallet KEK.
