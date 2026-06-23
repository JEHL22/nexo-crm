<x-guest-layout>
<div class="lf-anim lf-fade" style="animation-delay: .15s">
    <h2 class="lf-title">Bienvenido de nuevo</h2>
    <p class="lf-subtitle">Ingresa tus credenciales para acceder al panel.</p>
</div>

<!-- Estado de sesión -->
@if (session('status'))
    <div class="lf-status lf-anim lf-fade" style="animation-delay: .2s">{{ session('status') }}</div>
@endif

<form method="POST" action="{{ route('login') }}" x-data="{ showPassword: false }">
    @csrf

    <!-- Usuario -->
    <div class="lf-field lf-anim lf-fade" style="animation-delay: .25s">
        <label for="email" class="lf-label">Nombre de Usuario</label>
        <div class="lf-input-wrap">
            <svg class="lf-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
            <input id="email" name="email" type="email" value="{{ old('email') }}"
                   class="lf-input" placeholder="usuario@nexo.local"
                   required autofocus autocomplete="username">
        </div>
        <x-input-error :messages="$errors->get('email')" class="lf-error" />
    </div>

    <!-- Contraseña -->
    <div class="lf-field lf-anim lf-fade" style="animation-delay: .33s">
        <label for="password" class="lf-label">Contraseña</label>
        <div class="lf-input-wrap">
            <svg class="lf-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <input id="password" name="password"
                   :type="showPassword ? 'text' : 'password'"
                   class="lf-input lf-input--pw" placeholder="••••••••"
                   required autocomplete="current-password">
            <button type="button" class="lf-toggle"
                    @click="showPassword = !showPassword"
                    :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                <svg x-show="!showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                </svg>
                <svg x-show="showPassword" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
                </svg>
            </button>
        </div>
        <x-input-error :messages="$errors->get('password')" class="lf-error" />
    </div>

    <!-- Recuérdame -->
    <div class="lf-row lf-anim lf-fade" style="animation-delay: .41s">
        <label for="remember_me" class="lf-check">
            <input id="remember_me" type="checkbox" name="remember">
            <span>Recuérdame</span>
        </label>

        @if (Route::has('password.request'))
            <a class="lf-link" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
        @endif
    </div>

    <button type="submit" class="lf-submit lf-anim lf-fade" style="animation-delay: .49s">
        Ingresar
    </button>
</form>
</x-guest-layout>
