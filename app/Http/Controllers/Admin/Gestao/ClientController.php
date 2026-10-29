<?php

namespace App\Http\Controllers\Admin\Gestao;

use App\Enums\AuditAction;
use App\Enums\ClientStatus;
use App\Enums\ContactRole;
use App\Enums\StaffRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\User;
use App\Services\Gestao\ClientDataService;
use App\Support\BrazilianDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $status = ClientStatus::tryFrom((string) $request->query('status'));
        $search = trim((string) $request->query('q'));

        $clients = Client::query()
            ->withCount('projects')
            ->when($status, fn (Builder $q) => $q->where('status', $status->value))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $inner) => $inner
                ->where('legal_name', 'like', "%{$search}%")->orWhere('trade_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")->orWhere('document', 'like', '%'.BrazilianDocument::digits($search).'%')))
            ->orderBy('legal_name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.gestao.clients.index', [
            'clients' => $clients,
            'search' => $search,
            'current' => $status?->value ?? 'all',
            'counts' => ['all' => Client::count()] + Client::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all(),
        ]);
    }

    public function create(): View
    {
        return view('admin.gestao.clients.form', ['client' => new Client(['status' => ClientStatus::Active, 'person_type' => 'pj'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $client = Client::create($this->validated($request));
        AuditLog::record(AuditAction::Create, 'Cliente', $client->id, $client->legal_name, null, ['client_id' => $client->id]);

        return redirect()->route('admin.gestao.clients.show', $client)->with('status', 'Cliente cadastrado.');
    }

    public function show(Client $client): View
    {
        $client->load(['contacts', 'projects', 'portalUsers', 'attachments', 'domains']);

        return view('admin.gestao.clients.show', [
            'client' => $client,
            'contracts' => $client->contracts()->with('project')->latest()->get(),
            'charges' => $client->charges()->with('project')->latest('due_date')->limit(10)->get(),
            'history' => AuditLog::where('client_id', $client->id)->latest('id')->limit(15)->get(),
        ]);
    }

    public function edit(Client $client): View
    {
        return view('admin.gestao.clients.form', ['client' => $client]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $client->fill($this->validated($request, $client));
        $changed = array_keys($client->getDirty());
        $client->save();

        AuditLog::record(AuditAction::Update, 'Cliente', $client->id, $client->legal_name, 'Campos: '.implode(', ', $changed), ['client_id' => $client->id]);

        return redirect()->route('admin.gestao.clients.show', $client)->with('status', 'Cliente atualizado.');
    }

    public function storeContact(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', Rule::enum(ContactRole::class)],
            'position' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'document' => ['nullable', 'string', 'max:20', function ($attribute, $value, $fail) {
                if ($value && ! BrazilianDocument::isValidCpf($value)) {
                    $fail('CPF do contato inválido.');
                }
            }],
            'is_primary' => ['boolean'],
        ], [], ['name' => 'nome', 'role' => 'função']);

        $data['document'] = isset($data['document']) ? BrazilianDocument::digits($data['document']) : null;
        $contact = $client->contacts()->create($data);
        AuditLog::record(AuditAction::Create, 'Contato', $contact->id, $contact->name, $contact->role->label(), ['client_id' => $client->id]);

        return back()->with('status', 'Contato adicionado.');
    }

    public function destroyContact(Client $client, ClientContact $contact): RedirectResponse
    {
        abort_unless($contact->client_id === $client->id, 404);
        $contact->delete();
        AuditLog::record(AuditAction::Delete, 'Contato', $contact->id, $contact->name, null, ['client_id' => $client->id]);

        return back()->with('status', 'Contato removido.');
    }

    /**
     * Cria o acesso ao portal; o cliente define a própria senha pelo link de redefinição (a equipe nunca conhece a senha).
     */
    public function storePortalUser(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => Str::password(40)]);
        $user->is_admin = false;
        $user->role = StaffRole::Client;
        $user->client_id = $client->id;
        $user->save();

        $token = Password::broker()->createToken($user);
        AuditLog::record(AuditAction::Create, 'Acesso ao portal', $user->id, $user->email, null, ['client_id' => $client->id]);

        return back()->with('status', 'Acesso criado.')->with('portal_link', route('portal.password.show', ['token' => $token, 'email' => $user->email]));
    }

    /**
     * Portabilidade (LGPD): exporta os dados do cliente em JSON.
     */
    public function export(Client $client, ClientDataService $data): StreamedResponse
    {
        AuditLog::record(AuditAction::Export, 'Cliente', $client->id, $client->legal_name, 'Exportação de dados (LGPD)', ['client_id' => $client->id]);

        return response()->streamDownload(fn () => print (json_encode($data->export($client), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'cliente-'.$client->uuid.'.json', ['Content-Type' => 'application/json']);
    }

    public function anonymize(Request $request, Client $client, ClientDataService $data): RedirectResponse
    {
        $request->validate(['confirmation' => ['required', 'in:'.$client->uuid]], ['confirmation.in' => 'Digite o identificador exato do cliente para confirmar.']);

        $result = $data->anonymize($client);

        return $result === true
            ? redirect()->route('admin.gestao.clients.show', $client)->with('status', 'Dados pessoais anonimizados. Registros financeiros foram mantidos pelo prazo legal.')
            : back()->withErrors(['client' => $result]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Client $client = null): array
    {
        $data = $request->validate([
            'person_type' => ['required', 'in:pf,pj'],
            'legal_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'document' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'address_street' => ['nullable', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:20'],
            'address_complement' => ['nullable', 'string', 'max:255'],
            'address_district' => ['nullable', 'string', 'max:255'],
            'address_city' => ['nullable', 'string', 'max:255'],
            'address_state' => ['nullable', 'string', 'size:2'],
            'address_zip' => ['nullable', 'string', 'max:10'],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'billing_notes' => ['nullable', 'string', 'max:2000'],
            'relationship_started_at' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(ClientStatus::class)],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ], [], ['legal_name' => 'nome/razão social', 'document' => 'CPF/CNPJ', 'email' => 'e-mail']);

        if (filled($data['document'] ?? null)) {
            $digits = BrazilianDocument::digits($data['document']);
            $valid = $data['person_type'] === 'pf' ? BrazilianDocument::isValidCpf($digits) : BrazilianDocument::isValidCnpj($digits);

            if (! $valid) {
                throw ValidationException::withMessages(['document' => $data['person_type'] === 'pf' ? 'CPF inválido.' : 'CNPJ inválido.']);
            }

            if (Client::withTrashed()->where('document', $digits)->when($client, fn ($q) => $q->whereKeyNot($client->id))->exists()) {
                throw ValidationException::withMessages(['document' => 'Já existe um cliente com este CPF/CNPJ.']);
            }

            $data['document'] = $digits;
        }

        $data['address_state'] = isset($data['address_state']) ? strtoupper($data['address_state']) : null;

        return $data;
    }
}
