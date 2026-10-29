<?php

namespace App\Http\Controllers\Admin\Gestao;

use App\Enums\CommandStatus;
use App\Enums\CommandType;
use App\Http\Controllers\Controller;
use App\Models\ClientProject;
use App\Models\RemoteCommand;
use App\Services\Operations\Actor;
use App\Services\Operations\CommandNotAllowedException;
use App\Services\Operations\RemoteCommandService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommandController extends Controller
{
    public function __construct(protected RemoteCommandService $commands) {}

    /**
     * Manutenção programada ou reativação manual pelo painel. Suspensão só nasce de uma cobrança (ver ChargeController).
     */
    public function store(Request $request, ClientProject $project): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:maintenance,reactivate'],
            'reason' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:500'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'idempotency_key' => ['required', 'string', 'max:100'],
        ], [], ['reason' => 'motivo']);

        try {
            $this->commands->issue($project, CommandType::from($data['type']), $data, 'panel:'.$data['idempotency_key'], Actor::user($request->user()));
        } catch (CommandNotAllowedException $exception) {
            return back()->withErrors(['command' => $exception->getMessage()]);
        }

        return back()->with('status', 'Comando registrado.');
    }

    public function approve(Request $request, RemoteCommand $command): RedirectResponse
    {
        try {
            $this->commands->approve($command, $request->user());
        } catch (CommandNotAllowedException $exception) {
            return back()->withErrors(['command' => $exception->getMessage()]);
        }

        return back()->with('status', 'Comando aprovado.');
    }

    public function cancel(Request $request, RemoteCommand $command): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'max:255']])['reason'];

        try {
            $this->commands->cancel($command, $reason, Actor::user($request->user()));
        } catch (CommandNotAllowedException $exception) {
            return back()->withErrors(['command' => $exception->getMessage()]);
        }

        return back()->with('status', 'Comando cancelado.');
    }

    /**
     * Comandos de canal `operator` (ex.: suspensão de hospedagem): a equipe executa e registra a evidência.
     */
    public function confirmOperator(Request $request, RemoteCommand $command): RedirectResponse
    {
        abort_unless($command->channel === 'operator' && $command->status === CommandStatus::Queued, 422, 'Comando não aguarda execução manual.');
        $notes = $request->validate(['notes' => ['required', 'string', 'max:1000']])['notes'];

        $this->commands->acknowledge($command, CommandStatus::Executed, ['message' => Str::limit($notes, 1000)], Actor::user($request->user()));

        return back()->with('status', 'Execução registrada.');
    }
}
