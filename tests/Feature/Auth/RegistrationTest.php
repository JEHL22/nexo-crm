<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El registro público está deshabilitado a propósito: este CRM es interno
 * y los usuarios los crea el Administrador. Las rutas de register están
 * comentadas en routes/auth.php. Estos tests garantizan que nadie las
 * reactive por accidente.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_registration_submission_is_disabled(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();

        $this->assertGuest();
    }
}
