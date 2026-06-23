<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Interaction;
use App\Models\Lead;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationPulseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_validation_pulse_returns_html_and_respects_filters(): void
    {
        $mesaControl = User::factory()->create();
        $mesaControl->assignRole('Mesa de Control');

        $matchingSale = $this->createSale([
            'customer_ruc' => '20111111111',
            'customer_business_name' => 'Cliente Uno SAC',
            'sisac_status' => 'en_evaluacion',
            'supervisor_validation_status' => 'validado',
        ]);

        $nonMatchingSale = $this->createSale([
            'customer_ruc' => '20999999999',
            'customer_business_name' => 'Cliente Dos SAC',
            'sisac_status' => 'activo',
            'supervisor_validation_status' => 'validado',
        ]);

        $response = $this->actingAs($mesaControl)->get(route('validation.pulse', [
            'sisac_status' => 'en_evaluacion',
            'ruc' => '201111',
        ]));

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonStructure([
            'ok',
            'updated_at_label',
            'list_html',
        ]);

        $listHtml = (string) $response->json('list_html');

        $this->assertStringContainsString('Cliente Uno SAC', $listHtml);
        $this->assertStringContainsString('20111111111', $listHtml);
        $this->assertStringNotContainsString('Cliente Dos SAC', $listHtml);
        $this->assertStringNotContainsString((string) $nonMatchingSale->customer_ruc, $listHtml);
        $this->assertStringContainsString((string) $matchingSale->customer_ruc, $listHtml);
    }

    public function test_validation_pulse_is_forbidden_for_non_mesa_de_control_users(): void
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');

        $this->actingAs($supervisor)
            ->get(route('validation.pulse'))
            ->assertForbidden();
    }

    public function test_validation_pulse_reflects_supervisor_handoff_without_full_reload(): void
    {
        $mesaControl = User::factory()->create();
        $mesaControl->assignRole('Mesa de Control');

        $sale = $this->createSale([
            'customer_ruc' => '20444444444',
            'customer_business_name' => 'Cliente Handoff SAC',
            'management_status' => 'pendiente_supervision',
            'sisac_status' => 'pendiente_supervision',
            'supervisor_validation_status' => 'pendiente',
        ]);

        $initialPulse = $this->actingAs($mesaControl)->get(route('validation.pulse'));
        $initialPulse->assertOk();
        $this->assertStringNotContainsString('Cliente Handoff SAC', (string) $initialPulse->json('list_html'));

        $this->actingAs($sale->supervisor)
            ->post(route('supervisor.agreements.validate', $sale))
            ->assertRedirect(route('supervisor.agreements.index'));

        $sale->refresh();

        $this->assertSame('validado', $sale->supervisor_validation_status);
        $this->assertSame('pendiente_validacion', $sale->management_status);
        $this->assertSame('en_evaluacion', $sale->sisac_status);

        $refreshedPulse = $this->actingAs($mesaControl)->get(route('validation.pulse'));
        $refreshedPulse->assertOk();
        $this->assertStringContainsString('Cliente Handoff SAC', (string) $refreshedPulse->json('list_html'));
        $this->assertStringContainsString('20444444444', (string) $refreshedPulse->json('list_html'));
    }

    private function createSale(array $saleOverrides = []): Sale
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');

        $executive = User::factory()->create();
        $executive->assignRole('Ejecutivo');

        $campaign = Campaign::create([
            'name' => 'Campana Validacion '.uniqid(),
            'active' => true,
        ]);

        $lead = Lead::create([
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => $executive->id,
            'created_by_user_id' => $executive->id,
            'supervisor_user_id' => $supervisor->id,
            'source' => 'empresa',
            'delivery_status' => 'gestionado',
            'taken_at' => now(),
            'full_name' => 'Cliente Validacion',
            'business_name' => $saleOverrides['customer_business_name'] ?? 'Cliente Validacion SAC',
            'representative_name' => 'Rosa Perez',
            'dni' => '12345678',
            'ruc' => $saleOverrides['customer_ruc'] ?? '20123456789',
            'current_operator' => 'Entel',
            'status_general' => 'contactado',
            'status_specific' => 'acuerdo_aceptado',
            'status_final' => 'acuerdo_aceptado',
            'call_summary' => 'Acuerdo listo para validar.',
            'last_contact_name' => 'Rosa Perez',
            'last_contact_phone' => '987654321',
        ]);

        $interaction = Interaction::create([
            'lead_id' => $lead->id,
            'user_id' => $executive->id,
            'campaign_id' => $campaign->id,
            'status' => 'negociacion',
            'interaction_type' => 'acuerdo_aceptado',
            'status_general' => 'contactado',
            'status_specific' => 'acuerdo_aceptado',
            'call_detail' => 'Acuerdo listo para validacion.',
            'contact_name' => 'Rosa Perez',
            'contact_phone' => '987654321',
            'is_agreement' => true,
            'agreed_at' => now(),
        ]);

        return Sale::create(array_merge([
            'lead_id' => $lead->id,
            'interaction_id' => $interaction->id,
            'campaign_id' => $campaign->id,
            'executive_user_id' => $executive->id,
            'supervisor_user_id' => $supervisor->id,
            'status' => 'acuerdo_aceptado',
            'management_status' => 'pendiente_validacion',
            'sisac_status' => 'en_evaluacion',
            'product_type' => 'fijo',
            'offered_line_count' => 0,
            'monthly_payment' => 79.90,
            'customer_ruc' => '20123456789',
            'customer_business_name' => 'Cliente Validacion SAC',
            'customer_dni' => '12345678',
            'customer_representative_name' => 'Rosa Perez',
            'customer_phone' => '987654321',
            'customer_address' => 'Av. Principal 123',
            'customer_coordinates' => '-12.0464,-77.0428',
            'plan_code' => 'PLN-VAL-001',
            'approval_code' => null,
            'customer_email' => 'cliente@example.com',
            'service_channel' => null,
            'attention_time_slot' => null,
            'attention_date' => null,
            'operator_name' => null,
            'delivery_type' => null,
            'fixed_agreement_supports' => ['contrato_fijo'],
            'products_snapshot' => [
                [
                    'type' => 'fijo',
                    'label' => 'Fijo',
                    'detail' => 'Internet 200 Mbps',
                    'line_count' => 0,
                    'price' => 79.90,
                    'line_total' => 79.90,
                    'summary_value' => null,
                ],
            ],
            'portability_phone_numbers_snapshot' => [],
            'attachment_paths' => [],
            'supervisor_validation_status' => 'validado',
            'accepted_at' => now(),
        ], $saleOverrides));
    }
}
