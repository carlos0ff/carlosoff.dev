<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_project_id')->constrained()->restrictOnDelete();
            $table->string('status', 30)->default('draft')->index();
            $table->string('object');
            $table->text('scope')->nullable();
            $table->text('deliverables')->nullable();
            $table->unsignedBigInteger('setup_fee_cents')->default(0);
            $table->unsignedBigInteger('monthly_fee_cents')->default(0);
            $table->string('payment_method')->nullable();
            $table->text('payment_terms')->nullable();
            $table->unsignedTinyInteger('billing_day')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->text('renewal_rules')->nullable();
            // Cláusulas textuais (obrigações, suporte, garantia, rescisão, PI, LGPD...).
            $table->json('clauses')->nullable();
            // SLA por prioridade: {"urgent": {"response_hours": 4, "resolution_hours": 24}, ...}
            $table->json('support_sla')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('contract_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('snapshot');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('change_summary')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['contract_id', 'version']);
        });

        Schema::create('billing_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('reminder_days_before')->default(3);
            $table->unsignedSmallInteger('overdue_notice_interval_days')->default(3);
            $table->unsignedSmallInteger('grace_days')->default(10);
            $table->unsignedSmallInteger('pre_suspension_notice_days')->default(5);
            // Por padrão o contrato NÃO prevê suspensão: precisa ser habilitado conscientemente.
            $table->boolean('suspension_allowed')->default(false);
            $table->string('restriction_mode', 40)->default('none');
            $table->boolean('suspension_requires_approval')->default(true);
            $table->string('reactivation_mode', 20)->default('automatic');
            $table->string('contract_clause_reference')->nullable();
            $table->text('regularization_instructions')->nullable();
            $table->timestamps();
        });

        Schema::create('contract_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('document_type', 40)->index();
            $table->longText('body');
            $table->unsignedInteger('version')->default(1);
            // Só modelos revisados juridicamente podem gerar documentos para assinatura.
            $table->timestamp('legal_reviewed_at')->nullable();
            $table->foreignId('legal_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('legal_review_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_templates');
        Schema::dropIfExists('billing_policies');
        Schema::dropIfExists('contract_versions');
        Schema::dropIfExists('contracts');
    }
};
