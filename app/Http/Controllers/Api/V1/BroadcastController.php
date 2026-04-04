<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Chain\Exceptions\BroadcastRejectedException;
use App\Domain\Notifications\Enums\NotificationCategory;
use App\Domain\Notifications\Enums\NotificationSeverity;
use App\Domain\Notifications\Services\NotificationFactory;
use App\Domain\Notifications\Services\NotificationReader;
use App\Domain\Notifications\Support\NotificationMessageCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\BroadcastTransactionRequest;
use App\Http\Responses\AjaxEnvelope;
use App\Http\Responses\AjaxResponseCode;
use App\Models\TransactionIntent;
use App\Models\Wallet;
use App\Services\Tx\BroadcastService;
use Illuminate\Http\JsonResponse;

final class BroadcastController extends Controller
{
    public function store(
        BroadcastTransactionRequest $request,
        Wallet $wallet,
        TransactionIntent $intent,
        BroadcastService $broadcast,
        NotificationFactory $notifications,
        NotificationReader $notificationReader,
    ): JsonResponse {
        $this->authorize('createTransactionIntent', $wallet);
        if ((int) $intent->wallet_id !== (int) $wallet->id) {
            abort(404);
        }
        $this->authorize('broadcast', $intent);

        $row = $request->validated();
        $idem = $request->header('Idempotency-Key') ?? $row['idempotency_key'] ?? null;

        try {
            $result = $broadcast->broadcast(
                $request->user(),
                $intent,
                $row['server_nonce'],
                $row['signed_tx_hex'],
                $idem,
            );
        } catch (BroadcastRejectedException) {
            $resolved = NotificationMessageCatalog::resolve('tx.broadcast_failed');

            return AjaxEnvelope::error(
                AjaxResponseCode::BroadcastRejected,
                $resolved['body'] ?? $resolved['title'],
                NotificationSeverity::Danger,
                toast: AjaxEnvelope::toastPayload(
                    $resolved['title'],
                    (string) ($resolved['body'] ?? __('notification_strings.tx.broadcast_failed.body')),
                    NotificationSeverity::Danger,
                    6000,
                    'broadcast_failed:'.$intent->id,
                ),
                meta: [
                    'retryable' => true,
                    'partial' => false,
                ],
            )->toJsonResponse(422);
        }

        $intent = $result['intent'];
        $intent->load('supportedNetwork');

        $txid = $result['txid'];
        $dedupeKey = 'tx_broadcast:'.$intent->id.':'.$txid;

        $persisted = $notifications->createFromCatalogKey(
            $request->user(),
            NotificationCategory::Transaction,
            'tx.broadcast_success',
            ['txid' => $txid],
            [
                'dedupe_key' => $dedupeKey,
                'subject_type' => TransactionIntent::class,
                'subject_id' => $intent->id,
                'action_url' => $intent->supportedNetwork->explorerUrlForTxid($txid),
            ],
        );

        $toastResolved = NotificationMessageCatalog::resolve('tx.broadcast_success', ['txid' => $txid]);

        return AjaxEnvelope::ok(
            message: $toastResolved['title'],
            data: [
                'txid' => $txid,
                'explorer_url' => $intent->supportedNetwork->explorerUrlForTxid($txid),
                'intent_status' => $intent->status,
            ],
            severity: NotificationSeverity::Success,
            toast: AjaxEnvelope::toastPayload(
                $toastResolved['title'],
                (string) ($toastResolved['body'] ?? ''),
                NotificationSeverity::Success,
                5000,
                $dedupeKey,
            ),
            notification: $persisted !== null ? AjaxEnvelope::notificationPayload($persisted) : null,
            meta: [
                'unread_count' => $notificationReader->unreadCount($request->user()),
                'retryable' => false,
                'partial' => false,
            ],
        )->toJsonResponse(200);
    }
}
