<?php

namespace App\Http\Controllers;

use App\Models\Interaction;
use App\Models\Lead;
use App\Models\PromoDocument;
use App\Models\PromotionName;
use App\Models\ReminderNotification;
use App\Models\Sale;
use App\Models\User;
use App\Support\PromotionRows;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WorkController extends MyWorkController
{
    private const GENERAL = [
        'contactado' => 'Contactado',
        'no_contactado' => 'No contactado',
    ];

    private const SPECIFIC = [
        'contactado' => [
            'reprogramado' => 'Reprogramado',
            'negociacion' => 'Negociación',
            'no_desea' => 'No desea',
        ],
        'no_contactado' => [
            'no_contesta' => 'No contesta',
            'telefono_apagado' => 'Teléfono apagado',
            'no_existe' => 'No existe',
        ],
    ];

    private const ENABLE_COMMERCIAL = ['negociacion'];

    private const EXECUTIVE_STATUS_GAUGE_LABELS = [
        'reprogramado' => 'Reprogramados',
        'negociacion' => 'Negociación',
    ];

    private const NO_CONTACT_REASSIGN_STATUSES = [
        'no_contesta',
        'telefono_apagado',
    ];

    private const NO_CONTACT_DISABLE_THRESHOLD = 3;

    private const NO_CONTACT_RELEASE_DELAY_MINUTES = 10;

    private const INITIAL_SPEECH_CASE_KEY = 'accesible';

    private const AGREEMENT_ERROR_FIELDS = [
        'agreement',
        'customer_ruc',
        'customer_business_name',
        'customer_dni',
        'customer_representative_name',
        'customer_phone',
        'customer_address',
        'customer_coordinates',
        'plan_code',
        'customer_email',
        'service_channel',
        'attention_time_slot',
        'attention_date',
        'operator_name',
        'delivery_type',
        'fixed_agreement_supports',
        'fixed_agreement_supports.*',
        'portability_phone_numbers',
        'portability_phone_numbers.*',
        'agreement_attachments',
        'agreement_attachments.*',
    ];

    public function show($lead = null)
    {
        $request = request();
        $user = Auth::user();
        $campaignId = $user->campaigns()->value('campaigns.id');
        $hasSisacTable = Schema::hasTable('lead_sisac_data');
        $focusedLeadId = $request->integer('focused_lead');
        $isFocusedLeadMode = false;

        if (! $campaignId) {
            abort(403, 'No tienes una campaña asignada.');
        }

        $lead = null;

        if ($focusedLeadId > 0) {
            $lead = $this->resolveFocusedLead($campaignId, $user->id, $focusedLeadId, $hasSisacTable);
            $isFocusedLeadMode = $lead !== null;
        }

        if (! $lead) {
            $leadQuery = Lead::query()
                ->with(['phones' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('id')])
                ->where('campaign_id', $campaignId)
                ->where('assigned_to_user_id', $user->id)
                ->whereNull('disabled_at')
                ->whereIn('delivery_status', ['asignado', 'disponible'])
                ->whereNotIn('status_final', [Lead::FINAL_CLOSED_NO_SALE, Lead::FINAL_AGREEMENT_ACCEPTED])
                ->orderBy('delivery_status', 'asc')
                ->orderBy('updated_at', 'asc');

            if ($hasSisacTable) {
                $leadQuery->with('sisacData');
            }

            $lead = $leadQuery->first();

            if ($lead && $lead->delivery_status === 'disponible') {
                $lead->delivery_status = 'asignado';
                $lead->taken_at = now();
                $lead->save();
            }
        }

        if (! $lead && ! $isFocusedLeadMode) {
            $lead = $this->assignNextLead($campaignId, $user->id);
        }

        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek(Carbon::MONDAY)->startOfDay();

        $interactionMetricsQuery = Interaction::query()
            ->where('user_id', $user->id)
            ->where('interaction_type', 'a_negociar');

        $contactadosHoy = (clone $interactionMetricsQuery)
            ->where('created_at', '>=', $today)
            ->where('status_general', 'contactado')
            ->count();

        $dailyStatusTotals = (clone $interactionMetricsQuery)
            ->selectRaw('status_specific, COUNT(*) as total')
            ->where('created_at', '>=', $today)
            ->whereIn('status_specific', array_keys(self::EXECUTIVE_STATUS_GAUGE_LABELS))
            ->groupBy('status_specific')
            ->pluck('total', 'status_specific')
            ->toArray();

        $weeklyStatusTotals = (clone $interactionMetricsQuery)
            ->selectRaw('status_specific, COUNT(*) as total')
            ->where('created_at', '>=', $weekStart)
            ->whereIn('status_specific', array_keys(self::EXECUTIVE_STATUS_GAUGE_LABELS))
            ->groupBy('status_specific')
            ->pluck('total', 'status_specific')
            ->toArray();

        $acuerdosAceptadosHoy = Sale::query()
            ->where('executive_user_id', $user->id)
            ->where('status', Sale::STATUS_ACCEPTED)
            ->where('accepted_at', '>=', $today)
            ->count();

        $dailySalesGoal = $this->dashboardGoalDailyPerExecutive('acuerdo_aceptado');
        $salesGauge = $this->buildGaugeData(
            'acuerdo_aceptado',
            $this->formatGaugeLabel('Meta de ventas diarias', $dailySalesGoal),
            $acuerdosAceptadosHoy,
            $dailySalesGoal
        );

        $dailyStatusGauges = collect(self::EXECUTIVE_STATUS_GAUGE_LABELS)
            ->map(fn (string $label, string $key) => $this->buildGaugeData(
                $key,
                $this->formatGaugeLabel($label, $this->dashboardGoalDailyPerExecutive($key)),
                (int) ($dailyStatusTotals[$key] ?? 0),
                $this->dashboardGoalDailyPerExecutive($key)
            ))
            ->values()
            ->all();

        $weeklyStatusGauges = [
            $this->buildGaugeData(
                'reprogramado_semanal',
                $this->formatGaugeLabel('Reprogramados semanal', $this->dashboardGoalWeeklyPerExecutive('reprogramado')),
                (int) ($weeklyStatusTotals['reprogramado'] ?? 0),
                $this->dashboardGoalWeeklyPerExecutive('reprogramado')
            ),
            $this->buildGaugeData(
                'negociacion_semanal',
                $this->formatGaugeLabel('Negociación semanal', $this->dashboardGoalWeeklyPerExecutive('negociacion')),
                (int) ($weeklyStatusTotals['negociacion'] ?? 0),
                $this->dashboardGoalWeeklyPerExecutive('negociacion')
            ),
        ];

        $promoDocuments = Schema::hasTable('promo_documents')
            ? PromoDocument::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get(['id', 'badge', 'title', 'pdf_path', 'sort_order'])
            : collect();

        $promotionNames = Schema::hasTable('promotion_names')
            ? PromotionName::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'monthly_price', 'sort_order'])
            : collect();

        $agreementViewData = $lead
            ? $this->buildAgreementViewData($lead, [
                'portability_promotion_name' => collect($lead->interactions->sortByDesc('created_at')->first()?->offers ?? [])
                    ->where('mobile_mode', 'portabilidad')
                    ->pluck('portability_promotion_name')
                    ->filter(fn ($value) => filled($value))
                    ->values()
                    ->all(),
                'new_promotion_name' => collect($lead->interactions->sortByDesc('created_at')->first()?->offers ?? [])
                    ->where('mobile_mode', 'alta_nueva')
                    ->pluck('new_promotion_name')
                    ->filter(fn ($value) => filled($value))
                    ->values()
                    ->all(),
            ])
            : [
                'agreementProducts' => [],
                'agreementPortabilityRows' => [],
                'agreementDraft' => [],
                'fixedAgreementSupportOptions' => [],
                'existingAgreement' => null,
            ];

        $openAgreementModalRequested = $request->boolean('open_agreement_modal');
        $validationErrors = session()->get('errors') ?: new ViewErrorBag;

        return view('work.show', array_merge([
            'lead' => $lead,
            'hasSisacTable' => $hasSisacTable,
            'generalOptions' => self::GENERAL,
            'specificOptions' => self::SPECIFIC,
            'enableCommercial' => self::ENABLE_COMMERCIAL,
            'contactadosHoy' => $contactadosHoy,
            'salesGauge' => $salesGauge,
            'dailyStatusGauges' => $dailyStatusGauges,
            'weeklyStatusGauges' => $weeklyStatusGauges,
            'promoDocuments' => $promoDocuments,
            'promotionNames' => $promotionNames,
            'agreementSubmitUrl' => $lead ? route('work.accept-agreement', ['lead' => $lead->id]) : null,
            'isFocusedLeadMode' => $isFocusedLeadMode,
            'agreementModalExitUrl' => route('work.show'),
            'openAgreementModalRequested' => $openAgreementModalRequested,
            'serverNowIso' => now()->setTimezone(config('app.timezone'))->toIso8601String(),
            'serverNowLocalValue' => now()->setTimezone(config('app.timezone'))->format('Y-m-d\TH:i'),
        ], $agreementViewData, [
            'promotionNames' => $promotionNames,
        ],
            $this->buildHeaderProfileData($user),
            $this->buildSpeechReferenceData(),
            $this->buildLeadPresentationData($lead, $hasSisacTable),
            $this->buildOldOfferRowsData(),
            $this->buildOfferErrorData($validationErrors),
            $this->buildAgreementUiState($agreementViewData, $validationErrors, $openAgreementModalRequested)
        ));
    }

    private function dashboardGoalWeeklyPerExecutive(string $metric): int
    {
        $weeklyGoals = config('dashboard_goals.weekly_per_executive', []);

        if (! array_key_exists($metric, $weeklyGoals)) {
            throw new \RuntimeException('No se encontró la meta semanal configurada para el dashboard: '.$metric);
        }

        return max(0, (int) $weeklyGoals[$metric]);
    }

    private function dashboardGoalDailyPerExecutive(string $metric): int
    {
        $dailyGoals = config('dashboard_goals.daily_per_executive', []);

        if (array_key_exists($metric, $dailyGoals)) {
            return max(0, (int) $dailyGoals[$metric]);
        }

        $weeklyGoal = $this->dashboardGoalWeeklyPerExecutive($metric);

        if ($weeklyGoal % 5 !== 0) {
            throw new \RuntimeException('La meta semanal debe ser divisible entre 5 para calcular meta diaria: '.$metric);
        }

        return intdiv($weeklyGoal, 5);
    }

    private function formatGaugeLabel(string $label, int $goal): string
    {
        return $label.' - '.number_format($goal);
    }

    private function buildGaugeData(string $key, string $label, int $value, int $goal): array
    {
        $safeGoal = max(1, $goal);
        $progress = min($value / $safeGoal, 1);

        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'goal' => $safeGoal,
            'angle' => -90 + (180 * $progress),
        ];
    }

    private function buildHeaderProfileData(?User $user): array
    {
        return [
            'headerUser' => $user,
            'headerProfilePhotoUrl' => $user?->profilePhotoUrl(),
            'headerUserInitials' => $user?->initials() ?: 'U',
        ];
    }

    private function buildSpeechReferenceData(): array
    {
        return [
            'speechCases' => $this->speechCases(),
            'initialSpeechCaseKey' => self::INITIAL_SPEECH_CASE_KEY,
            'initialSpeechOpenStages' => [
                self::INITIAL_SPEECH_CASE_KEY => 0,
            ],
        ];
    }

    private function buildLeadPresentationData(?Lead $lead, bool $hasSisacTable): array
    {
        if (! $lead) {
            return [
                'phones' => collect(),
                'phone' => null,
                'repName' => '-',
                'sisacData' => null,
                'direccionFiscal' => '-',
                'segmento' => '-',
                'velocidadMax' => '-',
                'paquete' => '-',
                'tecnologia' => '-',
            ];
        }

        $phones = $lead->phones->sortByDesc('is_primary')->values();

        return [
            'phones' => $phones,
            'phone' => optional($phones->first())->phone,
            'repName' => $lead->representative_name ?: ($lead->full_name ?: '-'),
            'sisacData' => $hasSisacTable ? $lead->sisacData : null,
            'direccionFiscal' => $lead->fiscal_address ?: '-',
            'segmento' => $lead->segment ?: '-',
            'velocidadMax' => $lead->max_speed ?: '-',
            'paquete' => $lead->package ?: '-',
            'tecnologia' => $lead->technology ?: '-',
        ];
    }

    private function buildOldOfferRowsData(): array
    {
        return [
            'oldPortabilityRows' => $this->buildOfferRows(old('portability_lines'), old('portability_promotion_name')),
            'oldNewRows' => $this->buildOfferRows(old('new_lines'), old('new_promotion_name')),
        ];
    }

    private function buildOfferRows($lines, $promotions): array
    {
        $lines = is_array($lines) ? array_values($lines) : (filled($lines) ? [$lines] : []);
        $promotions = is_array($promotions) ? array_values($promotions) : (filled($promotions) ? [$promotions] : []);
        $total = max(count($lines), count($promotions), 1);
        $rows = [];

        for ($index = 0; $index < $total; $index++) {
            $rows[] = [
                'lines' => $lines[$index] ?? '',
                'promotion_name' => $promotions[$index] ?? '',
            ];
        }

        return $rows;
    }

    private function buildOfferErrorData(ViewErrorBag $errors): array
    {
        return [
            'portabilityOfferError' => $this->offerSectionError($errors, 'portability_rows', ['portability_lines_', 'portability_promotion_']),
            'newOfferError' => $this->offerSectionError($errors, 'new_rows', ['new_lines_', 'new_promotion_']),
        ];
    }

    private function offerSectionError(ViewErrorBag $errors, string $groupKey, array $prefixes): ?string
    {
        if ($errors->has($groupKey)) {
            return $errors->first($groupKey);
        }

        foreach ($errors->getMessages() as $field => $messages) {
            foreach ($prefixes as $prefix) {
                if (Str::startsWith($field, $prefix) && ! empty($messages[0])) {
                    return $messages[0];
                }
            }
        }

        return null;
    }

    private function buildAgreementUiState(array $agreementViewData, ViewErrorBag $errors, bool $openAgreementModalRequested): array
    {
        $existingAgreement = $agreementViewData['existingAgreement'] ?? null;
        $agreementErrors = $errors->hasAny(self::AGREEMENT_ERROR_FIELDS);
        $agreementProductType = collect($agreementViewData['agreementProducts'] ?? [])->pluck('type')->unique()->values();

        return [
            'agreementLocked' => (bool) ($existingAgreement && in_array($existingAgreement->supervisor_validation_status, ['pendiente', 'validado'], true)),
            'agreementErrors' => $agreementErrors,
            'agreementProductType' => $agreementProductType,
            'isFixedOnlyAgreement' => $agreementProductType->count() === 1 && $agreementProductType->first() === 'fijo',
            'requiresFixedAgreementSupport' => $agreementProductType->contains('fijo'),
            'agreementPortabilityRows' => collect($agreementViewData['agreementPortabilityRows'] ?? [])->values(),
            'shouldOpenAgreementModal' => $agreementErrors || $openAgreementModalRequested || session('open_agreement_modal'),
        ];
    }

    private function speechCases(): array
    {
        return [
            [
                'key' => 'accesible',
                'label' => 'Cliente accesible',
                'tone' => 'emerald',
                'description' => 'Cliente abierto a conversar, valida rápido la propuesta y permite avanzar hacia el cierre con menos fricción.',
                'stages' => [
                    [
                        'label' => 'Presentación',
                        'focus' => 'Abrir con seguridad, validar identidad y confirmar que hablas con quien decide.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => 'Buenas tardes, ¿hablo con José Luis de Microfarma?'],
                            ['speaker' => 'Cliente', 'text' => 'Buenas tardes, sí, con él habla.'],
                            ['speaker' => 'Asesor', 'text' => 'Hola, José. Es un gusto saludarte. Te habla Edson Vega, ejecutivo corporativo de Claro. Entiendo que tú eres el encargado de las líneas corporativas de la empresa.'],
                            ['speaker' => 'Cliente', 'text' => 'Hola, Edson. Efectivamente. ¿En qué te puedo ayudar?'],
                        ],
                    ],
                    [
                        'label' => 'Motivo',
                        'focus' => 'Presentar la campaña como una mejora concreta en costo y beneficios.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => 'Te contacto porque tenemos una campaña para Microfarma en la cual vas a tener una mejora tanto en datos y minutos como una reducción de costo por línea.'],
                            ['speaker' => 'Cliente', 'text' => '¿Y cómo es que Microfarma accede a esa campaña?'],
                            ['speaker' => 'Asesor', 'text' => 'Es por la cantidad de líneas y por el tipo de rubro.'],
                            ['speaker' => 'Cliente', 'text' => 'Ok...'],
                        ],
                    ],
                    [
                        'label' => 'Sondeo',
                        'focus' => 'Actualizar la base, validar líneas activas y entender el uso real de cada grupo.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => 'Para continuar con la propuesta, ¿las 12 líneas que me figuran en sistema aún están activas con tu actual operador?'],
                            ['speaker' => 'Cliente', 'text' => 'No, solo tenemos 10 activas y 2 están suspendidas.'],
                            ['speaker' => 'Asesor', 'text' => 'Entiendo. ¿Y qué uso les das a estas 10 líneas? Quizás para ventas, delivery o administrativo.'],
                            ['speaker' => 'Cliente', 'text' => 'Son 5 líneas para ventas, 2 para reparto y 3 para administrativo.'],
                        ],
                    ],
                    [
                        'label' => 'Detección de necesidades',
                        'focus' => 'Llevar la conversación al dolor operativo principal por área.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => 'Excelente, José. Y dime, ¿cuál de todas las problemáticas que tienes actualmente con tu operador es la que más te está afectando? Quizás la conectividad, la señal a la hora de llamar o la falta de datos.'],
                            ['speaker' => 'Cliente', 'text' => 'Depende del área. Con ventas tengo problemas con las llamadas por la señal, con los chicos de delivery la falta de datos por el consumo en Waze y, bueno, la conectividad deja mucho que desear.'],
                        ],
                    ],
                    [
                        'label' => 'Presentación de oferta',
                        'focus' => 'Conectar directamente el plan con los dolores que el cliente ya reconoció.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => 'Entiendo tu molestia, José. Yo te puedo ofrecer la solución con nuestro plan Max Negocios de S/ 55.90, el cual te da minutos nacionales e internacionales y SMS ilimitados, 75 GB y 105 GB por 6 meses por tu portabilidad. A eso súmale que la conectividad y la señal por tu zona es mucho mejor, ya que Claro está teniendo picos muy altos de conectividad. Eso te daría la confianza de hacer tu gestión diaria sin problemas.'],
                            ['speaker' => 'Cliente', 'text' => 'Entiendo, pero el precio me parece muy alto para lo que estoy pagando hoy.'],
                        ],
                    ],
                    [
                        'label' => 'Manejo de objeciones',
                        'focus' => 'Responder precio y soporte sin perder el control de la conversación.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => 'Manejemos esa objeción. ¿Te parece si por hacer tu portabilidad te doy un 50% por todas las líneas? Es decir, ya no pagarías S/ 55.90 sino S/ 27.95. Eso es mucho menos de lo que pagas actualmente.'],
                            ['speaker' => 'Cliente', 'text' => 'Sí, pero ¿qué pasa si tengo alguna caída de sistema o algún percance con las líneas?'],
                            ['speaker' => 'Asesor', 'text' => 'Las caídas se dan con cualquier operador, José. Sin embargo, con nosotros vas a tener soporte 24/07 y, por mi parte, tendrás la ayuda de postventa ante cualquier consulta para darte la solución que necesitas.'],
                            ['speaker' => 'Cliente', 'text' => 'Me queda claro entonces.'],
                        ],
                    ],
                    [
                        'label' => 'Cierre de venta',
                        'focus' => 'Cerrar con inmediatez, documentos concretos y expectativa operativa clara.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => 'Listo, José. Entonces ingreso los datos al sistema y programamos la visita del courier con la entrega de tus chips.'],
                            ['speaker' => 'Cliente', 'text' => '¿Y para cuándo tendría los chips operativos?'],
                            ['speaker' => 'Asesor', 'text' => 'Sería para hoy mismo. Termino de ingresar los datos y tendrás los chips operativos.'],
                            ['speaker' => 'Cliente', 'text' => 'Ok, quedo a la espera de la comunicación.'],
                            ['speaker' => 'Asesor', 'text' => 'Lo último que necesitaría sería una foto de tu DNI por cada lado y un recibo de servicio de tu antiguo operador.'],
                            ['speaker' => 'Cliente', 'text' => 'Listo, te lo envío.'],
                            ['speaker' => 'Asesor', 'text' => 'Bienvenido a Claro, José, y que tengas buen día.'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'intermedio',
                'label' => 'Cliente intermedio',
                'tone' => 'amber',
                'description' => 'Cliente con resistencia inicial y poco tiempo. Hay apertura, pero exige precisión, rapidez y sustento claro.',
                'stages' => [
                    [
                        'label' => 'Presentación',
                        'focus' => 'Romper la barrera inicial sin discutir ni alargar demasiado la entrada.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => 'Buenas tardes, ¿me comunico con Emilia?'],
                            ['speaker' => 'Cliente', 'text' => 'Habla Emilia. ¿Con quién hablo?'],
                            ['speaker' => 'Asesor', 'text' => 'Hola, Emilia. Te habla Edson Vega, asesor corporativo de Claro.'],
                            ['speaker' => 'Cliente', 'text' => 'Hola, Edson. Desde ya te digo que no necesito ningún producto de Claro.'],
                        ],
                    ],
                    [
                        'label' => 'Motivo',
                        'focus' => 'Ganar atención aterrizando el beneficio a la empresa y no solo a la persona.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => 'Quizás no para ti, Emilia, pero sí para tu empresa. Llamo específicamente por las 4 líneas que son de Luminus Spa. Tengo entendido que eres la dueña.'],
                            ['speaker' => 'Cliente', 'text' => '¡Exactamente!'],
                            ['speaker' => 'Asesor', 'text' => 'Entonces hablo con la persona indicada. Emilia, vengo con una propuesta que te reducirá costos y te ayudará con la llegada a tus clientes.'],
                            ['speaker' => 'Cliente', 'text' => 'Tengo buena llegada a mis clientes, así que por ese lado no requiero ayuda.'],
                            ['speaker' => 'Asesor', 'text' => 'Entonces déjame hablarte de la reducción de costos.'],
                            ['speaker' => 'Cliente', 'text' => 'Escucho, pero eso sí: abro mi spa en 15 minutos. Tiempo es lo que no tengo.'],
                        ],
                    ],
                    [
                        'label' => 'Sondeo',
                        'focus' => 'Tomar solo la información mínima necesaria para construir una propuesta rápida.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => 'Más que suficiente, Emilia. ¿El costo aproximado de esas cuatro líneas será de 300 soles?'],
                            ['speaker' => 'Cliente', 'text' => 'Mi línea y la de mi administradora son de 39 soles y la de los chicos de MKT es de 59 soles, pero no te voy a dar más información.'],
                            ['speaker' => 'Asesor', 'text' => 'Es lo que necesito para armar una oferta para ti. Entiendo que MKT tiene un mayor costo por los datos y las redes sociales.'],
                            ['speaker' => 'Cliente', 'text' => 'Sí.'],
                        ],
                    ],
                    [
                        'label' => 'Detección de necesidades',
                        'focus' => 'Aterrizar el problema real para que el cliente se vea reflejado en la propuesta.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => 'Lo que tú necesitas es un plan que te genere menos costo y, al mismo tiempo, datos que cubran esa navegación por redes.'],
                            ['speaker' => 'Cliente', 'text' => 'Por así decirlo, sí.'],
                            ['speaker' => 'Asesor', 'text' => 'Eso quiere decir que has presentado cortes con tu navegación y has tenido que comprar paquetes de megas.'],
                            ['speaker' => 'Cliente', 'text' => 'En algunas ocasiones. Edson, esto se está alargando mucho y debo cortar.'],
                            ['speaker' => 'Asesor', 'text' => 'Solo necesito un minuto. Con esos datos ya tengo la oferta comercial lista para ti.'],
                            ['speaker' => 'Cliente', 'text' => 'Te escucho.'],
                        ],
                    ],
                    [
                        'label' => 'Presentación de oferta',
                        'focus' => 'Proponer una solución mixta por perfiles de uso y dejar claro el diferencial de beneficios.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => 'Entiendo que los dos primeros planes solo te dan llamadas y cierta cantidad mínima de megas.'],
                            ['speaker' => 'Cliente', 'text' => 'Sí.'],
                            ['speaker' => 'Asesor', 'text' => 'Te ofrezco lo siguiente: dos planes de S/ 59.90 con minutos nacionales e internacionales y SMS ilimitados, 75 GB y 105 GB por 6 meses. Y para MKT, dos planes de S/ 69.90. Esto, adicional al plan anterior, viene con internet ilimitado, pero 110 GB de navegación en alta velocidad. Son dos planes mucho más completos de los que actualmente tienes.'],
                            ['speaker' => 'Cliente', 'text' => 'Pero, Edson, todo me sale casi S/ 260 cuando pago S/ 196. No me conviene para nada en costos.'],
                        ],
                    ],
                    [
                        'label' => 'Manejo de objeciones',
                        'focus' => 'Resolver ahorro y estabilidad contractual con respuestas concretas y sin rodeos.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => '¿Y si te doy un 50% de descuento, no en uno sino en todos los planes que portes con nosotros? El monto sería de S/ 130. Es más de S/ 60 de ahorro.'],
                            ['speaker' => 'Cliente', 'text' => '¿Pero ese es el monto fijo?'],
                            ['speaker' => 'Asesor', 'text' => 'Es fijo por todo el tiempo del contrato, que es de 12 meses.'],
                            ['speaker' => 'Cliente', 'text' => '¿Y pasados los 12 meses? ¿El precio y los beneficios se mantienen?'],
                            ['speaker' => 'Asesor', 'text' => 'El contrato es renovable. De mantener el pago regular, el área de retención se comunicará contigo.'],
                            ['speaker' => 'Cliente', 'text' => 'Pero por el tiempo del contrato, ¿todo se mantiene? Ya me han estafado antes y no quiero sorpresas.'],
                        ],
                    ],
                    [
                        'label' => 'Cierre de venta',
                        'focus' => 'Bajar el cierre a respaldo documental y siguiente acción concreta.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => 'Emilia, en tu contrato vas a tener todas las especificaciones y, tal cual como te he mencionado, los beneficios.'],
                            ['speaker' => 'Cliente', 'text' => 'De igual forma necesito un respaldo.'],
                            ['speaker' => 'Asesor', 'text' => 'Luego de pasar el biométrico con el courier, a tu correo te llegará el contrato digital.'],
                            ['speaker' => 'Cliente', 'text' => '¿Y qué es lo que necesitas?'],
                            ['speaker' => 'Asesor', 'text' => 'Solo una foto de tu DNI por ambos lados y un recibo de servicio de tu comercio.'],
                            ['speaker' => 'Cliente', 'text' => 'Te lo envío luego de abrir mi tienda.'],
                            ['speaker' => 'Asesor', 'text' => 'Listo, quedo a la espera. Que tengas un buen día.'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'dificil',
                'label' => 'Cliente difícil',
                'tone' => 'slate',
                'description' => 'Cliente desafiante, corrige datos, cuestiona la oferta y pone condiciones. Requiere control, firmeza y precisión.',
                'stages' => [
                    [
                        'label' => 'Presentación',
                        'focus' => 'Recuperar la llamada aunque el cliente entre a la defensiva desde el inicio.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => 'Buenas tardes, ¿me comunico con Gerónimo?'],
                            ['speaker' => 'Cliente', 'text' => 'Número equivocado. ¿Quién habla?'],
                            ['speaker' => 'Asesor', 'text' => 'Mi nombre es Edson Vega. Quería comunicarme con Gerónimo Richetti, de la empresa Gold Level.'],
                            ['speaker' => 'Cliente', 'text' => 'Él habla. ¿Qué desea?'],
                        ],
                    ],
                    [
                        'label' => 'Motivo',
                        'focus' => 'Sostener la conversación aunque el cliente quiera cortar de inmediato.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => 'Buenas tardes, sr. Richetti. Quiero conversar con usted sobre sus líneas corporativas.'],
                            ['speaker' => 'Cliente', 'text' => 'No tengo tiempo. Llame otro día.'],
                            ['speaker' => 'Asesor', 'text' => 'Con gusto, sr. Richetti, pero tenemos para Gold Level un plan acorde a sus necesidades.'],
                            ['speaker' => 'Cliente', 'text' => 'Pero si no te he dado ningún dato ni información.'],
                        ],
                    ],
                    [
                        'label' => 'Sondeo',
                        'focus' => 'Actualizar la base rápido y convertir la corrección del cliente en información útil.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => 'Usted tiene 8 líneas activas con planes de 89 soles en Movistar, ¿cierto?'],
                            ['speaker' => 'Cliente', 'text' => 'Error. Son 6 líneas: 5 de 79 soles y la mía que es de 250. En lo único que tienes razón es en lo de Movistar.'],
                            ['speaker' => 'Asesor', 'text' => 'Tendré que actualizar mi base entonces. Imagino que su plan es por los datos y las llamadas.'],
                            ['speaker' => 'Cliente', 'text' => 'Viajo.'],
                            ['speaker' => 'Asesor', 'text' => 'Listo, entonces es por el roaming.'],
                            ['speaker' => 'Cliente', 'text' => 'Por lo que sea, le repito que no necesito el servicio.'],
                        ],
                    ],
                    [
                        'label' => 'Detección de necesidades',
                        'focus' => 'Ir directo al punto sensible y obtener una ventana corta para proponer.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => 'Sr. Richetti, seré directo: empresas como la suya siempre necesitan mejorar.'],
                            ['speaker' => 'Cliente', 'text' => 'Por supuesto.'],
                            ['speaker' => 'Asesor', 'text' => 'Y estoy seguro de que su roaming no cumple con sus expectativas, teniendo problemas en su gestión.'],
                            ['speaker' => 'Cliente', 'text' => 'Al fin acertó en algo.'],
                            ['speaker' => 'Asesor', 'text' => 'Y en los planes de sus colaboradores podríamos darle una reducción extra en el costo.'],
                            ['speaker' => 'Cliente', 'text' => 'Tienes 2 minutos.'],
                        ],
                    ],
                    [
                        'label' => 'Presentación de oferta',
                        'focus' => 'Mostrar beneficios premium y ahorro sin perder autoridad frente al cliente.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => 'Plan de S/ 69.90 con minutos nacionales e internacionales, SMS y navegación ilimitados, con 110 GB de navegación de alta velocidad. Y para usted, lo antes mencionado pero con 200 GB en alta velocidad y el uso del roaming con 750 minutos en llamada, 25 GB de internet y 1000 SMS, en más de 15 países europeos, toda Sudamérica a excepción de Venezuela, Estados Unidos, Canadá y Centroamérica.'],
                            ['speaker' => 'Cliente', 'text' => 'Ok.'],
                            ['speaker' => 'Asesor', 'text' => 'Sumado a toda esta mejora en beneficios, los planes mencionados vienen a mitad de precio.'],
                            ['speaker' => 'Cliente', 'text' => '¿Para siempre?'],
                            ['speaker' => 'Asesor', 'text' => 'Por el tiempo de contrato, que es de 12 meses.'],
                            ['speaker' => 'Cliente', 'text' => 'Entonces ahí está el truco: termina mi contrato y termina la promoción.'],
                        ],
                    ],
                    [
                        'label' => 'Manejo de objeciones',
                        'focus' => 'Responder con firmeza la renovación, penalidades y respaldo documental.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => 'Sr. Richetti, los contratos son renovables y, siendo sinceros, mientras usted mantenga el pago al día, retenciones tendrá una oferta comercial para usted.'],
                            ['speaker' => 'Cliente', 'text' => '¿Y toda esta maravilla me la darán así nomás? ¿Qué hago con mi penalidad por los equipos?'],
                            ['speaker' => 'Asesor', 'text' => 'Los documentos se enviarán por correo para que usted tenga el sustento y la seguridad de lo adquirido.'],
                            ['speaker' => 'Cliente', 'text' => '¿Y las penalidades?'],
                            ['speaker' => 'Asesor', 'text' => 'Osiptel, el ente que nos regula, no indica nada sobre las penalidades. Puede usted consultarlo en su página.'],
                            ['speaker' => 'Cliente', 'text' => 'Ok.'],
                        ],
                    ],
                    [
                        'label' => 'Cierre de venta',
                        'focus' => 'Mantener el control del cierre, pero respetando las condiciones del cliente.',
                        'conversation' => [
                            ['speaker' => 'Asesor', 'text' => 'Sr. Richetti, ya habiendo solucionado sus objeciones, paso a ingresar los datos al sistema.'],
                            ['speaker' => 'Cliente', 'text' => 'Prefiero conversarlo y te tendría una respuesta mañana o en la semana.'],
                            ['speaker' => 'Asesor', 'text' => 'Sr. Gerónimo, siendo usted el dueño, creo que está en la facultad para decidir. Además, si hago el ingreso de datos hoy, por la tarde ya tendría los chips listos para la entrega y operativos.'],
                            ['speaker' => 'Cliente', 'text' => '¿Los tendrás para hoy con todo lo mencionado?'],
                            ['speaker' => 'Asesor', 'text' => 'Definitivamente.'],
                            ['speaker' => 'Cliente', 'text' => '¿Qué necesitas?'],
                            ['speaker' => 'Asesor', 'text' => 'Algo simple: solo su foto por ambos lados de su DNI y un recibo de su antiguo operador.'],
                            ['speaker' => 'Cliente', 'text' => 'Mi secretaria te enviará la documentación y, por favor, que la llamada sea en exactamente media hora y no demore más de 10 minutos.'],
                            ['speaker' => 'Asesor', 'text' => 'Listo, Richetti. Le haré las indicaciones que me menciona a la srta. de legal para la grabación. Que tenga buen día.'],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function store(Request $request, int $leadId)
    {
        $user = Auth::user();
        $submitIntent = $request->input('submit_intent', 'register');

        $validated = $request->validate([
            'contact_name' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[\pL\s\'.-]+$/u'],
            'contact_phone' => ['required', 'string', 'digits:9'],
            'general_status' => 'required|string|in:contactado,no_contactado',
            'specific_status' => 'required|string',
            'next_contact_at' => 'nullable|required_if:specific_status,reprogramado|date_format:Y-m-d\TH:i|after:now',
            'notes' => 'required|string|max:5000',

            'channel' => 'nullable|string|in:movil,fijo,movil_fijo',
            'mobile_mode' => 'nullable|string|in:portabilidad,alta_nueva,porta_alta',
            'portability_lines' => 'nullable|array|max:20',
            'portability_lines.*' => 'nullable|integer|min:1|max:999',
            'portability_promotion_name' => 'nullable|array|max:20',
            'portability_promotion_name.*' => ['nullable', 'string', 'max:160', Rule::exists('promotion_names', 'name')],
            'new_lines' => 'nullable|array|max:20',
            'new_lines.*' => 'nullable|integer|min:1|max:999',
            'new_promotion_name' => 'nullable|array|max:20',
            'new_promotion_name.*' => ['nullable', 'string', 'max:160', Rule::exists('promotion_names', 'name')],
            'internet_speed' => 'nullable|string|max:255',
            'fixed_monthly' => 'nullable|numeric|min:0',
        ], [
            'contact_name.regex' => 'El nombre solo puede contener letras, espacios, apóstrofes, puntos y guiones.',
            'contact_phone.digits' => 'El número de teléfono debe contener exactamente 9 dígitos.',
            'next_contact_at.required_if' => 'Debes registrar la fecha y hora de devolución para una llamada reprogramada.',
            'next_contact_at.date_format' => 'La fecha de devolución debe tener un formato válido.',
            'next_contact_at.after' => 'La fecha de devolución debe ser posterior a la hora actual.',
        ]);

        $general = $validated['general_status'];
        $specific = $validated['specific_status'];
        $nextContactAt = ($specific === 'reprogramado' && ! empty($validated['next_contact_at']))
            ? Carbon::createFromFormat('Y-m-d\TH:i', $validated['next_contact_at'], config('app.timezone'))
            : null;

        if (! array_key_exists($general, self::SPECIFIC) || ! array_key_exists($specific, self::SPECIFIC[$general])) {
            abort(422, 'Estado específico inválido.');
        }

        $needsCommercial = ($general === 'contactado' && in_array($specific, self::ENABLE_COMMERCIAL, true));
        $shouldOpenAgreementShortcut = $submitIntent === 'agreement_shortcut';

        if ($shouldOpenAgreementShortcut && ! ($general === 'contactado' && $specific === 'negociacion')) {
            return back()->withErrors([
                'agreement_shortcut' => 'El atajo de acuerdo aceptado solo está disponible para gestiones en negociación.',
            ])->withInput();
        }

        if ($needsCommercial && empty($validated['channel'])) {
            abort(422, 'Selecciona un Producto (Móvil/Fijo).');
        }

        $channel = $validated['channel'] ?? null;
        $mobileMode = $validated['mobile_mode'] ?? null;
        $portabilityRows = PromotionRows::normalize(
            $validated['portability_lines'] ?? [],
            $validated['portability_promotion_name'] ?? []
        );
        $newRows = PromotionRows::normalize(
            $validated['new_lines'] ?? [],
            $validated['new_promotion_name'] ?? []
        );

        if (! $needsCommercial) {
            $validated['channel'] = null;
            $validated['mobile_mode'] = null;
            $portabilityRows = [];
            $newRows = [];
            $validated['internet_speed'] = null;
            $validated['fixed_monthly'] = null;
        }

        if (! in_array($channel, ['movil', 'movil_fijo'], true)) {
            $validated['mobile_mode'] = null;
            $portabilityRows = [];
            $newRows = [];
        }

        if (! in_array($channel, ['fijo', 'movil_fijo'], true)) {
            $validated['internet_speed'] = null;
            $validated['fixed_monthly'] = null;
        }

        if ($mobileMode === 'portabilidad') {
            $newRows = [];
        }

        if ($mobileMode === 'alta_nueva') {
            $portabilityRows = [];
        }

        if (! in_array($mobileMode, ['portabilidad', 'alta_nueva', 'porta_alta'], true)) {
            $portabilityRows = [];
            $newRows = [];
        }

        $commercialErrors = [];

        if ($needsCommercial && in_array($channel, ['movil', 'movil_fijo'], true) && empty($mobileMode)) {
            $commercialErrors['mobile_mode'] = 'Selecciona el tipo de gestión móvil.';
        }

        if ($needsCommercial && in_array($channel, ['movil', 'movil_fijo'], true)) {
            if (in_array($mobileMode, ['portabilidad', 'porta_alta'], true)) {
                $commercialErrors = array_merge(
                    $commercialErrors,
                    PromotionRows::validate($portabilityRows, 'portability', 'Portabilidad')
                );
            }

            if (in_array($mobileMode, ['alta_nueva', 'porta_alta'], true)) {
                $commercialErrors = array_merge(
                    $commercialErrors,
                    PromotionRows::validate($newRows, 'new', 'Alta nueva')
                );
            }
        }

        if ($needsCommercial && in_array($channel, ['fijo', 'movil_fijo'], true)) {
            if (! filled($validated['internet_speed'] ?? null)) {
                $commercialErrors['internet_speed'] = 'Ingresa la velocidad del servicio fijo.';
            }

            if (! filled($validated['fixed_monthly'] ?? null)) {
                $commercialErrors['fixed_monthly'] = 'Ingresa la mensualidad del servicio fijo.';
            }
        }

        if (! empty($commercialErrors)) {
            throw ValidationException::withMessages($commercialErrors);
        }

        $lead = Lead::query()->findOrFail($leadId);

        if ($lead->assigned_to_user_id !== $user->id) {
            abort(403, 'Este cliente pertenece a otro ejecutivo.');
        }

        if ($lead->delivery_status !== 'asignado') {
            abort(409, 'Este lead ya no está disponible para registrar una nueva llamada.');
        }

        DB::transaction(function () use ($lead, $user, $general, $specific, $validated, $needsCommercial, $nextContactAt, $portabilityRows, $newRows) {
            $interaction = Interaction::create([
                'lead_id' => $lead->id,
                'user_id' => $user->id,
                'campaign_id' => $lead->campaign_id,
                'status' => $specific,
                'interaction_type' => 'a_negociar',
                'status_general' => $general,
                'status_specific' => $specific,
                'call_detail' => $validated['notes'],
                'next_contact_at' => $nextContactAt,
                'contact_name' => $validated['contact_name'],
                'contact_phone' => $validated['contact_phone'],
                'is_agreement' => false,
                'agreed_at' => null,
            ]);

            if ($needsCommercial) {
                $channel = $validated['channel'];

                if ($channel === 'movil' || $channel === 'movil_fijo') {
                    foreach ($portabilityRows as $row) {
                        $interaction->offers()->create([
                            'product_type' => 'movil',
                            'mobile_mode' => 'portabilidad',
                            'portability_lines' => $row['lines'],
                            'portability_promotion_name' => $row['promotion_name'],
                        ]);
                    }

                    foreach ($newRows as $row) {
                        $interaction->offers()->create([
                            'product_type' => 'movil',
                            'mobile_mode' => 'alta_nueva',
                            'new_lines' => $row['lines'],
                            'new_promotion_name' => $row['promotion_name'],
                        ]);
                    }
                }

                if ($channel === 'fijo' || $channel === 'movil_fijo') {
                    $interaction->offers()->create([
                        'product_type' => 'fijo',
                        'internet_speed' => $validated['internet_speed'] ?? null,
                        'fixed_monthly' => $validated['fixed_monthly'] ?? null,
                    ]);
                }
            }

            $lead->last_contact_name = $validated['contact_name'];
            $lead->last_contact_phone = $validated['contact_phone'];
            $lead->status_general = $general;
            $lead->status_specific = $specific;
            $lead->call_summary = $validated['notes'];

            if (in_array($specific, self::NO_CONTACT_REASSIGN_STATUSES, true)) {
                $lead->status_final = 'sin_gestion';
                $lead->delivery_status = 'gestionado';
                $lead->no_contact_attempts = min(
                    self::NO_CONTACT_DISABLE_THRESHOLD,
                    max(0, (int) $lead->no_contact_attempts) + 1
                );

                if ($lead->no_contact_attempts >= self::NO_CONTACT_DISABLE_THRESHOLD) {
                    $lead->assigned_to_user_id = null;
                    $lead->taken_at = null;
                    $lead->released_at = null;
                    $lead->disabled_at = now();
                    $lead->disabled_reason = 'sin_contacto_3_ejecutivos';
                } else {
                    $lead->released_at = now()->addMinutes(self::NO_CONTACT_RELEASE_DELAY_MINUTES);
                    $lead->disabled_at = null;
                    $lead->disabled_reason = null;
                }
            } else {
                $lead->no_contact_attempts = 0;
                $lead->released_at = null;
                $lead->disabled_at = null;
                $lead->disabled_reason = null;
            }

            if (in_array($specific, ['reprogramado', 'negociacion'], true)) {
                $lead->status_final = 'en_seguimiento';
                $lead->delivery_status = 'gestionado';
            }

            if (in_array($specific, ['no_desea', 'no_existe'], true)) {
                $lead->status_final = 'cerrado_sin_venta';
                $lead->delivery_status = 'gestionado';
            }
            $lead->save();

            ReminderNotification::query()
                ->where('user_id', $user->id)
                ->where('lead_id', $lead->id)
                ->delete();
        });

        if ($shouldOpenAgreementShortcut) {
            return redirect()
                ->route('work.show', [
                    'focused_lead' => $lead->id,
                    'open_agreement_modal' => 1,
                ])
                ->with('success', 'Gestión guardada correctamente. Completa ahora el acuerdo aceptado.');
        }

        return redirect()->route('work.show');
    }

    public function acceptAgreement(Request $request, int $lead)
    {
        $user = Auth::user();
        $record = $this->resolveWorkLeadForUser($user->campaigns()->value('campaigns.id'), $user->id, $lead);

        return $this->performAcceptAgreement($request, $record, $user, 'work.show');
    }

    private function resolveFocusedLead(int $campaignId, int $userId, int $leadId, bool $hasSisacTable): ?Lead
    {
        if ($leadId <= 0) {
            return null;
        }

        $leadQuery = Lead::query()
            ->with(['phones' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('id')])
            ->where('id', $leadId)
            ->where('campaign_id', $campaignId)
            ->where('assigned_to_user_id', $userId)
            ->whereNull('disabled_at')
            ->whereNotIn('status_final', [Lead::FINAL_CLOSED_NO_SALE, Lead::FINAL_AGREEMENT_ACCEPTED]);

        if ($hasSisacTable) {
            $leadQuery->with('sisacData');
        }

        return $leadQuery->first();
    }

    private function resolveWorkLeadForUser(?int $campaignId, int $userId, int $leadId): Lead
    {
        abort_unless($campaignId, 403, 'No tienes una campaña asignada.');

        return Lead::query()
            ->with([
                'phones' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('id'),
                'interactions' => function ($query) {
                    $query->latest('created_at')->with('offers', 'user');
                },
            ])
            ->where('id', $leadId)
            ->where('campaign_id', $campaignId)
            ->where('assigned_to_user_id', $userId)
            ->whereNull('disabled_at')
            ->firstOrFail();
    }

    private function assignNextLead(int $campaignId, int $userId): ?Lead
    {
        $this->releaseDueNoContactLeads($campaignId);

        return DB::transaction(function () use ($campaignId, $userId) {
            $leadQuery = Lead::query()
                ->with(['phones' => fn ($q) => $q->orderByDesc('is_primary')->orderBy('id')])
                ->where('campaign_id', $campaignId)
                ->whereNull('assigned_to_user_id')
                ->whereNull('disabled_at')
                ->where('delivery_status', 'disponible')
                ->where('status_final', 'sin_gestion')
                ->where(function ($query) use ($userId) {
                    $query->where('no_contact_attempts', 0)
                        ->orWhereDoesntHave('interactions', function ($interactionQuery) use ($userId) {
                            $interactionQuery
                                ->where('user_id', $userId)
                                ->whereIn('status_specific', self::NO_CONTACT_REASSIGN_STATUSES);
                        });
                })
                ->orderBy('id', 'asc')
                ->lockForUpdate();

            if (Schema::hasTable('lead_sisac_data')) {
                $leadQuery->with('sisacData');
            }

            $lead = $leadQuery->first();

            if (! $lead) {
                return null;
            }

            $lead->assigned_to_user_id = $userId;
            $lead->delivery_status = 'asignado';
            $lead->taken_at = now();
            $lead->save();

            return $lead;
        });
    }

    private function releaseDueNoContactLeads(int $campaignId): void
    {
        $now = now();

        Lead::query()
            ->where('campaign_id', $campaignId)
            ->whereNull('disabled_at')
            ->whereIn('status_specific', self::NO_CONTACT_REASSIGN_STATUSES)
            ->where('no_contact_attempts', '>', 0)
            ->whereNotNull('assigned_to_user_id')
            ->whereNotNull('released_at')
            ->where('released_at', '<=', $now)
            ->update([
                'assigned_to_user_id' => null,
                'delivery_status' => 'disponible',
                'taken_at' => null,
                'released_at' => null,
                'updated_at' => $now,
            ]);
    }
}
