<?php

namespace App\Http\Controllers\Portal;

use App\Enums\AuditAction;
use App\Enums\StaffRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('portal.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);

        if (! Auth::attempt($credentials + ['is_admin' => false, 'role' => StaffRole::Client->value])) {
            return back()->withErrors(['email' => 'E-mail ou senha inválidos.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();

        if (! $user->isPortalUser()) {
            Auth::logout();

            return back()->withErrors(['email' => 'Acesso não liberado. Fale com o responsável pelo contrato.'])->onlyInput('email');
        }

        $user->forceFill(['last_login_at' => now()])->save();
        AuditLog::record(AuditAction::Access, 'Portal', $user->id, $user->email, 'Login no portal', ['client_id' => $user->client_id]);

        return redirect()->intended(route('portal.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }

    public function showPassword(Request $request, string $token): View
    {
        return view('portal.auth.password', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    /**
     * Definição de senha pelo próprio cliente (link de convite = token de redefinição do Laravel).
     */
    public function storePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->mixedCase()->numbers()],
        ], [], ['password' => 'senha']);

        $status = Password::broker()->reset($data, function (User $user, string $password) {
            abort_unless($user->isPortalUser(), 403);
            $user->forceFill(['password' => $password])->save();
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('portal.login')->with('status', 'Senha definida. Faça login.')
            : back()->withErrors(['email' => 'Link inválido ou expirado. Solicite um novo convite.']);
    }
}
