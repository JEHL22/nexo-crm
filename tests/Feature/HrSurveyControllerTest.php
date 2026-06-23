<?php

namespace Tests\Feature;

use App\Models\HrSurvey;
use App\Models\HrSurveyRecipient;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HrSurveyControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_rrhh_user_can_view_store_and_fetch_surveys(): void
    {
        $this->seed(RoleSeeder::class);

        $rrhh = User::factory()->create();
        $rrhh->assignRole('RRHH');

        $firstExecutive = User::factory()->create();
        $firstExecutive->assignRole('Ejecutivo');

        $secondExecutive = User::factory()->create();
        $secondExecutive->assignRole('Ejecutivo');

        $this->actingAs($rrhh)
            ->get(route('rrhh.surveys.index'))
            ->assertOk()
            ->assertSee('Formularios breves');

        $this->actingAs($rrhh)
            ->post(route('rrhh.surveys.store'), [
                'title' => 'Estado diario',
                'prompt' => '¿Cómo te fue hoy?',
                'response_type' => 'option_with_detail',
                'options_text' => "Bien\nNecesito apoyo",
                'detail_placeholder' => 'Cuéntanos un breve detalle...',
                'recipient_user_ids' => [$firstExecutive->id, $secondExecutive->id],
            ])
            ->assertRedirect(route('rrhh.surveys.index'))
            ->assertSessionHas('success', 'Formulario enviado correctamente.');

        $survey = HrSurvey::query()->with('recipients')->firstOrFail();

        $this->assertSame($rrhh->id, $survey->sender_user_id);
        $this->assertSame('Estado diario', $survey->title);
        $this->assertSame('option_with_detail', $survey->response_type);
        $this->assertSame(['Bien', 'Necesito apoyo'], $survey->options_json);
        $this->assertCount(2, $survey->recipients);

        $this->actingAs($rrhh)
            ->getJson(route('rrhh.surveys.feed'))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('surveys.0.title', 'Estado diario')
            ->assertJsonPath('surveys.0.answered_count', 0)
            ->assertJsonPath('surveys.0.pending_count', 2);
    }

    public function test_rrhh_user_can_update_a_survey_and_reset_existing_answers(): void
    {
        $this->seed(RoleSeeder::class);

        $rrhh = User::factory()->create();
        $rrhh->assignRole('RRHH');

        $firstExecutive = User::factory()->create();
        $firstExecutive->assignRole('Ejecutivo');

        $secondExecutive = User::factory()->create();
        $secondExecutive->assignRole('Ejecutivo');

        $survey = HrSurvey::create([
            'sender_user_id' => $rrhh->id,
            'title' => 'Pulso inicial',
            'prompt' => '¿Cómo te sientes?',
            'response_type' => 'option_with_detail',
            'options_json' => ['Bien', 'Mal'],
            'detail_placeholder' => 'Cuéntanos más',
            'is_active' => true,
        ]);

        $answeredRecipient = HrSurveyRecipient::create([
            'hr_survey_id' => $survey->id,
            'user_id' => $firstExecutive->id,
            'displayed_at' => Carbon::now()->subMinutes(5),
            'answered_at' => Carbon::now()->subMinute(),
            'selected_option' => 'Bien',
            'answer_detail' => 'Todo en orden',
        ]);

        HrSurveyRecipient::create([
            'hr_survey_id' => $survey->id,
            'user_id' => $secondExecutive->id,
        ]);

        $this->actingAs($rrhh)
            ->put(route('rrhh.surveys.update', $survey), [
                'title' => 'Pulso actualizado',
                'prompt' => 'Cuéntanos cómo vas hoy',
                'response_type' => 'text_only',
                'detail_placeholder' => 'Escribe tu respuesta',
                'recipient_user_ids' => [$firstExecutive->id],
            ])
            ->assertRedirect(route('rrhh.surveys.index'))
            ->assertSessionHas('success', 'Formulario actualizado y reenviado correctamente.');

        $survey->refresh();
        $answeredRecipient->refresh();

        $this->assertSame('Pulso actualizado', $survey->title);
        $this->assertSame('Cuéntanos cómo vas hoy', $survey->prompt);
        $this->assertSame('text_only', $survey->response_type);
        $this->assertNull($survey->options_json);
        $this->assertSame('Escribe tu respuesta', $survey->detail_placeholder);

        $this->assertNull($answeredRecipient->displayed_at);
        $this->assertNull($answeredRecipient->answered_at);
        $this->assertNull($answeredRecipient->selected_option);
        $this->assertNull($answeredRecipient->answer_detail);
        $this->assertDatabaseMissing('hr_survey_recipients', [
            'hr_survey_id' => $survey->id,
            'user_id' => $secondExecutive->id,
        ]);
    }
}
