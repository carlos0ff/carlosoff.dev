<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_project_id')->constrained()->cascadeOnDelete();
            $table->string('version', 60);
            $table->string('environment', 30)->default('staging');
            $table->string('environment_url')->nullable();
            $table->text('features_evaluated');
            $table->text('tests_performed')->nullable();
            $table->text('known_issues')->nullable();
            $table->text('evidence')->nullable();
            $table->string('outcome', 30)->default('pending')->index();
            $table->text('client_notes')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->string('response_ip', 45)->nullable();
            $table->string('response_user_agent')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('scope_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->text('reason')->nullable();
            $table->text('affected_features')->nullable();
            $table->text('impact_analysis')->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->unsignedBigInteger('amount_cents')->nullable();
            $table->unsignedSmallInteger('estimated_days')->nullable();
            $table->text('dependencies')->nullable();
            $table->text('acceptance_criteria')->nullable();
            $table->string('status', 30)->default('requested')->index();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('quoted_at')->nullable();
            // Consentimento do cliente: quem, quando, de onde e sobre qual texto exato.
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_ip', 45)->nullable();
            $table->string('decision_user_agent')->nullable();
            $table->string('decision_terms_hash', 64)->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_project_id')->constrained()->restrictOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('scope_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_validation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contract_template_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('template_version')->nullable();
            $table->string('type', 40)->index();
            $table->string('title');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('supersedes_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->longText('body_html');
            $table->string('status', 30)->default('draft')->index();
            $table->boolean('visible_to_client')->default(true);
            // Versão imutável enviada para assinatura.
            $table->string('file_path')->nullable();
            $table->string('file_sha256', 64)->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            // Provedor de assinatura.
            $table->string('provider', 30)->nullable();
            $table->string('provider_envelope_id')->nullable()->index();
            $table->string('provider_document_id')->nullable()->index();
            $table->string('provider_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('signed_file_path')->nullable();
            $table->string('signed_file_sha256', 64)->nullable();
            $table->string('audit_trail_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('document_signers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('document_number', 20)->nullable();
            $table->string('role', 20)->default('client');
            $table->string('auth_method', 20)->default('email');
            $table->unsignedSmallInteger('sign_order')->default(1);
            $table->string('provider_signer_id')->nullable()->index();
            $table->string('sign_url')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('signed_at')->nullable();
            $table->json('evidence')->nullable();
            $table->timestamps();
        });

        Schema::create('signature_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 30);
            $table->string('event', 60);
            $table->string('payload_sha256', 64)->unique();
            $table->json('payload');
            $table->boolean('signature_valid');
            $table->timestamp('occurred_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signature_events');
        Schema::dropIfExists('document_signers');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('scope_requests');
        Schema::dropIfExists('project_validations');
    }
};
