<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\ExecutiveActivitySession;
use App\Models\Interaction;
use App\Models\Lead;
use App\Models\Sale;
use App\Models\SupervisorExecutive;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_dashboard_table_uses_current_snapshot_for_gestion_total_and_contactado(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-30 10:00:00'));

        try {
            $this->seed(RoleSeeder::class);

            $supervisor = User::factory()->create(['name' => 'Supervisor Uno']);
            $supervisor->assignRole('Supervisor');

            $otherSupervisor = User::factory()->create(['name' => 'Supervisor Dos']);
            $otherSupervisor->assignRole('Supervisor');

            $executive = User::factory()->create(['name' => 'Ejecutivo Uno']);
            $executive->assignRole('Ejecutivo');

            $outsideExecutive = User::factory()->create(['name' => 'Ejecutivo Fuera']);
            $outsideExecutive->assignRole('Ejecutivo');

            $campaign = Campaign::create([
                'name' => 'Campana Norte',
                'active' => true,
            ]);

            SupervisorExecutive::create([
                'supervisor_user_id' => $supervisor->id,
                'executive_user_id' => $executive->id,
                'campaign_id' => $campaign->id,
            ]);

            SupervisorExecutive::create([
                'supervisor_user_id' => $otherSupervisor->id,
                'executive_user_id' => $outsideExecutive->id,
                'campaign_id' => $campaign->id,
            ]);

            $baseLead = [
                'campaign_id' => $campaign->id,
                'assigned_to_user_id' => $executive->id,
                'created_by_user_id' => $executive->id,
                'supervisor_user_id' => $supervisor->id,
                'delivery_status' => 'gestionado',
                'taken_at' => now(),
                'source' => 'empresa',
                'full_name' => 'Contacto Demo',
                'business_name' => 'Empresa Demo',
                'call_summary' => 'Seguimiento de prueba',
                'status_general' => 'contactado',
            ];

            $reprogramadoLead = Lead::create($baseLead + [
                'ruc' => '10000000001',
                'status_specific' => 'reprogramado',
                'status_final' => 'en_seguimiento',
            ]);

            $negociacionLead = Lead::create($baseLead + [
                'ruc' => '10000000002',
                'status_specific' => 'negociacion',
                'status_final' => 'en_seguimiento',
            ]);

            $agreementLead = Lead::create($baseLead + [
                'ruc' => '10000000003',
                'status_specific' => 'acuerdo_aceptado',
                'status_final' => 'acuerdo_aceptado',
            ]);

            $noDeseaLead = Lead::create($baseLead + [
                'ruc' => '10000000004',
                'status_specific' => 'no_desea',
                'status_final' => 'cerrado_sin_venta',
            ]);

            Lead::create([
                'campaign_id' => $campaign->id,
                'assigned_to_user_id' => $outsideExecutive->id,
                'created_by_user_id' => $outsideExecutive->id,
                'supervisor_user_id' => $otherSupervisor->id,
                'delivery_status' => 'gestionado',
                'taken_at' => now(),
                'source' => 'empresa',
                'full_name' => 'Contacto Externo',
                'business_name' => 'Empresa Externa',
                'call_summary' => 'No debe aparecer',
                'status_general' => 'contactado',
                'status_specific' => 'negociacion',
                'status_final' => 'en_seguimiento',
                'ruc' => '10000000009',
            ]);

            $response = $this->actingAs($supervisor)->get(route('supervisor.dashboard.index', [
                'from' => '2026-03-30',
                'to' => '2026-03-30',
            ]));

            $response->assertOk();
            $response->assertSee('Todos los ejecutivos asignados');
            $response->assertSee('Arrastre diario por ejecutivo');
            $response->assertSee('Arrastre semanal por ejecutivo');
            $response->assertSee('Seguimiento semanal acumulado del mes');
            $response->assertSee('Acuerdo aceptado');
            $response->assertSee('Ejecutivo Uno');
            $response->assertDontSee('Ejecutivo Fuera');
            $response->assertDontSee('Semana 5');
            $response->assertViewHas('dashboardPayload', function (array $payload) use (
                $executive,
                $reprogramadoLead,
                $negociacionLead,
                $agreementLead,
                $noDeseaLead
            ) {
                $rows = collect($payload['executives'] ?? [])->keyBy('executive_user_id');
                $row = $rows->get($executive->id);
                $contactadoIds = collect(data_get($row, 'lead_details.contactado', []))->pluck('id')->sort()->values()->all();
                $teamTotals = $payload['team_totals'] ?? [];

                return $rows->count() === 1
                    && count($payload['month_weeks'] ?? []) === 4
                    && is_array($row)
                    && ($row['gestion_total'] ?? null) === 4
                    && ($row['total'] ?? null) === 4
                    && ($row['contactado'] ?? null) === 3
                    && ($row['reprogramado'] ?? null) === 1
                    && ($row['negociacion'] ?? null) === 1
                    && ($row['acuerdo_aceptado'] ?? null) === 1
                    && ($row['no_desea'] ?? null) === 1
                    && count($contactadoIds) === 3
                    && $contactadoIds === collect([
                        $reprogramadoLead->id,
                        $negociacionLead->id,
                        $agreementLead->id,
                    ])->sort()->values()->all()
                    && ! in_array($noDeseaLead->id, $contactadoIds, true)
                    && ($teamTotals['gestion_total'] ?? null) === 4
                    && ($teamTotals['contactado'] ?? null) === 3
                    && ($teamTotals['executives'] ?? null) === 1
                    && ($teamTotals['executives_with_portfolio'] ?? null) === 1;
            });
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_supervisor_dashboard_keeps_inherited_daily_and_weekly_carryover_before_first_login_of_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-03 10:00:00'));

        try {
            $this->seed(RoleSeeder::class);

            [$supervisor, $executive, $campaign] = $this->createMappedSupervisorContext();

            $this->createExecutiveActivitySession($campaign, $supervisor, $executive, '2026-03-30 08:00:00');

            $response = $this->actingAs($supervisor)->get(route('supervisor.dashboard.index', [
                'from' => '2026-04-03',
                'to' => '2026-04-03',
            ]));

            $response->assertOk();
            $response->assertViewHas('dashboardPayload', function (array $payload) use ($executive) {
                $row = collect($payload['executives'] ?? [])
                    ->firstWhere('executive_user_id', $executive->id);

                return is_array($row)
                    && data_get($row, 'carryover.negociacion.today_actual') === 0
                    && data_get($row, 'carryover.negociacion.daily_goal') === 0
                    && data_get($row, 'carryover.negociacion.carryover_pending') === 4
                    && data_get($row, 'carryover.negociacion.current_target') === 4
                    && data_get($row, 'weekly_carryover.negociacion.current_week_actual') === 0
                    && data_get($row, 'weekly_carryover.negociacion.weekly_goal') === 0
                    && data_get($row, 'weekly_carryover.negociacion.carryover_pending') === 4
                    && data_get($row, 'weekly_carryover.negociacion.current_target') === 4
                    && data_get($row, 'weekly_month_breakdown.negociacion.week_1.progress_label') === 'N/A'
                    && data_get($row, 'weekly_month_breakdown.negociacion.week_2.progress_label') === 'N/A'
                    && data_get($row, 'weekly_month_breakdown.negociacion.week_3.progress_label') === 'N/A'
                    && data_get($row, 'weekly_month_breakdown.negociacion.week_4.progress_label') === 'N/A';
            });
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_supervisor_dashboard_resumes_daily_carryover_on_first_login_of_new_month_without_counting_previous_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-06 10:00:00'));

        try {
            $this->seed(RoleSeeder::class);

            [$supervisor, $executive, $campaign, $lead] = $this->createMappedSupervisorContext();

            $this->createExecutiveActivitySession($campaign, $supervisor, $executive, '2026-03-30 08:00:00');
            $this->createExecutiveActivitySession($campaign, $supervisor, $executive, '2026-04-06 08:00:00');

            $this->createInteraction($lead, $executive, $campaign, 'negociacion', '2026-04-06 11:00:00');

            $response = $this->actingAs($supervisor)->get(route('supervisor.dashboard.index', [
                'from' => '2026-04-06',
                'to' => '2026-04-06',
            ]));

            $response->assertOk();
            $response->assertViewHas('dashboardPayload', function (array $payload) use ($executive) {
                $row = collect($payload['executives'] ?? [])
                    ->firstWhere('executive_user_id', $executive->id);

                return is_array($row)
                    && data_get($row, 'carryover.negociacion.today_actual') === 1
                    && data_get($row, 'carryover.negociacion.daily_goal') === 2
                    && data_get($row, 'carryover.negociacion.carryover_pending') === 4
                    && data_get($row, 'carryover.negociacion.current_target') === 6;
            });
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_supervisor_dashboard_builds_weekly_carryover_with_inherited_debt_and_partial_first_week_of_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-10 10:00:00'));

        try {
            $this->seed(RoleSeeder::class);

            [$supervisor, $executive, $campaign, $lead] = $this->createMappedSupervisorContext();

            $this->createExecutiveActivitySession($campaign, $supervisor, $executive, '2026-03-30 08:00:00');
            $this->createExecutiveActivitySession($campaign, $supervisor, $executive, '2026-04-09 08:00:00');

            $this->createInteraction($lead, $executive, $campaign, 'negociacion', '2026-04-10 11:00:00');

            $response = $this->actingAs($supervisor)->get(route('supervisor.dashboard.index', [
                'from' => '2026-04-10',
                'to' => '2026-04-10',
            ]));

            $response->assertOk();
            $response->assertSee('Arrastre semanal por ejecutivo');
            $response->assertViewHas('dashboardPayload', function (array $payload) use ($executive) {
                $row = collect($payload['executives'] ?? [])
                    ->firstWhere('executive_user_id', $executive->id);

                return is_array($row)
                    && data_get($row, 'weekly_carryover.negociacion.current_week_actual') === 1
                    && data_get($row, 'weekly_carryover.negociacion.weekly_goal') === 4
                    && data_get($row, 'weekly_carryover.negociacion.carryover_pending') === 4
                    && data_get($row, 'weekly_carryover.negociacion.current_target') === 8;
            });
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_supervisor_dashboard_builds_accumulated_monthly_week_table_with_inherited_carryover(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-30 10:00:00'));

        try {
            $this->seed(RoleSeeder::class);

            [$supervisor, $executive, $campaign, $lead] = $this->createMappedSupervisorContext();

            $this->createExecutiveActivitySession($campaign, $supervisor, $executive, '2026-03-30 08:00:00');
            $this->createExecutiveActivitySession($campaign, $supervisor, $executive, '2026-04-20 08:00:00');

            for ($i = 0; $i < 18; $i++) {
                $this->createInteraction($lead, $executive, $campaign, 'negociacion', '2026-04-28 10:00:00');
            }

            $response = $this->actingAs($supervisor)->get(route('supervisor.dashboard.index', [
                'from' => '2026-04-30',
                'to' => '2026-04-30',
            ]));

            $response->assertOk();
            $response->assertSee('Seguimiento semanal acumulado del mes');
            $response->assertSee('N/A');
            $response->assertViewHas('dashboardPayload', function (array $payload) use ($executive) {
                $row = collect($payload['executives'] ?? [])
                    ->firstWhere('executive_user_id', $executive->id);

                return is_array($row)
                    && count($payload['month_weeks'] ?? []) === 4
                    && data_get($row, 'weekly_month_breakdown.negociacion.week_1.progress_label') === 'N/A'
                    && data_get($row, 'weekly_month_breakdown.negociacion.week_2.progress_label') === 'N/A'
                    && data_get($row, 'weekly_month_breakdown.negociacion.week_3.progress_label') === 'N/A'
                    && data_get($row, 'weekly_month_breakdown.negociacion.week_4.base_goal') === 18
                    && data_get($row, 'weekly_month_breakdown.negociacion.week_4.carryover_pending') === 4
                    && data_get($row, 'weekly_month_breakdown.negociacion.week_4.current_target') === 22
                    && data_get($row, 'weekly_month_breakdown.negociacion.week_4.progress_label') === '18/22'
                    && data_get($row, 'weekly_month_breakdown.negociacion.week_4.met_goal') === false;
            });
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_supervisor_dashboard_marks_goal_completion_flags_against_persistent_daily_target(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-06 10:00:00'));

        try {
            $this->seed(RoleSeeder::class);

            [$supervisor, $executive, $campaign, $lead] = $this->createMappedSupervisorContext();

            $this->createExecutiveActivitySession($campaign, $supervisor, $executive, '2026-03-30 08:00:00');
            $this->createExecutiveActivitySession($campaign, $supervisor, $executive, '2026-04-06 08:00:00');

            for ($i = 0; $i < 6; $i++) {
                $this->createInteraction($lead, $executive, $campaign, 'negociacion', '2026-04-06 11:00:00');
            }

            for ($i = 0; $i < 20; $i++) {
                $this->createInteraction($lead, $executive, $campaign, 'reprogramado', '2026-04-06 12:00:00');
            }

            for ($i = 0; $i < 3; $i++) {
                $this->createAcceptedAgreement($campaign, $supervisor, $executive, '2026-04-06 15:00:00', '1000000800'.($i + 1));
            }

            $response = $this->actingAs($supervisor)->get(route('supervisor.dashboard.index', [
                'from' => '2026-04-06',
                'to' => '2026-04-06',
            ]));

            $response->assertOk();
            $response->assertSee('!border-emerald-400 !bg-emerald-600 !text-white', false);
            $response->assertViewHas('dashboardPayload', function (array $payload) use ($executive) {
                $row = collect($payload['executives'] ?? [])
                    ->firstWhere('executive_user_id', $executive->id);

                return is_array($row)
                    && data_get($row, 'carryover.negociacion.current_target') === 6
                    && data_get($row, 'carryover.reprogramado.current_target') === 60
                    && data_get($row, 'carryover.acuerdo_aceptado.current_target') === 3
                    && data_get($row, 'goal_completion.contactado') === true
                    && data_get($row, 'goal_completion.negociacion') === true
                    && data_get($row, 'goal_completion.reprogramado') === false
                    && data_get($row, 'goal_completion.acuerdo_aceptado') === true;
            });
        } finally {
            Carbon::setTestNow();
        }
    }

    private function createMappedSupervisorContext(): array
    {
        $supervisor = User::factory()->create(['name' => 'Supervisor Uno']);
        $supervisor->assignRole('Supervisor');

        $executive = User::factory()->create(['name' => 'Ejecutivo Uno']);
        $executive->assignRole('Ejecutivo');

        $campaign = Campaign::create([
            'name' => 'Campana Norte',
            'active' => true,
        ]);

        SupervisorExecutive::create([
            'supervisor_user_id' => $supervisor->id,
            'executive_user_id' => $executive->id,
            'campaign_id' => $campaign->id,
        ]);

        $lead = $this->createDashboardLead($campaign, $supervisor, $executive, '10000000001');

        return [$supervisor, $executive, $campaign, $lead];
    }

    private function createDashboardLead(Campaign $campaign, User $supervisor, User $executive, string $ruc): Lead
    {
        return Lead::create([
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => $executive->id,
            'created_by_user_id' => $executive->id,
            'supervisor_user_id' => $supervisor->id,
            'delivery_status' => 'gestionado',
            'taken_at' => now(),
            'source' => 'empresa',
            'full_name' => 'Contacto Demo',
            'business_name' => 'Empresa Demo',
            'call_summary' => 'Seguimiento de prueba',
            'status_general' => 'contactado',
            'status_specific' => 'negociacion',
            'status_final' => 'en_seguimiento',
            'ruc' => $ruc,
        ]);
    }

    private function createExecutiveActivitySession(
        Campaign $campaign,
        User $supervisor,
        User $executive,
        string $loginAt
    ): ExecutiveActivitySession {
        $loginAtCarbon = Carbon::parse($loginAt, config('app.timezone'));

        return ExecutiveActivitySession::create([
            'executive_user_id' => $executive->id,
            'supervisor_user_id' => $supervisor->id,
            'campaign_id' => $campaign->id,
            'login_at' => $loginAtCarbon,
            'last_seen_at' => $loginAtCarbon->copy()->addHour(),
            'current_module_name' => 'my-work',
            'current_route_name' => 'my-work.index',
            'current_page_url' => 'https://nexo-crm.test/my-work',
            'current_page_entered_at' => $loginAtCarbon,
            'is_crm_focused' => true,
            'last_focus_change_at' => $loginAtCarbon,
            'total_blurred_seconds' => 0,
        ]);
    }

    private function createInteraction(
        Lead $lead,
        User $executive,
        Campaign $campaign,
        string $specificStatus,
        string $createdAt
    ): Interaction {
        $interaction = Interaction::create([
            'lead_id' => $lead->id,
            'user_id' => $executive->id,
            'campaign_id' => $campaign->id,
            'status' => $specificStatus,
            'interaction_type' => 'a_negociar',
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
    }

    private function createAcceptedAgreement(
        Campaign $campaign,
        User $supervisor,
        User $executive,
        string $acceptedAt,
        string $ruc
    ): Sale {
        $lead = Lead::create([
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => $executive->id,
            'created_by_user_id' => $executive->id,
            'supervisor_user_id' => $supervisor->id,
            'delivery_status' => 'gestionado',
            'taken_at' => now(),
            'source' => 'empresa',
            'full_name' => 'Contacto Cierre',
            'business_name' => 'Empresa Cierre '.$ruc,
            'call_summary' => 'Cierre de prueba',
            'status_general' => 'contactado',
            'status_specific' => 'acuerdo_aceptado',
            'status_final' => 'acuerdo_aceptado',
            'ruc' => $ruc,
        ]);

        $interaction = $this->createInteraction($lead, $executive, $campaign, 'acuerdo_aceptado', $acceptedAt);

        return Sale::create([
            'lead_id' => $lead->id,
            'interaction_id' => $interaction->id,
            'campaign_id' => $campaign->id,
            'executive_user_id' => $executive->id,
            'supervisor_user_id' => $supervisor->id,
            'status' => 'acuerdo_aceptado',
            'management_status' => 'pendiente_validacion',
            'sisac_status' => 'pendiente_validacion',
            'product_type' => 'movil',
            'offered_line_count' => 1,
            'monthly_payment' => 99.90,
            'accepted_at' => Carbon::parse($acceptedAt, config('app.timezone')),
        ]);
    }
}
