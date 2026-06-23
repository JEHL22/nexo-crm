<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\PromotionName;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyWorkStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_work_update_rejects_removed_contact_statuses(): void
    {
        $this->seed(RoleSeeder::class);

        $executive = User::factory()->create();
        $executive->assignRole('Ejecutivo');

        $campaign = Campaign::create([
            'name' => 'Campana Mi Chamba',
            'active' => true,
        ]);

        $executive->campaigns()->attach($campaign->id);

        $lead = Lead::create([
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => $executive->id,
            'created_by_user_id' => $executive->id,
            'source' => 'empresa',
            'delivery_status' => 'gestionado',
            'taken_at' => now(),
            'full_name' => 'Cliente Mi Chamba',
            'business_name' => 'Empresa Mi Chamba SAC',
            'status_general' => 'contactado',
            'status_specific' => 'reprogramado',
            'status_final' => 'en_seguimiento',
            'call_summary' => 'Seguimiento previo',
            'last_contact_name' => 'Ana Perez',
            'last_contact_phone' => '987654321',
        ]);

        $response = $this->from(route('my-work.show', $lead))
            ->actingAs($executive)
            ->post(route('my-work.update', $lead), [
                'general_status' => 'contactado',
                'specific_status' => 'interesado',
                'notes' => 'Intento registrar un estado retirado.',
                'contact_name' => 'Ana Perez',
                'contact_phone' => '987654321',
            ]);

        $response->assertRedirect(route('my-work.show', $lead));
        $response->assertSessionHasErrors('specific_status');
    }

    public function test_my_work_requires_commercial_data_only_for_negotiation(): void
    {
        $this->seed(RoleSeeder::class);

        $executive = User::factory()->create();
        $executive->assignRole('Ejecutivo');

        $campaign = Campaign::create([
            'name' => 'Campana Mi Chamba',
            'active' => true,
        ]);

        $executive->campaigns()->attach($campaign->id);

        $lead = Lead::create([
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => $executive->id,
            'created_by_user_id' => $executive->id,
            'source' => 'empresa',
            'delivery_status' => 'gestionado',
            'taken_at' => now(),
            'full_name' => 'Cliente Seguimiento',
            'business_name' => 'Empresa Seguimiento SAC',
            'status_general' => 'contactado',
            'status_specific' => 'negociacion',
            'status_final' => 'en_seguimiento',
            'call_summary' => 'Seguimiento previo',
            'last_contact_name' => 'Mario Ruiz',
            'last_contact_phone' => '912345678',
        ]);

        $response = $this->from(route('my-work.show', $lead))
            ->actingAs($executive)
            ->post(route('my-work.update', $lead), [
                'general_status' => 'contactado',
                'specific_status' => 'negociacion',
                'notes' => 'Se mantiene en negociación.',
                'contact_name' => 'Mario Ruiz',
                'contact_phone' => '912345678',
            ]);

        $response->assertRedirect(route('my-work.show', $lead));
        $response->assertSessionHasErrors('channel');

        $scheduledFor = now()->addHour()->startOfMinute();

        $response = $this->from(route('my-work.show', $lead))
            ->actingAs($executive)
            ->post(route('my-work.update', $lead), [
                'general_status' => 'contactado',
                'specific_status' => 'reprogramado',
                'next_contact_at' => $scheduledFor->format('Y-m-d\TH:i'),
                'notes' => 'Se reagenda la llamada.',
                'contact_name' => 'Mario Ruiz',
                'contact_phone' => '912345678',
            ]);

        $response->assertRedirect(route('my-work.show', $lead));
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('interactions', [
            'lead_id' => $lead->id,
            'interaction_type' => 'edicion_mi_chamba',
            'status_specific' => 'reprogramado',
        ]);
    }

    public function test_my_work_rejects_duplicate_portability_promotions_in_multiple_rows(): void
    {
        $this->seed(RoleSeeder::class);

        $executive = User::factory()->create();
        $executive->assignRole('Ejecutivo');

        $campaign = Campaign::create([
            'name' => 'Campana Mi Chamba',
            'active' => true,
        ]);

        $executive->campaigns()->attach($campaign->id);

        $promotion = PromotionName::create([
            'name' => 'Promo Seguimiento 29.90',
            'monthly_price' => 29.90,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $lead = Lead::create([
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => $executive->id,
            'created_by_user_id' => $executive->id,
            'source' => 'empresa',
            'delivery_status' => 'gestionado',
            'taken_at' => now(),
            'full_name' => 'Cliente Seguimiento',
            'business_name' => 'Empresa Seguimiento SAC',
            'status_general' => 'contactado',
            'status_specific' => 'negociacion',
            'status_final' => 'en_seguimiento',
            'call_summary' => 'Seguimiento previo',
            'last_contact_name' => 'Mario Ruiz',
            'last_contact_phone' => '912345678',
        ]);

        $response = $this->from(route('my-work.show', $lead))
            ->actingAs($executive)
            ->post(route('my-work.update', $lead), [
                'general_status' => 'contactado',
                'specific_status' => 'negociacion',
                'notes' => 'Se intenta repetir promo en seguimiento.',
                'contact_name' => 'Mario Ruiz',
                'contact_phone' => '912345678',
                'channel' => 'movil',
                'mobile_mode' => 'portabilidad',
                'portability_lines' => [1, 2],
                'portability_promotion_name' => [$promotion->name, $promotion->name],
                'new_lines' => [],
                'new_promotion_name' => [],
            ]);

        $response->assertRedirect(route('my-work.show', $lead));
        $response->assertSessionHasErrors([
            'portability_rows' => 'No repitas la misma promoción en Portabilidad. Si deseas más líneas para ese plan, aumenta la cantidad en una sola fila.',
        ]);

        $this->assertDatabaseMissing('interactions', [
            'lead_id' => $lead->id,
            'interaction_type' => 'edicion_mi_chamba',
            'status_specific' => 'negociacion',
            'call_detail' => 'Se intenta repetir promo en seguimiento.',
        ]);
    }
}
