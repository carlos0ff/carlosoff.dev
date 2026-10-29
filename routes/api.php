<?php

use App\Http\Controllers\Api\V1\ProjectApiController;
use App\Http\Controllers\Webhooks\PaymentWebhookController;
use App\Http\Controllers\Webhooks\SignatureWebhookController;
use Illuminate\Support\Facades\Route;

// API de gerenciamento remoto (HMAC por credencial + anti-replay + rate limit). Ver docs/gestao/API.md.
Route::prefix('v1')->middleware(['api.signature', 'throttle:gestao-api'])->name('api.v1.')->group(function () {
    Route::get('projects/{project}/status', [ProjectApiController::class, 'status'])->name('projects.status');
    Route::get('projects/{project}/health', [ProjectApiController::class, 'health'])->name('projects.health');
    Route::post('projects/{project}/health', [ProjectApiController::class, 'reportHealth'])->name('projects.health.report');
    Route::get('projects/{project}/billing-status', [ProjectApiController::class, 'billingStatus'])->name('projects.billing-status');
    Route::post('projects/{project}/commands/{type}', [ProjectApiController::class, 'issueCommand'])
        ->whereIn('type', ['maintenance', 'suspend', 'reactivate'])->name('projects.commands.issue');
    Route::get('projects/{project}/commands/pending', [ProjectApiController::class, 'pendingCommands'])->name('projects.commands.pending');
    Route::post('projects/{project}/commands/{command}/ack', [ProjectApiController::class, 'acknowledge'])->whereUuid('command')->name('projects.commands.ack');
});

// Webhooks: autenticidade validada pela assinatura de cada provedor.
Route::post('webhooks/assinatura/{provider}', SignatureWebhookController::class)->middleware('throttle:120,1')->name('webhooks.signature');
Route::post('webhooks/pagamento/{provider}', PaymentWebhookController::class)->middleware('throttle:120,1')->name('webhooks.payment');
