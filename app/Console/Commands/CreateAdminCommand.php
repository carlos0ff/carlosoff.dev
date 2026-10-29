<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('admin:create')]
#[Description('Cria um usuário administrador do painel (a senha é digitada de forma oculta)')]
class CreateAdminCommand extends Command
{
    public function handle(): int
    {
        $name = text('Nome', required: true);
        $email = text('E-mail', required: true, validate: fn (string $value) => $this->validateEmail($value));
        $plainPassword = password('Senha (mínimo 8 caracteres)', required: true, validate: fn (string $value) => strlen($value) < 8 ? 'A senha precisa ter pelo menos 8 caracteres.' : null);

        if ($plainPassword !== password('Confirme a senha', required: true)) {
            $this->components->error('As senhas não conferem. Nenhum usuário foi criado.');

            return self::FAILURE;
        }

        $admin = new User([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($plainPassword),
        ]);
        $admin->is_admin = true;
        $admin->save();

        $this->components->info("Administrador {$email} criado. Acesse /admin/login.");

        return self::SUCCESS;
    }

    private function validateEmail(string $value): ?string
    {
        $validator = Validator::make(['email' => $value], ['email' => ['email', 'unique:users,email']], [
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Já existe um usuário com este e-mail.',
        ]);

        return $validator->fails() ? $validator->errors()->first('email') : null;
    }
}
