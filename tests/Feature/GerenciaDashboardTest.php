<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Interaction;
use App\Models\Lead;
use App\Models\Sale;
use App\Models\SupervisorExecutive;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GerenciaDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_gerencia_user_sees_management_dashboard_with_real_metrics(): void
    {
        $this->seed(RoleSeeder::class);

        $gerencia = User::factory()->create();
        $gerencia->assignRole('Gerencia');

        $supervisor = User::factory()->create(['name' => 'Supervisor Uno']);
        $supervisor->assignRole('Supervisor');

        $asesor = User::factory()->create(['name' => 'Asesor Uno']);
        $asesor->assignRole('Ejecutivo');

        $campaign = Campaign::create([
            'name' => 'Campana Norte',
            'active' => true,
        ]);

        SupervisorExecutive::create([
            'supervisor_user_id' => $supervisor->id,
            'executive_user_id' => $asesor->id,
            'campaign_id' => $campaign->id,
        ]);

        $baseLead = [
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => $asesor->id,
            'supervisor_user_id' => $supervisor->id,
            'created_by_user_id' => $asesor->id,
            'delivery_status' => 'gestionado',
            'taken_at' => now(),
            'full_name' => 'Contacto Demo',
            'business_name' => 'Empresa Demo',
            'status_general' => 'contactado',
            'call_summary' => 'Seguimiento',
        ];

        Lead::create($baseLead + [
            'source' => 'empresa',
            'ruc' => '10000000001',
            'status_specific' => 'reprogramado',
            'status_final' => 'en_seguimiento',
        ]);

        $leadWithOpportunity = Lead::create($baseLead + [
            'source' => 'empresa',
            'ruc' => '10000000002',
            'status_specific' => 'negociacion',
            'status_final' => 'en_seguimiento',
        ]);

        Lead::create($baseLead + [
            'source' => 'mi_base',
            'ruc' => '10000000003',
            'status_specific' => 'no_contesta',
            'status_general' => 'no_contactado',
            'status_final' => 'sin_gestion',
        ]);

        Lead::create($baseLead + [
            'source' => 'empresa',
            'ruc' => '10000000004',
            'status_specific' => 'no_desea',
            'status_final' => 'cerrado_sin_venta',
        ]);

        $agreementInteraction = Interaction::create([
            'lead_id' => $leadWithOpportunity->id,
            'user_id' => $asesor->id,
            'campaign_id' => $campaign->id,
            'status' => 'acuerdo_aceptado',
            'interaction_type' => 'a_negociar',
            'status_general' => 'contactado',
            'status_specific' => 'acuerdo_aceptado',
            'call_detail' => 'Acuerdo de prueba.',
            'is_agreement' => true,
            'agreed_at' => now(),
        ]);

        Sale::create([
            'lead_id' => $leadWithOpportunity->id,
            'interaction_id' => $agreementInteraction->id,
            'campaign_id' => $campaign->id,
            'executive_user_id' => $asesor->id,
            'supervisor_user_id' => $supervisor->id,
            'status' => 'acuerdo_aceptado',
            'management_status' => 'pendiente_validacion',
            'sisac_status' => 'pendiente_validacion',
            'product_type' => 'movil',
            'offered_line_count' => 3,
            'monthly_payment' => 199.90,
            'accepted_at' => now(),
        ]);

        $response = $this->actingAs($gerencia)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard comercial');
        $response->assertSee('Cierres requeridos al mes');
        $response->assertSee('Asesor Uno');
        $response->assertSee('Vista de gerencia');
        $response->assertSee('Supervisores y estados');
        $response->assertDontSee('Interesado');
        $response->assertDontSee('Sí verbal');
        $response->assertViewHas('goalMetricCards', function (array $cards) {
            return ($cards[0]['goal'] ?? null) === 400
                && ($cards[1]['goal'] ?? null) === 100
                && ($cards[2]['goal'] ?? null) === 40
                && ($cards[3]['goal'] ?? null) === 10
                && ($cards[4]['goal'] ?? null) === 8
                && ($cards[5]['goal'] ?? null) === 2;
        });
    }
}
