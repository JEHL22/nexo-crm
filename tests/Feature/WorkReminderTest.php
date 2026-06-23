<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Interaction;
use App\Models\Lead;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_executive_can_store_a_rescheduled_call_with_next_contact_datetime(): void
    {
        $this->seed(RoleSeeder::class);

        $executive = User::factory()->create();
        $executive->assignRole('Ejecutivo');

        $campaign = Campaign::create([
            'name' => 'Campana Seguimiento',
            'type' => 'outbound',
            'status' => 'activa',
        ]);

        $executive->campaigns()->attach($campaign->id);

        $lead = Lead::create([
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => $executive->id,
            'created_by_user_id' => $executive->id,
            'source' => 'empresa',
            'delivery_status' => 'asignado',
            'taken_at' => now(),
            'full_name' => 'Cliente Demo',
            'business_name' => 'Empresa Demo SAC',
            'status_final' => 'sin_gestion',
        ]);

        $scheduledFor = now()->addHour()->startOfMinute();

        $response = $this->actingAs($executive)->post(route('work.store', $lead), [
            'contact_name' => 'Ana Perez',
            'contact_phone' => '987654321',
            'general_status' => 'contactado',
            'specific_status' => 'reprogramado',
            'next_contact_at' => $scheduledFor->format('Y-m-d\TH:i'),
            'notes' => 'Se reagenda la llamada por solicitud del cliente.',
        ]);

        $response->assertRedirect(route('work.show'));

        $this->assertDatabaseHas('interactions', [
            'lead_id' => $lead->id,
            'user_id' => $executive->id,
            'interaction_type' => 'a_negociar',
            'status_general' => 'contactado',
            'status_specific' => 'reprogramado',
            'contact_name' => 'Ana Perez',
            'contact_phone' => '987654321',
        ]);

        $interaction = Interaction::query()->where('lead_id', $lead->id)->firstOrFail();

        $this->assertNotNull($interaction->next_contact_at);
        $this->assertSame(
            $scheduledFor->format('Y-m-d H:i:s'),
            $interaction->next_contact_at->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s')
        );

        $lead->refresh();

        $this->assertSame('reprogramado', $lead->status_specific);
        $this->assertSame('en_seguimiento', $lead->status_final);
        $this->assertSame('gestionado', $lead->delivery_status);
        $this->assertSame(0, $lead->no_contact_attempts);
        $this->assertNull($lead->released_at);
    }

    public function test_executive_cannot_store_removed_contact_statuses(): void
    {
        $this->seed(RoleSeeder::class);

        $executive = User::factory()->create();
        $executive->assignRole('Ejecutivo');

        $campaign = Campaign::create([
            'name' => 'Campana Seguimiento',
            'type' => 'outbound',
            'status' => 'activa',
        ]);

        $executive->campaigns()->attach($campaign->id);

        $lead = Lead::create([
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => $executive->id,
            'created_by_user_id' => $executive->id,
            'source' => 'empresa',
            'delivery_status' => 'asignado',
            'taken_at' => now(),
            'full_name' => 'Cliente Demo',
            'business_name' => 'Empresa Demo SAC',
            'status_final' => 'sin_gestion',
        ]);

        $response = $this->actingAs($executive)->post(route('work.store', $lead), [
            'contact_name' => 'Ana Perez',
            'contact_phone' => '987654321',
            'general_status' => 'contactado',
            'specific_status' => 'interesado',
            'notes' => 'Intento registrar un estado retirado.',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('interactions', [
            'lead_id' => $lead->id,
            'status_specific' => 'interesado',
        ]);
    }

    public function test_no_contact_lead_waits_one_hour_and_is_reassigned_to_a_different_executive(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 31, 9, 0, 0, config('app.timezone')));

        try {
            $this->seed(RoleSeeder::class);

            $firstExecutive = User::factory()->create();
            $firstExecutive->assignRole('Ejecutivo');

            $secondExecutive = User::factory()->create();
            $secondExecutive->assignRole('Ejecutivo');

            $campaign = Campaign::create([
                'name' => 'Campana No Contacto',
                'type' => 'outbound',
                'status' => 'activa',
            ]);

            $firstExecutive->campaigns()->attach($campaign->id);
            $secondExecutive->campaigns()->attach($campaign->id);

            $lead = Lead::create([
                'campaign_id' => $campaign->id,
                'assigned_to_user_id' => $firstExecutive->id,
                'created_by_user_id' => $firstExecutive->id,
                'source' => 'empresa',
                'delivery_status' => 'asignado',
                'taken_at' => now(),
                'full_name' => 'Cliente No Contacto',
                'business_name' => 'Empresa No Contacto SAC',
                'status_final' => 'sin_gestion',
            ]);

            $response = $this->actingAs($firstExecutive)->post(route('work.store', $lead), [
                'contact_name' => 'Ana Perez',
                'contact_phone' => '987654321',
                'general_status' => 'no_contactado',
                'specific_status' => 'no_contesta',
                'notes' => 'No responde la llamada.',
            ]);

            $response->assertRedirect(route('work.show'));

            $lead->refresh();

            $this->assertSame('no_contesta', $lead->status_specific);
            $this->assertSame('sin_gestion', $lead->status_final);
            $this->assertSame('gestionado', $lead->delivery_status);
            $this->assertSame(1, $lead->no_contact_attempts);
            $this->assertSame($firstExecutive->id, $lead->assigned_to_user_id);
            $this->assertNotNull($lead->released_at);

            Carbon::setTestNow(now()->addMinutes(11));

            $response = $this->actingAs($firstExecutive)->get(route('work.show'));
            $response->assertOk();
            $response->assertViewHas('lead', fn ($viewLead) => $viewLead === null);

            $lead->refresh();

            $this->assertNull($lead->assigned_to_user_id);
            $this->assertSame('disponible', $lead->delivery_status);
            $this->assertNull($lead->released_at);

            $response = $this->actingAs($secondExecutive)->get(route('work.show'));
            $response->assertOk();
            $response->assertViewHas('lead', fn ($viewLead) => $viewLead?->id === $lead->id);

            $lead->refresh();

            $this->assertSame($secondExecutive->id, $lead->assigned_to_user_id);
            $this->assertSame('asignado', $lead->delivery_status);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_lead_is_disabled_after_three_distinct_executives_report_no_contact(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 31, 10, 0, 0, config('app.timezone')));

        try {
            $this->seed(RoleSeeder::class);

            $firstExecutive = User::factory()->create();
            $firstExecutive->assignRole('Ejecutivo');

            $secondExecutive = User::factory()->create();
            $secondExecutive->assignRole('Ejecutivo');

            $thirdExecutive = User::factory()->create();
            $thirdExecutive->assignRole('Ejecutivo');

            $campaign = Campaign::create([
                'name' => 'Campana Tres Intentos',
                'type' => 'outbound',
                'status' => 'activa',
            ]);

            collect([$firstExecutive, $secondExecutive, $thirdExecutive])
                ->each(fn (User $executive) => $executive->campaigns()->attach($campaign->id));

            $lead = Lead::create([
                'campaign_id' => $campaign->id,
                'assigned_to_user_id' => $firstExecutive->id,
                'created_by_user_id' => $firstExecutive->id,
                'source' => 'empresa',
                'delivery_status' => 'asignado',
                'taken_at' => now(),
                'full_name' => 'Cliente Tres Intentos',
                'business_name' => 'Empresa Tres Intentos SAC',
                'status_final' => 'sin_gestion',
            ]);

            $this->actingAs($firstExecutive)->post(route('work.store', $lead), [
                'contact_name' => 'Ana Perez',
                'contact_phone' => '987654321',
                'general_status' => 'no_contactado',
                'specific_status' => 'no_contesta',
                'notes' => 'Primer intento sin respuesta.',
            ])->assertRedirect(route('work.show'));

            Carbon::setTestNow(now()->addMinutes(11));

            $this->actingAs($secondExecutive)->get(route('work.show'))
                ->assertOk()
                ->assertViewHas('lead', fn ($viewLead) => $viewLead?->id === $lead->id);

            $this->actingAs($secondExecutive)->post(route('work.store', $lead), [
                'contact_name' => 'Ana Perez',
                'contact_phone' => '987654321',
                'general_status' => 'no_contactado',
                'specific_status' => 'telefono_apagado',
                'notes' => 'Segundo intento con teléfono apagado.',
            ])->assertRedirect(route('work.show'));

            Carbon::setTestNow(now()->addMinutes(11));

            $this->actingAs($thirdExecutive)->get(route('work.show'))
                ->assertOk()
                ->assertViewHas('lead', fn ($viewLead) => $viewLead?->id === $lead->id);

            $this->actingAs($thirdExecutive)->post(route('work.store', $lead), [
                'contact_name' => 'Ana Perez',
                'contact_phone' => '987654321',
                'general_status' => 'no_contactado',
                'specific_status' => 'no_contesta',
                'notes' => 'Tercer intento sin respuesta.',
            ])->assertRedirect(route('work.show'));

            $lead->refresh();

            $this->assertSame('no_contesta', $lead->status_specific);
            $this->assertSame('sin_gestion', $lead->status_final);
            $this->assertSame(3, $lead->no_contact_attempts);
            $this->assertSame('gestionado', $lead->delivery_status);
            $this->assertNull($lead->assigned_to_user_id);
            $this->assertNull($lead->released_at);
            $this->assertNotNull($lead->disabled_at);
            $this->assertSame('sin_contacto_3_ejecutivos', $lead->disabled_reason);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_work_show_provides_daily_and_weekly_gauges_for_current_week(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 25, 10, 0, 0, config('app.timezone')));

        try {
            $this->seed(RoleSeeder::class);

            $executive = User::factory()->create();
            $executive->assignRole('Ejecutivo');

            $otherExecutive = User::factory()->create();
            $otherExecutive->assignRole('Ejecutivo');

            $campaign = Campaign::create([
                'name' => 'Campana Metricas',
                'type' => 'outbound',
                'status' => 'activa',
            ]);

            $executive->campaigns()->attach($campaign->id);

            $lead = Lead::create([
                'campaign_id' => $campaign->id,
                'assigned_to_user_id' => $executive->id,
                'created_by_user_id' => $executive->id,
                'source' => 'empresa',
                'delivery_status' => 'asignado',
                'taken_at' => now(),
                'full_name' => 'Cliente Metricas',
                'business_name' => 'Empresa Metricas SAC',
                'status_final' => 'sin_gestion',
            ]);

            $registerInteraction = function (
                User $user,
                string $specificStatus,
                string $createdAt,
                string $interactionType = 'a_negociar'
            ) use ($campaign, $lead): Interaction {
                $interaction = Interaction::create([
                    'lead_id' => $lead->id,
                    'user_id' => $user->id,
                    'campaign_id' => $campaign->id,
                    'status' => $specificStatus,
                    'interaction_type' => $interactionType,
                    'status_general' => 'contactado',
                    'status_specific' => $specificStatus,
                    'call_detail' => 'Seguimiento de prueba.',
                    'contact_name' => 'Ana Perez',
                    'contact_phone' => '987654321',
                ]);

                $interaction->forceFill([
                    'created_at' => Carbon::parse($createdAt, config('app.timezone')),
                    'updated_at' => Carbon::parse($createdAt, config('app.timezone')),
                ])->saveQuietly();

                return $interaction;
            };

            $registerInteraction($executive, 'reprogramado', '2026-03-25 08:00:00');
            $agreementSourceInteraction = $registerInteraction($executive, 'negociacion', '2026-03-25 09:00:00');
            $registerInteraction($executive, 'reprogramado', '2026-03-24 11:00:00');
            $registerInteraction($executive, 'negociacion', '2026-03-23 12:00:00');
            $registerInteraction($executive, 'reprogramado', '2026-03-22 18:00:00');
            $registerInteraction($executive, 'reprogramado', '2026-03-25 07:30:00', 'edicion_mi_chamba');
            $registerInteraction($otherExecutive, 'negociacion', '2026-03-25 08:30:00');

            Sale::create([
                'lead_id' => $lead->id,
                'interaction_id' => $agreementSourceInteraction->id,
                'campaign_id' => $campaign->id,
                'executive_user_id' => $executive->id,
                'status' => 'acuerdo_aceptado',
                'management_status' => 'pendiente_validacion',
                'sisac_status' => 'pendiente_validacion',
                'product_type' => 'movil',
                'offered_line_count' => 2,
                'monthly_payment' => 109.90,
                'accepted_at' => now()->subHour(),
            ]);

            $response = $this->actingAs($executive)->get(route('work.show'));

            $response->assertOk();
            $response->assertSee('Reprogramados semanal');
            $response->assertSee('Negociación semanal');
            $response->assertViewHas('salesGauge', fn (array $gauge) => $gauge['value'] === 1 && $gauge['goal'] === 1);
            $response->assertViewHas('dailyStatusGauges', function (array $gauges) {
                $gaugesByKey = collect($gauges)->keyBy('key');

                return $gaugesByKey->get('reprogramado')['value'] === 1
                    && $gaugesByKey->get('reprogramado')['goal'] === 20
                    && $gaugesByKey->get('negociacion')['value'] === 1
                    && $gaugesByKey->get('negociacion')['goal'] === 2;
            });
            $response->assertViewHas('weeklyStatusGauges', function (array $gauges) {
                $gaugesByKey = collect($gauges)->keyBy('key');

                return $gaugesByKey->get('reprogramado_semanal')['value'] === 2
                    && $gaugesByKey->get('reprogramado_semanal')['goal'] === 100
                    && $gaugesByKey->get('negociacion_semanal')['value'] === 2
                    && $gaugesByKey->get('negociacion_semanal')['goal'] === 10;
            });
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_reminder_pulse_includes_upcoming_rescheduled_calls(): void
    {
        $this->seed(RoleSeeder::class);

        $executive = User::factory()->create();
        $executive->assignRole('Ejecutivo');

        $campaign = Campaign::create([
            'name' => 'Campana Norte',
            'type' => 'outbound',
            'status' => 'activa',
        ]);

        $executive->campaigns()->attach($campaign->id);

        $lead = Lead::create([
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => $executive->id,
            'created_by_user_id' => $executive->id,
            'source' => 'empresa',
            'delivery_status' => 'asignado',
            'taken_at' => now(),
            'business_name' => 'Cliente Recordatorio',
            'status_specific' => 'reprogramado',
            'status_final' => 'en_seguimiento',
        ]);

        $interaction = Interaction::create([
            'lead_id' => $lead->id,
            'user_id' => $executive->id,
            'campaign_id' => $campaign->id,
            'status' => 'reprogramado',
            'interaction_type' => 'a_negociar',
            'status_general' => 'contactado',
            'status_specific' => 'reprogramado',
            'call_detail' => 'Volver a llamar al cierre de la tarde.',
            'next_contact_at' => now()->addMinutes(4),
            'contact_name' => 'Mario Ruiz',
            'contact_phone' => '912345678',
        ]);

        $response = $this->actingAs($executive)->get(route('work.reminder-notifications.pulse'));

        $response->assertOk();
        $response->assertJsonPath('scheduled_reminders.0.interaction_id', $interaction->id);
        $response->assertJsonPath('scheduled_reminders.0.lead_label', 'Cliente Recordatorio');
    }
}
