<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Interaction;
use App\Models\Lead;
use App\Models\Sale;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Los adjuntos de acuerdos viven en storage privado y solo se sirven vía
 * ruta autorizada. Estos tests garantizan que nadie pueda descargar
 * contratos de ventas ajenas.
 */
class AgreementAttachmentDownloadTest extends TestCase
{
    use RefreshDatabase;

    private const ATTACHMENT_PATH = 'agreement-attachments/20260610120000-test.pdf';

    private function createSaleWithAttachment(): array
    {
        $this->seed(RoleSeeder::class);

        $executive = User::factory()->create();
        $executive->assignRole('Ejecutivo');

        $supervisor = User::factory()->create();
        $supervisor->assignRole('Supervisor');

        $campaign = Campaign::create(['name' => 'Campana Adjuntos', 'active' => true]);
        $executive->campaigns()->attach($campaign->id);

        $lead = Lead::create([
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => $executive->id,
            'created_by_user_id' => $executive->id,
            'supervisor_user_id' => $supervisor->id,
            'source' => 'empresa',
            'delivery_status' => 'gestionado',
            'taken_at' => now(),
            'business_name' => 'Empresa Adjuntos SAC',
            'status_general' => 'contactado',
            'status_specific' => 'acuerdo_aceptado',
            'status_final' => 'acuerdo_aceptado',
        ]);

        $interaction = Interaction::create([
            'lead_id' => $lead->id,
            'user_id' => $executive->id,
            'campaign_id' => $campaign->id,
            'status' => 'acuerdo_aceptado',
            'interaction_type' => 'a_negociar',
            'status_general' => 'contactado',
            'status_specific' => 'acuerdo_aceptado',
            'call_detail' => 'Acuerdo con adjunto.',
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
            'offered_line_count' => 1,
            'monthly_payment' => 99.90,
            'accepted_at' => now(),
            'attachment_paths' => [self::ATTACHMENT_PATH],
        ]);

        Storage::fake('local');
        Storage::disk('local')->put(self::ATTACHMENT_PATH, '%PDF-1.4 contenido de prueba');

        return [$sale, $executive, $supervisor];
    }

    private function downloadUrl(Sale $sale, ?string $filename = null): string
    {
        return route('agreements.attachments.show', [
            'sale' => $sale->id,
            'filename' => $filename ?? basename(self::ATTACHMENT_PATH),
        ]);
    }

    public function test_executive_owner_can_download_own_attachment(): void
    {
        [$sale, $executive] = $this->createSaleWithAttachment();

        $this->actingAs($executive)
            ->get($this->downloadUrl($sale))
            ->assertOk();
    }

    public function test_sale_supervisor_can_download_attachment(): void
    {
        [$sale, , $supervisor] = $this->createSaleWithAttachment();

        $this->actingAs($supervisor)
            ->get($this->downloadUrl($sale))
            ->assertOk();
    }

    public function test_mesa_de_control_can_download_attachment(): void
    {
        [$sale] = $this->createSaleWithAttachment();

        $mesa = User::factory()->create();
        $mesa->assignRole('Mesa de Control');

        $this->actingAs($mesa)
            ->get($this->downloadUrl($sale))
            ->assertOk();
    }

    public function test_unrelated_executive_cannot_download_attachment(): void
    {
        [$sale] = $this->createSaleWithAttachment();

        $otherExecutive = User::factory()->create();
        $otherExecutive->assignRole('Ejecutivo');

        $this->actingAs($otherExecutive)
            ->get($this->downloadUrl($sale))
            ->assertForbidden();
    }

    public function test_filename_not_registered_in_sale_returns_404(): void
    {
        [$sale, $executive] = $this->createSaleWithAttachment();

        $this->actingAs($executive)
            ->get($this->downloadUrl($sale, 'otro-archivo.pdf'))
            ->assertNotFound();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        [$sale] = $this->createSaleWithAttachment();

        $this->get($this->downloadUrl($sale))
            ->assertRedirect(route('login'));
    }
}
