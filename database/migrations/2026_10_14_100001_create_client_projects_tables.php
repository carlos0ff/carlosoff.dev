<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('technical_lead_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('objective')->nullable();
            $table->string('technologies')->nullable();
            $table->string('repository_url')->nullable();
            $table->string('primary_domain')->nullable();
            $table->string('staging_url')->nullable();
            $table->string('production_url')->nullable();
            $table->date('started_at')->nullable();
            $table->date('due_at')->nullable();
            $table->date('published_at')->nullable();
            // Quatro estados independentes: nunca um único "status".
            $table->string('development_status', 30)->default('planning')->index();
            $table->string('contract_status', 30)->default('none')->index();
            $table->string('financial_status', 30)->default('up_to_date')->index();
            $table->string('operational_status', 30)->default('active')->index();
            $table->string('plan_name')->nullable();
            $table->unsignedBigInteger('setup_fee_cents')->default(0);
            $table->unsignedBigInteger('monthly_fee_cents')->default(0);
            $table->unsignedTinyInteger('billing_day')->nullable();
            $table->boolean('monitoring_enabled')->default(true);
            // Exceção autorizada: enquanto vigente, nenhuma suspensão é proposta.
            $table->date('suspension_hold_until')->nullable();
            $table->string('suspension_hold_reason')->nullable();
            // Restrição ativa no lado central (bloqueio de novas solicitações / serviços adicionais).
            $table->string('active_restriction', 40)->nullable();
            $table->string('deployed_version', 60)->nullable();
            $table->timestamp('agent_last_seen_at')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('registrar')->nullable();
            // Titular é quem consta no registro — não presumimos que somos nós.
            $table->string('registrant_name')->nullable();
            $table->string('registrant_document', 20)->nullable();
            $table->string('managed_by', 20)->default('client');
            $table->string('payer', 20)->default('client');
            $table->date('registered_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->timestamp('expires_checked_at')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->date('ssl_expires_at')->nullable();
            $table->string('ssl_issuer')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('hosting_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider');
            $table->string('plan')->nullable();
            $table->text('resources')->nullable();
            $table->string('managed_by', 20)->default('us');
            $table->string('payer', 20)->default('client');
            $table->unsignedBigInteger('monthly_cost_cents')->default(0);
            $table->date('renews_at')->nullable();
            $table->text('limits_and_responsibilities')->nullable();
            $table->text('backup_policy')->nullable();
            $table->text('migration_procedure')->nullable();
            $table->timestamp('last_backup_confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosting_services');
        Schema::dropIfExists('domains');
        Schema::dropIfExists('client_projects');
    }
};
