<?php

namespace App\Http\Controllers\Api;

use App\Domain\Agents\Services\AgentManagementService;
use App\Domain\Agents\Services\AgentRunRecorder;
use App\Domain\Agents\Services\AgentToolExecutionService;
use App\Domain\Agents\Services\ProviderComparisonService;
use App\Domain\Agents\Services\ProviderRouterService;
use App\Domain\Tools\ToolRegistry;
use App\Domain\Workflows\Services\WorkflowDefinitionValidator;
use App\Domain\Workflows\Services\WorkflowRunService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ajax\Agents\CompareProvidersRequest;
use App\Http\Requests\Ajax\Agents\ExecuteAgentToolRequest;
use App\Http\Requests\Ajax\Agents\RunAgentRequest;
use App\Http\Requests\Ajax\Agents\StoreAgentRequest;
use App\Http\Requests\Ajax\Agents\StoreWorkflowRequest;
use App\Http\Requests\Ajax\Agents\UpdateAgentBindingsRequest;
use App\Http\Requests\Ajax\Agents\UpdateAgentPromptRequest;
use App\Http\Requests\Ajax\Agents\UpdateAgentRequest;
use App\Http\Requests\Ajax\Agents\UpdateAgentToolsRequest;
use App\Http\Responses\AjaxEnvelope;
use App\Http\Responses\AjaxResponseCode;
use App\Jobs\Agents\ExecuteWorkflowRunJob;
use App\Jobs\Agents\RunAgentJob;
use App\Models\Agent;
use App\Models\AgentApiCredential;
use App\Models\AgentProviderBinding;
use App\Models\AgentRun;
use App\Models\AgentTool;
use App\Models\ProviderHealthCheck;
use App\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentsAjaxController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agent::class);

        $user = $request->user();
        $activeAgents = Agent::query()->where('user_id', $user->id)->where('status', 'active')->count();
        $runs7d = AgentRun::query()->where('user_id', $user->id)->where('created_at', '>=', now()->subDays(7))->count();
        $credentials = AgentApiCredential::query()->where('user_id', $user->id)->count();

        $degraded = 0;
        $credIds = AgentApiCredential::query()->where('user_id', $user->id)->pluck('id');
        foreach ($credIds as $cid) {
            $h = ProviderHealthCheck::query()->where('credential_id', $cid)->orderByDesc('checked_at')->first();
            if ($h && $h->status === 'error') {
                $degraded++;
            }
        }

        $recentRuns = AgentRun::query()
            ->where('user_id', $user->id)
            ->with(['agent:id,name'])
            ->orderByDesc('created_at')
            ->limit(12)
            ->get(['id', 'agent_id', 'status', 'outcome', 'provider', 'created_at']);

        return AjaxEnvelope::ok(
            message: '',
            data: [
                'widgets' => [
                    'active_agents' => $activeAgents,
                    'runs_7d' => $runs7d,
                    'credentials_count' => $credentials,
                    'degraded_providers' => $degraded,
                ],
                'recent_runs' => $recentRuns,
            ],
        )->toJsonResponse();
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agent::class);

        $agents = Agent::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->get(['id', 'name', 'slug', 'type', 'status', 'updated_at']);

        return AjaxEnvelope::ok(
            message: '',
            data: ['agents' => $agents],
        )->toJsonResponse();
    }

    public function store(StoreAgentRequest $request, AgentManagementService $management): JsonResponse
    {
        $this->authorize('create', Agent::class);

        $agent = $management->create($request->user(), $request->validated());

        return AjaxEnvelope::ok(
            message: __('Agent created.'),
            data: ['agent' => $agent],
        )->toJsonResponse();
    }

    public function show(Request $request, Agent $agent): JsonResponse
    {
        $this->authorize('view', $agent);

        $agent->load([
            'promptVersions' => fn ($q) => $q->orderByDesc('version')->limit(1),
            'safetyPolicy',
            'tools',
            'providerBindings.credential',
        ]);

        $prompt = $agent->promptVersions->first();

        return AjaxEnvelope::ok(
            message: '',
            data: [
                'agent' => $agent,
                'latest_prompt' => $prompt,
                'tools' => $agent->tools,
                'bindings' => $agent->providerBindings,
            ],
        )->toJsonResponse();
    }

    public function update(UpdateAgentRequest $request, Agent $agent, AgentManagementService $management): JsonResponse
    {
        $this->authorize('update', $agent);

        $agent = $management->update($agent, $request->validated());

        return AjaxEnvelope::ok(
            message: __('Agent updated.'),
            data: ['agent' => $agent],
        )->toJsonResponse();
    }

    public function updatePrompt(UpdateAgentPromptRequest $request, Agent $agent, AgentManagementService $management): JsonResponse
    {
        $this->authorize('update', $agent);

        $version = $management->updatePrompt($agent, $request->validated());

        return AjaxEnvelope::ok(
            message: __('Prompt saved.'),
            data: ['prompt_version' => $version],
        )->toJsonResponse();
    }

    public function run(RunAgentRequest $request, Agent $agent, AgentRunRecorder $recorder): JsonResponse
    {
        $this->authorize('run', $agent);

        $msg = $request->validated()['message'];
        $run = $recorder->startQueued(
            $request->user(),
            $agent,
            'chat',
            substr($msg, 0, 500),
            ['user_message' => $msg],
        );

        RunAgentJob::dispatch($run->id);

        return AjaxEnvelope::ok(
            message: __('Run queued.'),
            data: ['run_id' => $run->id, 'status' => $run->status, 'correlation_id' => $run->correlation_id],
            meta: [
                'run_status' => ['id' => $run->id, 'status' => $run->status],
            ],
        )->toJsonResponse();
    }

    public function executeTool(ExecuteAgentToolRequest $request, Agent $agent, AgentToolExecutionService $toolExecution): JsonResponse
    {
        $this->authorize('run', $agent);

        $v = $request->validated();
        try {
            $result = $toolExecution->executeEnabledTool(
                $request->user(),
                $agent,
                $v['tool_key'],
                $v['args'] ?? [],
            );
        } catch (\InvalidArgumentException $e) {
            return AjaxEnvelope::error(
                AjaxResponseCode::InvalidRequest,
                $e->getMessage(),
            )->toJsonResponse(422);
        }

        return AjaxEnvelope::ok(
            message: __('Tool executed.'),
            data: ['result' => $result],
        )->toJsonResponse();
    }

    public function updateTools(UpdateAgentToolsRequest $request, Agent $agent, ToolRegistry $registry): JsonResponse
    {
        $this->authorize('update', $agent);

        $allowed = collect($registry->catalog())->pluck('key')->all();
        foreach ($request->validated()['tools'] as $row) {
            if (! in_array($row['tool_key'], $allowed, true)) {
                return AjaxEnvelope::validationFailed(
                    ['tools' => [__('Unknown tool key.')]],
                )->toJsonResponse(422);
            }
        }

        DB::transaction(function () use ($request, $agent): void {
            foreach ($request->validated()['tools'] as $row) {
                AgentTool::query()->updateOrCreate(
                    ['agent_id' => $agent->id, 'tool_key' => $row['tool_key']],
                    ['enabled' => $row['enabled']],
                );
            }
        });

        return AjaxEnvelope::ok(message: __('Tools updated.'))->toJsonResponse();
    }

    public function updateBindings(UpdateAgentBindingsRequest $request, Agent $agent): JsonResponse
    {
        $this->authorize('update', $agent);

        $user = $request->user();
        foreach ($request->validated()['bindings'] as $row) {
            $cred = AgentApiCredential::query()
                ->where('user_id', $user->id)
                ->whereKey($row['credential_id'])
                ->first();
            if ($cred === null) {
                return AjaxEnvelope::validationFailed(
                    ['bindings' => [__('Invalid credential reference.')]],
                )->toJsonResponse(422);
            }
        }

        DB::transaction(function () use ($request, $agent): void {
            AgentProviderBinding::query()->where('agent_id', $agent->id)->delete();
            foreach ($request->validated()['bindings'] as $row) {
                AgentProviderBinding::query()->create([
                    'agent_id' => $agent->id,
                    'credential_id' => $row['credential_id'],
                    'priority' => $row['priority'],
                    'enabled' => $row['enabled'],
                ]);
            }
        });

        return AjaxEnvelope::ok(message: __('Provider bindings updated.'))->toJsonResponse();
    }

    public function compare(
        CompareProvidersRequest $request,
        Agent $agent,
        ProviderComparisonService $comparison,
        ProviderRouterService $router,
    ): JsonResponse {
        $this->authorize('run', $agent);

        $creds = $router->orderedCredentialsForAgent($agent);
        if ($creds === []) {
            return AjaxEnvelope::error(
                AjaxResponseCode::AgentProviderUnavailable,
                __('No provider credentials bound to this agent.'),
            )->toJsonResponse(422);
        }

        $v = $request->validated();
        $result = $comparison->compareLlm(
            $request->user(),
            $agent,
            $creds,
            $v['message'],
            (int) ($v['max_concurrency'] ?? 2),
            (int) ($v['budget_max_calls'] ?? 6),
        );

        return AjaxEnvelope::ok(
            message: __('Comparison completed.'),
            data: $result,
        )->toJsonResponse();
    }

    public function runStatus(Request $request, AgentRun $agent_run): JsonResponse
    {
        if ((int) $agent_run->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        return AjaxEnvelope::ok(
            message: '',
            data: [
                'run' => [
                    'id' => $agent_run->id,
                    'status' => $agent_run->status,
                    'outcome' => $agent_run->outcome,
                    'output_text' => $agent_run->output_text,
                    'error_code' => $agent_run->error_code,
                    'error_message' => $agent_run->error_message,
                    'provider' => $agent_run->provider,
                    'latency_ms' => $agent_run->latency_ms,
                ],
            ],
            meta: [
                'run_status' => ['id' => $agent_run->id, 'status' => $agent_run->status],
            ],
        )->toJsonResponse();
    }

    public function storeWorkflow(StoreWorkflowRequest $request, WorkflowDefinitionValidator $validator): JsonResponse
    {
        $this->authorize('viewAny', Agent::class);

        $def = $request->validated()['definition_json'];
        $validator->validate($def);

        $agentId = $request->validated()['agent_id'] ?? null;
        if ($agentId !== null) {
            $agent = Agent::query()->where('user_id', $request->user()->id)->whereKey($agentId)->firstOrFail();
            $this->authorize('update', $agent);
        }

        $wf = Workflow::query()->create([
            'user_id' => $request->user()->id,
            'agent_id' => $agentId,
            'name' => $request->validated()['name'],
            'definition_json' => $def,
            'version' => 1,
            'is_active' => true,
        ]);

        return AjaxEnvelope::ok(
            message: __('Workflow saved.'),
            data: ['workflow' => $wf],
        )->toJsonResponse();
    }

    public function runWorkflow(Request $request, Workflow $workflow, WorkflowRunService $workflowRunService): JsonResponse
    {
        if ((int) $workflow->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        $this->authorize('viewAny', Agent::class);

        $started = $workflowRunService->startManualRun($request->user(), $workflow);
        ExecuteWorkflowRunJob::dispatch($started['run']->id);

        return AjaxEnvelope::ok(
            message: __('Workflow run started.'),
            data: [
                'workflow_run_id' => $started['run']->id,
                'correlation_id' => $started['run']->correlation_id,
            ],
            meta: [
                'workflow_status' => ['id' => $started['run']->id, 'status' => $started['run']->status],
            ],
        )->toJsonResponse();
    }
}
