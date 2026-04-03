# Vault and message envelope specification (v1)

This document is the **normative contract** for client-generated ciphertext the server stores without being able to decrypt. Bump `vault_version` / `version` fields when breaking changes occur.

---

## 1. Wallet vault (`vault_version`: `1`)

### 1.1 Plaintext payload (client-only, never sent to server)

JSON object before encryption:

| Field | Type | Description |
|-------|------|-------------|
| `v` | int | Inner schema version, always `1`. |
| `chains` | object | Per-chain opaque blobs (keys, xpubs) — chain-specific inner formats added in later phases. |
| `created_at` | string | ISO-8601 UTC from client clock (informational). |

The **canonical serialization** for encryption input is **UTF-8 bytes** of JSON with stable key ordering (lexicographic keys) to aid testing.

### 1.2 Outer encrypted blob (`wallet_vault_ciphertext`)

Stored as a single JSON string (or base64 wrapper) containing:

| Field | Type | Description |
|-------|------|-------------|
| `format` | string | Always `artwallet-vault-v1`. |
| `alg` | string | `AES-256-GCM`. |
| `kdf` | string | `argon2id`. |
| `kdf_params` | object | Mirrors DB `kdf_params`: `salt` (base64), `iterations`, `memoryKiB`, `parallelism`, `hashLength` (32). |
| `nonce` | string | 12-byte random nonce, base64. |
| `ciphertext` | string | AES-GCM ciphertext + tag, base64 (Web Crypto returns ciphertext with tag appended). |
| `aad_hint` | string | Constant `vault-v1` for domain separation in tests (also used as AAD). |

**Key derivation:** `KEK = Argon2id(password=utf8(wallet_password), salt, params)` → 32 bytes.  
**AEAD:** Web Crypto `AES-GCM`, 256-bit key, **96-bit IV** = `nonce`, **AAD** = UTF-8 bytes of string `vault-v1`.

The **account password** (Laravel login) MUST NOT be used unless explicitly documented as a combined product mode (out of scope for v1).

---

## 2. Message envelope (`messages.version`: `1`)

### 2.1 Plaintext (client-only)

| Field | Type | Description |
|-------|------|-------------|
| `body` | string | UTF-8 message text. |
| `attachments` | array | Optional list of `{ attachment_id, file_key_wrap }` after Phase 5. |

### 2.2 Encrypted record (DB row)

| Column | Value |
|--------|--------|
| `ciphertext` | Base64 of AEAD output (ciphertext + tag). |
| `nonce` | Base64, 12-byte IV. |
| `alg` | `AES-256-GCM`. |
| `version` | `1`. |
| `message_index` | Monotonic per `conversation_id`, assigned by server API to prevent reuse (anti-replay coordination). |

**Message key:** `MK = HKDF-SHA256(CK, salt=empty, info=utf8("msg-v1"|message_index), length=32)` where `CK` is the conversation key only held in clients.

**AAD (associated authenticated data):** canonical string:

`v1|{conversation_id}|{message_index}|{sender_user_id}`

UTF-8 encode that exact string for `additionalData` in GCM.

### 2.3 Conversation key wrap (`conversation_members.wrapped_conv_key_ciphertext`)

Opaque base64 blob, format `artwallet-wrap-v1`:

- For each member public key, use **ECDH X25519** + **HKDF** to derive a wrap key, then **AES-256-GCM** encrypt `CK`.  
- Detailed binary layout is deferred to Phase 4 implementation; server stores only ciphertext.

---

## 3. Attachment file (Phase 5 preview)

| Element | Description |
|---------|-------------|
| On-disk bytes | AES-256-GCM stream or chunked chunks; never plaintext. |
| `crypto_meta` | JSON: `{ alg, chunk_size, file_nonce, chunks: [{ offset, nonce }] }` (exact shape TBD). |
| File key | Random 32 bytes, wrapped inside message ciphertext or member wrap table. |

**Preview / thumbnail:** generated in browser from decrypted bytes only; server never receives raw image for E2EE mode.

---

## 4. Operational notes

- **Nonce uniqueness:** one nonce per GCM key per encryption; never reuse `(key, nonce)`.  
- **Rotation:** new `vault_version` when changing algorithms; support decrypt-only for old versions in client.  
- **Server validation:** PHP may validate base64 length, JSON shape, and allowed `alg` / `version` enums without decrypting.
