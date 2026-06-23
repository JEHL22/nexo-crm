<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ExecutiveActivitySessionController;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $request->session()->put('crm_authenticated_at', now()->toIso8601String());

        $user = $request->user();

        if ($user->hasRole('Ejecutivo')) {
            ExecutiveActivitySessionController::closeOpenSessionsForExecutive($user->id, 'Nueva sesión iniciada');
        }

        if ($user->hasRole('Ejecutivo')) {
            return redirect()->intended(route('work.show'));
        }

        if ($user->hasRole('Postventa')) {
            return redirect()->intended(route('post-sale.index'));
        }

        if ($user->hasRole('Supervisor')) {
            return redirect()->intended(route('supervisor.agreements.index'));
        }

        if ($user->hasRole('Mesa de Control')) {
            return redirect()->intended(route('validation.index'));
        }

        if ($user->hasRole('Gerencia')) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        if ($user->hasRole('RRHH')) {
            return redirect()->intended(route('rrhh.surveys.index'));
        }

        if ($user->hasRole('MKT')) {
            return redirect()->intended(route('mkt.phrases.index'));
        }

        if ($user->hasRole('administrador de promociones')) {
            return redirect()->intended(route('promotion-admin.index'));
        }

        if (
            $user->hasRole('Administrador') &&
            in_array($user->email, Config::get('admin.bootstrap_admin_emails', []), true)
        ) {
            return redirect()->intended(route('admin.home'));
        }

        return redirect()->intended('/');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user && $user->hasRole('Ejecutivo')) {
            ExecutiveActivitySessionController::closeOpenSessionsForExecutive($user->id, 'Cierre de sesión CRM');
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
