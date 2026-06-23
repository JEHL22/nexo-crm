<?php

namespace App\Http\Controllers;

use App\Models\Interaction;
use App\Models\Lead;
use App\Models\LeadWorkSession;
use App\Models\PromotionName;
use App\Models\ReminderNotification;
use App\Models\Sale;
use App\Support\AgreementAttachmentStorage;
use App\Support\AgreementProducts;
use App\Support\PromotionRows;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MyWorkController extends Controller
{
    private const FIXED_AGREEMENT_SUPPORT_OPTIONS = [
        'contrato_fijo' => 'Contrato fijo',
        'grabacion_de_voz' => 'Grabación de voz',
    ];

    public function index(Request $request)
    {
        $user = Auth::user();
        $campaignId = $user->campaigns()->value('campaigns.id');

        if (! $campaignId) {
            abort(403, 'No tienes una campaña asignada.');
        }

        $status = $request->get('status');
        $search = trim((string) $request->get('search'));

        $allowedStatuses = ['reprogramado', 'negociacion'];

        $statusOptions = [
            'reprogramado' => 'Reprogramado',
            'negociacion' => 'Negociación',
        ];

        $query = Lead::query()
            ->with([
                'phones',
                'interactions' => function ($query) {
                    $query->latest('created_at')->with('offers', 'user');
                },
            ])
            ->where('campaign_id', $campaignId)
            ->where('assigned_to_user_id', $user->id)
            ->whereIn('status_specific', $allowedStatuses)
            ->where('source', '!=', Lead::SOURCE_MY_BASE);

        if ($status && in_array($status, $allowedStatuses, true)) {
            $query->where('status_specific', $status);
        }

        if ($search !== '') {
            $query->where('ruc', 'like', "%{$search}%");
        }

        $leads = $query
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        return view('my-work.index', compact('leads', 'statusOptions', 'status', 'search'));
    }

    public function base(Request $request)
    {
        $user = Auth::user();
        $campaignId = $user->campaigns()->value('campaigns.id');

        if (! $campaignId) {
            abort(403, 'No tienes una campaña asignada.');
        }

        $search = trim((string) $request->get('search'));

        $query = Lead::query()
            ->with([
                'phones',
                'interactions' => function ($query) {
                    $query->latest('created_at')->with('offers', 'user');
                },
            ])
            ->where('campaign_id', $campaignId)
            ->where('created_by_user_id', $user->id)
            ->where('source', Lead::SOURCE_MY_BASE);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('ruc', 'like', "%{$search}%")
                    ->orWhere('business_name', 'like', "%{$search}%");
            });
        }

        $leads = $query
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('my-work.base', compact('leads', 'search'));
    }

    public function storeBase(Request $request)
    {
        $user = Auth::user();
        $campaignId = $user->campaigns()->value('campaigns.id');

        if (! $campaignId) {
            abort(403, 'No tienes una campaña asignada.');
        }

        $validated = $request->validate([
            'ruc' => ['required', 'string', 'digits:11'],
            'business_name' => 'required|string|max:255',
            'representative_name' => ['nullable', 'string', 'min:2', 'max:255', 'regex:/^[\pL\s\'.-]+$/u'],
            'dni' => ['nullable', 'string', 'digits:8'],
            'fiscal_address' => 'nullable|string|max:255',
            'primary_phone' => ['required', 'string', 'digits:9'],
            'cellphones' => 'nullable|array|max:5',
            'cellphones.*' => ['nullable', 'string', 'digits:9', 'distinct'],
            'current_operator' => 'nullable|string|in:Claro,Movistar,Entel,Bitel',
            'current_line_count' => 'nullable|integer|min:1|max:999',
            'segment' => 'nullable|string|max:255',
            'max_speed' => 'nullable|string|max:255',
            'package' => 'nullable|string|max:255',
            'technology' => 'nullable|string|max:255',
        ], [
            'ruc.digits' => 'El RUC debe contener exactamente 11 dígitos.',
            'representative_name.min' => 'El nombre del representante debe tener al menos 2 caracteres.',
            'representative_name.regex' => 'El nombre del representante solo puede contener letras, espacios, apóstrofes, puntos y guiones.',
            'dni.digits' => 'El DNI debe contener exactamente 8 dígitos.',
            'primary_phone.digits' => 'El teléfono principal debe contener exactamente 9 dígitos.',
            'cellphones.max' => 'Solo puedes registrar hasta 5 celulares adicionales.',
            'cellphones.*.digits' => 'Cada celular adicional debe contener exactamente 9 dígitos.',
            'cellphones.*.distinct' => 'No repitas celulares adicionales en el mismo registro.',
            'current_line_count.min' => 'La cantidad de líneas debe ser mayor o igual a 1.',
            'current_line_count.max' => 'La cantidad de líneas no puede superar 999.',
        ]);

        $primaryPhone = trim((string) $validated['primary_phone']);
        $duplicatePrimary = collect($validated['cellphones'] ?? [])
            ->map(fn ($phone) => trim((string) $phone))
            ->filter()
            ->contains(fn ($phone) => $phone === $primaryPhone);

        if ($duplicatePrimary) {
            throw ValidationException::withMessages([
                'cellphones' => 'Los celulares adicionales no pueden repetir el teléfono principal.',
            ]);
        }

        foreach (['segment', 'max_speed', 'package', 'technology'] as $sisacField) {
            $validated[$sisacField] = trim((string) ($validated[$sisacField] ?? '')) ?: null;
        }

        $createdLeadId = DB::transaction(function () use ($campaignId, $user, $validated) {
            $lead = new Lead([
                'campaign_id' => $campaignId,
                'assigned_to_user_id' => $user->id,
                'supervisor_user_id' => null,
                'created_by_user_id' => $user->id,
                'source' => Lead::SOURCE_MY_BASE,
                'delivery_status' => 'gestionado',
                'taken_at' => now(),
                'full_name' => $validated['representative_name'] ?? null,
                'ruc' => $validated['ruc'],
                'business_name' => $validated['business_name'],
                'representative_name' => $validated['representative_name'] ?? null,
                'dni' => $validated['dni'] ?? null,
                'fiscal_address' => $validated['fiscal_address'] ?? null,
                'current_operator' => $validated['current_operator'] ?? null,
                'current_line_count' => $validated['current_line_count'] ?? null,
                'segment' => $validated['segment'],
                'max_speed' => $validated['max_speed'],
                'package' => $validated['package'],
                'technology' => $validated['technology'],
                'status_general' => 'sin_contacto',
                'status_specific' => 'sin_gestion',
                'status_final' => 'sin_gestion',
                'call_summary' => null,
                'no_contact_attempts' => 0,
            ]);

            $lead->save();

            $lead->phones()->create([
                'phone' => trim((string) $validated['primary_phone']),
                'type' => 'movil',
                'is_primary' => true,
            ]);

            collect($validated['cellphones'] ?? [])
                ->map(fn ($phone) => trim((string) $phone))
                ->filter()
                ->unique()
                ->reject(fn ($phone) => $phone === trim((string) $validated['primary_phone']))
                ->each(function (string $phone) use ($lead) {
                    $lead->phones()->create([
                        'phone' => $phone,
                        'type' => 'movil',
                        'is_primary' => false,
                    ]);
                });

            return $lead->id;
        });

        return redirect()
            ->route('my-work.show', ['lead' => $createdLeadId])
            ->with('success', 'Lead registrado en Mi base correctamente.');
    }

    public function show(int $lead)
    {
        $user = Auth::user();

        $record = $this->resolveMyWorkLeadForViewer($user, $lead);

        return $this->renderMyWorkShow($record, [
            'backRoute' => route('my-work.index'),
            'updateRoute' => route('my-work.update', $record->id),
            'acceptAgreementRoute' => route('my-work.accept-agreement', $record->id),
            'sisacUpdateRoute' => null,
            'canEditSisac' => false,
            'pageContext' => 'mi_chamba',
        ]);
    }

    public function update(Request $request, int $lead)
    {
        $user = Auth::user();

        $record = $this->resolveMyWorkLeadForViewer($user, $lead);

        return $this->performLeadUpdate($request, $record, $user, 'my-work.show');
    }

    public function acceptAgreement(Request $request, int $lead)
    {
        $user = Auth::user();

        $record = $this->resolveMyWorkLeadForViewer($user, $lead);

        return $this->performAcceptAgreement($request, $record, $user, 'my-work.index');
    }

    protected function renderMyWorkShow(Lead $record, array $viewData = [])
    {
        $specificOptions = [
            'reprogramado' => 'Reprogramado',
            'negociacion' => 'Negociación',
            'no_desea' => 'No desea',
        ];

        $enableCommercial = ['negociacion'];

        $latestInteraction = $record->interactions->sortByDesc('created_at')->first();

        $editData = [
            'general_status' => 'contactado',
            'specific_status' => $record->status_specific,
            'notes' => $latestInteraction?->call_detail ?? $record->call_summary,
            'contact_name' => $latestInteraction?->contact_name ?? $record->last_contact_name,
            'contact_phone' => $latestInteraction?->contact_phone ?? $record->last_contact_phone,
            'next_contact_at' => $latestInteraction?->next_contact_at?->setTimezone(config('app.timezone'))->format('Y-m-d\TH:i'),
            'channel' => null,
            'mobile_mode' => null,
            'portability_lines' => [],
            'portability_promotion_name' => [],
            'new_lines' => [],
            'new_promotion_name' => [],
            'internet_speed' => null,
            'fixed_monthly' => null,
        ];

        if ($latestInteraction) {
            $offers = $latestInteraction->offers;

            $hasMovil = $offers->contains(fn ($offer) => $offer->product_type === 'movil');
            $hasFijo = $offers->contains(fn ($offer) => $offer->product_type === 'fijo');

            if ($hasMovil && $hasFijo) {
                $editData['channel'] = 'movil_fijo';
            } elseif ($hasMovil) {
                $editData['channel'] = 'movil';
            } elseif ($hasFijo) {
                $editData['channel'] = 'fijo';
            }

            $mobileOffers = $offers->where('product_type', 'movil')->values();
            $fixedOffer = $offers->firstWhere('product_type', 'fijo');

            if ($mobileOffers->isNotEmpty()) {
                $mobileModes = $mobileOffers->pluck('mobile_mode')->filter()->unique()->values();

                $editData['mobile_mode'] = match (true) {
                    $mobileModes->contains('portabilidad') && $mobileModes->contains('alta_nueva') => 'porta_alta',
                    $mobileModes->contains('alta_nueva') => 'alta_nueva',
                    $mobileModes->contains('portabilidad') => 'portabilidad',
                    default => $mobileModes->first(),
                };

                $editData['portability_lines'] = $mobileOffers
                    ->where('mobile_mode', 'portabilidad')
                    ->pluck('portability_lines')
                    ->filter(fn ($value) => ! is_null($value))
                    ->map(fn ($value) => (int) $value)
                    ->values()
                    ->all();

                $editData['portability_promotion_name'] = $mobileOffers
                    ->where('mobile_mode', 'portabilidad')
                    ->pluck('portability_promotion_name')
                    ->map(fn ($value) => $value !== null ? (string) $value : '')
                    ->values()
                    ->all();

                $editData['new_lines'] = $mobileOffers
                    ->where('mobile_mode', 'alta_nueva')
                    ->pluck('new_lines')
                    ->filter(fn ($value) => ! is_null($value))
                    ->map(fn ($value) => (int) $value)
                    ->values()
                    ->all();

                $editData['new_promotion_name'] = $mobileOffers
                    ->where('mobile_mode', 'alta_nueva')
                    ->pluck('new_promotion_name')
                    ->map(fn ($value) => $value !== null ? (string) $value : '')
                    ->values()
                    ->all();
            }

            if ($fixedOffer) {
                $editData['internet_speed'] = $fixedOffer->internet_speed;
                $editData['fixed_monthly'] = $fixedOffer->fixed_monthly;
            }
        }

        return view('my-work.show', array_merge([
            'record' => $record,
            'specificOptions' => $specificOptions,
            'enableCommercial' => $enableCommercial,
            'editData' => $editData,
            'backRoute' => route('my-work.index'),
            'updateRoute' => route('my-work.update', $record->id),
            'acceptAgreementRoute' => route('my-work.accept-agreement', $record->id),
            'sisacUpdateRoute' => null,
            'canEditSisac' => false,
            'pageContext' => 'mi_chamba',
        ], $this->buildAgreementViewData($record, $editData), $viewData));
    }

    protected function buildAgreementViewData(Lead $record, array $editData = []): array
    {
        $record->loadMissing([
            'phones',
            'interactions' => function ($query) {
                $query->latest('created_at')->with('offers', 'user');
            },
        ]);

        $existingAgreement = Sale::query()
            ->where('lead_id', $record->id)
            ->latest('accepted_at')
            ->first();

        $latestInteraction = $record->interactions->sortByDesc('created_at')->first();
        $agreementProducts = $this->buildAgreementProducts(
            $latestInteraction && $latestInteraction->offers->isNotEmpty() ? $latestInteraction : null
        );
        $agreementPortabilityRows = $this->buildAgreementPortabilityRows(
            $latestInteraction && $latestInteraction->offers->isNotEmpty() ? $latestInteraction : null
        );

        $selectedPromotionNames = collect(array_merge(
            (array) ($editData['portability_promotion_name'] ?? []),
            (array) ($editData['new_promotion_name'] ?? [])
        ))
            ->filter(fn ($value) => filled($value))
            ->values()
            ->all();

        return [
            'agreementProducts' => $agreementProducts,
            'agreementPortabilityRows' => $agreementPortabilityRows,
            'agreementDraft' => [
                'customer_ruc' => old('customer_ruc', $existingAgreement?->customer_ruc ?: $record->ruc),
                'customer_business_name' => old('customer_business_name', $existingAgreement?->customer_business_name ?: $record->business_name),
                'customer_dni' => old('customer_dni', $existingAgreement?->customer_dni ?: $record->dni),
                'customer_representative_name' => old('customer_representative_name', $existingAgreement?->customer_representative_name ?: ($record->representative_name ?: $record->full_name)),
                'customer_phone' => old('customer_phone', $existingAgreement?->customer_phone ?: ($record->last_contact_phone ?: optional($record->phones->first())->phone)),
                'customer_address' => old('customer_address', $existingAgreement?->customer_address ?: ''),
                'customer_coordinates' => old('customer_coordinates', $existingAgreement?->customer_coordinates ?: ''),
                'plan_code' => old('plan_code', $existingAgreement?->plan_code ?: ''),
                'customer_email' => old('customer_email', $existingAgreement?->customer_email ?: ''),
                'service_channel' => old('service_channel', $existingAgreement?->service_channel ?: ''),
                'attention_time_slot' => old('attention_time_slot', $existingAgreement?->attention_time_slot ?: ''),
                'attention_date' => old('attention_date', optional($existingAgreement?->attention_date)->format('Y-m-d') ?: now()->format('Y-m-d')),
                'operator_name' => old('operator_name', $existingAgreement?->operator_name ?: $record->current_operator ?: ''),
                'delivery_type' => old('delivery_type', $existingAgreement?->delivery_type ?: ''),
                'fixed_agreement_supports' => old('fixed_agreement_supports', $existingAgreement?->fixed_agreement_supports ?: []),
                'portability_phone_numbers' => old(
                    'portability_phone_numbers',
                    collect($existingAgreement?->portability_phone_numbers_snapshot ?? [])
                        ->pluck('phone_number')
                        ->values()
                        ->all()
                ),
            ],
            'promotionNames' => Schema::hasTable('promotion_names')
                ? PromotionName::query()
                    ->where(function ($query) use ($selectedPromotionNames) {
                        $query->where('is_active', true);

                        if (! empty($selectedPromotionNames)) {
                            $query->orWhereIn('name', $selectedPromotionNames);
                        }
                    })
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(['id', 'name', 'monthly_price', 'sort_order', 'is_active'])
                : collect(),
            'fixedAgreementSupportOptions' => self::FIXED_AGREEMENT_SUPPORT_OPTIONS,
            'existingAgreement' => $existingAgreement,
        ];
    }

    protected function performLeadUpdate(Request $request, Lead $record, $user, string $redirectRoute): RedirectResponse
    {
        $shouldOpenAgreementModalAfterSave = $request->boolean('open_agreement_modal_after_save');

        $validated = $this->validateLeadUpdateRequest($request);

        $specific = $validated['specific_status'];
        $nextContactAt = ($specific === 'reprogramado' && ! empty($validated['next_contact_at']))
            ? Carbon::createFromFormat('Y-m-d\TH:i', $validated['next_contact_at'], config('app.timezone'))
            : null;
        $needsCommercial = $specific === 'negociacion';

        if ($needsCommercial && empty($validated['channel'])) {
            return back()->withErrors(['channel' => 'Selecciona un producto ofrecido.'])->withInput();
        }

        $channel = $validated['channel'] ?? null;
        $mobileMode = $validated['mobile_mode'] ?? null;

        [$validated, $portabilityRows, $newRows] = $this->normalizeCommercialFields($validated, $needsCommercial, $channel, $mobileMode);

        $commercialErrors = $this->collectCommercialOfferErrors($validated, $needsCommercial, $channel, $mobileMode, $portabilityRows, $newRows);

        if ($commercialErrors !== []) {
            return back()->withErrors($commercialErrors)->withInput();
        }

        $offeredLineCount = 0;
        $monthlyPayment = 0.0;

        if ($needsCommercial) {
            $offeredLineCount = collect($portabilityRows)->sum('lines')
                + collect($newRows)->sum('lines');

            $monthlyPayment = (float) ($validated['fixed_monthly'] ?? 0);
        }

        if ($this->leadUpdateHasNoChanges($record, $validated, $specific, $nextContactAt, $needsCommercial, $channel)) {
            $redirect = redirect()
                ->route($redirectRoute, ['lead' => $record->id])
                ->with('info', 'No se detectaron cambios para guardar.');

            if ($shouldOpenAgreementModalAfterSave) {
                $redirect->with('open_agreement_modal', true);
            }

            return $redirect;
        }

        $ownerUserId = $this->resolveLeadOwnerUserId($record);

        DB::transaction(function () use ($record, $user, $validated, $specific, $nextContactAt, $needsCommercial, $channel, $offeredLineCount, $monthlyPayment, $ownerUserId, $portabilityRows, $newRows) {
            $interaction = Interaction::create($this->buildLeadUpdateInteractionPayload(
                $record, $user, $validated, $specific, $nextContactAt, $needsCommercial, $channel, $offeredLineCount, $monthlyPayment
            ));

            if ($needsCommercial) {
                $this->storeLeadUpdateOffers($interaction, $channel, $portabilityRows, $newRows, $validated);
            }

            $this->applyLeadStatusAfterUpdate($record, $validated, $specific);

            $this->deleteLeadReminders($ownerUserId, $record);
        });

        $redirect = redirect()
            ->route($redirectRoute, ['lead' => $record->id])
            ->with('success', 'Registro actualizado correctamente.');

        if ($shouldOpenAgreementModalAfterSave) {
            $redirect->with('open_agreement_modal', true);
        }

        return $redirect;
    }

    private function validateLeadUpdateRequest(Request $request): array
    {
        return $request->validate([
            'general_status' => 'required|string|in:contactado',
            'specific_status' => 'required|string|in:reprogramado,negociacion,no_desea',
            'next_contact_at' => 'nullable|required_if:specific_status,reprogramado|date_format:Y-m-d\TH:i|after:now',
            'notes' => 'required|string|max:5000',
            'contact_name' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[\pL\s\'.-]+$/u'],
            'contact_phone' => ['required', 'string', 'digits:9'],
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
            'fixed_monthly' => 'nullable|numeric|min:0|max:999999.99',
        ], [
            'contact_name.regex' => 'El nombre solo puede contener letras, espacios, apóstrofes, puntos y guiones.',
            'contact_phone.digits' => 'El número de teléfono debe contener exactamente 9 dígitos.',
            'next_contact_at.required_if' => 'Debes registrar la fecha y hora de devolución para una llamada reprogramada.',
            'next_contact_at.date_format' => 'La fecha de devolución debe tener un formato válido.',
            'next_contact_at.after' => 'La fecha de devolución debe ser posterior a la hora actual.',
            'portability_lines.max' => 'La cantidad de líneas de portabilidad no puede ser mayor a 999.',
            'new_lines.max' => 'La cantidad de líneas nuevas no puede ser mayor a 999.',
        ]);
    }

    private function normalizeCommercialFields(array $validated, bool $needsCommercial, ?string $channel, ?string $mobileMode): array
    {
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

        return [$validated, $portabilityRows, $newRows];
    }

    private function collectCommercialOfferErrors(array $validated, bool $needsCommercial, ?string $channel, ?string $mobileMode, array $portabilityRows, array $newRows): array
    {
        $commercialErrors = [];

        if ($needsCommercial && in_array($channel, ['movil', 'movil_fijo'], true) && empty($mobileMode)) {
            $commercialErrors['mobile_mode'] = 'Selecciona el tipo de gestión móvil.';
        }

        if (in_array($channel, ['movil', 'movil_fijo'], true) && in_array($mobileMode, ['portabilidad', 'porta_alta'], true)) {
            $commercialErrors = array_merge(
                $commercialErrors,
                PromotionRows::validate($portabilityRows, 'portability', 'Portabilidad')
            );
        }

        if (in_array($channel, ['movil', 'movil_fijo'], true) && in_array($mobileMode, ['alta_nueva', 'porta_alta'], true)) {
            $commercialErrors = array_merge(
                $commercialErrors,
                PromotionRows::validate($newRows, 'new', 'Alta nueva')
            );
        }

        if (in_array($channel, ['fijo', 'movil_fijo'], true)) {
            if (! filled($validated['internet_speed'] ?? null)) {
                $commercialErrors['internet_speed'] = 'Ingresa la velocidad del servicio fijo.';
            }

            if (! filled($validated['fixed_monthly'] ?? null)) {
                $commercialErrors['fixed_monthly'] = 'Ingresa la mensualidad del servicio fijo.';
            }
        }

        return $commercialErrors;
    }

    private function leadUpdateHasNoChanges(Lead $record, array $validated, string $specific, ?Carbon $nextContactAt, bool $needsCommercial, ?string $channel): bool
    {
        $latestInteraction = $record->interactions->sortByDesc('created_at')->first();
        $currentSnapshot = $this->buildMyWorkEditSnapshotFromInteraction($record, $latestInteraction);
        $incomingSnapshot = $this->buildMyWorkEditSnapshotFromValidated(
            $validated,
            $specific,
            $nextContactAt,
            $needsCommercial,
            $channel
        );

        return $currentSnapshot === $incomingSnapshot;
    }

    private function buildLeadUpdateInteractionPayload(Lead $record, $user, array $validated, string $specific, ?Carbon $nextContactAt, bool $needsCommercial, ?string $channel, int $offeredLineCount, float $monthlyPayment): array
    {
        return [
            'lead_id' => $record->id,
            'user_id' => $user->id,
            'campaign_id' => $record->campaign_id,
            'status' => $specific,
            'interaction_type' => 'edicion_mi_chamba',
            'status_general' => 'contactado',
            'status_specific' => $specific,
            'product_type_offered' => $needsCommercial ? $channel : null,
            'offered_line_count' => $needsCommercial ? ($offeredLineCount > 0 ? $offeredLineCount : 0) : null,
            'monthly_payment' => $needsCommercial ? $monthlyPayment : null,
            'call_detail' => $validated['notes'],
            'next_contact_at' => $nextContactAt,
            'contact_name' => $validated['contact_name'],
            'contact_phone' => $validated['contact_phone'],
            'is_agreement' => false,
            'agreed_at' => null,
        ];
    }

    private function storeLeadUpdateOffers(Interaction $interaction, ?string $channel, array $portabilityRows, array $newRows, array $validated): void
    {
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

    private function applyLeadStatusAfterUpdate(Lead $record, array $validated, string $specific): void
    {
        $record->status_general = 'contactado';
        $record->status_specific = $specific;
        $record->call_summary = $validated['notes'];
        $record->last_contact_name = $validated['contact_name'];
        $record->last_contact_phone = $validated['contact_phone'];
        $record->no_contact_attempts = 0;
        $record->released_at = null;

        if (in_array($specific, ['reprogramado', 'negociacion'], true)) {
            $record->status_final = 'en_seguimiento';
        }

        if ($specific === 'no_desea') {
            $record->status_final = 'cerrado_sin_venta';
        }

        $record->save();
    }

    private function deleteLeadReminders(?int $userId, Lead $record): void
    {
        if (! $userId) {
            return;
        }

        ReminderNotification::query()
            ->where('user_id', $userId)
            ->where('lead_id', $record->id)
            ->delete();
    }

    protected function performSisacUpdate(Request $request, Lead $record, string $redirectRoute): RedirectResponse
    {
        $validated = $request->validate([
            'segment' => 'nullable|string|max:255',
            'max_speed' => 'nullable|string|max:255',
            'package' => 'nullable|string|max:255',
            'technology' => 'nullable|string|max:255',
        ]);

        $record->update($validated);

        return redirect()
            ->route($redirectRoute, ['lead' => $record->id])
            ->with('success', 'Datos SISAC actualizados correctamente.');
    }

    protected function performAcceptAgreement(
        Request $request,
        Lead $record,
        $user,
        string $redirectRoute,
        array $redirectParameters = []
    ): RedirectResponse {
        $record->loadMissing([
            'interactions' => function ($query) {
                $query->latest('created_at')->with('offers');
            },
        ]);

        $commercialInteraction = $record->interactions
            ->sortByDesc('created_at')
            ->first();

        if ($guardRedirect = $this->guardAgreementPreconditions($record, $user, $commercialInteraction)) {
            return $guardRedirect;
        }

        $productsSnapshot = $this->buildAgreementProducts($commercialInteraction);
        $portabilityRequirementRows = $this->buildAgreementPortabilityRows($commercialInteraction);
        $productType = $this->resolveProductTypeFromProducts($productsSnapshot);
        $requiresFixedAgreementSupport = in_array($productType, ['fijo', 'movil_fijo'], true);
        $isFixedOnlyAgreement = $productType === 'fijo';
        $requiresPortabilityNumbers = count($portabilityRequirementRows) > 0;

        $validated = $this->validateAgreementRequest(
            $request,
            $isFixedOnlyAgreement,
            $requiresFixedAgreementSupport,
            $requiresPortabilityNumbers,
            $portabilityRequirementRows
        );

        $this->validateAgreementAttachmentUploads($request);

        $validated = $this->normalizeAgreementFields($validated, $isFixedOnlyAgreement, $requiresFixedAgreementSupport, $requiresPortabilityNumbers);

        [$offeredLineCount, $monthlyPayment] = $this->calculateAgreementTotals($productsSnapshot);
        $portabilityPhoneNumbersSnapshot = $this->buildPortabilityPhoneNumbersSnapshot($portabilityRequirementRows, $validated);
        $attachmentPaths = [];

        try {
            if ($request->hasFile('agreement_attachments')) {
                $attachmentPaths = AgreementAttachmentStorage::store(
                    (array) $request->file('agreement_attachments')
                );
            }

            $executiveUserId = $this->resolveLeadOwnerUserId($record);
            $tmoToAgreementSeconds = $this->calculateTmoToAgreementSeconds($record, $executiveUserId);

            DB::transaction(function () use ($record, $user, $validated, $commercialInteraction, $productsSnapshot, $productType, $offeredLineCount, $monthlyPayment, $portabilityPhoneNumbersSnapshot, $tmoToAgreementSeconds, $attachmentPaths, $executiveUserId) {
                $agreementInteraction = $this->createAgreementInteraction($record, $user, $validated, $productType, $offeredLineCount, $monthlyPayment);

                $this->copyOffersToAgreementInteraction($commercialInteraction, $agreementInteraction);

                $this->applyLeadStatusAfterAgreement($record, $validated);

                $sale = $this->upsertAgreementSale(
                    $record, $agreementInteraction, $executiveUserId, $validated, $productType, $offeredLineCount,
                    $monthlyPayment, $tmoToAgreementSeconds, $productsSnapshot, $portabilityPhoneNumbersSnapshot, $attachmentPaths
                );

                $this->recordAgreementRegistrationHistory($sale, $user);

                $this->closeLeadWorkSessions($record, $executiveUserId);

                $this->deleteLeadReminders($executiveUserId, $record);
            });
        } catch (\Throwable $e) {
            AgreementAttachmentStorage::delete($attachmentPaths);
            throw $e;
        }

        return redirect()
            ->route($redirectRoute, $redirectParameters)
            ->with('success', 'El acuerdo ha sido enviado para que sea validado por el supervisor.');
    }

    private function guardAgreementPreconditions(Lead $record, $user, ?Interaction $commercialInteraction): ?RedirectResponse
    {
        if (! $record->supervisor_user_id) {
            $preferredSupervisorId = $user->hasRole('Supervisor') ? $user->id : null;
            $record->supervisor_user_id = $this->resolveLeadSupervisorId($record, $preferredSupervisorId);
        }

        if (! $record->supervisor_user_id) {
            return back()->withErrors([
                'agreement' => 'Este lead no tiene un supervisor asignado. Asigna uno antes de registrar el acuerdo aceptado.',
            ])->withInput();
        }

        $existingAgreement = Sale::query()
            ->where('lead_id', $record->id)
            ->whereIn('supervisor_validation_status', ['pendiente', 'validado'])
            ->first();

        if ($existingAgreement) {
            return back()->withErrors([
                'agreement' => 'Este lead ya tiene un acuerdo enviado al supervisor. No se puede registrar nuevamente.',
            ])->withInput();
        }

        if (! $commercialInteraction || $commercialInteraction->offers->isEmpty()) {
            return back()->withErrors([
                'agreement' => 'Antes de cerrar como acuerdo aceptado debes guardar una gestión reciente con producto ofrecido. Si el último estado fue reprogramado, primero registra la oferta actual.',
            ])->withInput();
        }

        return null;
    }

    private function validateAgreementRequest(
        Request $request,
        bool $isFixedOnlyAgreement,
        bool $requiresFixedAgreementSupport,
        bool $requiresPortabilityNumbers,
        array $portabilityRequirementRows
    ): array {
        return $request->validate([
            'customer_ruc' => ['required', 'string', 'digits:11'],
            'customer_business_name' => 'required|string|max:255',
            'customer_dni' => ['required', 'string', 'digits:8'],
            'customer_representative_name' => 'required|string|max:255',
            'customer_phone' => ['required', 'string', 'digits:9'],
            'customer_address' => 'required|string|max:255',
            'customer_coordinates' => 'required|string|max:255',
            'plan_code' => 'required|string|max:120',
            'customer_email' => 'required|email|max:255',
            'service_channel' => ($isFixedOnlyAgreement ? 'nullable' : 'required').'|string|in:pdv,centralizado',
            'attention_time_slot' => ($isFixedOnlyAgreement ? 'nullable' : 'required').'|string|in:9 am - 11 am,11 am - 1 pm,2 pm - 4 pm,4 pm - 6 pm',
            'attention_date' => ($isFixedOnlyAgreement ? 'nullable' : 'required').'|date',
            'operator_name' => ($isFixedOnlyAgreement ? 'nullable' : 'required').'|string|in:Entel,Bitel,Claro,Movistar',
            'delivery_type' => ($isFixedOnlyAgreement ? 'nullable' : 'required').'|string|in:regular,express,almacen_propio',
            'fixed_agreement_supports' => ($requiresFixedAgreementSupport ? 'required|array|size:1' : 'nullable|array'),
            'fixed_agreement_supports.*' => 'string|in:'.implode(',', array_keys(self::FIXED_AGREEMENT_SUPPORT_OPTIONS)),
            'portability_phone_numbers' => ($requiresPortabilityNumbers ? 'required|array|size:'.count($portabilityRequirementRows) : 'nullable|array'),
            'portability_phone_numbers.*' => ($requiresPortabilityNumbers ? 'required' : 'nullable').'|string|digits:9|distinct',
            'agreement_attachments' => 'nullable|array|max:8',
            'agreement_attachments.*' => 'file|mimetypes:image/jpeg,image/png,image/webp,application/pdf,application/x-pdf|max:20480',
        ], [
            'fixed_agreement_supports.required' => 'Marca al menos un soporte del acuerdo para fija.',
            'fixed_agreement_supports.size' => 'Selecciona solo un soporte del acuerdo para fija.',
            'portability_phone_numbers.required' => 'Debes registrar los números de teléfono para todas las líneas de portabilidad.',
            'portability_phone_numbers.size' => 'Debes registrar exactamente '.count($portabilityRequirementRows).' número(s) de portabilidad según la oferta seleccionada.',
            'portability_phone_numbers.*.required' => 'Completa cada número de portabilidad solicitado.',
            'portability_phone_numbers.*.digits' => 'Cada número de portabilidad debe contener exactamente 9 dígitos.',
            'portability_phone_numbers.*.distinct' => 'No repitas números de teléfono dentro de la portabilidad.',
            'agreement_attachments.*.mimetypes' => 'Los adjuntos solo pueden ser JPG, PNG, WEBP o PDF.',
            'agreement_attachments.*.max' => 'Cada adjunto puede pesar hasta 20 MB.',
            'agreement_attachments.*.uploaded' => 'Uno de los adjuntos no se pudo subir correctamente. Verifica si ese archivo supera el límite permitido del servidor.',
        ]);
    }

    private function normalizeAgreementFields(array $validated, bool $isFixedOnlyAgreement, bool $requiresFixedAgreementSupport, bool $requiresPortabilityNumbers): array
    {
        if ($isFixedOnlyAgreement) {
            $validated['service_channel'] = null;
            $validated['attention_time_slot'] = null;
            $validated['attention_date'] = null;
            $validated['operator_name'] = null;
            $validated['delivery_type'] = null;
        }

        if (! $requiresFixedAgreementSupport) {
            $validated['fixed_agreement_supports'] = [];
        }

        if (! $requiresPortabilityNumbers) {
            $validated['portability_phone_numbers'] = [];
        } else {
            $validated['portability_phone_numbers'] = array_values(array_map(
                fn ($value) => trim((string) $value),
                (array) ($validated['portability_phone_numbers'] ?? [])
            ));
        }

        return $validated;
    }

    private function calculateAgreementTotals(array $productsSnapshot): array
    {
        return AgreementProducts::calculateTotals($productsSnapshot);
    }

    private function buildPortabilityPhoneNumbersSnapshot(array $portabilityRequirementRows, array $validated): array
    {
        return collect($portabilityRequirementRows)
            ->values()
            ->map(function (array $row, int $index) use ($validated) {
                return [
                    'phone_number' => $validated['portability_phone_numbers'][$index] ?? null,
                    'offer_label' => $row['offer_label'],
                    'promotion_name' => $row['promotion_name'],
                    'display_offer' => $row['display_offer'],
                    'row_label' => $row['row_label'],
                ];
            })
            ->all();
    }

    private function calculateTmoToAgreementSeconds(Lead $record, ?int $executiveUserId): int
    {
        $tmoDurationSql = DB::connection()->getDriverName() === 'sqlite'
            ? "COALESCE(SUM(strftime('%s', COALESCE(ended_at, last_heartbeat_at)) - strftime('%s', started_at)), 0)"
            : 'COALESCE(SUM(TIMESTAMPDIFF(SECOND, started_at, COALESCE(ended_at, last_heartbeat_at))), 0)';

        return (int) LeadWorkSession::query()
            ->where('lead_id', $record->id)
            ->where('executive_user_id', $executiveUserId)
            ->selectRaw($tmoDurationSql.' as total_seconds')
            ->value('total_seconds');
    }

    private function createAgreementInteraction(Lead $record, $user, array $validated, ?string $productType, ?int $offeredLineCount, ?float $monthlyPayment): Interaction
    {
        return Interaction::create([
            'lead_id' => $record->id,
            'user_id' => $user->id,
            'campaign_id' => $record->campaign_id,
            'status' => Lead::SPECIFIC_AGREEMENT_ACCEPTED,
            'interaction_type' => 'acuerdo_aceptado',
            'status_general' => Lead::GENERAL_CONTACTED,
            'status_specific' => Lead::SPECIFIC_AGREEMENT_ACCEPTED,
            'product_type_offered' => $productType,
            'offered_line_count' => $offeredLineCount,
            'monthly_payment' => $monthlyPayment,
            'call_detail' => 'Acuerdo aceptado pendiente de validación por supervisor.',
            'next_contact_at' => null,
            'contact_name' => $validated['customer_representative_name'],
            'contact_phone' => $validated['customer_phone'],
            'is_agreement' => true,
            'agreed_at' => now(),
        ]);
    }

    private function copyOffersToAgreementInteraction(Interaction $commercialInteraction, Interaction $agreementInteraction): void
    {
        foreach ($commercialInteraction->offers as $offer) {
            $agreementInteraction->offers()->create([
                'product_type' => $offer->product_type,
                'mobile_mode' => $offer->mobile_mode,
                'portability_lines' => $offer->portability_lines,
                'portability_monthly' => $offer->portability_monthly,
                'portability_promotion_name' => $offer->portability_promotion_name,
                'new_lines' => $offer->new_lines,
                'new_monthly' => $offer->new_monthly,
                'new_promotion_name' => $offer->new_promotion_name,
                'internet_speed' => $offer->internet_speed,
                'fixed_monthly' => $offer->fixed_monthly,
            ]);
        }
    }

    private function applyLeadStatusAfterAgreement(Lead $record, array $validated): void
    {
        $record->update([
            'status_general' => Lead::GENERAL_CONTACTED,
            'status_specific' => Lead::SPECIFIC_AGREEMENT_ACCEPTED,
            'status_final' => Lead::FINAL_AGREEMENT_ACCEPTED,
            'supervisor_user_id' => $record->supervisor_user_id,
            'last_contact_name' => $validated['customer_representative_name'],
            'last_contact_phone' => $validated['customer_phone'],
            'call_summary' => 'Acuerdo aceptado y enviado a validación de supervisor.',
        ]);
    }

    private function upsertAgreementSale(
        Lead $record,
        Interaction $agreementInteraction,
        ?int $executiveUserId,
        array $validated,
        ?string $productType,
        ?int $offeredLineCount,
        ?float $monthlyPayment,
        int $tmoToAgreementSeconds,
        array $productsSnapshot,
        array $portabilityPhoneNumbersSnapshot,
        array $attachmentPaths
    ): Sale {
        return Sale::updateOrCreate(
            ['lead_id' => $record->id],
            [
                'interaction_id' => $agreementInteraction->id,
                'campaign_id' => $record->campaign_id,
                'executive_user_id' => $executiveUserId,
                'supervisor_user_id' => $record->supervisor_user_id,
                'status' => Sale::STATUS_ACCEPTED,
                'management_status' => Sale::MANAGEMENT_PENDING_SUPERVISION,
                'sisac_status' => Sale::SISAC_PENDING_SUPERVISION,
                'product_type' => $productType,
                'offered_line_count' => $offeredLineCount,
                'monthly_payment' => $monthlyPayment,
                'customer_ruc' => $validated['customer_ruc'],
                'customer_business_name' => $validated['customer_business_name'],
                'customer_dni' => $validated['customer_dni'],
                'customer_representative_name' => $validated['customer_representative_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_address' => $validated['customer_address'],
                'customer_coordinates' => $validated['customer_coordinates'],
                'plan_code' => $validated['plan_code'],
                'customer_email' => $validated['customer_email'],
                'service_channel' => $validated['service_channel'],
                'attention_time_slot' => $validated['attention_time_slot'],
                'attention_date' => $validated['attention_date'],
                'operator_name' => $validated['operator_name'],
                'delivery_type' => $validated['delivery_type'],
                'fixed_agreement_supports' => $validated['fixed_agreement_supports'],
                'tmo_to_agreement_seconds' => $tmoToAgreementSeconds,
                'products_snapshot' => $productsSnapshot,
                'portability_phone_numbers_snapshot' => $portabilityPhoneNumbersSnapshot,
                'attachment_paths' => $attachmentPaths,
                'supervisor_validation_status' => 'pendiente',
                'supervisor_validated_at' => null,
                'accepted_at' => now(),
            ]
        );
    }

    private function recordAgreementRegistrationHistory(Sale $sale, $user): void
    {
        $sale->histories()->create([
            'user_id' => $user->id,
            'action' => $user->hasRole('Supervisor') ? 'acuerdo_registrado_supervisor' : 'acuerdo_registrado_ejecutivo',
            'changed_fields' => null,
            'notes' => $user->hasRole('Supervisor')
                ? 'Supervisor registró el acuerdo aceptado desde Mi base del equipo y lo envió a validación.'
                : 'Ejecutivo registró el acuerdo aceptado y lo envió a validación del supervisor.',
        ]);
    }

    private function closeLeadWorkSessions(Lead $record, ?int $executiveUserId): void
    {
        LeadWorkSession::query()
            ->where('lead_id', $record->id)
            ->where('executive_user_id', $executiveUserId)
            ->whereNull('ended_at')
            ->update([
                'ended_at' => now(),
                'last_heartbeat_at' => now(),
                'updated_at' => now(),
            ]);
    }

    protected function resolveMyWorkLeadForViewer($user, int $leadId): Lead
    {
        return Lead::query()
            ->with([
                'phones',
                'assignedTo',
                'supervisor',
                'createdBy',
                'interactions' => function ($query) {
                    $query->latest('created_at')->with('offers', 'user');
                },
            ])
            ->where(function ($query) use ($user) {
                $query->where('assigned_to_user_id', $user->id)
                    ->orWhere('supervisor_user_id', $user->id)
                    ->orWhere('created_by_user_id', $user->id);

                if ($user->hasRole('Supervisor')) {
                    $query->orWhere(function ($teamQuery) use ($user) {
                        $teamQuery->where('source', Lead::SOURCE_MY_BASE);
                        $this->applySupervisorTeamBaseScope($teamQuery, $user->id);
                    });
                }
            })
            ->whereNull('disabled_at')
            ->findOrFail($leadId);
    }

    private function validateAgreementAttachmentUploads(Request $request): void
    {
        $attachments = $request->file('agreement_attachments', []);

        foreach ((array) $attachments as $index => $attachment) {
            if (! $attachment || $attachment->isValid()) {
                continue;
            }

            $errorCode = $attachment->getError();
            $message = match ($errorCode) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'El archivo '.($index + 1).' excede el tamaño máximo permitido por el servidor. Máximo por archivo: 20 MB.',
                UPLOAD_ERR_PARTIAL => 'El archivo '.($index + 1).' no terminó de subirse correctamente. Intenta nuevamente.',
                UPLOAD_ERR_NO_FILE => 'Falta adjuntar el archivo '.($index + 1).'.',
                default => 'El archivo '.($index + 1).' no se pudo subir. '.$attachment->getErrorMessage(),
            };

            throw ValidationException::withMessages([
                'agreement_attachments.'.($index) => $message,
            ]);
        }
    }

    protected function applySupervisorTeamBaseScope(Builder $query, int $supervisorUserId): void
    {
        $query->whereExists(function ($subQuery) use ($supervisorUserId) {
            $subQuery->selectRaw('1')
                ->from('supervisor_executive as se')
                ->where('se.supervisor_user_id', $supervisorUserId)
                ->where(function ($campaignQuery) {
                    $campaignQuery->whereNull('se.campaign_id')
                        ->orWhereColumn('se.campaign_id', 'leads.campaign_id');
                })
                ->where(function ($executiveQuery) {
                    $executiveQuery->whereColumn('se.executive_user_id', 'leads.created_by_user_id')
                        ->orWhereColumn('se.executive_user_id', 'leads.assigned_to_user_id');
                });
        });
    }

    protected function resolveLeadOwnerUserId(Lead $record): ?int
    {
        return $record->assigned_to_user_id ?: $record->created_by_user_id;
    }

    protected function resolveLeadSupervisorId(Lead $record, ?int $preferredSupervisorId = null): ?int
    {
        $executiveIds = collect([$record->assigned_to_user_id, $record->created_by_user_id])
            ->filter()
            ->unique()
            ->values();

        if ($executiveIds->isEmpty()) {
            return null;
        }

        return DB::table('supervisor_executive')
            ->whereIn('executive_user_id', $executiveIds)
            ->when($preferredSupervisorId, function ($query, $preferredSupervisorId) {
                $query->where('supervisor_user_id', $preferredSupervisorId);
            })
            ->where(function ($campaignQuery) use ($record) {
                $campaignQuery->whereNull('campaign_id')
                    ->orWhere('campaign_id', $record->campaign_id);
            })
            ->value('supervisor_user_id');
    }

    private function buildAgreementProducts(?Interaction $interaction): array
    {
        if (! $interaction) {
            return [];
        }

        $promotionPrices = $this->getPromotionPriceMap();

        return $interaction->offers->map(function ($offer) use ($promotionPrices) {
            if ($offer->product_type === 'movil') {
                $lineCount = (int) ($offer->portability_lines ?? 0) + (int) ($offer->new_lines ?? 0);
                $promotionName = trim((string) ($offer->portability_promotion_name ?: $offer->new_promotion_name ?: ''));
                $unitPrice = (float) ($promotionPrices[$promotionName] ?? 0);
                $detailParts = [];

                if ($offer->mobile_mode) {
                    $detailParts[] = 'Tipo: '.ucfirst(str_replace('_', ' + ', $offer->mobile_mode));
                }

                if ((int) ($offer->portability_lines ?? 0) > 0 || filled($offer->portability_promotion_name)) {
                    $portabilityDetail = 'Portabilidad';

                    if ((int) ($offer->portability_lines ?? 0) > 0) {
                        $portabilityDetail .= ': '.$offer->portability_lines.' línea(s)';
                    }

                    if (filled($offer->portability_promotion_name)) {
                        $portabilityDetail .= ' - '.$offer->portability_promotion_name;
                    }

                    $detailParts[] = $portabilityDetail;
                }

                if ((int) ($offer->new_lines ?? 0) > 0 || filled($offer->new_promotion_name)) {
                    $newDetail = 'Alta nueva';

                    if ((int) ($offer->new_lines ?? 0) > 0) {
                        $newDetail .= ': '.$offer->new_lines.' línea(s)';
                    }

                    if (filled($offer->new_promotion_name)) {
                        $newDetail .= ' - '.$offer->new_promotion_name;
                    }

                    $detailParts[] = $newDetail;
                }

                return [
                    'type' => 'movil',
                    'label' => 'Móvil',
                    'detail' => $detailParts !== [] ? implode(' | ', $detailParts) : 'Sin detalle',
                    'line_count' => $lineCount,
                    'price' => $unitPrice > 0 ? $unitPrice : null,
                    'line_total' => $unitPrice * max($lineCount, 0),
                    'summary_value' => collect([
                        $offer->portability_promotion_name,
                        $offer->new_promotion_name,
                    ])->filter()->implode(' + '),
                ];
            }

            return [
                'type' => 'fijo',
                'label' => 'Fijo',
                'detail' => $offer->internet_speed ?: 'Sin detalle',
                'line_count' => 0,
                'price' => (float) ($offer->fixed_monthly ?? 0),
                'line_total' => (float) ($offer->fixed_monthly ?? 0),
                'summary_value' => null,
            ];
        })->values()->all();
    }

    private function getPromotionPriceMap(): array
    {
        if (! Schema::hasTable('promotion_names')) {
            return [];
        }

        return PromotionName::query()
            ->pluck('monthly_price', 'name')
            ->map(fn ($value) => (float) $value)
            ->all();
    }

    private function buildAgreementPortabilityRows(?Interaction $interaction): array
    {
        if (! $interaction) {
            return [];
        }

        $rows = [];

        foreach ($interaction->offers->where('product_type', 'movil')->where('mobile_mode', 'portabilidad')->values() as $offerIndex => $offer) {
            $lineCount = max((int) ($offer->portability_lines ?? 0), 0);
            $promotionName = trim((string) ($offer->portability_promotion_name ?? ''));
            $displayOffer = $promotionName !== ''
                ? $promotionName
                : 'Sin promoción';

            for ($lineIndex = 1; $lineIndex <= $lineCount; $lineIndex++) {
                $rows[] = [
                    'offer_index' => $offerIndex,
                    'offer_label' => 'Portabilidad',
                    'promotion_name' => $promotionName,
                    'display_offer' => $displayOffer,
                    'row_label' => 'Línea '.$lineIndex,
                ];
            }
        }

        return $rows;
    }

    private function resolveProductTypeFromProducts(array $products): ?string
    {
        return AgreementProducts::resolveProductType($products);
    }

    private function buildMyWorkEditSnapshotFromInteraction(Lead $record, ?Interaction $interaction): array
    {
        $mobileOffers = $interaction?->offers?->where('product_type', 'movil')->values() ?? collect();
        $fixedOffer = $interaction?->offers?->firstWhere('product_type', 'fijo');

        $channel = null;

        if ($mobileOffers->isNotEmpty() && $fixedOffer) {
            $channel = 'movil_fijo';
        } elseif ($mobileOffers->isNotEmpty()) {
            $channel = 'movil';
        } elseif ($fixedOffer) {
            $channel = 'fijo';
        }

        $mobileModes = $mobileOffers->pluck('mobile_mode')->filter()->unique()->values();

        return [
            'specific_status' => $this->normalizeNullableString($record->status_specific),
            'next_contact_at' => $interaction?->next_contact_at?->setTimezone(config('app.timezone'))->format('Y-m-d\TH:i'),
            'notes' => $this->normalizeNullableString($interaction?->call_detail ?? $record->call_summary),
            'contact_name' => $this->normalizeNullableString($interaction?->contact_name ?? $record->last_contact_name),
            'contact_phone' => $this->normalizeNullableString($interaction?->contact_phone ?? $record->last_contact_phone),
            'channel' => $channel,
            'mobile_mode' => match (true) {
                $mobileModes->contains('portabilidad') && $mobileModes->contains('alta_nueva') => 'porta_alta',
                $mobileModes->contains('alta_nueva') => 'alta_nueva',
                $mobileModes->contains('portabilidad') => 'portabilidad',
                default => $this->normalizeNullableString($mobileModes->first()),
            },
            'portability_lines' => $mobileOffers->where('mobile_mode', 'portabilidad')->pluck('portability_lines')->filter(fn ($value) => ! is_null($value))->map(fn ($value) => (int) $value)->values()->all(),
            'portability_promotion_name' => $mobileOffers->where('mobile_mode', 'portabilidad')->pluck('portability_promotion_name')->map(fn ($value) => $this->normalizeNullableString($value) ?? '')->values()->all(),
            'new_lines' => $mobileOffers->where('mobile_mode', 'alta_nueva')->pluck('new_lines')->filter(fn ($value) => ! is_null($value))->map(fn ($value) => (int) $value)->values()->all(),
            'new_promotion_name' => $mobileOffers->where('mobile_mode', 'alta_nueva')->pluck('new_promotion_name')->map(fn ($value) => $this->normalizeNullableString($value) ?? '')->values()->all(),
            'internet_speed' => $this->normalizeNullableString($fixedOffer?->internet_speed),
            'fixed_monthly' => $this->normalizeNullableDecimal($fixedOffer?->fixed_monthly),
        ];
    }

    private function buildMyWorkEditSnapshotFromValidated(
        array $validated,
        string $specific,
        ?Carbon $nextContactAt,
        bool $needsCommercial,
        ?string $channel
    ): array {
        return [
            'specific_status' => $this->normalizeNullableString($specific),
            'next_contact_at' => $nextContactAt?->copy()->setTimezone(config('app.timezone'))->format('Y-m-d\TH:i'),
            'notes' => $this->normalizeNullableString($validated['notes'] ?? null),
            'contact_name' => $this->normalizeNullableString($validated['contact_name'] ?? null),
            'contact_phone' => $this->normalizeNullableString($validated['contact_phone'] ?? null),
            'channel' => $needsCommercial ? $this->normalizeNullableString($channel) : null,
            'mobile_mode' => $needsCommercial ? $this->normalizeNullableString($validated['mobile_mode'] ?? null) : null,
            'portability_lines' => $needsCommercial ? array_values(array_map('intval', array_filter((array) ($validated['portability_lines'] ?? []), fn ($value) => filled($value)))) : [],
            'portability_promotion_name' => $needsCommercial ? array_values(array_map(fn ($value) => trim((string) $value), array_filter((array) ($validated['portability_promotion_name'] ?? []), fn ($value) => filled($value)))) : [],
            'new_lines' => $needsCommercial ? array_values(array_map('intval', array_filter((array) ($validated['new_lines'] ?? []), fn ($value) => filled($value)))) : [],
            'new_promotion_name' => $needsCommercial ? array_values(array_map(fn ($value) => trim((string) $value), array_filter((array) ($validated['new_promotion_name'] ?? []), fn ($value) => filled($value)))) : [],
            'internet_speed' => $needsCommercial ? $this->normalizeNullableString($validated['internet_speed'] ?? null) : null,
            'fixed_monthly' => $needsCommercial ? $this->normalizeNullableDecimal($validated['fixed_monthly'] ?? null) : null,
        ];
    }

    private function normalizeNullableString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeNullableDecimal($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }
}
