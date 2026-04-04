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
}
