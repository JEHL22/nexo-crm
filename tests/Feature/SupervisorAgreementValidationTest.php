<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Interaction;
use App\Models\Lead;
use App\Models\PromotionName;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisorAgreementValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_cannot_repeat_the_same_portability_promotion_in_multiple_rows(): void
    {
        $this->seed(RoleSeeder::class);

        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');

        $executive = User::factory()->create();
        $executive->assignRole('Ejecutivo');

        $campaign = Campaign::create([
            'name' => 'Campana Supervisor',
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
            'full_name' => 'Cliente Supervisor',
            'business_name' => 'Cliente Supervisor SAC',
            'representative_name' => 'Rosa Perez',
            'dni' => '12345678',
            'ruc' => '20123456789',
            'current_operator' => 'Entel',
            'status_general' => 'contactado',
            'status_specific' => 'acuerdo_aceptado',
            'status_final' => 'acuerdo_aceptado',
            'call_summary' => 'Acuerdo listo para validar.',
            'last_contact_name' => 'Rosa Perez',
            'last_contact_phone' => '987654321',
        ]);

        $promotion = PromotionName::create([
            'name' => 'Internet 2gb velocidad',
            'monthly_price' => 29.90,
            'is_active' => true,
            'sort_order' => 1,
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

        $sale = Sale::create([
            'lead_id' => $lead->id,
            'interaction_id' => $interaction->id,
            'campaign_id' => $campaign->id,
            'executive_user_id' => $executive->id,
            'supervisor_user_id' => $supervisor->id,
            'status' => 'acuerdo_aceptado',
            'management_status' => 'pendiente_supervision',
            'sisac_status' => 'pendiente_supervision',
            'product_type' => 'movil',
            'offered_line_count' => 2,
            'monthly_payment' => 59.80,
            'customer_ruc' => '20123456789',
            'customer_business_name' => 'Cliente Supervisor SAC',
            'customer_dni' => '12345678',
            'customer_representative_name' => 'Rosa Perez',
            'customer_phone' => '987654321',
            'customer_address' => 'Av. Principal 123',
            'customer_coordinates' => '-12.0464,-77.0428',
            'plan_code' => 'PLN-001',
            'approval_code' => null,
            'customer_email' => 'cliente@example.com',
            'service_channel' => 'pdv',
            'attention_time_slot' => '9 am - 11 am',
            'attention_date' => '2026-04-01',
            'operator_name' => 'Entel',
            'delivery_type' => 'regular',
            'fixed_agreement_supports' => [],
            'products_snapshot' => [
                [
                    'type' => 'movil',
                    'label' => 'Móvil',
                    'detail' => 'Portabilidad',
                    'line_count' => 2,
                    'price' => 29.90,
                    'line_total' => 59.80,
                    'summary_value' => $promotion->name,
                ],
            ],
            'portability_phone_numbers_snapshot' => [
                [
                    'offer_index' => 0,
                    'offer_key' => 'portability-0',
                    'offer_label' => 'Portabilidad',
                    'promotion_name' => $promotion->name,
                    'display_offer' => $promotion->name,
                    'row_label' => 'Línea 1',
                    'phone_number' => '987654321',
                ],
                [
                    'offer_index' => 0,
                    'offer_key' => 'portability-0',
                    'offer_label' => 'Portabilidad',
                    'promotion_name' => $promotion->name,
                    'display_offer' => $promotion->name,
                    'row_label' => 'Línea 2',
                    'phone_number' => '912345678',
                ],
            ],
            'attachment_paths' => [],
            'supervisor_validation_status' => 'pendiente',
        ]);

        $response = $this->from(route('supervisor.agreements.show', $sale))
            ->actingAs($supervisor)
            ->put(route('supervisor.agreements.update', $sale), [
                'customer_ruc' => '20123456789',
                'customer_business_name' => 'Cliente Supervisor SAC',
                'customer_dni' => '12345678',
                'customer_representative_name' => 'Rosa Perez',
                'customer_phone' => '987654321',
                'customer_address' => 'Av. Principal 123',
                'customer_coordinates' => '-12.0464,-77.0428',
                'plan_code' => 'PLN-001',
                'approval_code' => '',
                'customer_email' => 'cliente@example.com',
                'service_channel' => 'pdv',
                'attention_time_slot' => '9 am - 11 am',
                'attention_date' => '2026-04-01',
                'operator_name' => 'Entel',
                'delivery_type' => 'regular',
                'fixed_agreement_supports' => [],
                'portability_lines' => [2, 3],
                'portability_promotion_name' => [$promotion->name, $promotion->name],
                'new_lines' => [],
                'new_promotion_name' => [],
                'portability_phone_numbers' => [
                    '987654321',
                    '912345678',
                    '923456781',
                    '934567812',
                    '945678123',
                ],
                'kept_attachment_paths' => [],
                'validate_after_save' => '0',
            ]);

        $response->assertRedirect(route('supervisor.agreements.show', $sale));
        $response->assertSessionHasErrors([
            'portability_rows' => 'No repitas la misma promoción en Portabilidad. Si deseas más líneas para ese plan, aumenta la cantidad en una sola fila.',
        ]);

        $sale->refresh();

        $this->assertSame(2, $sale->offered_line_count);
        $this->assertCount(1, $sale->products_snapshot ?? []);
        $this->assertSame(2, data_get($sale->products_snapshot, '0.line_count'));
        $this->assertSame($promotion->name, data_get($sale->products_snapshot, '0.summary_value'));
    }
}
