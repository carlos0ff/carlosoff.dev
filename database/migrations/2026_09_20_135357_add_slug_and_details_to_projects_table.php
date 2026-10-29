<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('title');
            $table->json('details')->nullable()->after('technologies');
        });

        // Projetos que já existiam ganham um slug estável (a partir do título).
        $taken = [];

        DB::table('projects')->orderBy('id')->get(['id', 'title'])->each(function (object $project) use (&$taken): void {
            $base = Str::slug($project->title) ?: 'projeto-'.$project->id;
            $slug = $base;

            for ($suffix = 2; in_array($slug, $taken, true); $suffix++) {
                $slug = $base.'-'.$suffix;
            }

            $taken[] = $slug;

            DB::table('projects')->where('id', $project->id)->update(['slug' => $slug]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'details']);
        });
    }
};
