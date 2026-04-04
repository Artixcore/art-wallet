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
}
