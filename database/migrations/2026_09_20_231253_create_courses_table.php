<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('institution');
            $table->string('type')->default('course');
            $table->text('description')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('completed_at')->nullable();
            $table->unsignedSmallInteger('workload_hours')->nullable();
            $table->string('certificate_url')->nullable();
            $table->string('credential')->nullable();
            $table->string('image')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
