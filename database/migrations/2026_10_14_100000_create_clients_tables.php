<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('person_type', 2)->default('pj');
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('document', 20)->nullable()->unique();
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('address_street')->nullable();
            $table->string('address_number', 20)->nullable();
            $table->string('address_complement')->nullable();
            $table->string('address_district')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_state', 2)->nullable();
            $table->string('address_zip', 10)->nullable();
            $table->string('billing_email')->nullable();
            $table->text('billing_notes')->nullable();
            $table->date('relationship_started_at')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->text('internal_notes')->nullable();
            $table->timestamp('anonymized_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('client_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('role', 20)->default('other');
            $table->string('position')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('document', 20)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('admin')->after('is_admin');
            $table->foreignId('client_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->timestamp('last_login_at')->nullable()->after('client_id');
        });

        // Usuários existentes: equipe vira `admin`; os demais (sem acesso ao painel) ficam sem papel de equipe.
        DB::table('users')->where('is_admin', false)->update(['role' => 'cliente']);

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('actor_type', 20)->default('user')->after('user_name');
            $table->unsignedBigInteger('client_id')->nullable()->after('resource_label')->index();
            $table->unsignedBigInteger('client_project_id')->nullable()->after('client_id')->index();
            $table->json('context')->nullable()->after('details');
            $table->boolean('visible_to_client')->default(false)->after('context');
            $table->string('ip_address', 45)->nullable()->after('visible_to_client');
            $table->string('user_agent')->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['client_id']);
            $table->dropIndex(['client_project_id']);
            $table->dropColumn(['actor_type', 'client_id', 'client_project_id', 'context', 'visible_to_client', 'ip_address', 'user_agent']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn(['role', 'last_login_at']);
        });

        Schema::dropIfExists('client_contacts');
        Schema::dropIfExists('clients');
    }
};
