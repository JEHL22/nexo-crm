<?php

namespace App\Http\Controllers;

use App\Models\PromotionName;
use App\Models\Sale;
use App\Models\SaleSupervisorHistory;
use App\Support\AgreementAttachmentStorage;
use App\Support\AgreementProducts;
use App\Support\PromotionRows;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SupervisorAgreementController extends Controller
{
    private const FIXED_AGREEMENT_SUPPORT_OPTIONS = [
        'contrato_fijo' => 'Contrato fijo',
        'grabacion_de_voz' => 'Grabación de voz',
    ];

    private const SUPERVISOR_STATUS_OPTIONS = [
        'pendiente' => 'Pendiente',
        'validado' => 'Validado',
    ];

    private const MANAGEMENT_STATUS_OPTIONS = [
        'pendiente_validacion' => 'Pendiente validación',
        'aprobado' => 'Aprobado',
        'rechazado' => 'Rechazado',
        'observado' => 'Observado',
    ];

    private const SISAC_STATUS_OPTIONS = [
        'en_evaluacion' => 'En evaluación',
        'activo' => 'Activo',
        'rechazado' => 'Rechazado',
        'entregado' => 'Entregado',
    ];

    private const FIELD_LABELS = [
        'customer_ruc' => 'RUC',
        'customer_business_name' => 'Razón social',
        'customer_dni' => 'DNI',
        'customer_representative_name' => 'Nombre del representante',
        'customer_phone' => 'Celular',
        'customer_address' => 'Dirección',
        'customer_coordinates' => 'Coordenadas',
        'plan_code' => 'Plano',
        'approval_code' => 'Código de aprobación',
        'customer_email' => 'Correo electrónico',
        'service_channel' => 'Canal',
        'attention_time_slot' => 'Franja de atención',
        'attention_date' => 'Fecha de atención',
        'operator_name' => 'Operador',
        'delivery_type' => 'Tipo de entrega',
        'fixed_agreement_supports' => 'Soporte del acuerdo fija',
        'products_snapshot' => 'Oferta comercial',
        'portability_phone_numbers_snapshot' => 'Números de portabilidad',
        'attachment_paths' => 'Adjuntos del acuerdo',
    ];

    public function index(Request $request): View
    {
        [$filters, $sales] = $this->buildIndexPayload($request);
        $openTraceabilitySaleId = (int) $request->integer('traceability_sale') ?: null;

        return view('supervisor.agreements.index', [
            'sales' => $sales,
            'filters' => $filters,
            'openTraceabilitySaleId' => $openTraceabilitySaleId,
            'supervisorStatusOptions' => self::SUPERVISOR_STATUS_OPTIONS,
            'managementStatusOptions' => self::MANAGEMENT_STATUS_OPTIONS,
            'sisacStatusOptions' => self::SISAC_STATUS_OPTIONS,
            'pulseRoute' => route('supervisor.agreements.pulse', array_filter([
                ...$filters,
                'traceability_sale' => $openTraceabilitySaleId ?: null,
                'page' => $sales->currentPage() > 1 ? $sales->currentPage() : null,
            ], fn ($value) => $value !== null && $value !== '')),
        ]);
    }

    public function pulse(Request $request): JsonResponse
    {
        [$filters, $sales] = $this->buildIndexPayload($request);
        $openTraceabilitySaleId = (int) $request->integer('traceability_sale') ?: null;

        return response()->json([
            'ok' => true,
            'updated_at_label' => now()->setTimezone(config('app.timezone'))->format('d/m/Y H:i:s'),
            'list_html' => view('supervisor.agreements.partials.list', [
                'sales' => $sales,
                'openTraceabilitySaleId' => $openTraceabilitySaleId,
            ])->render(),
        ]);
    }

    private function buildIndexPayload(Request $request): array
    {
        $filters = $request->only(['ruc', 'supervisor_validation_status', 'management_status', 'sisac_status']);

        $query = Sale::query()
            ->with([
                'lead.phones',
                'lead.interactions' => function ($query) {
                    $query->latest('created_at')->with('user');
                },
                'campaign',
                'executive',
                'supervisor',
                'histories.user',
                'postSaleUpdates.user',
                'validationUpdates.user',
                'validationUpdates.simDetails',
                'simDetails',
            ])
            ->where('status', Sale::STATUS_ACCEPTED)
            ->where('supervisor_user_id', Auth::id());

        if (! empty($filters['ruc'])) {
            $search = trim((string) $filters['ruc']);
            $query->where('customer_ruc', 'like', "%{$search}%");
        }

        if (! empty($filters['supervisor_validation_status']) && array_key_exists($filters['supervisor_validation_status'], self::SUPERVISOR_STATUS_OPTIONS)) {
            $query->where('supervisor_validation_status', $filters['supervisor_validation_status']);
        }

        if (! empty($filters['management_status']) && array_key_exists($filters['management_status'], self::MANAGEMENT_STATUS_OPTIONS)) {
            $query->where('management_status', $filters['management_status']);
        }

        if (! empty($filters['sisac_status']) && array_key_exists($filters['sisac_status'], self::SISAC_STATUS_OPTIONS)) {
            $query->whereIn('sisac_status', $this->resolveSisacStatuses($filters['sisac_status']));
        }

        $sales = $query
            ->orderByDesc(DB::raw('COALESCE(supervisor_validated_at, accepted_at, created_at)'))
            ->paginate(10)
            ->withQueryString();

        return [$filters, $sales];
    }

    public function show(Sale $sale)
    {
        abort_unless($sale->supervisor_user_id === Auth::id(), 403);
        abort_unless($sale->supervisor_validation_status === Sale::SUPERVISOR_VALIDATION_PENDING, 403);

        $sale->load([
            'lead.phones',
            'lead.interactions' => function ($query) {
                $query->latest('created_at')->with('offers', 'user');
            },
            'campaign',
            'executive',
            'supervisor',
            'interaction.user',
            'histories.user',
        ]);

        $executiveFeedbackInteraction = $sale->lead?->interactions
            ?->first(fn ($interaction) => ! $interaction->is_agreement && filled($interaction->call_detail));
        $agreementEditData = $this->buildAgreementEditDataFromSale($sale);
        $oldAgreementEditData = [
            'portability_lines' => old('portability_lines', $agreementEditData['portability_lines']),
            'portability_promotion_name' => old('portability_promotion_name', $agreementEditData['portability_promotion_name']),
            'new_lines' => old('new_lines', $agreementEditData['new_lines']),
            'new_promotion_name' => old('new_promotion_name', $agreementEditData['new_promotion_name']),
            'internet_speed' => old('internet_speed', $agreementEditData['internet_speed']),
            'fixed_monthly' => old('fixed_monthly', $agreementEditData['fixed_monthly']),
            'has_fixed' => $agreementEditData['has_fixed'],
        ];
        $agreementPortabilityRows = $this->buildPortabilityRowsFromEditData(
            $oldAgreementEditData,
            old(
                'portability_phone_numbers',
                collect($sale->portability_phone_numbers_snapshot ?? [])
                    ->pluck('phone_number')
                    ->values()
                    ->all()
            )
        );

        $selectedPromotionNames = collect(array_merge(
            (array) ($oldAgreementEditData['portability_promotion_name'] ?? []),
            (array) ($oldAgreementEditData['new_promotion_name'] ?? [])
        ))
            ->filter(fn ($value) => filled($value))
            ->values()
            ->all();

        return view('supervisor.agreements.show', [
            'sale' => $sale,
            'fixedAgreementSupportOptions' => self::FIXED_AGREEMENT_SUPPORT_OPTIONS,
            'executiveFeedbackInteraction' => $executiveFeedbackInteraction,
            'agreementEditData' => $oldAgreementEditData,
            'agreementPortabilityRows' => $agreementPortabilityRows,
            'promotionNames' => $this->buildPromotionOptionsForSale($sale, $selectedPromotionNames),
        ]);
    }

    public function update(Request $request, Sale $sale)
    {
        abort_unless($sale->supervisor_user_id === Auth::id(), 403);
        abort_unless($sale->supervisor_validation_status === Sale::SUPERVISOR_VALIDATION_PENDING, 403);

        $validated = $this->validateAgreementData($request);
        $shouldValidateAfterSave = $request->boolean('validate_after_save');

        $attachmentsToDelete = array_values(array_diff($sale->attachment_paths ?? [], $validated['attachment_paths'] ?? []));
        $newAttachmentPaths = array_values(array_diff($validated['attachment_paths'] ?? [], $sale->attachment_paths ?? []));

        try {
            DB::transaction(function () use ($sale, $validated, $shouldValidateAfterSave) {
                $changes = $this->collectChanges($sale, $validated);

                $sale->update($validated);

                if ($changes !== [] && ! $shouldValidateAfterSave) {
                    SaleSupervisorHistory::create([
                        'sale_id' => $sale->id,
                        'user_id' => Auth::id(),
                        'action' => 'actualizacion_supervisor',
                        'changed_fields' => $changes,
                        'notes' => 'Supervisor actualizó datos del acuerdo antes de validar.',
                    ]);
                }

                if ($shouldValidateAfterSave) {
                    $this->performValidationTransition(
                        $sale,
                        $changes !== [] ? $changes : null,
                        $changes !== []
                            ? 'Supervisor guardó cambios y validó el acuerdo para liberarlo a postventa y mesa de control.'
                            : null
                    );
                }
            });
        } catch (\Throwable $e) {
            $this->deleteAttachmentFiles($newAttachmentPaths);
            throw $e;
        }

        $this->deleteAttachmentFiles($attachmentsToDelete);

        if ($shouldValidateAfterSave) {
            return redirect()
                ->route('supervisor.agreements.index')
                ->with('success', 'El registro fue validado y ya se envió a Mesa de Control.');
        }

        return redirect()
            ->route('supervisor.agreements.show', $sale)
            ->with('success', 'Datos del acuerdo actualizados correctamente.');
    }

    public function validateAgreement(Sale $sale)
    {
        abort_unless($sale->supervisor_user_id === Auth::id(), 403);
        abort_unless($sale->supervisor_validation_status === Sale::SUPERVISOR_VALIDATION_PENDING, 403);

        $this->validateCurrentSaleData($sale, true);

        DB::transaction(function () use ($sale) {
            $this->performValidationTransition($sale);
        });

        return redirect()
            ->route('supervisor.agreements.index')
            ->with('success', 'El registro fue validado y ya se envió a Mesa de Control.');
    }

    private function validateAgreementData(Request $request): array
    {
        $validated = $this->validateBaseAgreementFields($request);

        $this->validateSupervisorAttachmentUploads($request);

        $sale = $request->route('sale');
        $sale = $sale instanceof Sale ? $sale : null;

        $agreementEditData = $this->resolveAgreementEditData($sale);
        $existingAttachmentPaths = $sale ? array_values($sale->attachment_paths ?? []) : [];
        $isFixedOnlyAgreement = $sale && $sale->product_type === 'fijo';
        $requiresFixedAgreementSupport = $sale && in_array($sale->product_type, ['fijo', 'movil_fijo'], true);
        $currentServiceChannel = (string) ($validated['service_channel'] ?? $sale?->service_channel ?? '');
        $requiresApprovalCode = $this->requiresApprovalCode($currentServiceChannel, $isFixedOnlyAgreement);

        $this->validateApprovalCodeWhenValidating($request, $requiresApprovalCode);

        $validated = $this->normalizeAgreementFieldsByProductType(
            $validated,
            $isFixedOnlyAgreement,
            $requiresApprovalCode,
            $requiresFixedAgreementSupport
        );

        $this->validateCommercialOfferInputs($request);

        [$portabilityRows, $newRows] = $this->normalizeAndValidateOfferRows($request, $agreementEditData);

        $this->validatePromotionNamesAgainstCatalog($request, $sale);

        $validated = $this->applyCommercialSnapshot($validated, $request, $agreementEditData, $portabilityRows, $newRows, $sale);

        return $this->applyAttachmentsAndPortabilityPhones($request, $validated, $existingAttachmentPaths, $portabilityRows);
    }

    private function validateBaseAgreementFields(Request $request): array
    {
        return $request->validate($this->agreementRules(), [
            'fixed_agreement_supports.required' => 'Marca al menos un soporte del acuerdo para fija.',
            'fixed_agreement_supports.size' => 'Selecciona solo un soporte del acuerdo para fija.',
            'approval_code.required' => 'Ingresa el código de aprobación antes de validar el acuerdo.',
            'portability_phone_numbers.required' => 'Debes registrar todos los números de portabilidad del acuerdo.',
            'portability_phone_numbers.size' => 'Debes mantener la misma cantidad de números de portabilidad registrados en la oferta.',
            'portability_phone_numbers.*.required' => 'Completa cada número de portabilidad.',
            'portability_phone_numbers.*.digits' => 'Cada número de portabilidad debe contener exactamente 9 dígitos.',
            'portability_phone_numbers.*.distinct' => 'No repitas números de portabilidad.',
            'new_agreement_attachments.*.mimetypes' => 'Los adjuntos solo pueden ser JPG, PNG, WEBP o PDF.',
            'new_agreement_attachments.*.max' => 'Cada adjunto puede pesar hasta 20 MB.',
            'new_agreement_attachments.*.uploaded' => 'Uno de los adjuntos no se pudo subir correctamente. Verifica si ese archivo supera el límite permitido del servidor.',
        ]);
    }

    private function resolveAgreementEditData(?Sale $sale): array
    {
        if ($sale) {
            return $this->buildAgreementEditDataFromSale($sale);
        }

        return [
            'portability_lines' => [],
            'portability_promotion_name' => [],
            'new_lines' => [],
            'new_promotion_name' => [],
            'internet_speed' => null,
            'fixed_monthly' => null,
            'has_fixed' => false,
        ];
    }

    private function validateApprovalCodeWhenValidating(Request $request, bool $requiresApprovalCode): void
    {
        if ($request->boolean('validate_after_save') && $requiresApprovalCode) {
            $request->validate([
                'approval_code' => ['required', 'string', 'max:120'],
            ], [
                'approval_code.required' => 'Ingresa el código de aprobación antes de validar el acuerdo.',
            ]);
        }
    }

    private function normalizeAgreementFieldsByProductType(
        array $validated,
        bool $isFixedOnlyAgreement,
        bool $requiresApprovalCode,
        bool $requiresFixedAgreementSupport
    ): array {
        if ($isFixedOnlyAgreement) {
            $validated['service_channel'] = null;
            $validated['attention_time_slot'] = null;
            $validated['attention_date'] = null;
            $validated['operator_name'] = null;
            $validated['delivery_type'] = null;
        }

        if (! $requiresApprovalCode) {
            $validated['approval_code'] = null;
        }

        if (! $requiresFixedAgreementSupport) {
            $validated['fixed_agreement_supports'] = [];
        }

        return $validated;
    }

    private function validateCommercialOfferInputs(Request $request): void
    {
        $request->validate([
            'portability_lines' => 'nullable|array|max:20',
            'portability_lines.*' => 'nullable|integer|min:1|max:999',
            'new_lines' => 'nullable|array|max:20',
            'new_lines.*' => 'nullable|integer|min:1|max:999',
            'internet_speed' => 'nullable|string|max:255',
            'fixed_monthly' => 'nullable|numeric|min:0|max:999999.99',
        ], [
            'portability_lines.*.integer' => 'La cantidad de líneas de portabilidad debe ser un número entero.',
            'portability_lines.*.min' => 'La cantidad de líneas de portabilidad debe ser al menos 1.',
            'portability_lines.*.max' => 'La cantidad de líneas de portabilidad no puede superar 999.',
            'new_lines.*.integer' => 'La cantidad de líneas de alta nueva debe ser un número entero.',
            'new_lines.*.min' => 'La cantidad de líneas de alta nueva debe ser al menos 1.',
            'new_lines.*.max' => 'La cantidad de líneas de alta nueva no puede superar 999.',
            'fixed_monthly.numeric' => 'La mensualidad del producto fijo debe ser un número.',
            'fixed_monthly.min' => 'La mensualidad del producto fijo no puede ser negativa.',
            'fixed_monthly.max' => 'La mensualidad del producto fijo no puede superar 999,999.99.',
        ]);
    }

    private function normalizeAndValidateOfferRows(Request $request, array $agreementEditData): array
    {
        $portabilityRows = PromotionRows::normalize(
            (array) $request->input('portability_lines', []),
            (array) $request->input('portability_promotion_name', [])
        );
        $newRows = PromotionRows::normalize(
            (array) $request->input('new_lines', []),
            (array) $request->input('new_promotion_name', [])
        );

        $offerValidationErrors = [];

        if (! empty($portabilityRows)) {
            $offerValidationErrors = array_merge($offerValidationErrors, PromotionRows::validate($portabilityRows, 'portability', 'Portabilidad', requireRows: false));
        }

        if (! empty($newRows)) {
            $offerValidationErrors = array_merge($offerValidationErrors, PromotionRows::validate($newRows, 'new', 'Alta nueva', requireRows: false));
        }

        if ($agreementEditData['has_fixed']) {
            if (! filled($request->input('internet_speed'))) {
                $offerValidationErrors['internet_speed'] = 'Completa la velocidad del producto fijo.';
            }

            if (! filled($request->input('fixed_monthly'))) {
                $offerValidationErrors['fixed_monthly'] = 'Completa la mensualidad del producto fijo.';
            }
        }

        if (! empty($offerValidationErrors)) {
            validator([], [])->after(function ($validator) use ($offerValidationErrors) {
                foreach ($offerValidationErrors as $field => $message) {
                    $validator->errors()->add($field, $message);
                }
            })->validate();
        }

        return [$portabilityRows, $newRows];
    }

    private function validatePromotionNamesAgainstCatalog(Request $request, ?Sale $sale): void
    {
        if (! Schema::hasTable('promotion_names')) {
            return;
        }

        $allowedPromotionNames = $this->getAllowedPromotionNamesForSale($sale);

        $request->validate([
            'portability_promotion_name.*' => ['nullable', 'string', 'max:160', Rule::in($allowedPromotionNames)],
            'new_promotion_name.*' => ['nullable', 'string', 'max:160', Rule::in($allowedPromotionNames)],
        ]);
    }

    private function applyCommercialSnapshot(
        array $validated,
        Request $request,
        array $agreementEditData,
        array $portabilityRows,
        array $newRows,
        ?Sale $sale
    ): array {
        $validated['products_snapshot'] = $this->buildAgreementProductsFromEditData([
            'portability_rows' => $portabilityRows,
            'new_rows' => $newRows,
            'internet_speed' => $request->input('internet_speed'),
            'fixed_monthly' => $request->input('fixed_monthly'),
            'has_fixed' => (bool) $agreementEditData['has_fixed'],
        ], $sale);
        $validated['product_type'] = $this->resolveProductTypeFromProducts($validated['products_snapshot']);
        [$validated['offered_line_count'], $validated['monthly_payment']] = AgreementProducts::calculateTotals($validated['products_snapshot']);

        return $validated;
    }

    private function applyAttachmentsAndPortabilityPhones(
        Request $request,
        array $validated,
        array $existingAttachmentPaths,
        array $portabilityRows
    ): array {
        $portabilityRowsForPhones = $this->buildPortabilityRowsFromEditData([
            'portability_lines' => array_map(fn ($row) => $row['lines'], $portabilityRows),
            'portability_promotion_name' => array_map(fn ($row) => $row['promotion_name'], $portabilityRows),
            'new_lines' => [],
            'new_promotion_name' => [],
            'internet_speed' => null,
            'fixed_monthly' => null,
            'has_fixed' => false,
        ]);

        $attachmentValidated = $request->validate([
            'kept_attachment_paths' => 'nullable|array',
            'kept_attachment_paths.*' => ['string', Rule::in($existingAttachmentPaths)],
            'new_agreement_attachments' => 'nullable|array|max:8',
            'new_agreement_attachments.*' => 'file|mimetypes:image/jpeg,image/png,image/webp,application/pdf,application/x-pdf|max:20480',
        ]);

        $portabilityValidated = $request->validate([
            'portability_phone_numbers' => ! empty($portabilityRowsForPhones)
                ? 'required|array|size:'.count($portabilityRowsForPhones)
                : 'nullable|array',
            'portability_phone_numbers.*' => ! empty($portabilityRowsForPhones)
                ? 'required|string|digits:9|distinct'
                : 'nullable|string|digits:9|distinct',
        ]);

        $validated['portability_phone_numbers_snapshot'] = collect($portabilityRowsForPhones)
            ->values()
            ->map(function (array $row, int $index) use ($portabilityValidated) {
                $row['phone_number'] = trim((string) (($portabilityValidated['portability_phone_numbers'][$index] ?? '')));

                return $row;
            })
            ->all();

        $keptAttachmentPaths = array_values(array_intersect(
            $existingAttachmentPaths,
            (array) ($attachmentValidated['kept_attachment_paths'] ?? [])
        ));
        $newAttachmentPaths = $this->storeUploadedAttachments((array) $request->file('new_agreement_attachments', []));

        $validated['attachment_paths'] = array_values(array_merge($keptAttachmentPaths, $newAttachmentPaths));

        return $validated;
    }

    private function validateCurrentSaleData(Sale $sale, bool $requireApprovalCode = false): void
    {
        validator($sale->only(array_keys($this->agreementRules())), $this->agreementRules())->validate();

        if ($requireApprovalCode && $this->requiresApprovalCode((string) $sale->service_channel, $sale->product_type === 'fijo')) {
            validator(
                ['approval_code' => $sale->approval_code],
                ['approval_code' => ['required', 'string', 'max:120']],
                ['approval_code.required' => 'Ingresa el código de aprobación antes de validar el acuerdo.']
            )->validate();
        }
    }

    private function performValidationTransition(Sale $sale, ?array $changedFields = null, ?string $notes = null): void
    {
        $sale->update([
            'supervisor_validation_status' => 'validado',
            'supervisor_validated_at' => now(),
            'management_status' => 'pendiente_validacion',
            'sisac_status' => 'en_evaluacion',
        ]);

        if (! $sale->postSaleUpdates()->exists()) {
            $sale->postSaleUpdates()->create([
                'user_id' => null,
                'management_status' => 'pendiente_validacion',
                'feedback' => null,
            ]);
        }

        if (! $sale->validationUpdates()->exists()) {
            $sale->validationUpdates()->create([
                'user_id' => null,
                'sisac_status' => 'en_evaluacion',
                'feedback' => null,
            ]);
        }

        SaleSupervisorHistory::create([
            'sale_id' => $sale->id,
            'user_id' => Auth::id(),
            'action' => 'validado_supervisor',
            'changed_fields' => $changedFields,
            'notes' => $notes ?: 'Supervisor validó el acuerdo y liberó el flujo a postventa y mesa de control.',
        ]);
    }

    private function agreementRules(): array
    {
        $sale = request()->route('sale');
        $productType = $sale instanceof Sale ? $sale->product_type : null;
        $isFixedOnlyAgreement = $productType === 'fijo';
        $requiresFixedAgreementSupport = in_array($productType, ['fijo', 'movil_fijo'], true);

        $fixedAgreementSupportsRule = $requiresFixedAgreementSupport
            ? 'required|array|size:1'
            : 'nullable|array';

        return [
            'customer_ruc' => ['required', 'string', 'digits:11'],
            'customer_business_name' => 'required|string|max:255',
            'customer_dni' => ['required', 'string', 'digits:8'],
            'customer_representative_name' => 'required|string|max:255',
            'customer_phone' => ['required', 'string', 'digits:9'],
            'customer_address' => 'required|string|max:255',
            'customer_coordinates' => 'required|string|max:255',
            'plan_code' => 'required|string|max:120',
            'approval_code' => 'nullable|string|max:120',
            'customer_email' => 'required|email|max:255',
            'service_channel' => ($isFixedOnlyAgreement ? 'nullable' : 'required').'|string|in:pdv,centralizado',
            'attention_time_slot' => ($isFixedOnlyAgreement ? 'nullable' : 'required').'|string|in:9 am - 11 am,11 am - 1 pm,2 pm - 4 pm,4 pm - 6 pm',
            'attention_date' => ($isFixedOnlyAgreement ? 'nullable' : 'required').'|date',
            'operator_name' => ($isFixedOnlyAgreement ? 'nullable' : 'required').'|string|in:Entel,Bitel,Claro,Movistar',
            'delivery_type' => ($isFixedOnlyAgreement ? 'nullable' : 'required').'|string|in:regular,express,almacen_propio',
            'fixed_agreement_supports' => $fixedAgreementSupportsRule,
            'fixed_agreement_supports.*' => 'string|in:'.implode(',', array_keys(self::FIXED_AGREEMENT_SUPPORT_OPTIONS)),
        ];
    }

    private function requiresApprovalCode(?string $serviceChannel, bool $isFixedOnlyAgreement = false): bool
    {
        if ($isFixedOnlyAgreement) {
            return false;
        }

        return $serviceChannel === 'centralizado';
    }

    private function collectChanges(Sale $sale, array $validated): array
    {
        $changes = [];

        foreach (self::FIELD_LABELS as $field => $label) {
            $original = $this->formatFieldValue($field, $sale->{$field} ?? null);
            $updated = $this->formatFieldValue($field, $validated[$field] ?? null);

            if ($original !== $updated) {
                $changes[] = [
                    'field' => $field,
                    'label' => $label,
                    'old' => $original,
                    'new' => $updated,
                ];
            }
        }

        return $changes;
    }

    private function formatFieldValue(string $field, mixed $value): string
    {
        if ($value instanceof CarbonInterface) {
            $value = $field === 'attention_date'
                ? $value->format('Y-m-d')
                : $value->format('Y-m-d H:i:s');
        }

        if ($value === null) {
            return '';
        }

        return match ($field) {
            'service_channel' => match ((string) $value) {
                'pdv' => 'PDV',
                'centralizado' => 'Centralizado',
                default => (string) $value,
            },
            'fixed_agreement_supports' => collect((array) $value)
                ->map(fn (string $item) => self::FIXED_AGREEMENT_SUPPORT_OPTIONS[$item] ?? $item)
                ->filter()
                ->implode(', '),
            'delivery_type' => match ((string) $value) {
                'regular' => 'Regular',
                'express' => 'Express',
                'almacen_propio' => 'Almacen Propio',
                default => (string) $value,
            },
            'products_snapshot' => collect((array) $value)
                ->map(function (array $item) {
                    $label = trim((string) ($item['label'] ?? 'Producto'));
                    $detail = trim((string) ($item['detail'] ?? ''));
                    $lineCount = (int) ($item['line_count'] ?? 0);
                    $summary = trim((string) ($item['summary_value'] ?? ''));
                    $price = (float) ($item['price'] ?? 0);

                    $parts = array_filter([
                        $label,
                        $detail !== '' ? $detail : null,
                        $lineCount > 0 ? 'Líneas: '.$lineCount : null,
                        $summary !== '' ? $summary : null,
                        $price > 0 ? 'S/ '.number_format($price, 2) : null,
                    ]);

                    return implode(' | ', $parts);
                })
                ->filter()
                ->implode(' || '),
            'portability_phone_numbers_snapshot' => collect((array) $value)
                ->map(function (array $item) {
                    $phoneNumber = trim((string) ($item['phone_number'] ?? ''));
                    $label = trim((string) ($item['row_label'] ?? 'Línea'));
                    $offer = trim((string) ($item['display_offer'] ?? ''));

                    return trim($label.': '.$phoneNumber.($offer !== '' ? ' ('.$offer.')' : ''));
                })
                ->filter()
                ->implode(' | '),
            'attachment_paths' => collect((array) $value)
                ->map(fn (string $item) => basename($item))
                ->filter()
                ->implode(', '),
            'attention_date' => (string) $value,
            default => (string) $value,
        };
    }

    private function buildAgreementEditDataFromSale(Sale $sale): array
    {
        $products = collect($sale->products_snapshot ?? []);
        $fixedProduct = $products->firstWhere('type', 'fijo');

        return [
            'portability_lines' => $products
                ->filter(fn (array $product) => ($product['type'] ?? null) === 'movil' && str_contains(Str::lower((string) ($product['detail'] ?? '')), 'portabilidad'))
                ->pluck('line_count')
                ->map(fn ($value) => (int) $value)
                ->values()
                ->all(),
            'portability_promotion_name' => $products
                ->filter(fn (array $product) => ($product['type'] ?? null) === 'movil' && str_contains(Str::lower((string) ($product['detail'] ?? '')), 'portabilidad'))
                ->pluck('summary_value')
                ->map(fn ($value) => trim((string) $value))
                ->values()
                ->all(),
            'new_lines' => $products
                ->filter(fn (array $product) => ($product['type'] ?? null) === 'movil' && str_contains(Str::lower((string) ($product['detail'] ?? '')), 'alta'))
                ->pluck('line_count')
                ->map(fn ($value) => (int) $value)
                ->values()
                ->all(),
            'new_promotion_name' => $products
                ->filter(fn (array $product) => ($product['type'] ?? null) === 'movil' && str_contains(Str::lower((string) ($product['detail'] ?? '')), 'alta'))
                ->pluck('summary_value')
                ->map(fn ($value) => trim((string) $value))
                ->values()
                ->all(),
            'internet_speed' => is_array($fixedProduct) ? ($fixedProduct['detail'] ?? null) : null,
            'fixed_monthly' => $fixedProduct
                ? number_format((float) ($fixedProduct['price'] ?? 0), 2, '.', '')
                : null,
            'has_fixed' => $products->contains(fn (array $product) => ($product['type'] ?? null) === 'fijo'),
        ];
    }

    private function validateSupervisorAttachmentUploads(Request $request): void
    {
        $attachments = $request->file('new_agreement_attachments', []);

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
                'new_agreement_attachments.'.($index) => $message,
            ]);
        }
    }

    private function buildAgreementProductsFromEditData(array $editData, ?Sale $sale = null): array
    {
        $products = [];
        $promotionPrices = $this->getPromotionPriceMap($sale);

        foreach ($editData['portability_rows'] ?? [] as $row) {
            $lineCount = (int) ($row['lines'] ?? 0);
            $unitPrice = (float) ($promotionPrices[trim((string) ($row['promotion_name'] ?? ''))] ?? 0);

            $products[] = [
                'type' => 'movil',
                'label' => 'Móvil',
                'detail' => 'Portabilidad',
                'line_count' => $lineCount,
                'price' => $unitPrice,
                'line_total' => $unitPrice * max($lineCount, 0),
                'summary_value' => $row['promotion_name'] ?? null,
            ];
        }

        foreach ($editData['new_rows'] ?? [] as $row) {
            $lineCount = (int) ($row['lines'] ?? 0);
            $unitPrice = (float) ($promotionPrices[trim((string) ($row['promotion_name'] ?? ''))] ?? 0);

            $products[] = [
                'type' => 'movil',
                'label' => 'Móvil',
                'detail' => 'Alta nueva',
                'line_count' => $lineCount,
                'price' => $unitPrice,
                'line_total' => $unitPrice * max($lineCount, 0),
                'summary_value' => $row['promotion_name'] ?? null,
            ];
        }

        if (! empty($editData['has_fixed'])) {
            $products[] = [
                'type' => 'fijo',
                'label' => 'Fijo',
                'detail' => trim((string) ($editData['internet_speed'] ?? '')),
                'line_count' => 0,
                'price' => (float) ($editData['fixed_monthly'] ?? 0),
                'line_total' => (float) ($editData['fixed_monthly'] ?? 0),
                'summary_value' => null,
            ];
        }

        return array_values($products);
    }

    private function getPromotionPriceMap(?Sale $sale = null): array
    {
        $currentPromotionPrices = Schema::hasTable('promotion_names')
            ? PromotionName::query()
                ->pluck('monthly_price', 'name')
                ->map(fn ($value) => (float) $value)
                ->all()
            : [];

        if (! $sale) {
            return $currentPromotionPrices;
        }

        return $this->getHistoricalPromotionPriceMap($sale) + $currentPromotionPrices;
    }

    private function buildPromotionOptionsForSale(Sale $sale, array $selectedPromotionNames = [])
    {
        if (! Schema::hasTable('promotion_names')) {
            return collect($selectedPromotionNames)
                ->filter(fn ($value) => filled($value))
                ->unique()
                ->values()
                ->map(fn ($name) => (object) [
                    'id' => null,
                    'name' => $name,
                    'monthly_price' => null,
                    'sort_order' => null,
                    'is_active' => false,
                    'is_legacy' => true,
                ]);
        }

        $databasePromotions = PromotionName::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'monthly_price', 'sort_order', 'is_active']);

        $databasePromotionNames = $databasePromotions->pluck('name')->all();
        $historicalPriceMap = $this->getHistoricalPromotionPriceMap($sale);

        $legacyPromotions = collect($selectedPromotionNames)
            ->filter(fn ($value) => filled($value))
            ->unique()
            ->reject(fn ($name) => in_array($name, $databasePromotionNames, true))
            ->values()
            ->map(fn ($name) => (object) [
                'id' => null,
                'name' => $name,
                'monthly_price' => $historicalPriceMap[$name] ?? null,
                'sort_order' => null,
                'is_active' => false,
                'is_legacy' => true,
            ]);

        return $databasePromotions->concat($legacyPromotions)->values();
    }

    private function getAllowedPromotionNamesForSale(Sale $sale): array
    {
        $databaseNames = Schema::hasTable('promotion_names')
            ? PromotionName::query()->pluck('name')->all()
            : [];

        return collect(array_merge(
            $databaseNames,
            $this->getHistoricalPromotionNames($sale)->all()
        ))
            ->filter(fn ($value) => filled($value))
            ->unique()
            ->values()
            ->all();
    }

    private function getHistoricalPromotionNames(Sale $sale)
    {
        return collect($sale->products_snapshot ?? [])
            ->filter(fn (array $product) => ($product['type'] ?? null) === 'movil')
            ->pluck('summary_value')
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => trim((string) $value))
            ->values();
    }

    private function getHistoricalPromotionPriceMap(Sale $sale): array
    {
        return collect($sale->products_snapshot ?? [])
            ->filter(fn (array $product) => ($product['type'] ?? null) === 'movil')
            ->filter(fn (array $product) => filled($product['summary_value'] ?? null))
            ->mapWithKeys(function (array $product) {
                return [
                    trim((string) $product['summary_value']) => (float) ($product['price'] ?? 0),
                ];
            })
            ->all();
    }

    private function buildPortabilityRowsFromEditData(array $editData, array $phoneNumbers = []): array
    {
        $rows = [];
        $phones = array_values($phoneNumbers);
        $pointer = 0;

        foreach (PromotionRows::normalize(
            (array) ($editData['portability_lines'] ?? []),
            (array) ($editData['portability_promotion_name'] ?? [])
        ) as $offerIndex => $row) {
            $lineCount = max((int) ($row['lines'] ?? 0), 0);
            $promotionName = trim((string) ($row['promotion_name'] ?? ''));
            $offerKey = 'portability-'.$offerIndex;

            for ($lineIndex = 1; $lineIndex <= $lineCount; $lineIndex++) {
                $rows[] = [
                    'offer_index' => $offerIndex,
                    'offer_key' => $offerKey,
                    'offer_label' => 'Portabilidad',
                    'promotion_name' => $promotionName,
                    'display_offer' => $promotionName !== '' ? $promotionName : 'Sin promoción',
                    'row_label' => 'Línea '.$lineIndex,
                    'phone_number' => $phones[$pointer] ?? '',
                ];
                $pointer++;
            }
        }

        return $rows;
    }

    private function resolveProductTypeFromProducts(array $products): ?string
    {
        return AgreementProducts::resolveProductType($products);
    }

    private function storeUploadedAttachments(array $files): array
    {
        return AgreementAttachmentStorage::store($files);
    }

    private function deleteAttachmentFiles(array $paths): void
    {
        AgreementAttachmentStorage::delete($paths);
    }

    private function resolveSisacStatuses(string $status): array
    {
        return match ($status) {
            'en_evaluacion' => ['en_evaluacion', 'pendiente_validacion', 'observado'],
            'activo' => ['activo', 'aprobado'],
            'rechazado' => ['rechazado'],
            'entregado' => ['entregado'],
            default => [$status],
        };
    }
}
