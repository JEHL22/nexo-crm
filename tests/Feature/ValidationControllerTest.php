<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Interaction;
use App\Models\Lead;
use App\Models\Sale;
use App\Models\SupervisorStatusNotification;
use App\Models\User;
use App\Models\ValidationUpdate;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidationControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createSale(string $supervisorValidationStatus, array $overrides = [], string $ruc = '20333333333'): Sale
    {
        $executive = User::factory()->create();
        $executive->assignRole('Ejecutivo');

        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');

        $campaign = Campaign::firstOrCreate(['name' => 'Campana Mesa Control'], ['active' => true]);
        $executive->campaigns()->syncWithoutDetaching([$campaign->id]);

        $lead = Lead::create([
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => $executive->id,
            'created_by_user_id' => $executive->id,
            'supervisor_user_id' => $supervisor->id,
            'source' => 'empresa',
            'delivery_status' => 'gestionado',
            'taken_at' => now(),
            'business_name' => 'Empresa Mesa '.$ruc,
            'status_general' => Lead::GENERAL_CONTACTED,
            'status_specific' => Lead::SPECIFIC_AGREEMENT_ACCEPTED,
            'status_final' => Lead::FINAL_AGREEMENT_ACCEPTED,
            'ruc' => $ruc,
        ]);

        $interaction = Interaction::create([
            'lead_id' => $lead->id,
            'user_id' => $executive->id,
            'campaign_id' => $campaign->id,
            'status' => Lead::SPECIFIC_AGREEMENT_ACCEPTED,
            'interaction_type' => 'a_negociar',
            'status_general' => Lead::GENERAL_CONTACTED,
            'status_specific' => Lead::SPECIFIC_AGREEMENT_ACCEPTED,
            'call_detail' => 'Acuerdo de prueba mesa de control.',
            'is_agreement' => true,
            'agreed_at' => now(),
        ]);

        return Sale::create(array_merge([
            'lead_id' => $lead->id,
            'interaction_id' => $interaction->id,
            'campaign_id' => $campaign->id,
            'executive_user_id' => $executive->id,
            'supervisor_user_id' => $supervisor->id,
            'status' => Sale::STATUS_ACCEPTED,
            'management_status' => Sale::MANAGEMENT_PENDING_VALIDATION,
            'sisac_status' => Sale::SISAC_IN_EVALUATION,
            'product_type' => 'movil',
            'offered_line_count' => 1,
            'monthly_payment' => 99.90,
            'accepted_at' => now(),
            'customer_business_name' => 'Empresa Mesa '.$ruc,
            'customer_ruc' => $ruc,
            'supervisor_validation_status' => $supervisorValidationStatus,
        ], $overrides));
    }

    private function createMesaUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Mesa de Control');

        return $user;
    }

    public function test_index_only_shows_sales_validated_by_supervisor(): void
    {
        $this->seed(RoleSeeder::class);

        $this->createSale(Sale::SUPERVISOR_VALIDATION_VALIDATED, [], '20333333333');
        $this->createSale(Sale::SUPERVISOR_VALIDATION_PENDING, [], '20444444444');

        $response = $this->actingAs($this->createMesaUser())
            ->get(route('validation.index'));

        $response->assertOk();
        $response->assertSee('20333333333');
        $response->assertDontSee('20444444444');
    }

    public function test_update_records_history_and_notifies_supervisor(): void
    {
        $this->seed(RoleSeeder::class);

        $sale = $this->createSale(Sale::SUPERVISOR_VALIDATION_VALIDATED);
        $mesa = $this->createMesaUser();

        $response = $this->actingAs($mesa)
            ->post(route('validation.update', $sale), [
                'sisac_status' => Sale::SISAC_ACTIVE,
                'feedback' => 'Servicio activado correctamente.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame(Sale::SISAC_ACTIVE, $sale->fresh()->sisac_status);

        $update = ValidationUpdate::query()->where('sale_id', $sale->id)->first();
        $this->assertNotNull($update);
        $this->assertSame($mesa->id, $update->user_id);
        $this->assertSame(Sale::SISAC_ACTIVE, $update->sisac_status);

        $this->assertNotNull(
            SupervisorStatusNotification::query()
                ->where('sale_id', $sale->id)
                ->where('user_id', $sale->supervisor_user_id)
                ->first()
        );
    }

    public function test_update_is_forbidden_for_sales_not_validated_by_supervisor(): void
    {
        $this->seed(RoleSeeder::class);

        $sale = $this->createSale(Sale::SUPERVISOR_VALIDATION_PENDING);

        $this->actingAs($this->createMesaUser())
            ->post(route('validation.update', $sale), [
                'sisac_status' => Sale::SISAC_ACTIVE,
                'feedback' => 'No debería poder.',
            ])
            ->assertForbidden();

        $this->assertSame(Sale::SISAC_IN_EVALUATION, $sale->fresh()->sisac_status);
    }

    public function test_update_rejects_sisac_status_outside_allowed_set(): void
    {
        $this->seed(RoleSeeder::class);

        $sale = $this->createSale(Sale::SUPERVISOR_VALIDATION_VALIDATED);

        $this->actingAs($this->createMesaUser())
            ->from(route('validation.index'))
            ->post(route('validation.update', $sale), [
                'sisac_status' => Sale::SISAC_PENDING_SUPERVISION,
                'feedback' => 'Estado que Mesa de Control no puede asignar.',
            ])
            ->assertSessionHasErrors('sisac_status');
    }

    public function test_delivered_status_requires_sim_details_for_mobile_sales(): void
    {
        $this->seed(RoleSeeder::class);

        $sale = $this->createSale(Sale::SUPERVISOR_VALIDATION_VALIDATED, [
            'portability_phone_numbers_snapshot' => [
                ['phone_number' => '987654321'],
            ],
        ]);

        $this->actingAs($this->createMesaUser())
            ->post(route('validation.update', $sale), [
                'sisac_status' => Sale::SISAC_DELIVERED,
                'feedback' => 'Entrega sin registrar series SIM.',
            ])
            ->assertStatus(422);
    }
}
