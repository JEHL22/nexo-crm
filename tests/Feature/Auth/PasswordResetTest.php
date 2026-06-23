<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El flujo de "olvidé mi contraseña" está deshabilitado a propósito
 * (no hay infraestructura de email; las contraseñas las restablece el
 * Administrador). Las rutas de forgot-password están comentadas en
 * routes/auth.php. Estos tests garantizan que nadie las reactive
 * por accidente.
 */
class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_is_disabled(): void
    {
        $this->get('/forgot-password')->assertNotFound();
    }

    public function test_forgot_password_submission_is_disabled(): void
    {
        $this->post('/forgot-password', ['email' => 'user@example.com'])
            ->assertNotFound();
    }
}
