<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Interaction;
use App\Models\Lead;
use App\Models\PostSaleUpdate;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostSaleControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createSale(string $supervisorValidationStatus, string $ruc = '20111111111'): Sale
    {
        $executive = User::factory()->create();
        $executive->assignRole('Ejecutivo');

        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');

        $campaign = Campaign::firstOrCreate(['name' => 'Campana Postventa'], ['active' => true]);
        $executive->campaigns()->syncWithoutDetaching([$campaign->id]);

        $lead = Lead::create([
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => $executive->id,
            'created_by_user_id' => $executive->id,
            'supervisor_user_id' => $supervisor->id,
            'source' => 'empresa',
            'delivery_status' => 'gestionado',
            'taken_at' => now(),
            'business_name' => 'Empresa Postventa '.$ruc,
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
            'call_detail' => 'Acuerdo de prueba postventa.',
            'is_agreement' => true,
            'agreed_at' => now(),
        ]);

        return Sale::create([
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
            'customer_business_name' => 'Empresa Postventa '.$ruc,
            'customer_ruc' => $ruc,
            'supervisor_validation_status' => $supervisorValidationStatus,
        ]);
    }

    private function createPostventaUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Postventa');

        return $user;
    }

    public function test_index_only_shows_sales_validated_by_supervisor(): void
    {
        $this->seed(RoleSeeder::class);

        $validated = $this->createSale(Sale::SUPERVISOR_VALIDATION_VALIDATED, '20111111111');
        $this->createSale(Sale::SUPERVISOR_VALIDATION_PENDING, '20222222222');

        $response = $this->actingAs($this->createPostventaUser())
            ->get(route('post-sale.index'));

        $response->assertOk();
        $response->assertSee('20111111111');
        $response->assertDontSee('20222222222');
    }

    public function test_update_changes_management_status_and_records_history(): void
    {
        $this->seed(RoleSeeder::class);

        $sale = $this->createSale(Sale::SUPERVISOR_VALIDATION_VALIDATED);
        $postventa = $this->createPostventaUser();

        $response = $this->actingAs($postventa)
            ->post(route('post-sale.update', $sale), [
                'management_status' => Sale::MANAGEMENT_APPROVED,
                'feedback' => 'Cliente conforme con la gestión.',
            ]);

        $response->assertRedirect(route('post-sale.index'));
        $response->assertSessionHas('success');

        $this->assertSame(Sale::MANAGEMENT_APPROVED, $sale->fresh()->management_status);

        $update = PostSaleUpdate::query()->where('sale_id', $sale->id)->first();
        $this->assertNotNull($update);
        $this->assertSame($postventa->id, $update->user_id);
        $this->assertSame(Sale::MANAGEMENT_APPROVED, $update->management_status);
    }

    public function test_update_is_forbidden_for_sales_not_validated_by_supervisor(): void
    {
        $this->seed(RoleSeeder::class);

        $sale = $this->createSale(Sale::SUPERVISOR_VALIDATION_PENDING);

        $this->actingAs($this->createPostventaUser())
            ->post(route('post-sale.update', $sale), [
                'management_status' => Sale::MANAGEMENT_APPROVED,
                'feedback' => 'No debería poder.',
            ])
            ->assertForbidden();

        $this->assertSame(Sale::MANAGEMENT_PENDING_VALIDATION, $sale->fresh()->management_status);
    }

    public function test_update_rejects_management_status_outside_allowed_set(): void
    {
        $this->seed(RoleSeeder::class);

        $sale = $this->createSale(Sale::SUPERVISOR_VALIDATION_VALIDATED);

        $this->actingAs($this->createPostventaUser())
            ->from(route('post-sale.index'))
            ->post(route('post-sale.update', $sale), [
                'management_status' => Sale::MANAGEMENT_PENDING_SUPERVISION,
                'feedback' => 'Estado fuera del set permitido.',
            ])
            ->assertSessionHasErrors('management_status');
    }

    public function test_other_roles_cannot_access_post_sale_module(): void
    {
        $this->seed(RoleSeeder::class);

        $executive = User::factory()->create();
        $executive->assignRole('Ejecutivo');

        $this->actingAs($executive)
            ->get(route('post-sale.index'))
            ->assertForbidden();
    }
}
