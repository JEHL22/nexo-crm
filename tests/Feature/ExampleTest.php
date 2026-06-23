<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * La raíz del CRM exige autenticación: un visitante anónimo
     * siempre es redirigido al login.
     */
    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
