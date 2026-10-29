<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);
            $table->string('name');
            $table->string('key_id', 40)->unique();
            // Criptografado (APP_KEY): o servidor precisa do segredo para validar o HMAC.
            $table->text('secret');
            $table->foreignId('client_project_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('abilities');
            $table->foreignId('rotated_from_id')->nullable()->constrained('api_credentials')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('remote_commands', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('client_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('charge_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reactivates_id')->nullable()->constrained('remote_commands')->nullOnDelete();
            $table->string('type', 20);
            $table->string('status', 30)->index();
            // agent = entregue ao agente; central = aplicado aqui; operator = executado manualmente pela equipe.
            $table->string('channel', 20)->default('agent');
            $table->string('idempotency_key', 100);
            $table->string('request_hash', 64);
            $table->string('restriction_mode', 40)->nullable();
            $table->string('reason');
            $table->text('message_to_users')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('guard_report')->nullable();
            // Quem ou qual regra autorizou.
            $table->string('triggered_by_type', 20);
            $table->unsignedBigInteger('triggered_by_id')->nullable();
            $table->string('triggered_by_label');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->json('result')->nullable();
            $table->unsignedSmallInteger('delivery_attempts')->default(0);
            $table->timestamps();

            $table->unique(['client_project_id', 'idempotency_key']);
        });

        Schema::create('health_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_project_id')->constrained()->cascadeOnDelete();
            $table->string('source', 20);
            $table->boolean('is_up');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();
            $table->string('deployed_version', 60)->nullable();
            $table->timestamp('last_backup_at')->nullable();
            $table->unsignedInteger('app_errors_last_hour')->nullable();
            // Somente chaves permitidas (sem segredos/dados pessoais).
            $table->json('metrics')->nullable();
            $table->string('error')->nullable();
            $table->timestamp('checked_at')->index();
        });

        Schema::create('monitoring_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_project_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30);
            $table->string('summary');
            $table->timestamp('opened_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->text('description');
            $table->string('priority', 20)->default('normal');
            $table->string('status', 20)->default('open')->index();
            // Prazo de resposta ≠ prazo de solução.
            $table->timestamp('response_due_at')->nullable();
            $table->timestamp('resolution_due_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->string('label');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('sha256', 64)->nullable();
            $table->boolean('visible_to_client')->default(false);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('monitoring_incidents');
        Schema::dropIfExists('health_checks');
        Schema::dropIfExists('remote_commands');
        Schema::dropIfExists('api_credentials');
    }
};
