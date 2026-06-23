@if($sales->isEmpty())
                    <div class="border border-dashed border-gray-300 rounded-xl p-8 text-center text-gray-500">
                        No se encontraron registros para validar con los filtros actuales.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($sales as $sale)
                            @php
                                $lead = $sale->lead;
                                $latestPostSale = $sale->postSaleUpdates->sortByDesc('created_at')->first();
                                $executiveFeedbackInteraction = $lead?->interactions?->first(fn ($interaction) => !$interaction->is_agreement && filled($interaction->call_detail));
                                $hasValidationReview = $sale->validationUpdates->contains(fn ($update) => !is_null($update->user_id));
                                $supervisorValidationLabel = $sale->supervisor_validation_status === 'validado' ? 'Validado por supervisor' : 'Pendiente de supervisor';
                                $productsSnapshot = collect($sale->products_snapshot ?? []);
                                $isNewForValidation = !$hasValidationReview;
                                $requiresSimDelivery = in_array($sale->product_type, ['movil', 'movil_fijo'], true);

                                $productLabel = match ($sale->product_type) {
                                    'movil' => 'Móvil',
                                    'fijo' => 'Fijo',
                                    'movil_fijo' => 'Móvil + Fijo',
                                    default => 'No especificado',
                                };

                                $offerCards = $productsSnapshot
                                    ->map(function ($product) {
                                        return [
                                            'title' => $product['label'] ?? 'Producto',
                                            'meta' => $product['detail'] ?? '-',
                                            'line_count' => (int) ($product['line_count'] ?? 0),
                                            'value' => ($product['summary_value'] ?? null) ?: (isset($product['price']) && $product['price'] !== null ? 'S/ '.number_format((float) $product['price'], 2) : '-'),
                                        ];
                                    })
                                    ->values();

                                $portabilityPhoneRows = collect($sale->portability_phone_numbers_snapshot ?? [])
                                    ->values()
                                    ->map(function ($row, $index) {
                                        return [
                                            'line' => $index + 1,
                                            'kind' => 'portabilidad',
                                            'row_label' => $row['row_label'] ?? ('Línea '.($index + 1)),
                                            'offer_label' => $row['display_offer'] ?? 'Sin promoción',
                                            'prefilled_sim_number' => trim((string) ($row['phone_number'] ?? '')),
                                        ];
                                    });

                                $newLineRows = $productsSnapshot
                                    ->filter(fn ($product) => ($product['type'] ?? null) === 'movil' && ($product['detail'] ?? null) === 'Alta nueva')
                                    ->flatMap(function ($product, $productIndex) {
                                        $lineCount = max((int) ($product['line_count'] ?? 0), 0);
                                        $offerLabel = trim((string) ($product['summary_value'] ?? '')) ?: 'Sin promoción';

                                        return ($lineCount > 0 ? collect(range(1, $lineCount)) : collect())->map(function ($lineNumber) use ($productIndex, $offerLabel) {
                                            return [
                                                'kind' => 'alta_nueva',
                                                'row_label' => 'Línea '.$lineNumber,
                                                'offer_label' => $offerLabel,
                                                'group_index' => $productIndex,
                                            ];
                                        });
                                    })
                                    ->values()
                                    ->map(function ($row, $index) {
                                        return [
                                            'line' => $index + 1,
                                            'kind' => $row['kind'],
                                            'row_label' => $row['row_label'],
                                            'offer_label' => $row['offer_label'],
                                            'prefilled_sim_number' => '',
                                        ];
                                    });

                                $deliveryLines = $portabilityPhoneRows
                                    ->concat($newLineRows)
                                    ->values()
                                    ->map(function ($row, $index) {
                                        return [
                                            'line' => $index + 1,
                                            'kind' => $row['kind'],
                                            'row_label' => $row['row_label'],
                                            'offer_label' => $row['offer_label'],
                                            'prefilled_sim_number' => $row['prefilled_sim_number'],
                                        ];
                                    })
                                    ->values();

                                $existingSimDetails = $sale->simDetails
                                    ->map(fn ($detail) => [
                                        'line' => $detail->line_number,
                                        'serial_number' => $detail->serial_number ?? '',
                                        'sim_number' => $detail->sim_number ?? '',
                                    ])
                                    ->keyBy('line');

                                $managementLabel = $latestPostSale
                                    ? ($statusOptions[$latestPostSale->management_status] ?? ucfirst(str_replace('_', ' ', $latestPostSale->management_status)))
                                    : 'Pendiente validación';

                                $sisacLabel = $statusLabels[$sale->sisac_status] ?? ucfirst(str_replace('_', ' ', $sale->sisac_status));
                            @endphp

                            <div
                                x-data="{
                                    openModal: false,
                                    openDeliveryModal: false,
                                    requiresSimDelivery: @js($requiresSimDelivery),
                                    selectedSisacStatus: @js(in_array($sale->sisac_status, ['en_evaluacion', 'pendiente_validacion', 'observado'], true) ? 'en_evaluacion' : (in_array($sale->sisac_status, ['activo', 'aprobado'], true) ? 'activo' : $sale->sisac_status)),
                                    deliveryLines: @js($deliveryLines->values()->all()),
                                    lineCount: {{ $requiresSimDelivery ? $deliveryLines->count() : 0 }},
                                    simDetails: @js($deliveryLines->map(function ($line) use ($existingSimDetails) {
                                        $existing = $existingSimDetails->get($line['line']);

                                        return [
                                            'line' => $line['line'],
                                            'kind' => $line['kind'],
                                            'row_label' => $line['row_label'],
                                            'offer_label' => $line['offer_label'],
                                            'is_portability' => $line['kind'] === 'portabilidad',
                                            'serial_number' => $existing['serial_number'] ?? '',
                                            'sim_number' => ($existing['sim_number'] ?? '') !== ''
                                                ? $existing['sim_number']
                                                : ($line['prefilled_sim_number'] ?? ''),
                                        ];
                                    })->values()->all()),
                                    copiedField: null,
                                    ensureSimDetails() {
                                        const existingByLine = new Map(
                                            (Array.isArray(this.simDetails) ? this.simDetails : [])
                                                .map((detail) => [Number(detail?.line ?? 0), detail])
                                        );

                                        this.simDetails = (Array.isArray(this.deliveryLines) ? this.deliveryLines : []).map((line, index) => {
                                            const existing = existingByLine.get(Number(line?.line ?? index + 1)) ?? {};
                                            const isPortability = line?.kind === 'portabilidad';
                                            const fallbackSimNumber = isPortability ? (line?.prefilled_sim_number ?? '') : '';

                                            return {
                                                line: index + 1,
                                                kind: line?.kind ?? null,
                                                row_label: line?.row_label ?? `Línea ${index + 1}`,
                                                offer_label: line?.offer_label ?? 'Sin promoción',
                                                is_portability: isPortability,
                                                serial_number: existing?.serial_number ?? '',
                                                sim_number: (existing?.sim_number ?? '') !== '' ? existing.sim_number : fallbackSimNumber,
                                            };
                                        });
                                    },
                                    syncDeliveryState() {
                                        if (!this.requiresSimDelivery || this.lineCount < 1) {
                                            this.openDeliveryModal = false;
                                            this.simDetails = [];
                                            return;
                                        }

                                        this.ensureSimDetails();

                                        if (this.selectedSisacStatus !== 'entregado') {
                                            this.openDeliveryModal = false;
                                            this.simDetails = this.simDetails.map((detail, index) => ({
                                                ...detail,
                                                line: index + 1,
                                                serial_number: '',
                                                sim_number: detail?.is_portability ? (this.deliveryLines[index]?.prefilled_sim_number ?? '') : '',
                                            }));
                                            return;
                                        }

                                        this.openDeliveryModal = true;
                                    },
                                    copyValue(field, value) {
                                        if (!value || value === '-') return;

                                        navigator.clipboard.writeText(value).then(() => {
                                            this.copiedField = field;
                                            setTimeout(() => {
                                                if (this.copiedField === field) {
                                                    this.copiedField = null;
                                                }
                                            }, 1800);
                                        });
                                    }
                                }"
                                x-init="ensureSimDetails()"
                                :data-validation-modal-open="openModal ? '1' : '0'"
                                :data-validation-delivery-modal-open="openDeliveryModal ? '1' : '0'"
                                class="rounded-2xl border {{ $isNewForValidation ? 'border-sky-400 bg-sky-50/50 ring-1 ring-sky-200' : ($loop->first ? 'border-blue-500' : 'border-gray-300') }} bg-white px-5 py-3"
                            >
                                <div class="space-y-3">
                                    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_max-content] gap-x-4 gap-y-2 items-start">
                                        <div class="min-w-0 rounded-lg bg-gray-50 border border-gray-200 px-2 py-1">
                                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[13px] text-gray-900 leading-tight">
                                                @if($isNewForValidation)
                                                    <span class="font-semibold border border-sky-300 bg-sky-100 px-2 py-0.5 rounded-lg text-sky-800 whitespace-nowrap">
                                                        Nuevo por revisar
                                                    </span>
                                                @endif
                                                <span class="font-semibold whitespace-nowrap">Estado: {{ ucfirst(str_replace('_', ' ', $sale->status)) }}</span>
                                                <span class="font-medium border border-gray-200 px-2 py-0.5 rounded-lg text-gray-700 whitespace-nowrap">
                                                    Gestión: {{ $managementLabel }}
                                                </span>
                                                <span class="font-medium border border-gray-200 px-2 py-0.5 rounded-lg text-gray-700 whitespace-nowrap">
                                                    Estado Sisac: {{ $sisacLabel }}
                                                </span>
                                                <span class="font-medium border border-gray-200 px-2 py-0.5 rounded-lg text-gray-700 whitespace-nowrap">
                                                    RUC: {{ $sale->customer_ruc ?? $lead->ruc ?? '-' }}
                                                </span>
                                                <span class="font-medium border border-gray-200 px-2 py-0.5 rounded-lg text-gray-700 whitespace-nowrap">
                                                    {{ $supervisorValidationLabel }}
                                                </span>
                                            </div>

                                            <div class="mt-1 flex flex-wrap items-center gap-x-5 gap-y-1 text-[13px] text-gray-900 leading-tight">
                                                <div class="whitespace-nowrap">
                                                    <span class="font-semibold">Ejecutivo:</span>
                                                    <span>{{ $sale->executive->name ?? '-' }}</span>
                                                </div>

                                                <div class="whitespace-nowrap">
                                                    <span class="font-semibold">Supervisor:</span>
                                                    <span>{{ $sale->supervisor->name ?? '-' }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-start justify-start xl:justify-end">
                                            <button @click="openModal = true"
                                                class="inline-flex items-center justify-center px-4 py-2 rounded-xl border-2 border-gray-900 text-gray-900 font-semibold hover:bg-gray-50 transition w-full xl:w-auto min-w-[104px]">
                                                Detallar
                                            </button>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-2 text-sm text-gray-900">
                                        <div class="space-y-2">
                                            <div>
                                                <span class="font-semibold">Campaña :</span>
                                                <span>{{ $sale->campaign->name ?? '-' }}</span>
                                            </div>

                                            <div>
                                                <span class="font-semibold">Razón Social:</span>
                                                <span>{{ $sale->customer_business_name ?? $lead->business_name ?? '-' }}</span>
                                            </div>

                                            <div>
                                                <span class="font-semibold">Nombre del representante:</span>
                                                <span>{{ $sale->customer_representative_name ?? $lead->last_contact_name ?? '-' }}</span>
                                            </div>

                                            <div>
                                                <span class="font-semibold">Plano:</span>
                                                <span>{{ $sale->plan_code ?? '-' }}</span>
                                            </div>

                                            <div>
                                                <span class="font-semibold">Código aprobación:</span>
                                                <span>{{ $sale->approval_code ?? '-' }}</span>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <div>
                                                <span class="font-semibold">Celular del representante:</span>
                                                <span>{{ $sale->customer_phone ?? $lead->last_contact_phone ?? '-' }}</span>
                                            </div>

                                            <div>
                                                <span class="font-semibold"># Líneas ofrecidas:</span>
                                                <span>{{ $sale->offered_line_count ?? '-' }}</span>
                                            </div>

                                            <div>
                                                <span class="font-semibold">Pago mensual:</span>
                                                <span>{{ !is_null($sale->monthly_payment) ? 'S/ '.number_format((float) $sale->monthly_payment, 2) : '-' }}</span>
                                            </div>

                                            <div>
                                                <span class="font-semibold">Operador:</span>
                                                <span>{{ $sale->operator_name ?? '-' }}</span>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <div>
                                                <span class="font-semibold">Producto ofrecido:</span>
                                                <span>{{ $productLabel }}</span>
                                            </div>

                                            <div class="flex flex-col">
                                                <span class="font-semibold">Datos de la oferta:</span>
                                                @forelse($offerCards as $offerCard)
                                                    <span class="mt-0.5">
                                                        {{ $offerCard['title'] }} | {{ $offerCard['meta'] }} | Líneas: {{ $offerCard['line_count'] }} | {{ $offerCard['value'] }}
                                                    </span>
                                                @empty
                                                    <span class="mt-0.5 text-gray-500">-</span>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div x-show="openModal"
                                     style="display: none;"
                                     class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 px-4">
                                    <div @click.outside="
                                            if (window.crmPdfPopup?.containsTarget?.($event.target)) {
                                                return;
                                            }

                                            if (window.crmPdfPopup?.isOpen?.()) {
                                                window.crmPdfPopup.close();
                                                return;
                                            }

                                            openModal = false;
                                        " class="bg-white rounded-2xl shadow-xl w-full max-w-5xl max-h-[92vh] overflow-y-auto p-6">
                                        <div class="flex justify-between items-center mb-5 pb-3 border-b border-gray-200">
                                            <h3 class="text-xl font-bold text-gray-900">Detalle de validación</h3>
                                            <button @click="openModal = false" class="text-gray-400 hover:text-red-500 font-bold text-2xl">&times;</button>
                                        </div>

                                        @php
                                            $copyFieldsLeft = [
                                                'ruc' => ['label' => 'RUC', 'value' => $sale->customer_ruc ?? $lead->ruc ?? '-', 'copyable' => true],
                                                'business_name' => ['label' => 'Razón Social', 'value' => $sale->customer_business_name ?? $lead->business_name ?? '-', 'copyable' => true],
                                                'representative' => ['label' => 'Nombre del representante', 'value' => $sale->customer_representative_name ?? $lead->representative_name ?? $lead->full_name ?? '-', 'copyable' => true],
                                                'dni' => ['label' => 'DNI', 'value' => $sale->customer_dni ?? $lead->dni ?? '-', 'copyable' => true],
                                                'phone' => ['label' => 'Celular del representante', 'value' => $sale->customer_phone ?? '-', 'copyable' => true],
                                                'email' => ['label' => 'Correo del representante', 'value' => $sale->customer_email ?? '-', 'copyable' => true],
                                                'address' => ['label' => 'Dirección', 'value' => $sale->customer_address ?? '-', 'copyable' => true],
                                                'coordinates' => ['label' => 'Coordenadas', 'value' => $sale->customer_coordinates ?? '-', 'copyable' => true],
                                                'plan_code' => ['label' => 'Plano', 'value' => $sale->plan_code ?? '-', 'copyable' => true],
                                                'approval_code' => ['label' => 'Código aprobación', 'value' => $sale->approval_code ?? '-', 'copyable' => true],
                                            ];

                                            $copyFieldsRight = [
                                                'executive' => ['label' => 'Ejecutivo', 'value' => $sale->executive->name ?? '-', 'copyable' => false],
                                                'supervisor' => ['label' => 'Supervisor', 'value' => $sale->supervisor->name ?? '-', 'copyable' => false],
                                                'management_status' => ['label' => 'Estado gestión', 'value' => $managementLabel, 'copyable' => false],
                                                'sisac_status' => ['label' => 'Estado M.C actual', 'value' => $sisacLabel, 'copyable' => false],
                                                ...(in_array($sale->product_type, ['fijo', 'movil_fijo'], true)
                                                    ? [
                                                        'fixed_agreement_supports' => ['label' => 'Soporte fija', 'value' => collect($sale->fixed_agreement_supports ?? [])->map(fn ($item) => match ($item) { 'contrato_fijo' => 'Contrato fijo', 'grabacion_de_voz' => 'Grabación de voz', default => $item })->implode(', ') ?: '-', 'copyable' => false],
                                                    ]
                                                    : [
                                                        'service_channel' => ['label' => 'Canal', 'value' => $sale->service_channel === 'pdv' ? 'PDV' : ($sale->service_channel === 'centralizado' ? 'Centralizado' : '-'), 'copyable' => false],
                                                        'attention_time_slot' => ['label' => 'Franja', 'value' => $sale->attention_time_slot ?? '-', 'copyable' => false],
                                                        'attention_date' => ['label' => 'Fecha', 'value' => optional($sale->attention_date)->format('d/m/Y') ?? '-', 'copyable' => false],
                                                        'operator_name' => ['label' => 'Operador', 'value' => $sale->operator_name ?? '-', 'copyable' => false],
                                                    ]),
                                                'offered_line_count' => ['label' => 'Cantidad de líneas', 'value' => (string) ($sale->offered_line_count ?? '-'), 'copyable' => false],
                                                ...($sale->product_type === 'fijo'
                                                    ? []
                                                    : ['delivery_type' => ['label' => 'Entrega', 'value' => ucfirst(str_replace('_', ' ', $sale->delivery_type ?? '-')), 'copyable' => false]]),
                                                'accepted_at' => ['label' => 'Fecha acuerdo', 'value' => optional($sale->accepted_at)->format('d/m/Y H:i') ?? '-', 'copyable' => false],
                                            ];

                                            $detailFields = collect($copyFieldsLeft)
                                                ->merge($copyFieldsRight)
                                                ->all();
                                        @endphp

                                        <div class="mb-6 grid grid-cols-1 gap-2.5 text-sm text-gray-900 md:grid-cols-2 xl:grid-cols-3">
                                            @foreach($detailFields as $fieldKey => $field)
                                                <div class="rounded-lg border border-gray-200 bg-gray-50/80 px-3 py-2">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div class="min-w-0">
                                                            <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-500">
                                                                {{ $field['label'] }}
                                                            </div>
                                                            <div class="mt-0.5 break-all text-[13px] font-semibold leading-4 text-gray-900">
                                                                {{ $field['value'] }}
                                                            </div>

                                                            @if($fieldKey === 'coordinates' && !empty($sale->customer_coordinates))
                                                                <a
                                                                    href="https://www.google.com/maps/search/?api=1&query={{ urlencode($sale->customer_coordinates) }}"
                                                                    target="_blank"
                                                                    rel="noopener noreferrer"
                                                                    class="mt-1 inline-flex items-center text-xs font-medium text-indigo-600 transition hover:text-indigo-800"
                                                                >
                                                                    Ver mapa
                                                                </a>
                                                            @endif
                                                        </div>

                                                        @if(!empty($field['copyable']))
                                                            <button
                                                                type="button"
                                                                @click="copyValue('{{ $fieldKey }}', @js($field['value']))"
                                                                class="shrink-0 rounded-md border border-gray-300 bg-white px-2 py-0.5 text-[11px] font-medium text-gray-700 transition hover:bg-gray-50"
                                                            >
                                                                <span x-show="copiedField !== '{{ $fieldKey }}'">Copiar</span>
                                                                <span x-show="copiedField === '{{ $fieldKey }}'" style="display: none;">Copiado</span>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="grid grid-cols-1 gap-6 border-t border-gray-200 pb-5 pt-5 xl:grid-cols-[minmax(0,1.05fr)_minmax(300px,0.95fr)]">
                                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                                                <div class="flex items-center justify-between gap-3">
                                                    <div>
                                                        <h4 class="text-base font-semibold text-gray-900">Productos ofrecidos</h4>
                                                        <p class="mt-1 text-sm text-gray-500">Detalle comercial tomado al momento del acuerdo.</p>
                                                    </div>
                                                    <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-gray-700">
                                                        {{ count($sale->products_snapshot ?? []) }} producto{{ count($sale->products_snapshot ?? []) === 1 ? '' : 's' }}
                                                    </span>
                                                </div>

                                                <div class="mt-4 space-y-3">
                                                    @forelse(($sale->products_snapshot ?? []) as $product)
                                                        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm">
                                                            <div class="flex items-start justify-between gap-3">
                                                                <div>
                                                                    <div class="font-semibold text-gray-900">{{ $product['label'] ?? '-' }}</div>
                                                                    <div class="mt-1 text-gray-600">{{ $product['detail'] ?? '-' }}</div>
                                                                </div>
                                                                <div class="text-right text-xs text-gray-500">
                                                                    <div>Líneas: {{ $product['line_count'] ?? 0 }}</div>
                                                                    <div class="mt-1 font-semibold text-gray-900">
                                                                        {{ ($product['summary_value'] ?? null) ?: (isset($product['price']) && $product['price'] !== null ? 'S/ '.number_format((float) $product['price'], 2) : '-') }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="rounded-2xl border border-dashed border-gray-300 px-4 py-5 text-sm text-gray-500">
                                                            No hay productos registrados para este acuerdo.
                                                        </div>
                                                    @endforelse
                                                </div>

                                                @if(!empty($sale->portability_phone_numbers_snapshot))
                                                    <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-4">
                                                        <div class="flex items-center justify-between gap-3">
                                                            <div>
                                                                <h5 class="text-sm font-semibold text-gray-900">Números de portabilidad</h5>
                                                                <p class="mt-1 text-xs text-gray-500">
                                                                    Total de números portados: {{ count($sale->portability_phone_numbers_snapshot ?? []) }}
                                                                </p>
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <button
                                                                    type="button"
                                                                    @click="copyValue('all_portability_numbers_{{ $sale->id }}', @js(collect($sale->portability_phone_numbers_snapshot ?? [])->pluck('phone_number')->filter()->implode(', ')))"
                                                                    class="inline-flex items-center rounded-full border border-gray-300 bg-white px-3 py-1 text-xs font-semibold text-gray-700 transition hover:border-gray-400 hover:bg-gray-50"
                                                                >
                                                                    <span x-show="copiedField !== 'all_portability_numbers_{{ $sale->id }}'">Copiar números</span>
                                                                    <span x-show="copiedField === 'all_portability_numbers_{{ $sale->id }}'" style="display: none;">Copiados</span>
                                                                </button>
                                                                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                                                                    {{ count($sale->portability_phone_numbers_snapshot ?? []) }} línea{{ count($sale->portability_phone_numbers_snapshot ?? []) === 1 ? '' : 's' }}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="mt-3 overflow-y-auto pr-2" style="max-height: 240px;">
                                                            <div class="space-y-2">
                                                                @foreach(($sale->portability_phone_numbers_snapshot ?? []) as $portabilityPhone)
                                                                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                                                                        <div class="flex items-center justify-between gap-3">
                                                                            <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-500">
                                                                                {{ $portabilityPhone['row_label'] ?? 'Línea' }}
                                                                            </div>
                                                                            <button
                                                                                type="button"
                                                                                @click="copyValue('portability_phone_{{ $sale->id }}_{{ $loop->index }}', @js($portabilityPhone['phone_number'] ?? ''))"
                                                                                class="inline-flex shrink-0 items-center rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-700 transition hover:border-gray-400 hover:bg-gray-100"
                                                                                title="Copiar número"
                                                                                aria-label="Copiar número"
                                                                            >
                                                                                <span x-show="copiedField !== 'portability_phone_{{ $sale->id }}_{{ $loop->index }}'">Copiar</span>
                                                                                <span x-show="copiedField === 'portability_phone_{{ $sale->id }}_{{ $loop->index }}'" style="display: none;">Copiado</span>
                                                                            </button>
                                                                        </div>
                                                                        <div class="mt-2 grid grid-cols-2 gap-x-4 gap-y-2">
                                                                            <div class="min-w-0">
                                                                                <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-500">Número</div>
                                                                                <div class="mt-1 font-semibold text-gray-900">{{ $portabilityPhone['phone_number'] ?? '-' }}</div>
                                                                            </div>
                                                                            <div class="min-w-0">
                                                                                <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-500">Promoción</div>
                                                                                <div class="mt-1 font-semibold text-gray-900">{{ $portabilityPhone['display_offer'] ?? '-' }}</div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="space-y-6">
                                                @if($executiveFeedbackInteraction)
                                                    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                                                        <div class="flex items-center justify-between gap-3">
                                                            <div>
                                                                <h4 class="text-base font-semibold text-gray-900">Feedback del ejecutivo</h4>
                                                                <p class="mt-1 text-sm text-gray-500">
                                                                    {{ $executiveFeedbackInteraction->user->name ?? ($sale->executive->name ?? 'Ejecutivo') }}
                                                                    | {{ optional($executiveFeedbackInteraction->created_at)->format('d/m/Y H:i') ?: '-' }}
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <div class="mt-4 rounded-2xl border border-gray-200 bg-white px-4 py-3 text-sm leading-6 text-gray-700 whitespace-pre-line">
                                                            {{ $executiveFeedbackInteraction->call_detail }}
                                                        </div>
                                                    </div>
                                                @endif

                                                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                                                    <div class="flex items-center justify-between gap-3">
                                                        <div>
                                                            <h4 class="text-base font-semibold text-gray-900">Adjuntos del acuerdo</h4>
                                                            <p class="mt-1 text-sm text-gray-500">Archivos finales enviados junto con la ficha.</p>
                                                        </div>
                                                        <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-gray-700">
                                                            {{ count($sale->attachment_paths ?? []) }} archivo{{ count($sale->attachment_paths ?? []) === 1 ? '' : 's' }}
                                                        </span>
                                                    </div>

                                                    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                        @forelse(($sale->attachment_paths ?? []) as $attachmentPath)
                                                            @php
                                                                $attachmentUrl = route('agreements.attachments.show', ['sale' => $sale->id, 'filename' => basename($attachmentPath)]);
                                                                $attachmentName = basename($attachmentPath);
                                                                $attachmentExtension = strtolower(pathinfo($attachmentPath, PATHINFO_EXTENSION));
                                                                $isPdfAttachment = $attachmentExtension === 'pdf';
                                                            @endphp
                                                            @if($isPdfAttachment)
                                                                <button
                                                                    type="button"
                                                                    data-pdf-popup-url="{{ $attachmentUrl }}"
                                                                    data-pdf-popup-title="{{ $attachmentName }}"
                                                                    class="flex h-36 w-full flex-col justify-between rounded-2xl border border-rose-200 bg-gradient-to-br from-rose-50 via-white to-slate-50 p-4 text-left transition hover:border-rose-300"
                                                                >
                                                                    <div>
                                                                        <div class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-600">PDF</div>
                                                                        <div class="mt-2 break-all text-sm font-semibold leading-5 text-slate-900">{{ $attachmentName }}</div>
                                                                    </div>
                                                                    <div class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700">
                                                                        Ver PDF
                                                                    </div>
                                                                </button>
                                                            @else
                                                                <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener noreferrer" class="overflow-hidden rounded-2xl border border-gray-200 bg-white transition hover:border-gray-300">
                                                                    <img src="{{ $attachmentUrl }}" alt="Adjunto del acuerdo" class="h-36 w-full object-cover">
                                                                </a>
                                                            @endif
                                                        @empty
                                                            <div class="rounded-2xl border border-dashed border-gray-300 px-4 py-5 text-sm text-gray-500 sm:col-span-2">
                                                                Este acuerdo no tiene archivos adjuntos.
                                                            </div>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)] gap-6 border-t border-gray-200 pt-5">
                                            <form method="POST" action="{{ route('validation.update', $sale) }}" class="space-y-4">
                                                @csrf

                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Estado de M.C</label>
                                                    <select
                                                        name="sisac_status"
                                                        x-model="selectedSisacStatus"
                                                        @change="syncDeliveryState()"
                                                        class="mt-1 block w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                        @foreach($statusOptions as $key => $label)
                                                            <option
                                                                value="{{ $key }}"
                                                                {{ match ($key) {
                                                                    'en_evaluacion' => in_array($sale->sisac_status, ['en_evaluacion', 'pendiente_validacion', 'observado'], true),
                                                                    'activo' => in_array($sale->sisac_status, ['activo', 'aprobado'], true),
                                                                    'rechazado' => $sale->sisac_status === 'rechazado',
                                                                    'entregado' => $sale->sisac_status === 'entregado',
                                                                    default => false,
                                                                } ? 'selected' : '' }}
                                                            >
                                                                {{ $label }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <template x-for="(detail, index) in simDetails" :key="index">
                                                    <div class="hidden">
                                                        <input type="hidden" :name="`sim_details[${index}][serial_number]`" :value="detail.serial_number">
                                                        <input type="hidden" :name="`sim_details[${index}][sim_number]`" :value="detail.sim_number">
                                                    </div>
                                                </template>

                                                <div
                                                    x-show="requiresSimDelivery && lineCount > 0 && selectedSisacStatus === 'entregado'"
                                                    style="display: none;"
                                                    class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3"
                                                >
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div>
                                                            <div class="text-sm font-semibold text-emerald-900">Datos de entrega SIM</div>
                                                        </div>

                                                        <button
                                                            type="button"
                                                            @click="openDeliveryModal = true"
                                                            class="shrink-0 rounded-xl border border-emerald-300 bg-white px-3 py-2 text-sm font-medium text-emerald-900 transition hover:bg-emerald-100"
                                                        >
                                                            Completar datos
                                                        </button>
                                                    </div>

                                                    <div class="mt-3 max-h-72 overflow-y-auto pr-2">
                                                        <div class="space-y-1.5 text-sm text-gray-900">
                                                            <template x-for="(detail, index) in simDetails" :key="`summary-${index}`">
                                                                <div class="rounded-xl border border-emerald-200 bg-white p-3 shadow-sm">
                                                                    <div class="mb-2 flex items-center justify-between gap-3">
                                                                        <div>
                                                                            <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-900" x-text="detail.row_label || `Línea ${index + 1}`"></div>
                                                                            <div class="mt-1 text-xs text-emerald-800" x-text="detail.is_portability ? 'Portabilidad' : 'Alta nueva'"></div>
                                                                        </div>
                                                                        <div class="flex items-center gap-2">
                                                                            <span
                                                                                class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                                                                :class="detail.serial_number && detail.sim_number ? 'bg-emerald-100 text-emerald-900' : 'bg-amber-100 text-amber-800'"
                                                                                x-text="detail.serial_number && detail.sim_number ? 'Completo' : 'Pendiente'"
                                                                            ></span>
                                                                            <div class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold text-emerald-900 max-w-[180px] truncate" x-text="detail.offer_label || 'Sin promoción'"></div>
                                                                        </div>
                                                                    </div>

                                                                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2 md:gap-3">
                                                                        <div class="rounded-lg border border-emerald-200 bg-emerald-50/60 px-3 py-2">
                                                                            <div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-emerald-900">Serie SIM</div>
                                                                            <div
                                                                                class="mt-1 text-[13px] font-semibold leading-5"
                                                                                :class="detail.serial_number ? 'text-gray-900' : 'text-amber-700'"
                                                                                x-text="detail.serial_number || 'Pendiente de registrar'"
                                                                            ></div>
                                                                        </div>

                                                                        <div class="rounded-lg border border-emerald-200 bg-emerald-50/60 px-3 py-2">
                                                                            <div
                                                                                class="text-[11px] font-semibold uppercase tracking-[0.12em] text-emerald-900"
                                                                                x-text="detail.is_portability ? 'Número de portabilidad' : 'Número asignado'"
                                                                            ></div>
                                                                            <div
                                                                                class="mt-1 text-[13px] font-semibold leading-5"
                                                                                :class="detail.sim_number ? 'text-gray-900' : 'text-amber-700'"
                                                                                x-text="detail.sim_number || 'Pendiente de registrar'"
                                                                            ></div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Feed-back de Mesa de Control</label>
                                                    <textarea
                                                        name="feedback"
                                                        rows="6"
                                                        class="mt-1 block w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                                        placeholder="Escribe el detalle de la validación..."
                                                        required
                                                    ></textarea>
                                                </div>

                                                <div class="flex justify-end gap-3">
                                                    <button type="button" @click="openModal = false"
                                                        class="px-5 py-2.5 rounded-xl border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                                        Cerrar
                                                    </button>

                                                    <button type="submit"
                                                        class="px-5 py-2.5 bg-black text-white text-sm font-medium rounded-xl hover:bg-gray-800 transition">
                                                        Guardar validación
                                                    </button>
                                                </div>
                                            </form>

                                            <div class="min-h-[360px] flex flex-col">
                                                <div class="shrink-0">
                                                    <h4 class="text-lg font-semibold text-gray-900 mb-3">Historial de feedbacks</h4>

                                                    <div class="mb-4 rounded-lg bg-gray-50 border px-4 py-3 text-sm">
                                                        <span class="font-semibold">Estado actual del registro:</span>
                                                        {{ $sisacLabel }}
                                                    </div>

                                                    @if($requiresSimDelivery && $sale->simDetails->isNotEmpty())
                                                        <div class="mb-4 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3">
                                                            <div class="text-sm font-semibold text-gray-900">SIM registrados actualmente</div>
                                                            <div class="mt-3 space-y-2 text-sm text-gray-700">
                                                                @foreach($sale->simDetails as $detail)
                                                                    <div class="rounded-xl border border-gray-200 bg-white px-3 py-2">
                                                                        <div class="font-semibold text-gray-900">Línea {{ $detail->line_number ?? $loop->iteration }}</div>
                                                                        <div class="mt-1 grid grid-cols-1 gap-2 md:grid-cols-2">
                                                                            <div>
                                                                                <span class="font-semibold">Serie SIM:</span>
                                                                                <span>{{ $detail->serial_number ?? '-' }}</span>
                                                                            </div>

                                                                            <div>
                                                                                <span class="font-semibold">Número SIM:</span>
                                                                                <span>{{ $detail->sim_number ?? '-' }}</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="flex-1 overflow-y-auto pr-1 space-y-4 max-h-[420px]">
                                                    @php
                                                        $validationHistory = $sale->validationUpdates->sortByDesc('created_at');
                                                    @endphp

                                                    @forelse($validationHistory as $history)
                                                        <div class="border rounded-xl p-4 space-y-3">
                                                            <div class="flex items-start justify-between gap-4">
                                                                <div>
                                                                    <div class="font-medium text-gray-900">
                                                                        Estado: {{ $statusLabels[$history->sisac_status] ?? ucfirst(str_replace('_', ' ', $history->sisac_status)) }}
                                                                    </div>
                                                                    <div class="text-sm text-gray-500">
                                                                        Usuario: {{ $history->user->name ?? 'Sin usuario' }}
                                                                    </div>
                                                                </div>

                                                                <div class="text-sm text-gray-500">
                                                                    {{ optional($history->created_at)->format('d/m/Y H:i') }}
                                                                </div>
                                                            </div>

                                                            @if($history->simDetails->isNotEmpty())
                                                                <div class="space-y-2 text-sm text-gray-700">
                                                                    @foreach($history->simDetails as $detail)
                                                                        <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                                                                            <div class="font-semibold text-gray-900">Línea {{ $detail->line_number ?? $loop->iteration }}</div>
                                                                            <div class="mt-1 grid grid-cols-1 gap-2 md:grid-cols-2">
                                                                                <div>
                                                                                    <span class="font-semibold">Serie SIM:</span>
                                                                                    <span>{{ $detail->serial_number ?? '-' }}</span>
                                                                                </div>

                                                                                <div>
                                                                                    <span class="font-semibold">Número SIM:</span>
                                                                                    <span>{{ $detail->sim_number ?? '-' }}</span>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif

                                                            <div class="text-sm text-gray-700 whitespace-pre-line">
                                                                {{ $history->feedback ?: 'Sin comentario registrado.' }}
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="border border-dashed border-gray-300 rounded-xl p-6 text-center text-gray-500">
                                                            Este registro todavía no tiene historial de Mesa de Control.
                                                        </div>
                                                    @endforelse
                                                </div>
                                            </div>

                                            <div
                                                x-show="requiresSimDelivery && lineCount > 0 && openDeliveryModal"
                                                style="display: none;"
                                                class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/60 px-4"
                                            >
                                                <div @click.outside="openDeliveryModal = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                                                    <div class="flex items-center justify-between gap-3 border-b border-gray-200 pb-3">
                                                        <div>
                                                            <h4 class="text-lg font-semibold text-gray-900">Datos de SIM entregado</h4>
                                                        </div>

                                                        <button type="button" @click="openDeliveryModal = false" class="text-2xl font-bold text-gray-400 transition hover:text-red-500">&times;</button>
                                                    </div>

                                                    <div class="mt-4">
                                                        <div class="space-y-3">
                                                            <div class="hidden md:grid md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] md:gap-3">
                                                                <div class="rounded-lg bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-700">
                                                                    Número de serie
                                                                </div>
                                                                <div class="rounded-lg bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-700">
                                                                    Número asignado
                                                                </div>
                                                            </div>

                                                            <div class="max-h-[52vh] overflow-y-auto pr-2">
                                                                <div class="space-y-2">
                                                                    <template x-for="(detail, index) in simDetails" :key="`form-${index}`">
                                                                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
                                                                            <div class="mb-3 flex items-center justify-between gap-3">
                                                                                <div>
                                                                                    <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-500" x-text="detail.row_label || `Línea ${index + 1}`"></div>
                                                                                    <div class="mt-1 text-xs text-gray-500" x-text="detail.is_portability ? 'Portabilidad' : 'Alta nueva'"></div>
                                                                                </div>
                                                                                <div class="rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-gray-700" x-text="detail.offer_label || 'Sin promoción'"></div>
                                                                            </div>

                                                                            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                                                                <input
                                                                                    type="text"
                                                                                    x-model="detail.serial_number"
                                                                                    class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-[13px] leading-5 focus:border-indigo-500 focus:ring-indigo-500"
                                                                                    placeholder="Ej. 8912345678901234567"
                                                                                >

                                                                                <input
                                                                                    type="text"
                                                                                    x-model="detail.sim_number"
                                                                                    :readonly="detail.is_portability"
                                                                                    :class="detail.is_portability ? 'bg-gray-100 text-gray-600' : ''"
                                                                                    class="block w-full rounded-md border-gray-300 px-2.5 py-1.5 text-[13px] leading-5 focus:border-indigo-500 focus:ring-indigo-500"
                                                                                    :placeholder="detail.is_portability ? 'Número de portabilidad precargado' : 'Ej. 9xxxxxxxx'"
                                                                                >
                                                                            </div>
                                                                        </div>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="mt-5 flex justify-end gap-3">
                                                        <button type="button" @click="openDeliveryModal = false" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                                                            Cerrar
                                                        </button>

                                                        <button
                                                            type="button"
                                                            @click="openDeliveryModal = false"
                                                            class="rounded-xl bg-black px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800"
                                                        >
                                                            Usar estos datos
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $sales->links() }}
                    </div>
@endif
