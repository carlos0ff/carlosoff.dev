<?php

use App\Http\Controllers\Admin\Gestao;
use App\Http\Controllers\Portal;
use App\Http\Middleware\UseBrazilianDates;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Gestão de clientes, projetos, contratos e cobranças
|--------------------------------------------------------------------------
| /admin/gestao/* → equipe (auth + admin + gates por função)
| /portal/*       → clientes (somente os dados da própria empresa)
*/

Route::prefix('admin/gestao')->name('admin.gestao.')->middleware(['auth', 'admin', 'can:gestao.view', UseBrazilianDates::class])->group(function () {
    Route::get('/', Gestao\DashboardController::class)->name('dashboard');

    // Clientes
    Route::resource('clientes', Gestao\ClientController::class)->parameters(['clientes' => 'client'])->except('destroy')->names('clients');
    Route::post('clientes/{client}/contatos', [Gestao\ClientController::class, 'storeContact'])->name('clients.contacts.store');
    Route::delete('clientes/{client}/contatos/{contact}', [Gestao\ClientController::class, 'destroyContact'])->name('clients.contacts.destroy');
    Route::post('clientes/{client}/acesso-portal', [Gestao\ClientController::class, 'storePortalUser'])->name('clients.portal-users.store');
    Route::get('clientes/{client}/exportar', [Gestao\ClientController::class, 'export'])->middleware('can:gestao.admin')->name('clients.export');
    Route::post('clientes/{client}/anonimizar', [Gestao\ClientController::class, 'anonymize'])->middleware('can:gestao.admin')->name('clients.anonymize');

    // Projetos
    Route::resource('projetos', Gestao\ClientProjectController::class)->parameters(['projetos' => 'project'])->except('destroy')->names('projects');
    Route::post('projetos/{project}/excecao', [Gestao\ClientProjectController::class, 'hold'])->middleware('can:gestao.finance')->name('projects.hold');
    Route::post('projetos/{project}/dominios', [Gestao\ClientProjectController::class, 'storeDomain'])->name('projects.domains.store');
    Route::post('projetos/{project}/hospedagem', [Gestao\ClientProjectController::class, 'storeHosting'])->name('projects.hosting.store');
    Route::post('projetos/{project}/validacoes', [Gestao\ClientProjectController::class, 'storeValidation'])->name('projects.validations.store');
    Route::post('projetos/{project}/credenciais', [Gestao\IntegrationController::class, 'storeInstallation'])->middleware('can:gestao.operations')->name('projects.credentials.store');
    Route::post('projetos/{project}/comandos', [Gestao\CommandController::class, 'store'])->middleware('can:gestao.operations')->name('projects.commands.store');
    Route::post('projetos/{project}/verificar-saude', [Gestao\MonitoringController::class, 'probe'])->middleware('can:gestao.operations')->name('projects.probe');

    // Comandos remotos
    Route::post('comandos/{command:uuid}/aprovar', [Gestao\CommandController::class, 'approve'])->middleware('can:gestao.admin')->name('commands.approve');
    Route::post('comandos/{command:uuid}/cancelar', [Gestao\CommandController::class, 'cancel'])->middleware('can:gestao.operations')->name('commands.cancel');
    Route::post('comandos/{command:uuid}/confirmar-operador', [Gestao\CommandController::class, 'confirmOperator'])->middleware('can:gestao.operations')->name('commands.confirm-operator');

    // Contratos e modelos
    Route::get('contratos', [Gestao\ContractController::class, 'index'])->name('contracts.index');
    Route::get('projetos/{project}/contratos/criar', [Gestao\ContractController::class, 'create'])->name('contracts.create');
    Route::post('projetos/{project}/contratos', [Gestao\ContractController::class, 'store'])->name('contracts.store');
    Route::get('contratos/{contract}', [Gestao\ContractController::class, 'show'])->name('contracts.show');
    Route::get('contratos/{contract}/editar', [Gestao\ContractController::class, 'edit'])->name('contracts.edit');
    Route::put('contratos/{contract}', [Gestao\ContractController::class, 'update'])->name('contracts.update');
    Route::put('contratos/{contract}/politica', [Gestao\ContractController::class, 'updatePolicy'])->middleware('can:gestao.finance')->name('contracts.policy');
    Route::post('contratos/{contract}/ativar', [Gestao\ContractController::class, 'activate'])->middleware('can:gestao.admin')->name('contracts.activate');
    Route::post('contratos/{contract}/encerrar', [Gestao\ContractController::class, 'end'])->middleware('can:gestao.admin')->name('contracts.end');
    Route::get('modelos', [Gestao\TemplateController::class, 'index'])->name('templates.index');
    Route::get('modelos/{template}/editar', [Gestao\TemplateController::class, 'edit'])->name('templates.edit');
    Route::put('modelos/{template}', [Gestao\TemplateController::class, 'update'])->middleware('can:gestao.admin')->name('templates.update');
    Route::post('modelos/{template}/revisao-juridica', [Gestao\TemplateController::class, 'approve'])->middleware('can:gestao.admin')->name('templates.approve');

    // Documentos e assinaturas
    Route::get('documentos', [Gestao\DocumentController::class, 'index'])->name('documents.index');
    Route::post('projetos/{project}/documentos', [Gestao\DocumentController::class, 'store'])->name('documents.store');
    Route::get('documentos/{document}', [Gestao\DocumentController::class, 'show'])->name('documents.show');
    Route::put('documentos/{document}', [Gestao\DocumentController::class, 'update'])->name('documents.update');
    Route::post('documentos/{document}/revisao', [Gestao\DocumentController::class, 'review'])->name('documents.review');
    Route::post('documentos/{document}/congelar', [Gestao\DocumentController::class, 'lock'])->middleware('can:gestao.admin')->name('documents.lock');
    Route::post('documentos/{document}/enviar', [Gestao\DocumentController::class, 'send'])->middleware('can:gestao.admin')->name('documents.send');
    Route::post('documentos/{document}/sincronizar', [Gestao\DocumentController::class, 'sync'])->name('documents.sync');
    Route::post('documentos/{document}/cancelar', [Gestao\DocumentController::class, 'cancel'])->middleware('can:gestao.admin')->name('documents.cancel');
    Route::post('documentos/{document}/signatarios', [Gestao\DocumentController::class, 'storeSigner'])->name('documents.signers.store');
    Route::delete('documentos/{document}/signatarios/{signer}', [Gestao\DocumentController::class, 'destroySigner'])->name('documents.signers.destroy');
    Route::get('documentos/{document}/pdf/{variant}', [Gestao\DocumentController::class, 'download'])->whereIn('variant', ['previa', 'original', 'assinado'])->name('documents.download');

    // Financeiro
    Route::get('cobrancas', [Gestao\ChargeController::class, 'index'])->name('charges.index');
    Route::get('cobrancas/criar', [Gestao\ChargeController::class, 'create'])->middleware('can:gestao.finance')->name('charges.create');
    Route::post('cobrancas', [Gestao\ChargeController::class, 'store'])->middleware('can:gestao.finance')->name('charges.store');
    Route::get('cobrancas/{charge:uuid}', [Gestao\ChargeController::class, 'show'])->name('charges.show');
    Route::middleware('can:gestao.finance')->group(function () {
        Route::post('cobrancas/{charge:uuid}/pagamentos', [Gestao\ChargeController::class, 'storePayment'])->name('charges.payments.store');
        Route::post('cobrancas/{charge:uuid}/estorno', [Gestao\ChargeController::class, 'refund'])->name('charges.refund');
        Route::post('cobrancas/{charge:uuid}/cancelar', [Gestao\ChargeController::class, 'cancel'])->name('charges.cancel');
        Route::post('cobrancas/{charge:uuid}/contestacao', [Gestao\ChargeController::class, 'dispute'])->name('charges.dispute');
        Route::post('cobrancas/{charge:uuid}/contestacao/resolver', [Gestao\ChargeController::class, 'resolveDispute'])->name('charges.dispute.resolve');
        Route::post('cobrancas/{charge:uuid}/negociacao', [Gestao\ChargeController::class, 'negotiate'])->name('charges.negotiate');
        Route::post('cobrancas/{charge:uuid}/propor-suspensao', [Gestao\ChargeController::class, 'proposeSuspension'])->name('charges.propose-suspension');
    });

    // Escopo, chamados, monitoramento, auditoria e integrações
    Route::get('solicitacoes', [Gestao\ScopeRequestController::class, 'index'])->name('scope.index');
    Route::post('projetos/{project}/solicitacoes', [Gestao\ScopeRequestController::class, 'store'])->name('scope.store');
    Route::get('solicitacoes/{scopeRequest}', [Gestao\ScopeRequestController::class, 'show'])->name('scope.show');
    Route::put('solicitacoes/{scopeRequest}/orcamento', [Gestao\ScopeRequestController::class, 'quote'])->name('scope.quote');
    Route::post('solicitacoes/{scopeRequest}/status', [Gestao\ScopeRequestController::class, 'status'])->name('scope.status');
    Route::get('chamados', [Gestao\TicketController::class, 'index'])->name('tickets.index');
    Route::get('chamados/{ticket}', [Gestao\TicketController::class, 'show'])->name('tickets.show');
    Route::post('chamados/{ticket}/responder', [Gestao\TicketController::class, 'reply'])->name('tickets.reply');
    Route::get('monitoramento', [Gestao\MonitoringController::class, 'index'])->name('monitoring.index');
    Route::get('auditoria', Gestao\AuditController::class)->name('audit.index');
    Route::get('integracoes', [Gestao\IntegrationController::class, 'index'])->name('integrations.index');
    Route::post('integracoes/automacao', [Gestao\IntegrationController::class, 'storeAutomation'])->middleware('can:gestao.admin')->name('integrations.automation.store');
    Route::post('credenciais/{credential}/rotacionar', [Gestao\IntegrationController::class, 'rotate'])->middleware('can:gestao.operations')->name('credentials.rotate');
    Route::post('credenciais/{credential}/revogar', [Gestao\IntegrationController::class, 'revoke'])->middleware('can:gestao.operations')->name('credentials.revoke');
    Route::post('anexos', [Gestao\AttachmentController::class, 'store'])->name('attachments.store');
    Route::get('anexos/{attachment}', [Gestao\AttachmentController::class, 'download'])->name('attachments.download');
});

