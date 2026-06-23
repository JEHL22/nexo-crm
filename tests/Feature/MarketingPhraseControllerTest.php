<?php

namespace Tests\Feature;

use App\Models\MarketingPhrase;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingPhraseControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_mkt_user_can_view_and_store_a_phrase(): void
    {
        // Fecha congelada: scheduled_for ('2026-04-07') debe ser futuro respecto a "ahora"
        Carbon::setTestNow(Carbon::create(2026, 4, 6, 10, 0, 0, config('app.timezone')));

        try {
            $this->seed(RoleSeeder::class);

            $mkt = User::factory()->create();
            $mkt->assignRole('MKT');

            $existingPhrase = MarketingPhrase::create([
                'sender_user_id' => $mkt->id,
                'title' => 'Actual',
                'phrase' => 'Seguimos avanzando.',
                'delivery_mode' => 'immediate',
                'is_active' => true,
                'starts_at' => Carbon::parse('2026-04-06 09:00:00'),
            ]);

            $this->actingAs($mkt)
                ->get(route('mkt.phrases.index'))
                ->assertOk()
                ->assertSee('Frases motivadoras');

            $scheduledFor = '2026-04-07T08:30';

            $this->actingAs($mkt)
                ->post(route('mkt.phrases.store'), [
                    'title' => 'Nuevo impulso',
                    'phrase' => 'Hoy cerramos con enfoque.',
                    'delivery_mode' => 'scheduled',
                    'scheduled_for' => $scheduledFor,
                    'is_active' => 1,
                ])
                ->assertRedirect(route('mkt.phrases.index'))
                ->assertSessionHas('success', 'Frase guardada correctamente.');

            $existingPhrase->refresh();
            $newPhrase = MarketingPhrase::query()->where('title', 'Nuevo impulso')->firstOrFail();

            $this->assertFalse($existingPhrase->is_active);
            $this->assertTrue($newPhrase->is_active);
            $this->assertSame('scheduled', $newPhrase->delivery_mode);
            $this->assertSame(
                Carbon::parse($scheduledFor, config('app.timezone'))->format('Y-m-d H:i:s'),
                $newPhrase->starts_at?->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_mkt_user_can_update_phrase_configuration(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 6, 10, 0, 0, config('app.timezone')));

        try {
            $this->seed(RoleSeeder::class);

            $mkt = User::factory()->create();
            $mkt->assignRole('MKT');

            $otherPhrase = MarketingPhrase::create([
                'sender_user_id' => $mkt->id,
                'title' => 'Otra activa',
                'phrase' => 'Mantente firme.',
                'delivery_mode' => 'immediate',
                'is_active' => true,
                'starts_at' => now()->subHour(),
            ]);

            $phrase = MarketingPhrase::create([
                'sender_user_id' => $mkt->id,
                'title' => 'Pendiente',
                'phrase' => 'Recuerda tu meta.',
                'delivery_mode' => 'scheduled',
                'is_active' => false,
                'starts_at' => now()->addHour(),
            ]);

            $this->actingAs($mkt)
                ->put(route('mkt.phrases.update', $phrase), [
                    'delivery_mode' => 'daily_login',
                    'is_active' => 1,
                ])
                ->assertRedirect(route('mkt.phrases.index'))
                ->assertSessionHas('success', 'Configuración de la frase actualizada.');

            $otherPhrase->refresh();
            $phrase->refresh();

            $this->assertFalse($otherPhrase->is_active);
            $this->assertTrue($phrase->is_active);
            $this->assertSame('daily_login', $phrase->delivery_mode);
            $this->assertSame(
                now()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                $phrase->starts_at?->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_mkt_user_can_toggle_a_phrase_for_immediate_publication(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 6, 11, 15, 0, config('app.timezone')));

        try {
            $this->seed(RoleSeeder::class);

            $mkt = User::factory()->create();
            $mkt->assignRole('MKT');

            $activePhrase = MarketingPhrase::create([
                'sender_user_id' => $mkt->id,
                'title' => 'Activa',
                'phrase' => 'Vamos con todo.',
                'delivery_mode' => 'immediate',
                'is_active' => true,
                'starts_at' => now()->subMinutes(30),
            ]);

            $targetPhrase = MarketingPhrase::create([
                'sender_user_id' => $mkt->id,
                'title' => 'Reactivar',
                'phrase' => 'Hoy sí se puede.',
                'delivery_mode' => 'scheduled',
                'is_active' => false,
                'starts_at' => now()->addDay(),
            ]);

            $this->actingAs($mkt)
                ->post(route('mkt.phrases.toggle', $targetPhrase))
                ->assertRedirect(route('mkt.phrases.index'))
                ->assertSessionHas('success', 'Frase publicada correctamente.');

            $activePhrase->refresh();
            $targetPhrase->refresh();

            $this->assertFalse($activePhrase->is_active);
            $this->assertTrue($targetPhrase->is_active);
            $this->assertSame('immediate', $targetPhrase->delivery_mode);
            $this->assertSame(
                now()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
                $targetPhrase->starts_at?->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
            );
        } finally {
            Carbon::setTestNow();
        }
    }
}
