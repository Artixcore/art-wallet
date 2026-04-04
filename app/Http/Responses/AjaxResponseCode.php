<?php

namespace App\Http\Responses;

enum AjaxResponseCode: string
{
    case Ok = 'OK';
    case ValidationFailed = 'VALIDATION_FAILED';
    case InsufficientFunds = 'INSUFFICIENT_FUNDS';
    case DeviceVerificationFailed = 'DEVICE_VERIFICATION_FAILED';
    case IntentExpired = 'INTENT_EXPIRED';
    case BroadcastRejected = 'BROADCAST_REJECTED';
    case ChainError = 'CHAIN_ERROR';
    case InvalidRequest = 'INVALID_REQUEST';
    case Unauthorized = 'UNAUTHORIZED';
    /** Refresh token presented after rotation; family revoked server-side. */
    case TokenReuseDetected = 'TOKEN_REUSE_DETECTED';
    /** Access token expired; client should refresh or re-authenticate. */
    case TokenExpired = 'TOKEN_EXPIRED';
    case Forbidden = 'FORBIDDEN';
    case NotFound = 'NOT_FOUND';
    case ServerError = 'SERVER_ERROR';
    case NetworkError = 'NETWORK_ERROR';
    case Conflict = 'CONFLICT';
    case StepUpRequired = 'STEP_UP_REQUIRED';
    case PolicyRejected = 'POLICY_REJECTED';
    /** Dashboard loaded with one or more subsystem failures; see meta.partial / meta.subsystems. */
    case PartialSuccess = 'PARTIAL_SUCCESS';
    /** Data is older than freshness TTL; see meta.stale / meta.stale_subsystems. */
    case StaleData = 'STALE_DATA';
    /** Participant must register a messaging identity key before this action. */
    case MessagingKeyRequired = 'MESSAGING_KEY_REQUIRED';
    /** Encrypted envelope failed structural validation (server does not decrypt). */
    case CryptoEnvelopeInvalid = 'CRYPTO_ENVELOPE_INVALID';
    /** Conversation does not exist or user is not a member. */
    case MessagingConversationNotFound = 'MESSAGING_CONVERSATION_NOT_FOUND';
    /** User exceeded attachment quota or size policy. */
    case AttachmentQuotaExceeded = 'ATTACHMENT_QUOTA_EXCEEDED';
    /** Encrypted blob could not be stored or verified. */
    case AttachmentUploadFailed = 'ATTACHMENT_UPLOAD_FAILED';
    /** Same idempotency key replayed; see meta.idempotent_replay. */
    case MessagingIdempotencyReplay = 'MESSAGING_IDEMPOTENCY_REPLAY';
}
