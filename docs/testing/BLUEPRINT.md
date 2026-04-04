# ArtWallet: Full Testing, QA, Security Verification, Red-Team, and Audit Blueprint

This document is the team reference for **how** ArtWallet is tested and verified. It complements the codebase: [`routes/ajax.php`](../../routes/ajax.php), [`routes/api.php`](../../routes/api.php), [`AjaxEnvelope`](../../app/Http/Responses/AjaxEnvelope.php), [`phpunit.xml`](../../phpunit.xml), [`.github/workflows/ci.yml`](../../.github/workflows/ci.yml).

---

## 1. Testing Architecture

**Principle**: Each layer has a **narrow claim**. No single layer proves “the wallet is secure end-to-end.”

| Layer | Responsibility | Must **never** assume |
|-------|----------------|------------------------|
| **Unit** | Pure logic: hashing, canonicalization, policy math | Real network, browser, DB side effects |
| **Feature (HTTP)** | Routes, middleware, auth, validation, policies, envelopes | Real chain finality, XSS in browsers |
| **Integration** | DB + queue + broadcast together | Production infra parity, DDoS |
| **Contract / API** | Stable JSON shapes, codes, headers | Partner behavior |
| **Security / negative** | IDOR, CSRF, replay, rate limits | All attacker creativity |
| **Crypto / structural** | Server-side envelope structure | Client-side key safety without review |
| **E2E / Browser** | Blade + Vite + jQuery + AJAX + SweetAlert2 | Cryptographic proofs |
| **Infra / smoke** | Deploy health, migrations, env | Application correctness |
| **Manual audit** | Threat modeling, config drift | Repeatability without humans |
| **External review** | Cryptography, RBAC, operator privacy | Anything automated |

---

## 2. Coverage Map by Feature Area

| Area | Core risks | Priority tests | Simulate | Release-blocking |
|------|------------|----------------|----------|-------------------|
| Auth / login | Session abuse, enumeration | Feature + API login/throttle | Brute force | Auth bypass |
| Wallet / vault | Key mishandling | Feature vault routes; no secrets in logs | Malformed ciphertext | Secrets in response |
| Backup / recovery | Wrong user binding | Feature AAD/user match | Cross-user | Recovery without step-up |
| Trusted devices | Spoofing | Device challenge + API device header | Mismatch | 200 on wrong device |
| Transactions | Wrong intent, replay | Intent hash, broadcast idempotency | Duplicate idempotency | False success on reject |
| Messaging | Non-member, replay | MessagingAjax patterns | Malformed envelope | Server decrypt |
| Operator | PII leak | RBAC + OperatorGate | Non-admin | Plaintext messages |
| API / webhooks | Token reuse, forged HMAC | ArtWalletApiIntegrationTest | Refresh reuse | Weak verification |

---

## 3. Validation Testing Strategy

Prove **dangerous input is rejected** with correct HTTP status + `AjaxEnvelope` / `AjaxResponseCode`.

- **422** + `VALIDATION_FAILED` + field errors; **no** side effects on validation failure.
- Idempotent replays return explicit replay codes (e.g. messaging idempotency).

---

## 4. Error Handling and Safe Failure Testing

**Rule**: No path returns **success** when the operation did not complete safely.

- Auth: **401** + `requires_reauth` in `meta` where applicable—not **200** with `success: false` for session clients in ways that confuse UIs.
- Partial: `PARTIAL_SUCCESS` + `meta.partial`—never imply full health for sends.
- Chain: `BROADCAST_REJECTED` + `meta.retryable` where appropriate—never OK without acceptance criteria.

---

## 5. Wallet and Transaction Safety Tests

**Automate**: intent ownership, signing nonce consumption, idempotency replay semantics, expired intent rejection.

**Manual / review**: client-side signing JS, secure memory.

---

## 6. Secure Messaging and E2E Verification

**Automate (server)**: membership, quota, malformed ciphertext codes.

**Never** assert decrypted user message content in PHP tests except controlled fixtures.

**External review**: protocol, metadata leakage, forward secrecy.

---

## 7. API / Realtime / Mobile Contract Testing

Contract tests in PHP Feature: auth, refresh, token reuse, device header, webhook HMAC.

Optional: OpenAPI or snapshot assertions on stable `data` keys for mobile clients.

Realtime: channel authorization in [`routes/channels.php`](../../routes/channels.php); events are **hints**—HTTP refetch for truth.

---

## 8. Frontend UX Safety

- CI: forbid `alert(`, `confirm(`, `prompt(` in [`resources/js`](../../resources/js) (see [`scripts/ops/no-browser-dialogs.sh`](../../scripts/ops/no-browser-dialogs.sh)).
- E2E: assert visible states match envelope (`success`, `code`, `meta.partial` / `stale`).
- **CSRF vs PHPUnit**: Laravel `PreventRequestForgery` skips CSRF when `runningUnitTests()` is true, so **Feature tests** do not prove CSRF. Use browser E2E ([`e2e/`](../../e2e/)) against `php artisan serve` for CSRF regressions on state-changing POSTs.

---

## 9. Database and Data Integrity

Assert: uniqueness (idempotency keys), FK behavior, intent status transitions, audit append-only where designed.

---

## 10. Performance and Load

Self-hosted focus: burst login, tx intent, messaging, queue backlog, indexer staleness—not hyperscale RPS.

Release-blocking: unbounded queries / obvious DoS—not micro-latency tuning unless regression.

---

## 11. Security and Red-Team Plan

| Theme | Auto | Manual | External |
|-------|------|--------|----------|
| Auth bypass | Feature + fuzz | Session config | Pentest |
| CSRF | POST without token → 419 | Cookie flags | — |
| IDOR | Cross-user wallet access | Route audit | Pentest |
| XSS | — | Stored content | — |
| Token replay | API refresh reuse | — | — |

---

## 12. Observability and Test Diagnostics

Use `meta.correlation_id` in failure triage. `Log::fake()` where appropriate. Deterministic factories and `Carbon::setTestNow` for time-bound flows.

---

## 13. CI / Automation

See [`.github/workflows/ci.yml`](../../.github/workflows/ci.yml): guardrails, PHPUnit, Pint, no-browser-dialogs, `composer audit`.

---

## 14. Manual Audit Checklists (gates)

- Wallet: secrets only client-side? Logs clean?
- Transaction: state machine documented? Pending vs confirmed in UI?
- Messaging: server never decrypts for product features?
- Operator: every field justified? Retention?
- Deployment: TLS, firewall, backup restore tested?

---

## 15. Release Gate Criteria

Must pass: full PHPUnit; manual gates for wallet/messaging/operator; staging smoke; rollback documented.

Block: known false-success paths; broken webhook verification; missing private channel auth.

---

## 16. Laravel Test Layout

```
tests/
  Feature/
    Security/           # negative security cases
    ...                 # existing modules
  Concerns/             # shared traits (optional)
```

---

## 17. Threat Model for Testing Gaps

Browser memory, user error, post-release misconfig, chain reorgs, crypto bugs, “green CI” complacency—**mitigate** with docs, audits, staged rollout—not tests alone.

---

## 18. Brutal Risk Review

Coverage percentage is not security. Green CI does not mean safe. Never rush key handling, operator access, or integration scopes. External review is mandatory for serious production crypto and high-value flows.

---

## Related

- [`tests/Feature/Security/`](../../tests/Feature/Security/) — automated security-negative cases
- [`e2e/README.md`](../../e2e/README.md) — Playwright pilot
