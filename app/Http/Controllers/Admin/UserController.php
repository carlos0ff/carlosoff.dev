<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', ['users' => User::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], ['name' => 'nome', 'email' => 'e-mail', 'password' => 'senha']);

        $user = new User($validated);
        $user->is_admin = true;
        $user->save();

        AuditLog::record(AuditAction::Create, 'Usuário', $user->id, $user->email);

        return redirect()->route('admin.users.index')->with('status', 'Administrador criado com sucesso.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Você não pode excluir o próprio usuário.']);
        }

        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return back()->withErrors(['user' => 'Não é possível excluir o último administrador.']);
        }

        $user->delete();

        AuditLog::record(AuditAction::Delete, 'Usuário', $user->id, $user->email);

        return redirect()->route('admin.users.index')->with('status', 'Usuário excluído.');
    }
}
