<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBootstrapAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'No autenticado.');
        }

        $allowedEmails = config('admin.bootstrap_admin_emails', []);

        if (!in_array($user->email, $allowedEmails, true)) {
            abort(403, 'No tienes acceso al panel administrador.');
        }

        return $next($request);
    }
}