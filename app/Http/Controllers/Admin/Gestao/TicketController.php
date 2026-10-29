<?php

namespace App\Http\Controllers\Admin\Gestao;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\Notifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $status = TicketStatus::tryFrom((string) $request->query('status'));

        return view('admin.gestao.tickets.index', [
            'tickets' => SupportTicket::with(['project', 'client'])
                ->when($status, fn (Builder $q) => $q->where('status', $status->value))
                ->orderByRaw("case priority when 'urgent' then 0 when 'high' then 1 when 'normal' then 2 else 3 end")
                ->latest()->paginate(20)->withQueryString(),
            'current' => $status?->value ?? 'all',
            'counts' => ['all' => SupportTicket::count()] + SupportTicket::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
        ]);
    }

    public function show(SupportTicket $ticket): View
    {
        return view('admin.gestao.tickets.show', ['ticket' => $ticket->load(['project', 'client', 'opener', 'messages.author'])]);
    }

    public function reply(Request $request, SupportTicket $ticket, Notifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
            'is_internal' => ['boolean'],
            'status' => ['required', Rule::enum(TicketStatus::class)],
        ]);
        $internal = $request->boolean('is_internal');

        $ticket->messages()->create(['user_id' => $request->user()->id, 'body' => $data['body'], 'is_internal' => $internal]);
        $ticket->forceFill([
            'status' => TicketStatus::from($data['status']),
            'first_response_at' => $ticket->first_response_at ?? ($internal ? null : now()),
            'resolved_at' => in_array($data['status'], ['resolved', 'closed'], true) ? ($ticket->resolved_at ?? now()) : null,
        ])->save();

        if (! $internal) {
            $notifier->client($ticket->client, "Atualização no chamado #{$ticket->id}: {$ticket->subject}", ['Há uma nova resposta da equipe.'], 'Ver chamado', route('portal.tickets.show', $ticket), 'suporte');
        }

        return back()->with('status', 'Resposta registrada.');
    }
}
