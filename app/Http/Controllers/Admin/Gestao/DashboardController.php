<?php

namespace App\Http\Controllers\Admin\Gestao;

use App\Enums\ChargeStatus;
use App\Enums\ClientStatus;
use App\Enums\ContractStatus;
use App\Enums\DevelopmentStatus;
use App\Enums\DocumentStatus;
use App\Enums\OperationalStatus;
use App\Enums\ScopeRequestStatus;
use App\Enums\ValidationOutcome;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Charge;
use App\Models\Client;
use App\Models\ClientProject;
use App\Models\Contract;
use App\Models\Document;
use App\Models\Domain;
use App\Models\HostingService;
use App\Models\MonitoringIncident;
use App\Models\Payment;
use App\Models\ProjectValidation;
use App\Models\RemoteCommand;
use App\Models\ScopeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Painel central. Os indicadores financeiros separam contratado, faturado, recebido, vencido e previsto.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = [
            'client' => $request->integer('client') ?: null,
            'project' => $request->integer('project') ?: null,
            'status' => $request->query('status'),
            'lead' => $request->integer('lead') ?: null,
            'period' => preg_match('/^\d{4}-\d{2}$/', (string) $request->query('period')) ? $request->query('period') : now()->format('Y-m'),
        ];

        $start = Carbon::createFromFormat('Y-m-d', $filters['period'].'-01')->startOfDay();
        $end = $start->copy()->endOfMonth();
        $today = Carbon::today();

        $projectIds = ClientProject::query()
            ->when($filters['client'], fn (Builder $q, $id) => $q->where('client_id', $id))
            ->when($filters['project'], fn (Builder $q, $id) => $q->whereKey($id))
            ->when($filters['lead'], fn (Builder $q, $id) => $q->where('technical_lead_id', $id))
            ->when($filters['status'], fn (Builder $q, $status) => $q->where(fn (Builder $inner) => $inner
                ->where('development_status', $status)->orWhere('financial_status', $status)->orWhere('operational_status', $status)))
            ->pluck('id');

        $projects = ClientProject::whereIn('id', $projectIds);
        $charges = fn () => Charge::whereIn('client_project_id', $projectIds);

        $overdue = $charges()->where('status', ChargeStatus::Overdue->value)->get();

        $kpis = [
            'clients' => Client::where('status', ClientStatus::Active->value)
                ->when($filters['client'] || $filters['project'] || $filters['lead'] || $filters['status'], fn (Builder $q) => $q->whereIn('id', (clone $projects)->pluck('client_id')))->count(),
            'in_development' => (clone $projects)->whereIn('development_status', [DevelopmentStatus::Planning->value, DevelopmentStatus::Development->value, DevelopmentStatus::Staging->value])->count(),
            'in_production' => (clone $projects)->whereNotNull('published_at')->where('operational_status', '!=', OperationalStatus::Terminated->value)->count(),
            'awaiting_signature' => Document::whereIn('client_project_id', $projectIds)->whereIn('status', [DocumentStatus::AwaitingSignature->value, DocumentStatus::PartiallySigned->value])->count(),
            'awaiting_validation' => ProjectValidation::whereIn('client_project_id', $projectIds)->where('outcome', ValidationOutcome::Pending->value)->count(),
            'charges_pending' => $charges()->whereIn('status', [ChargeStatus::Pending->value, ChargeStatus::PartiallyPaid->value])->count(),
            'charges_overdue' => $overdue->count(),
            'maintenance_or_suspended' => (clone $projects)->whereIn('operational_status', [OperationalStatus::ScheduledMaintenance->value, OperationalStatus::Suspended->value])->count(),
            'scope_awaiting' => ScopeRequest::whereIn('client_project_id', $projectIds)->where('status', ScopeRequestStatus::Quoted->value)->count(),
        ];

        $finance = [
            // Contratado: mensalidades de contratos vigentes (não é dinheiro recebido).
            'mrr_contracted' => (int) Contract::whereIn('client_project_id', $projectIds)->where('status', ContractStatus::Active->value)->sum('monthly_fee_cents'),
            // Faturado: cobranças emitidas com vencimento no período (exceto canceladas).
            'invoiced' => (int) $charges()->whereBetween('due_date', [$start, $end])->where('status', '!=', ChargeStatus::Cancelled->value)->sum('amount_cents'),
            // Recebido: somente pagamentos confirmados no período (líquido de estornos).
            'received' => (int) Payment::whereIn('charge_id', $charges()->select('id'))->whereBetween('paid_at', [$start, $end->copy()->endOfDay()])->sum('amount_cents'),
            // Vencido: saldo em aberto de cobranças vencidas.
            'overdue' => $overdue->sum(fn (Charge $charge) => $charge->outstandingCents()),
            // Previsto: saldo de cobranças ainda não vencidas com vencimento no período.
            'forecast' => $charges()->whereIn('status', [ChargeStatus::Pending->value, ChargeStatus::PartiallyPaid->value])->whereBetween('due_date', [$start, $end])->get()->sum(fn (Charge $charge) => $charge->outstandingCents()),
        ];

        // Série dos últimos 12 meses: recebido (pagamentos confirmados) × faturado (cobranças emitidas).
        $months = collect(range(11, 0))->map(fn (int $back) => $end->copy()->startOfMonth()->subMonthsNoOverflow($back));
        $paymentsByMonth = Payment::whereIn('charge_id', $charges()->select('id'))
            ->where('paid_at', '>=', $months->first())->get(['amount_cents', 'paid_at'])
            ->groupBy(fn (Payment $payment) => $payment->paid_at->format('Y-m'))->map->sum('amount_cents');
        $invoicedByMonth = $charges()->where('status', '!=', ChargeStatus::Cancelled->value)
            ->where('due_date', '>=', $months->first())->get(['amount_cents', 'due_date'])
            ->groupBy(fn (Charge $charge) => $charge->due_date->format('Y-m'))->map->sum('amount_cents');

        $openByClient = Charge::with('client')->whereIn('client_project_id', $projectIds)->open()->get()
            ->groupBy('client_id')
            ->map(fn ($group) => ['label' => $group->first()->client->displayName(), 'value' => (int) round($group->sum(fn (Charge $c) => $c->outstandingCents()) / 100), 'href' => route('admin.gestao.clients.show', $group->first()->client_id)])
            ->sortByDesc('value')->take(6)->values();

        $mrrByClient = Contract::with('client')->whereIn('client_project_id', $projectIds)->where('status', ContractStatus::Active->value)->get()
            ->groupBy('client_id')
            ->map(fn ($group) => ['label' => $group->first()->client->displayName(), 'value' => (int) round($group->sum('monthly_fee_cents') / 100), 'href' => route('admin.gestao.clients.show', $group->first()->client_id)])
            ->sortByDesc('value')->take(6)->values();

        return view('admin.gestao.dashboard', [
            'series' => [
                'labels' => $months->map(fn (Carbon $month) => $month->format('m/y'))->all(),
                'received' => $months->map(fn (Carbon $month) => (int) round(($paymentsByMonth[$month->format('Y-m')] ?? 0) / 100))->all(),
                'invoiced' => $months->map(fn (Carbon $month) => (int) round(($invoicedByMonth[$month->format('Y-m')] ?? 0) / 100))->all(),
            ],
            'openByClient' => $openByClient,
            'mrrByClient' => $mrrByClient,
            'recentLogs' => AuditLog::whereIn('client_project_id', $projectIds)->latest('id')->limit(8)->get(),
            'activeFilters' => collect($filters)->except('period')->filter()->count(),
            'filters' => $filters,
            'kpis' => $kpis,
            'finance' => $finance,
            'dueSoon' => $charges()->open()->whereBetween('due_date', [$today, $today->copy()->addDays(7)])->with('project.client')->orderBy('due_date')->limit(8)->get(),
            'overdueList' => $overdue->sortBy('due_date')->take(8)->load('project.client'),
            'expiringDomains' => Domain::whereIn('client_project_id', $projectIds)->where(fn (Builder $q) => $q
                ->whereBetween('expires_at', [$today, $today->copy()->addDays((int) config('gestao.monitoring.domain_warning_days'))])
                ->orWhereBetween('ssl_expires_at', [$today, $today->copy()->addDays((int) config('gestao.monitoring.ssl_warning_days'))]))->with('project')->limit(8)->get(),
            'renewingHosting' => HostingService::whereIn('client_project_id', $projectIds)->whereBetween('renews_at', [$today, $today->copy()->addDays(30)])->with('project')->limit(8)->get(),
            'pendingCommands' => RemoteCommand::whereIn('client_project_id', $projectIds)->where('status', 'pending_approval')->with('project')->latest()->get(),
            'openIncidents' => MonitoringIncident::whereIn('client_project_id', $projectIds)->whereNull('resolved_at')->with('project')->latest('opened_at')->get(),
            'attentionProjects' => ClientProject::whereIn('id', $projectIds)->whereIn('operational_status', [OperationalStatus::ScheduledMaintenance->value, OperationalStatus::Suspended->value])->with('client')->get(),
            'clients' => Client::orderBy('legal_name')->pluck('legal_name', 'id'),
            'projectOptions' => ClientProject::orderBy('name')->pluck('name', 'id'),
            'leads' => User::where('is_admin', true)->orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