Route::prefix('portal')->name('portal.')->middleware(UseBrazilianDates::class)->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [Portal\AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [Portal\AuthController::class, 'login'])->middleware('throttle:portal-login')->name('login.attempt');
        Route::get('definir-senha/{token}', [Portal\AuthController::class, 'showPassword'])->name('password.show');
        Route::post('definir-senha', [Portal\AuthController::class, 'storePassword'])->middleware('throttle:portal-login')->name('password.store');
    });

    Route::middleware(['auth', 'portal'])->group(function () {
        Route::post('logout', [Portal\AuthController::class, 'logout'])->name('logout');
        Route::get('/', Portal\DashboardController::class)->name('dashboard');
        Route::get('projetos/{project}', [Portal\ProjectController::class, 'show'])->name('projects.show');
        Route::get('documentos', [Portal\DocumentController::class, 'index'])->name('documents.index');
        Route::get('documentos/{document}', [Portal\DocumentController::class, 'show'])->name('documents.show');
        Route::get('documentos/{document}/pdf/{variant}', [Portal\DocumentController::class, 'download'])->whereIn('variant', ['original', 'assinado'])->name('documents.download');
        Route::get('cobrancas', [Portal\ChargeController::class, 'index'])->name('charges.index');
        Route::post('cobrancas/{charge:uuid}/comprovante', [Portal\ChargeController::class, 'uploadProof'])->middleware('throttle:10,1')->name('charges.proof');
        Route::post('cobrancas/{charge:uuid}/contestar', [Portal\ChargeController::class, 'dispute'])->middleware('throttle:10,1')->name('charges.dispute');
        Route::post('validacoes/{validation}', [Portal\ValidationController::class, 'respond'])->name('validations.respond');
        Route::get('solicitacoes', [Portal\ScopeRequestController::class, 'index'])->name('scope.index');
        Route::post('projetos/{project}/solicitacoes', [Portal\ScopeRequestController::class, 'store'])->middleware('throttle:20,1')->name('scope.store');
        Route::post('solicitacoes/{scopeRequest}/decisao', [Portal\ScopeRequestController::class, 'decide'])->name('scope.decide');
        Route::get('chamados', [Portal\TicketController::class, 'index'])->name('tickets.index');
        Route::post('projetos/{project}/chamados', [Portal\TicketController::class, 'store'])->middleware('throttle:20,1')->name('tickets.store');
        Route::get('chamados/{ticket}', [Portal\TicketController::class, 'show'])->name('tickets.show');
        Route::post('chamados/{ticket}/responder', [Portal\TicketController::class, 'reply'])->middleware('throttle:30,1')->name('tickets.reply');
        Route::get('notificacoes', [Portal\NotificationController::class, 'index'])->name('notifications.index');
    });
});
