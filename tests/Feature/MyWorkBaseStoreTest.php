<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyWorkBaseStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_base_store_allows_empty_sisac_fields(): void
    {
        [$executive, $campaign] = $this->createExecutiveWithCampaign();

        $response = $this->actingAs($executive)
            ->post(route('my-work.base.store'), $this->basePayload([
                'ruc' => '12345678901',
                'business_name' => 'Cliente Sin SISAC SAC',
                'segment' => '',
                'max_speed' => '',
                'package' => '',
                'technology' => '',
            ]));

        $lead = Lead::query()
            ->where('campaign_id', $campaign->id)
            ->where('ruc', '12345678901')
            ->firstOrFail();

        $response->assertRedirect(route('my-work.show', $lead));
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertSame('mi_base', $lead->source);
        $this->assertNull($lead->segment);
        $this->assertNull($lead->max_speed);
        $this->assertNull($lead->package);
        $this->assertNull($lead->technology);
    }

    public function test_my_base_store_persists_optional_sisac_fields_when_provided(): void
    {
        [$executive, $campaign] = $this->createExecutiveWithCampaign();

        $response = $this->actingAs($executive)
            ->post(route('my-work.base.store'), $this->basePayload([
                'ruc' => '10987654321',
                'business_name' => 'Cliente Con SISAC SAC',
                'segment' => 'Corporativo',
                'max_speed' => '600 Mbps',
                'package' => 'Duo Empresas',
                'technology' => 'Fibra',
            ]));

        $response->assertSessionHasNoErrors();

        $lead = Lead::query()
            ->where('campaign_id', $campaign->id)
            ->where('ruc', '10987654321')
            ->firstOrFail();

        $response->assertRedirect(route('my-work.show', $lead));

        $this->assertDatabaseHas('leads', [
            'campaign_id' => $campaign->id,
            'ruc' => '10987654321',
            'source' => 'mi_base',
            'segment' => 'Corporativo',
            'max_speed' => '600 Mbps',
            'package' => 'Duo Empresas',
            'technology' => 'Fibra',
        ]);
    }

    public function test_my_base_store_keeps_existing_required_validation_rules(): void
    {
        [$executive] = $this->createExecutiveWithCampaign();

        $response = $this->from(route('my-work.base'))
            ->actingAs($executive)
            ->post(route('my-work.base.store'), $this->basePayload([
                'ruc' => '',
                'business_name' => '',
                'primary_phone' => '',
                'segment' => '',
                'max_speed' => '',
                'package' => '',
                'technology' => '',
            ]));

        $response->assertRedirect(route('my-work.base'));
        $response->assertSessionHasErrors(['ruc', 'business_name', 'primary_phone']);
    }

    private function createExecutiveWithCampaign(): array
    {
        $this->seed(RoleSeeder::class);

        $executive = User::factory()->create();
        $executive->assignRole('Ejecutivo');

        $campaign = Campaign::create([
            'name' => 'Campana Mi Base',
            'type' => 'outbound',
            'status' => 'activa',
        ]);

        $executive->campaigns()->attach($campaign->id);

        return [$executive, $campaign];
    }

    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'ruc' => '20123456789',
            'business_name' => 'Mi Base Comercial SAC',
            'representative_name' => 'Maria Perez',
            'dni' => '12345678',
            'fiscal_address' => 'Av. Principal 123',
            'primary_phone' => '987654321',
            'cellphones' => ['912345678', '923456789'],
            'current_operator' => 'Claro',
            'current_line_count' => 2,
            'segment' => null,
            'max_speed' => null,
            'package' => null,
            'technology' => null,
        ], $overrides);
    }
}
