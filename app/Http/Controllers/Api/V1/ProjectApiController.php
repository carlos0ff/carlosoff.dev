<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\AuditAction;
use App\Enums\ChargeStatus;
use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Enums\CredentialKind;
use App\Http\Controllers\Controller;
use App\Models\ApiCredential;
use App\Models\AuditLog;
use App\Models\Charge;
use App\Models\ClientProject;
use App\Models\RemoteCommand;
use App\Services\Api\RequestSigner;
use App\Services\Monitoring\AgentHeartbeat;
use App\Services\Operations\Actor;
use App\Services\Operations\CommandNotAllowedException;
use App\Services\Operations\IdempotencyConflictException;
use App\Services\Operations\RemoteCommandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API v1 de gerenciamento remoto. Contratos documentados em docs/gestao/API.md.
 */
class ProjectApiController extends Controller
{
    public function __construct(protected RemoteCommandService $commands) {}

    public function status(Request $request, ClientProject $project): JsonResponse
    {
        $this->authorizeFor($request, $project, 'status:read');

        return response()->json(['data' => [
            'project' => ['id' => $project->uuid, 'name' => $project->name],
            'development_status' => $project->development_status->value,
            'contract_status' => $project->contract_status->value,
            'operational_status' => $project->operational_status->value,
            'restriction' => [
                'active' => $project->active_restriction !== null,
                'mode' => $project->active_restriction?->value,
            ],
            'deployed_version' => $project->deployed_version,
            'agent_last_seen_at' => $project->agent_last_seen_at?->toIso8601String(),
        ]]);
    }

    public function health(Request $request, ClientProject $project): JsonResponse
    {
        $this->authorizeFor($request, $project, 'health:read');

        $probe = $project->healthChecks()->where('source', 'probe')->latest('checked_at')->first();
        $agent = $project->healthChecks()->where('source', 'agent')->latest('checked_at')->first();

        return response()->json(['data' => [
            'probe' => $probe ? [
                'is_up' => $probe->is_up,
                'http_status' => $probe->http_status,
                'response_time_ms' => $probe->response_time_ms,
                'ssl_expires_at' => $probe->ssl_expires_at?->toIso8601String(),
                'checked_at' => $probe->checked_at->toIso8601String(),
            ] : null,
            'agent' => $agent ? [
                'is_up' => $agent->is_up,
                'deployed_version' => $agent->deployed_version,
                'last_backup_at' => $agent->last_backup_at?->toIso8601String(),
                'app_errors_last_hour' => $agent->app_errors_last_hour,
                'checked_at' => $agent->checked_at->toIso8601String(),
            ] : null,
            'open_incidents' => $project->incidents()->whereNull('resolved_at')->count(),
        ]]);
    }

    public function reportHealth(Request $request, ClientProject $project, AgentHeartbeat $heartbeat): JsonResponse
    {
        $this->authorizeFor($request, $project, 'health:write');

        $payload = $request->validate([
            'state' => ['nullable', 'in:active,maintenance,suspended,restricted'],
            'deployed_version' => ['nullable', 'string', 'max:60'],
            'agent_version' => ['nullable', 'string', 'max:20'],
            'php_version' => ['nullable', 'string', 'max:20'],
            'last_backup_at' => ['nullable', 'date'],
            'app_errors_last_hour' => ['nullable', 'integer', 'min:0'],
            'resources' => ['nullable', 'array'],
            'resources.cpu_load' => ['nullable', 'numeric', 'min:0'],
            'resources.memory_used_pct' => ['nullable', 'numeric', 'between:0,100'],
            'resources.disk_used_pct' => ['nullable', 'numeric', 'between:0,100'],
            'dependencies' => ['nullable', 'array', 'max:20'],
            'dependencies.*.name' => ['required', 'string', 'max:40'],
            'dependencies.*.ok' => ['required', 'boolean'],
            'dependencies.*.latency_ms' => ['nullable', 'integer', 'min:0'],
        ]);

        $check = $heartbeat->ingest($project, $payload);

        return response()->json(['data' => ['received_at' => $check->checked_at->toIso8601String()]], 202);
    }

    public function billingStatus(Request $request, ClientProject $project): JsonResponse
    {
        $credential = $this->authorizeFor($request, $project, 'billing:read');

        $data = [
            'financial_status' => $project->financial_status->value,
            'restricted' => $project->active_restriction !== null,
            'regularization_url' => route('portal.charges.index'),
        ];

        // Valores só para a automação administrativa (o agente recebe apenas o necessário).
        if ($credential->kind === CredentialKind::Automation) {
            $open = $project->charges()->open()->get();
            $data += [
                'open_cents' => $open->sum(fn (Charge $charge) => $charge->outstandingCents()),
                'overdue_cents' => $open->where('status', ChargeStatus::Overdue)->sum(fn (Charge $charge) => $charge->outstandingCents()),
                'next_due_date' => $open->where('status', '!=', ChargeStatus::Overdue)->min('due_date')?->toDateString(),
            ];
        }

        return response()->json(['data' => $data]);
    }

