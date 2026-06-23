<?php

namespace App\Http\Controllers;

use App\Models\HrSurvey;
use App\Models\HrSurveyRecipient;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HrSurveyController extends Controller
{
    private const LOGIN_GRACE_MINUTES = 5;

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('RRHH'), 403);

        $executives = User::query()
            ->role('Ejecutivo')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $surveys = HrSurvey::query()
            ->with(['recipients.user:id,name', 'sender:id,name'])
            ->where('sender_user_id', $user->id)
            ->latest()
            ->limit(12)
            ->get();

        return view('rrhh.surveys.index', [
            'executives' => $executives,
            'surveys' => $surveys,
            'surveysPayload' => $surveys->map(fn (HrSurvey $survey) => $this->formatSurveyForDashboard($survey))->values()->all(),
        ]);
    }

    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('RRHH'), 403);

        $surveys = HrSurvey::query()
            ->with(['recipients.user:id,name', 'sender:id,name'])
            ->where('sender_user_id', $user->id)
            ->latest()
            ->limit(12)
            ->get();

        return response()->json([
            'ok' => true,
            'surveys' => $surveys->map(fn (HrSurvey $survey) => $this->formatSurveyForDashboard($survey))->values()->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('RRHH'), 403);

        [$validated, $recipientIds, $options] = $this->validateSurveyRequest($request);

        DB::transaction(function () use ($user, $validated, $recipientIds, $options) {
            $survey = HrSurvey::create([
                'sender_user_id' => $user->id,
                'title' => filled($validated['title']) ? trim((string) $validated['title']) : null,
                'prompt' => trim((string) $validated['prompt']),
                'response_type' => $validated['response_type'],
                'options_json' => $validated['response_type'] === 'text_only' ? null : $options,
                'detail_placeholder' => $validated['response_type'] === 'option_only'
                    ? null
                    : (filled($validated['detail_placeholder'])
                        ? trim((string) $validated['detail_placeholder'])
                        : null),
                'is_active' => true,
            ]);

            foreach ($recipientIds as $recipientId) {
                HrSurveyRecipient::create([
                    'hr_survey_id' => $survey->id,
                    'user_id' => $recipientId,
                ]);
            }
        });

        return redirect()->route('rrhh.surveys.index')->with('success', 'Formulario enviado correctamente.');
    }

    public function update(Request $request, HrSurvey $survey): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('RRHH') && $survey->sender_user_id === $user->id, 403);

        [$validated, $recipientIds, $options] = $this->validateSurveyRequest($request);

        DB::transaction(function () use ($survey, $validated, $recipientIds, $options) {
            $survey->forceFill([
                'title' => filled($validated['title']) ? trim((string) $validated['title']) : null,
                'prompt' => trim((string) $validated['prompt']),
                'response_type' => $validated['response_type'],
                'options_json' => $validated['response_type'] === 'text_only' ? null : $options,
                'detail_placeholder' => $validated['response_type'] === 'option_only'
                    ? null
                    : (filled($validated['detail_placeholder'])
                        ? trim((string) $validated['detail_placeholder'])
                        : null),
                'is_active' => true,
            ])->save();

            $existingRecipients = $survey->recipients()->get()->keyBy('user_id');
            $recipientIdsCollection = collect($recipientIds);

            $survey->recipients()
                ->whereNotIn('user_id', $recipientIdsCollection->all())
                ->delete();

            foreach ($recipientIdsCollection as $recipientId) {
                $recipient = $existingRecipients->get($recipientId);

                if (!$recipient) {
                    HrSurveyRecipient::create([
                        'hr_survey_id' => $survey->id,
                        'user_id' => $recipientId,
                    ]);
                    continue;
                }

                $recipient->forceFill([
                    'displayed_at' => null,
                    'answered_at' => null,
                    'selected_option' => null,
                    'answer_detail' => null,
                ])->save();
            }
        });

        return redirect()->route('rrhh.surveys.index')->with('success', 'Formulario actualizado y reenviado correctamente.');
    }

    public function pulse(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('Ejecutivo'), 403);

        $recipient = HrSurveyRecipient::query()
            ->with(['survey.sender:id,name'])
            ->where('user_id', $user->id)
            ->whereNull('answered_at')
            ->whereHas('survey', fn ($query) => $query->where('is_active', true))
            ->latest()
            ->first();

        if ($recipient && !$this->canReceiveSurvey($request, $recipient)) {
            $recipient = null;
        }

        return response()->json([
            'ok' => true,
            'survey' => $recipient ? $this->formatSurveyRecipient($recipient) : null,
        ]);
    }

    public function markDisplayed(Request $request, HrSurveyRecipient $recipient): JsonResponse
    {
        abort_unless($recipient->user_id === $request->user()?->id, 403);

        if ($recipient->displayed_at === null) {
            $recipient->forceFill([
                'displayed_at' => now(),
            ])->save();
        }

        return response()->json(['ok' => true]);
    }

    public function answer(Request $request, HrSurveyRecipient $recipient): JsonResponse
    {
        abort_unless($recipient->user_id === $request->user()?->id, 403);
        $recipient->loadMissing('survey');

        if ($recipient->answered_at !== null) {
            return response()->json(['ok' => true]);
        }

        $survey = $recipient->survey;
        abort_if(!$survey || !$survey->is_active, 404);

        $validated = $request->validate([
            'selected_option' => ['nullable', 'string', 'max:120'],
            'answer_detail' => ['nullable', 'string', 'max:1000'],
        ]);

        $selectedOption = filled($validated['selected_option'] ?? null)
            ? trim((string) $validated['selected_option'])
            : null;
        $answerDetail = filled($validated['answer_detail'] ?? null)
            ? trim((string) $validated['answer_detail'])
            : null;

        if ($survey->response_type === 'text_only' && !$answerDetail) {
            throw ValidationException::withMessages([
                'answer_detail' => 'Ingresa un detalle para responder este formulario.',
            ]);
        }

        if ($survey->response_type !== 'text_only') {
            $allowedOptions = collect($survey->options_json ?? [])->filter()->values()->all();

            if (!$selectedOption || !in_array($selectedOption, $allowedOptions, true)) {
                throw ValidationException::withMessages([
                    'selected_option' => 'Selecciona una opción válida.',
                ]);
            }
        }

        $recipient->forceFill([
            'displayed_at' => $recipient->displayed_at ?: now(),
            'answered_at' => now(),
            'selected_option' => $survey->response_type === 'text_only' ? null : $selectedOption,
            'answer_detail' => $answerDetail,
        ])->save();

        return response()->json(['ok' => true]);
    }

    private function parseOptions(string $optionsText): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $optionsText) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function canReceiveSurvey(Request $request, HrSurveyRecipient $recipient): bool
    {
        $survey = $recipient->survey;
        $sessionAuthenticatedAt = $this->resolveSessionAuthenticatedAt($request);
        $publishedAt = $survey?->updated_at ?: $survey?->created_at;

        if (!$survey || !$sessionAuthenticatedAt || !$publishedAt) {
            return true;
        }

        if ($sessionAuthenticatedAt->lessThanOrEqualTo($publishedAt)) {
            return true;
        }

        return $sessionAuthenticatedAt->lessThanOrEqualTo(
            $publishedAt->copy()->addMinutes(self::LOGIN_GRACE_MINUTES)
        );
    }

    private function resolveSessionAuthenticatedAt(Request $request): ?Carbon
    {
        $authenticatedAt = $request->session()->get('crm_authenticated_at');

        if (!$authenticatedAt) {
            return null;
        }

        try {
            return Carbon::parse($authenticatedAt);
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function formatSurveyRecipient(HrSurveyRecipient $recipient): array
    {
        $survey = $recipient->survey;

        return [
            'id' => $recipient->id,
            'title' => $survey?->title ?: 'Consulta de RRHH',
            'prompt' => $survey?->prompt ?: '',
            'response_type' => $survey?->response_type ?: 'text_only',
            'options' => collect($survey?->options_json ?? [])->filter()->values()->all(),
            'detail_placeholder' => $survey?->detail_placeholder ?: 'Escribe un breve detalle...',
            'sender_name' => $survey?->sender?->name ?: 'RRHH',
            'mark_displayed_url' => route('rrhh.surveys.displayed', $recipient),
            'answer_url' => route('rrhh.surveys.answer', $recipient),
            'storage_key' => 'hr-survey-'.$recipient->id,
        ];
    }

    private function formatSurveyForDashboard(HrSurvey $survey): array
    {
        $recipients = $survey->recipients;
        $answeredCount = $recipients->whereNotNull('answered_at')->count();
        $recipientCount = $recipients->count();
        $pendingCount = $recipients->whereNull('answered_at')->count();
        $optionCounts = collect($survey->options_json ?? [])
            ->map(function ($option) use ($recipients) {
                return [
                    'label' => $option,
                    'count' => $recipients->where('selected_option', $option)->count(),
                ];
            })
            ->values()
            ->all();

        return [
            'id' => $survey->id,
            'title' => $survey->title ?: 'Consulta de RRHH',
            'raw_title' => $survey->title,
            'prompt' => $survey->prompt,
            'created_at' => optional($survey->created_at)->setTimezone(config('app.timezone'))->format('d/m/Y H:i'),
            'answered_count' => $answeredCount,
            'recipient_count' => $recipientCount,
            'pending_count' => $pendingCount,
            'response_type' => $survey->response_type,
            'options_text' => collect($survey->options_json ?? [])->implode("\n"),
            'option_counts' => $optionCounts,
            'detail_count' => $recipients->filter(fn ($recipient) => filled($recipient->answer_detail))->count(),
            'detail_placeholder' => $survey->detail_placeholder ?: 'Cuéntanos un breve detalle...',
            'recipient_user_ids' => $recipients->pluck('user_id')->filter()->values()->all(),
            'update_url' => route('rrhh.surveys.update', $survey),
            'recipients' => $recipients->map(function ($recipient) {
                return [
                    'name' => $recipient->user?->name ?: 'Ejecutivo',
                    'answered_at' => $recipient->answered_at?->setTimezone(config('app.timezone'))->format('d/m/Y H:i'),
                    'displayed_at' => $recipient->displayed_at?->setTimezone(config('app.timezone'))->format('d/m/Y H:i'),
                    'selected_option' => $recipient->selected_option,
                    'answer_detail' => $recipient->answer_detail,
                    'status_label' => $recipient->answered_at ? 'Respondido' : 'Pendiente',
                ];
            })->values()->all(),
        ];
    }

    private function validateSurveyRequest(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'prompt' => ['required', 'string', 'max:800'],
            'response_type' => ['required', 'string', 'in:option_only,option_with_detail,text_only'],
            'options_text' => ['nullable', 'string', 'max:1000'],
            'detail_placeholder' => ['nullable', 'string', 'max:180'],
            'recipient_user_ids' => ['required', 'array', 'min:1'],
            'recipient_user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ], [
            'prompt.required' => 'Escribe la pregunta o mensaje que verá el ejecutivo.',
            'response_type.required' => 'Selecciona el tipo de respuesta del formulario.',
            'response_type.in' => 'El tipo de respuesta seleccionado no es válido.',
            'options_text.max' => 'Las opciones no deben exceder los 1000 caracteres.',
            'detail_placeholder.max' => 'El placeholder del detalle no debe exceder los 180 caracteres.',
            'recipient_user_ids.required' => 'Selecciona al menos un ejecutivo para enviar la encuesta.',
            'recipient_user_ids.array' => 'Selecciona destinatarios válidos para la encuesta.',
            'recipient_user_ids.min' => 'Selecciona al menos un ejecutivo para enviar la encuesta.',
            'recipient_user_ids.*.integer' => 'Uno de los ejecutivos seleccionados no es válido.',
            'recipient_user_ids.*.distinct' => 'No repitas ejecutivos en la misma encuesta.',
            'recipient_user_ids.*.exists' => 'Uno de los ejecutivos seleccionados ya no está disponible.',
        ]);

        $validExecutivoCount = User::query()
            ->role('Ejecutivo')
            ->whereIn('id', $validated['recipient_user_ids'])
            ->count();

        if ($validExecutivoCount !== count($validated['recipient_user_ids'])) {
            throw ValidationException::withMessages([
                'recipient_user_ids' => 'Uno o más de los usuarios seleccionados no tiene el rol de Ejecutivo.',
            ]);
        }

        $recipientIds = $validated['recipient_user_ids'];

        $options = $this->parseOptions((string) ($validated['options_text'] ?? ''));

        if ($validated['response_type'] !== 'text_only' && count($options) < 2) {
            throw ValidationException::withMessages([
                'options_text' => 'Debes ingresar al menos 2 opciones, una por línea.',
            ]);
        }

        return [$validated, $recipientIds, $options];
    }
}
