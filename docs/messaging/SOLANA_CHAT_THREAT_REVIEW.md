# Solana address chat discovery — threat review checklist

Operational follow-up for the feature implemented per the Solana chat discovery blueprint. Use this as a release gate, not as a substitute for a third-party security review.

## Enumeration and privacy

- Rate limits on `POST /ajax/messaging/resolve-sol-address` (`messaging-resolve` limiter) reduce brute-force address probing; monitor logs for spikes.
- Blind “not found” responses (`contact_resolution_status: not_found`) align for unknown users and privacy-restricted users; audit rows use HMAC address hashes only.
- `discoverable_by_sol_address` defaults to `off` in migrations; raising exposure requires password step-up via `SettingsRiskEvaluator::messagingDiscoverabilityNeedsStepUp`.

## Data model

- `verified_wallet_addresses` enforces uniqueness on `(chain, address)`; conflicting sync from another user is skipped and logged (`verified_wallet_address_conflict`).
- `conversation_direct_index` enforces one direct conversation per user pair; `POST /ajax/conversations` returns `409 CONFLICT` with `meta.existing_conversation` when duplicate.

## Client / crypto gaps

- New DM UI resolves peers and opens existing threads; **creating** a new E2E direct conversation still requires browser-held X25519 material and wrap generation (not fully wired in UI). Do not imply “secure chat created” until `POST /ajax/conversations` succeeds.

## Tests

- See `tests/Feature/MessagingSolAddressResolveTest.php` and `tests/Unit/Base58Test.php` for regression coverage on validation, blind envelope, discovery, self-address, and duplicate conversation behavior.
