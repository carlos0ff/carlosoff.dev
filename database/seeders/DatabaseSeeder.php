<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Não cria usuários: o primeiro admin é criado com `php artisan admin:create`.
     */
    public function run(): void
    {
        $this->call([
            ProjectSeeder::class,
            PortfolioContentSeeder::class,
            ContractTemplateSeeder::class,
        ]);
    }
}
