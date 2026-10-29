<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_project_id')->constrained()->restrictOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('scope_request_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 20)->index();
            $table->string('description');
            // Competência (YYYY-MM) evita gerar a mesma recorrência duas vezes.
            $table->string('competence', 7)->nullable();
            $table->string('recurrence_key')->nullable()->unique();
            $table->unsignedBigInteger('amount_cents');
            $table->unsignedBigInteger('paid_cents')->default(0);
            $table->date('due_date')->index();
            $table->string('status', 20)->default('pending')->index();
            $table->string('gateway', 30)->default('manual');
            $table->string('gateway_charge_id')->nullable()->index();
            $table->string('payment_url')->nullable();
            // Contestação e negociação bloqueiam qualquer suspensão.
            $table->timestamp('disputed_at')->nullable();
            $table->text('dispute_reason')->nullable();
            $table->date('negotiation_until')->nullable();
            $table->text('negotiation_notes')->nullable();
            $table->date('suspension_eligible_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charge_id')->constrained()->restrictOnDelete();
            // Valor positivo = recebimento; negativo = estorno.
            $table->bigInteger('amount_cents');
            $table->timestamp('paid_at');
            $table->string('method', 30)->nullable();
            $table->string('source', 20);
            $table->string('gateway_payment_id')->nullable()->unique();
            $table->string('evidence_path')->nullable();
            $table->text('evidence_notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('charge_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charge_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 30);
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->string('channel', 20)->default('mail');
            $table->string('recipient')->nullable();
            $table->timestamp('sent_at');

            $table->unique(['charge_id', 'stage', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charge_notices');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('charges');
    }
};
