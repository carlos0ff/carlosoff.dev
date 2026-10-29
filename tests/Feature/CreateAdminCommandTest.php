<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_admin_with_a_hashed_password(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Nome', 'Admin Teste')
            ->expectsQuestion('E-mail', 'admin@example.com')
            ->expectsQuestion('Senha (mínimo 8 caracteres)', 'uma-senha-forte-123')
            ->expectsQuestion('Confirme a senha', 'uma-senha-forte-123')
            ->assertSuccessful();

        $user = User::where('email', 'admin@example.com')->firstOrFail();
        $this->assertSame('Admin Teste', $user->name);
        $this->assertTrue($user->is_admin);
        $this->assertNotSame('uma-senha-forte-123', $user->password);
        $this->assertTrue(Hash::check('uma-senha-forte-123', $user->password));
    }

    public function test_it_does_not_create_a_user_when_the_passwords_do_not_match(): void
    {
        $this->artisan('admin:create')
            ->expectsQuestion('Nome', 'Admin Teste')
            ->expectsQuestion('E-mail', 'admin@example.com')
            ->expectsQuestion('Senha (mínimo 8 caracteres)', 'uma-senha-forte-123')
            ->expectsQuestion('Confirme a senha', 'outra-senha-diferente')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }
}
