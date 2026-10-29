<?php

namespace App\Http\Controllers\Portal;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\ClientProject;
use App\Models\SupportTicket;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends PortalController
{
    public function index(Request $request): View
    {
        return view('portal.tickets.index', [
            'tickets' => SupportTicket::where('client_id', $this->clientId($request))->with('project')->latest()->paginate(20),
            'projects' => ClientProject::where('client_id', $this->clientId($request))->orderBy('name')->get(),
        ]);
    }

    /**
     * Prazo de resposta e prazo de solução vêm do SLA do contrato (são coisas diferentes).
     */
    public function store(Request $request, ClientProject $project, Notifier $notifier): RedirectResponse
    {
        $this->ensureProject($request, $project);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'priority' => ['required', Rule::enum(TicketPriority::class)],
        ], [], ['subject' => 'assunto', 'description' => 'descrição', 'priority' => 'prioridade']);

        $sla = $project->activeContract?->support_sla[$data['priority']] ?? null;

        $ticket = SupportTicket::create($data + [
            'client_id' => $project->client_id,
            'client_project_id' => $project->id,
            'opened_by' => $request->user()->id,
            'response_due_at' => $sla ? now()->addHours($sla['response_hours']) : null,
            'resolution_due_at' => $sla ? now()->addHours($sla['resolution_hours']) : null,
        ]);
        $ticket->forceFill(['status' => TicketStatus::Open])->save();

        $notifier->staff("Chamado #{$ticket->id} ({$ticket->priority->label()}): {$ticket->subject}", "Projeto {$project->name}", route('admin.gestao.tickets.show', $ticket), 'suporte');

        return redirect()->route('portal.tickets.show', $ticket)->with('status', 'Chamado aberto.');
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        $this->ensureOwned($request, $ticket);

        return view('portal.tickets.show', [
            'ticket' => $ticket->load(['project', 'messages' => fn ($q) => $q->where('is_internal', false)->with('author')]),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket, Notifier $notifier): RedirectResponse
    {
        $this->ensureOwned($request, $ticket);
        $body = $request->validate(['body' => ['required', 'string', 'max:10000']])['body'];

        $ticket->messages()->create(['user_id' => $request->user()->id, 'body' => $body, 'is_internal' => false]);
        if (in_array($ticket->status, [TicketStatus::WaitingClient, TicketStatus::Resolved], true)) {
            $ticket->forceFill(['status' => TicketStatus::InProgress, 'resolved_at' => null])->save();
        }

        $notifier->staff("Resposta do cliente no chamado #{$ticket->id}", $ticket->subject, route('admin.gestao.tickets.show', $ticket), 'suporte');

        return back()->with('status', 'Mensagem enviada.');
    }
}
