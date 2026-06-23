<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El perfil del CRM solo permite personalizar colores, tema y foto.
 * Nombre/email los gestiona el Administrador, y la eliminación de
 * cuenta propia está deshabilitada a propósito (ver ProfileController
 * y ProfileUpdateRequest).
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_customization_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'crm_primary_color' => '#1e293b',
                'crm_secondary_color' => '#0ea5e9',
                'crm_theme_mode' => 'dark',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('#1e293b', $user->crm_primary_color);
        $this->assertSame('#0ea5e9', $user->crm_secondary_color);
        $this->assertSame('dark', $user->crm_theme_mode);
    }

    public function test_theme_mode_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'crm_primary_color' => '#1e293b',
            ]);

        $response
            ->assertSessionHasErrors('crm_theme_mode')
            ->assertRedirect('/profile');
    }

    public function test_profile_name_and_email_cannot_be_changed_by_the_user(): void
    {
        $user = User::factory()->create();
        $originalName = $user->name;
        $originalEmail = $user->email;

        $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Otro Nombre',
                'email' => 'otro@example.com',
                'crm_theme_mode' => 'light',
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame($originalName, $user->name);
        $this->assertSame($originalEmail, $user->email);
    }

    public function test_account_deletion_is_disabled(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertStatus(405);

        $this->assertNotNull($user->fresh());
    }
}
