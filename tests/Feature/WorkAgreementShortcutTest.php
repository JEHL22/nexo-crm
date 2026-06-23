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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkAgreementShortcutTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_shortcut_saves_negotiation_and_redirects_back_to_focused_lead(): void
    {
        [$executive, $campaign] = $this->createExecutiveWithCampaign();
        $promotion = $this->createPromotion('Promo Porta 29.90');

        $lead = Lead::create([
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => $executive->id,
            'created_by_user_id' => $executive->id,
            'source' => 'empresa',
            'delivery_status' => 'asignado',
            'taken_at' => now(),
            'full_name' => 'Cliente Shortcut',
            'business_name' => 'Empresa Shortcut SAC',
            'status_final' => 'sin_gestion',
        ]);

        $response = $this->actingAs($executive)->post(route('work.store', $lead), [
            'contact_name' => 'Ana Perez',
            'contact_phone' => '987654321',
            'general_status' => 'contactado',
            'specific_status' => 'negociacion',
            'notes' => 'Cliente listo para cerrar.',
            'channel' => 'movil',
            'mobile_mode' => 'portabilidad',
            'portability_lines' => [2],
            'portability_promotion_name' => [$promotion->name],
            'new_lines' => [],
            'new_promotion_name' => [],
            'submit_intent' => 'agreement_shortcut',
        ]);

        $response->assertRedirect(route('work.show', [
            'focused_lead' => $lead->id,
            'open_agreement_modal' => 1,
        ]));

        $interaction = Interaction::query()
            ->where('lead_id', $lead->id)
            ->where('interaction_type', 'a_negociar')
            ->firstOrFail();

        $this->assertSame('negociacion', $interaction->status_specific);
        $this->assertSame('Ana Perez', $interaction->contact_name);

        $this->assertDatabaseHas('interaction_offers', [
            'interaction_id' => $interaction->id,
            'product_type' => 'movil',
            'mobile_mode' => 'portabilidad',
            'portability_lines' => 2,
            'portability_promotion_name' => $promotion->name,
        ]);

        $lead->refresh();

        $this->assertSame('negociacion', $lead->status_specific);
        $this->assertSame('en_seguimiento', $lead->status_final);
        $this->assertSame('gestionado', $lead->delivery_status);
    }

    public function test_work_show_can_focus_the_recently_saved_lead_without_assigning_the_next_one(): void
    {
        [$executive, $campaign] = $this->createExecutiveWithCampaign();
        $promotion = $this->createPromotion('Promo Focus 34.90');

        $focusedLead = Lead::create([
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => $executive->id,
            'created_by_user_id' => $executive->id,
            'source' => 'empresa',
            'delivery_status' => 'gestionado',
            'taken_at' => now(),
            'full_name' => 'Lead Enfocado',
            'business_name' => 'Lead Enfocado SAC',
            'status_general' => 'contactado',
            'status_specific' => 'negociacion',
            'status_final' => 'en_seguimiento',
            'call_summary' => 'Seguimiento activo.',
            'last_contact_name' => 'Mario Ruiz',
            'last_contact_phone' => '912345678',
        ]);

        $interaction = Interaction::create([
            'lead_id' => $focusedLead->id,
            'user_id' => $executive->id,
            'campaign_id' => $campaign->id,
            'status' => 'negociacion',
            'interaction_type' => 'a_negociar',
            'status_general' => 'contactado',
            'status_specific' => 'negociacion',
            'call_detail' => 'Seguimiento comercial.',
            'contact_name' => 'Mario Ruiz',
            'contact_phone' => '912345678',
        ]);

        $interaction->offers()->create([
            'product_type' => 'movil',
            'mobile_mode' => 'portabilidad',
            'portability_lines' => 1,
            'portability_promotion_name' => $promotion->name,
        ]);

        $nextLead = Lead::create([
            'campaign_id' => $campaign->id,
            'source' => 'empresa',
            'delivery_status' => 'disponible',
            'full_name' => 'Lead Disponible',
            'business_name' => 'Lead Disponible SAC',
            'status_final' => 'sin_gestion',
        ]);

        $response = $this->actingAs($executive)->get(route('work.show', [
            'focused_lead' => $focusedLead->id,
            'open_agreement_modal' => 1,
        ]));

        $response->assertOk();
        $response->assertViewHas('lead', fn ($lead) => $lead?->id === $focusedLead->id);
        $response->assertViewHas('isFocusedLeadMode', true);
        $response->assertViewHas('agreementProducts', function (array $products) use ($promotion) {
            return count($products) === 1
                && ($products[0]['summary_value'] ?? null) === $promotion->name;
        });

        $this->assertDatabaseHas('leads', [
            'id' => $nextLead->id,
            'assigned_to_user_id' => null,
            'delivery_status' => 'disponible',
        ]);
    }

    public function test_executive_can_accept_agreement_from_work_shortcut_and_return_to_work_queue(): void
    {
        [$executive, $campaign] = $this->createExecutiveWithCampaign();
        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');
        $promotion = $this->createPromotion('Promo Cierre 39.90');

        $lead = Lead::create([
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => $executive->id,
            'created_by_user_id' => $executive->id,
            'supervisor_user_id' => $supervisor->id,
            'source' => 'empresa',
            'delivery_status' => 'gestionado',
            'taken_at' => now(),
            'full_name' => 'Cliente Cierre',
            'business_name' => 'Cliente Cierre SAC',
            'representative_name' => 'Lucia Ramos',
            'dni' => '12345678',
            'ruc' => '20123456789',
            'current_operator' => 'Claro',
            'status_general' => 'contactado',
            'status_specific' => 'negociacion',
            'status_final' => 'en_seguimiento',
            'call_summary' => 'Listo para cierre.',
            'last_contact_name' => 'Lucia Ramos',
            'last_contact_phone' => '987654321',
        ]);

        $interaction = Interaction::create([
            'lead_id' => $lead->id,
            'user_id' => $executive->id,
            'campaign_id' => $campaign->id,
            'status' => 'negociacion',
            'interaction_type' => 'a_negociar',
            'status_general' => 'contactado',
            'status_specific' => 'negociacion',
            'call_detail' => 'Oferta lista.',
            'contact_name' => 'Lucia Ramos',
            'contact_phone' => '987654321',
        ]);

        $interaction->offers()->create([
            'product_type' => 'movil',
            'mobile_mode' => 'portabilidad',
            'portability_lines' => 2,
            'portability_promotion_name' => $promotion->name,
        ]);

        $focusUrl = route('work.show', [
            'focused_lead' => $lead->id,
            'open_agreement_modal' => 1,
        ]);

        $response = $this->from($focusUrl)
            ->actingAs($executive)
            ->post(route('work.accept-agreement', $lead), $this->agreementPayload([
                'portability_phone_numbers' => ['987654321', '912345678'],
            ]));

        $response->assertRedirect(route('work.show'));
        $response->assertSessionHas('success');

        $agreementInteraction = Interaction::query()
            ->where('lead_id', $lead->id)
            ->where('interaction_type', 'acuerdo_aceptado')
            ->firstOrFail();

        $this->assertDatabaseHas('interaction_offers', [
            'interaction_id' => $agreementInteraction->id,
            'product_type' => 'movil',
            'mobile_mode' => 'portabilidad',
            'portability_promotion_name' => $promotion->name,
        ]);

        $sale = Sale::query()->where('lead_id', $lead->id)->firstOrFail();

        $this->assertSame($agreementInteraction->id, $sale->interaction_id);
        $this->assertSame('acuerdo_aceptado', $sale->status);
        $this->assertSame('pendiente_supervision', $sale->management_status);
        $this->assertSame('pendiente_supervision', $sale->sisac_status);
        $this->assertSame($promotion->name, data_get($sale->products_snapshot, '0.summary_value'));
        $this->assertSame('987654321', data_get($sale->portability_phone_numbers_snapshot, '0.phone_number'));
        $this->assertSame('912345678', data_get($sale->portability_phone_numbers_snapshot, '1.phone_number'));

        $lead->refresh();

        $this->assertSame('acuerdo_aceptado', $lead->status_final);
        $this->assertSame('acuerdo_aceptado', $lead->status_specific);
    }

    public function test_executive_can_persist_multiple_agreement_attachments_when_accepting_from_work_shortcut(): void
    {
        [$executive, $campaign] = $this->createExecutiveWithCampaign();
        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');
        $promotion = $this->createPromotion('Promo Adjuntos 49.90');

        $lead = Lead::create([
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => $executive->id,
            'created_by_user_id' => $executive->id,
            'supervisor_user_id' => $supervisor->id,
            'source' => 'empresa',
            'delivery_status' => 'gestionado',
            'taken_at' => now(),
            'full_name' => 'Cliente Adjuntos',
            'business_name' => 'Cliente Adjuntos SAC',
            'representative_name' => 'Lucia Ramos',
            'dni' => '12345678',
            'ruc' => '20123456789',
            'current_operator' => 'Claro',
            'status_general' => 'contactado',
            'status_specific' => 'negociacion',
            'status_final' => 'en_seguimiento',
            'call_summary' => 'Listo para cierre con adjuntos.',
            'last_contact_name' => 'Lucia Ramos',
            'last_contact_phone' => '987654321',
        ]);

        $interaction = Interaction::create([
            'lead_id' => $lead->id,
            'user_id' => $executive->id,
            'campaign_id' => $campaign->id,
            'status' => 'negociacion',
            'interaction_type' => 'a_negociar',
            'status_general' => 'contactado',
            'status_specific' => 'negociacion',
            'call_detail' => 'Oferta lista con archivos.',
            'contact_name' => 'Lucia Ramos',
            'contact_phone' => '987654321',
        ]);

        $interaction->offers()->create([
            'product_type' => 'movil',
            'mobile_mode' => 'portabilidad',
            'portability_lines' => 2,
            'portability_promotion_name' => $promotion->name,
        ]);

        $focusUrl = route('work.show', [
            'focused_lead' => $lead->id,
            'open_agreement_modal' => 1,
        ]);

        // Disco falso: los archivos del test nunca tocan el storage real
        Storage::fake('local');

        $response = $this->from($focusUrl)
            ->actingAs($executive)
            ->post(route('work.accept-agreement', $lead), $this->agreementPayload([
                'portability_phone_numbers' => ['987654321', '912345678'],
                'agreement_attachments' => [
                    UploadedFile::fake()->image('contrato-1.png'),
                    UploadedFile::fake()->create('contrato-2.pdf', 256, 'application/pdf'),
                ],
            ]));

        $response->assertRedirect(route('work.show'));
        $response->assertSessionHas('success');

        $sale = Sale::query()->where('lead_id', $lead->id)->firstOrFail();
        $attachmentPaths = $sale->attachment_paths ?? [];

        $this->assertCount(2, $attachmentPaths);
        $this->assertTrue(collect($attachmentPaths)->every(
            fn ($path) => is_string($path) && str_starts_with($path, 'agreement-attachments/')
        ));
        $this->assertSame('pendiente_supervision', $sale->management_status);
        $this->assertSame('pendiente_supervision', $sale->sisac_status);

        foreach ($attachmentPaths as $path) {
            Storage::disk('local')->assertExists($path);
        }
    }

    public function test_work_shortcut_modal_validation_redirects_back_to_the_same_focused_lead(): void
    {
        [$executive, $campaign] = $this->createExecutiveWithCampaign();
        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');
        $promotion = $this->createPromotion('Promo Error 29.90');

        $lead = Lead::create([
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => $executive->id,
            'created_by_user_id' => $executive->id,
            'supervisor_user_id' => $supervisor->id,
            'source' => 'empresa',
            'delivery_status' => 'gestionado',
            'taken_at' => now(),
            'business_name' => 'Cliente Error SAC',
            'status_general' => 'contactado',
            'status_specific' => 'negociacion',
            'status_final' => 'en_seguimiento',
            'last_contact_name' => 'Pablo Ruiz',
            'last_contact_phone' => '987654321',
        ]);

        $interaction = Interaction::create([
            'lead_id' => $lead->id,
            'user_id' => $executive->id,
            'campaign_id' => $campaign->id,
            'status' => 'negociacion',
            'interaction_type' => 'a_negociar',
            'status_general' => 'contactado',
            'status_specific' => 'negociacion',
            'call_detail' => 'Oferta comercial.',
            'contact_name' => 'Pablo Ruiz',
            'contact_phone' => '987654321',
        ]);

        $interaction->offers()->create([
            'product_type' => 'movil',
            'mobile_mode' => 'portabilidad',
            'portability_lines' => 1,
            'portability_promotion_name' => $promotion->name,
        ]);

        $focusUrl = route('work.show', [
            'focused_lead' => $lead->id,
            'open_agreement_modal' => 1,
        ]);

        $response = $this->from($focusUrl)
            ->actingAs($executive)
            ->post(route('work.accept-agreement', $lead), $this->agreementPayload([
                'customer_ruc' => '',
                'portability_phone_numbers' => ['987654321'],
            ]));

        $response->assertRedirect($focusUrl);
        $response->assertSessionHasErrors('customer_ruc');
    }

    public function test_work_store_rejects_duplicate_portability_promotions_in_multiple_rows(): void
    {
        [$executive, $campaign] = $this->createExecutiveWithCampaign();
        $promotion = $this->createPromotion('Promo Duplicada 29.90');

        $lead = Lead::create([
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => $executive->id,
            'created_by_user_id' => $executive->id,
            'source' => 'empresa',
            'delivery_status' => 'asignado',
            'taken_at' => now(),
            'full_name' => 'Cliente Duplicado',
            'business_name' => 'Cliente Duplicado SAC',
            'status_final' => 'sin_gestion',
        ]);

        $focusUrl = route('work.show', [
            'focused_lead' => $lead->id,
        ]);

        $response = $this->from($focusUrl)
            ->actingAs($executive)
            ->post(route('work.store', $lead), [
                'contact_name' => 'Ana Perez',
                'contact_phone' => '987654321',
                'general_status' => 'contactado',
                'specific_status' => 'negociacion',
                'notes' => 'Se intenta repetir la misma promo.',
                'channel' => 'movil',
                'mobile_mode' => 'portabilidad',
                'portability_lines' => [2, 3],
                'portability_promotion_name' => [$promotion->name, $promotion->name],
                'new_lines' => [],
                'new_promotion_name' => [],
                'submit_intent' => 'register',
            ]);

        $response->assertRedirect($focusUrl);
        $response->assertSessionHasErrors([
            'portability_rows' => 'No repitas la misma promoción en Portabilidad. Si deseas más líneas para ese plan, aumenta la cantidad en una sola fila.',
        ]);

        $this->assertDatabaseMissing('interactions', [
            'lead_id' => $lead->id,
            'interaction_type' => 'a_negociar',
            'status_specific' => 'negociacion',
            'call_detail' => 'Se intenta repetir la misma promo.',
        ]);
    }

    private function createExecutiveWithCampaign(): array
    {
        $this->seed(RoleSeeder::class);

        $executive = User::factory()->create();
        $executive->assignRole('Ejecutivo');

        $campaign = Campaign::create([
            'name' => 'Campana Shortcut',
            'active' => true,
        ]);

        $executive->campaigns()->attach($campaign->id);

        return [$executive, $campaign];
    }

    private function createPromotion(string $name): PromotionName
    {
        return PromotionName::create([
            'name' => $name,
            'monthly_price' => 29.90,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function agreementPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_ruc' => '20123456789',
            'customer_business_name' => 'Cliente Corporativo SAC',
            'customer_dni' => '12345678',
            'customer_representative_name' => 'Lucia Ramos',
            'customer_phone' => '987654321',
            'customer_address' => 'Av. Principal 123',
            'customer_coordinates' => '-12.0464,-77.0428',
            'plan_code' => 'PLAN-001',
            'customer_email' => 'lucia@example.com',
            'service_channel' => 'pdv',
            'attention_time_slot' => '9 am - 11 am',
            'attention_date' => now()->addDay()->format('Y-m-d'),
            'operator_name' => 'Claro',
            'delivery_type' => 'regular',
            'fixed_agreement_supports' => [],
            'portability_phone_numbers' => [],
        ], $overrides);
    }
}
