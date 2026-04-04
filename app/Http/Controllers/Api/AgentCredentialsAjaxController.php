<?php

namespace App\Http\Controllers\Api;

use App\Domain\Agents\Services\AgentCredentialService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\Agents\StoreAgentCredentialRequest;
use App\Http\Responses\AjaxEnvelope;
use App\Jobs\Agents\TestProviderConnectionJob;
use App\Models\AgentApiCredential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentCredentialsAjaxController extends Controller
{
    public function index(Request $request, AgentCredentialService $credentials): JsonResponse
    {
        $this->authorize('viewAny', AgentApiCredential::class);

        $rows = AgentApiCredential::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        $data = $rows->map(fn (AgentApiCredential $c) => $credentials->toMaskedArray($c));

        return AjaxEnvelope::ok(
            message: '',
            data: ['credentials' => $data],
        )->toJsonResponse();
    }

    public function store(StoreAgentCredentialRequest $request, AgentCredentialService $credentials): JsonResponse
    {
        $this->authorize('create', AgentApiCredential::class);

        $v = $request->validated();
        $payload = ['api_key' => $v['api_key']];
        $meta = [];
        if (! empty($v['default_model'] ?? null)) {
            $meta['default_model'] = $v['default_model'];
        }

        $cred = $credentials->store(
            $request->user(),
            $v['provider'],
            $payload,
            $v['label'] ?? null,
        );

        if ($meta !== []) {
            $cred->metadata_json = array_merge($cred->metadata_json ?? [], $meta);
            $cred->save();
        }

        TestProviderConnectionJob::dispatch($cred->id);

        return AjaxEnvelope::ok(
            message: __('Credential stored. Health check queued.'),
            data: ['credential' => $credentials->toMaskedArray($cred)],
        )->toJsonResponse();
    }

    public function destroy(Request $request, AgentApiCredential $agentApiCredential, AgentCredentialService $credentials): JsonResponse
    {
        $this->authorize('delete', $agentApiCredential);

        $credentials->delete($request->user(), $agentApiCredential);

        return AjaxEnvelope::ok(message: __('Credential removed.'))->toJsonResponse();
    }

    public function test(Request $request, AgentApiCredential $agentApiCredential): JsonResponse
    {
        $this->authorize('view', $agentApiCredential);

        TestProviderConnectionJob::dispatchSync($agentApiCredential->id);

        return AjaxEnvelope::ok(message: __('Connection test finished.'))->toJsonResponse();
    }
}