    public function issueCommand(Request $request, ClientProject $project, string $type): JsonResponse
    {
        $credential = $this->authorizeFor($request, $project, 'commands:issue');

        if ($credential->kind !== CredentialKind::Automation) {
            return $this->error('forbidden', 'Somente credenciais administrativas emitem comandos.', 403);
        }

        $commandType = CommandType::from($type);
        $key = (string) $request->header('Idempotency-Key');
        if (! preg_match('/^[A-Za-z0-9:_\-.]{8,100}$/', $key)) {
            return $this->error('idempotency_key_required', 'Envie o cabeçalho Idempotency-Key (8 a 100 caracteres).', 400);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:500'],
            'charge_id' => [$commandType === CommandType::Suspend ? 'required' : 'nullable', 'uuid'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        if (isset($validated['charge_id'])) {
            $validated['charge_id'] = Charge::where('uuid', $validated['charge_id'])->where('client_project_id', $project->id)->value('id');
        }

        try {
            $command = $this->commands->issue($project, $commandType, $validated, $key, Actor::credential($credential));
        } catch (IdempotencyConflictException $exception) {
            return $this->error('idempotency_conflict', $exception->getMessage(), 409);
        } catch (CommandNotAllowedException $exception) {
            return response()->json(['error' => ['code' => 'not_allowed', 'message' => $exception->getMessage(), 'guard' => $exception->report]], 422);
        }

        return response()->json(['data' => $this->commandResource($command)], $command->wasRecentlyCreated ? 201 : 200);
    }

    public function pendingCommands(Request $request, ClientProject $project): JsonResponse
    {
        $credential = $this->authorizeFor($request, $project, 'commands:pull');

        if ($credential->kind !== CredentialKind::Installation) {
            return $this->error('forbidden', 'Somente o agente da instalação recebe comandos.', 403);
        }

        $commands = array_map(fn (RemoteCommand $command) => $command->toAgentPayload(), $this->commands->pullForAgent($project));

        return $this->signed($credential, ['data' => $commands]);
    }

    public function acknowledge(Request $request, ClientProject $project, string $command): JsonResponse
    {
        $credential = $this->authorizeFor($request, $project, 'commands:pull');

        $validated = $request->validate([
            'status' => ['required', 'in:executed,rejected,failed'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $remote = RemoteCommand::where('uuid', $command)->where('client_project_id', $project->id)->where('channel', 'agent')->first();
        if (! $remote) {
            return $this->error('not_found', 'Comando não encontrado.', 404);
        }

        $remote = $this->commands->acknowledge($remote, CommandStatus::from($validated['status']), array_filter(['message' => $validated['message'] ?? null]), Actor::credential($credential));

        return response()->json(['data' => $this->commandResource($remote)]);
    }

    /**
     * Autenticado ≠ autorizado: confere escopo da credencial e o vínculo com o projeto.
     */
    protected function authorizeFor(Request $request, ClientProject $project, string $ability): ApiCredential
    {
        /** @var ApiCredential $credential */
        $credential = $request->attributes->get('api_credential');

        // 404 para projeto de outro cliente: não revela que ele existe.
        abort_unless($credential->canAccessProject($project), 404);
        abort_unless($credential->can($ability), 403, "Credencial sem permissão: {$ability}");

        if ($credential->kind === CredentialKind::Automation && $request->method() !== 'GET') {
            AuditLog::forProject($project, AuditAction::Api, 'API', null, $request->path(), null, [], actorName: "API: {$credential->name}", actorType: 'api');
        }

        return $credential;
    }

    /**
     * @return array<string, mixed>
     */
    protected function commandResource(RemoteCommand $command): array
    {
        return [
            'id' => $command->uuid,
            'type' => $command->type->value,
            'status' => $command->status->value,
            'channel' => $command->channel,
            'mode' => $command->restriction_mode?->value,
            'reason' => $command->reason,
            'created_at' => $command->created_at->toIso8601String(),
            'acknowledged_at' => $command->acknowledged_at?->toIso8601String(),
        ];
    }

    /**
     * Resposta assinada com o segredo da credencial: o agente só executa comandos cuja assinatura confere.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function signed(ApiCredential $credential, array $payload): JsonResponse
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = (string) time();

        return response()->json($payload)
            ->setContent($body)
            ->header('X-Response-Timestamp', $timestamp)
            ->header('X-Response-Signature', RequestSigner::signResponse($credential->secret, $timestamp, $body));
    }

    protected function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status);
    }
}
