@if($sales->isEmpty())
    <div class="rounded-2xl border border-dashed border-gray-300 px-6 py-16 text-center text-sm text-gray-500">
        No hay acuerdos registrados para tu equipo con los filtros actuales.
    </div>
@else
    <div class="space-y-4">
        @foreach($sales as $sale)
            @php
                $supervisorLabel = $sale->supervisor_validation_status === 'validado' ? 'Validado' : 'Pendiente';
                $postSaleLabel = ucfirst(str_replace('_', ' ', $sale->management_status ?? '-'));
                $validationLabel = match ($sale->sisac_status) {
                    'en_evaluacion', 'pendiente_validacion', 'observado' => 'En evaluación',
                    'activo', 'aprobado' => 'Activo',
                    'rechazado' => 'Rechazado',
                    'entregado' => 'Entregado',
                    default => ucfirst(str_replace('_', ' ', $sale->sisac_status ?? '-')),
                };
                $productSummaryItems = collect($sale->products_snapshot ?? [])
                    ->filter(fn ($product) => filled($product['label'] ?? null))
                    ->groupBy('label')
                    ->map(function ($items, $label) {
                        $items = collect($items);
                        $isMobile = $label === 'Móvil';
                        $lineCount = $isMobile
                            ? $items->sum(fn ($item) => (int) ($item['line_count'] ?? 0))
                            : $items->count();

                        return [
                            'label' => $label,
                            'count' => $lineCount,
                        ];
                    })
                    ->values();
                $productSummary = $productSummaryItems->isNotEmpty()
                    ? $productSummaryItems
                        ->map(fn ($item) => ($item['label'] ?? 'Producto').(($item['count'] ?? 0) > 0 ? ' ('.$item['count'].')' : ''))
                        ->implode(' + ')
                    : 'Sin producto';
                $productSummaryCount = $productSummaryItems->sum('count');
                $splitHistoryList = function (?string $value, string $separator) {
                    return collect(explode($separator, (string) $value))
                        ->map(fn ($item) => trim((string) $item))
                        ->filter()
                        ->values();
                };
                $summarizeAttachmentItems = function ($items) {
                    $items = collect($items)->filter()->values();

                    if ($items->isEmpty()) {
                        return 'Sin archivos';
                    }

                    $extensions = $items
                        ->map(fn ($item) => strtolower((string) pathinfo($item, PATHINFO_EXTENSION)))
                        ->filter()
                        ->countBy()
                        ->map(fn ($count, $extension) => $count.' '.strtoupper($extension))
                        ->values()
                        ->implode(' | ');

                    return $items->count().' archivo(s)'.($extensions !== '' ? ' | '.$extensions : '');
                };
            @endphp
            @php
                $executiveFeedbackInteraction = $sale->lead?->interactions
                    ?->first(fn ($interaction) => !$interaction->is_agreement && filled($interaction->call_detail));
                $latestValidationWithSimDetails = $sale->validationUpdates
                    ->sortByDesc('created_at')
                    ->first(fn ($history) => $history->simDetails->isNotEmpty());
            @endphp

            <div
                x-data="{ openModal: {{ (($openTraceabilitySaleId ?? null) === $sale->id) ? 'true' : 'false' }} }"
                x-init="
                    window.__supervisorTraceabilityModalOpen = openModal;
                    $watch('openModal', value => {
                        window.__supervisorTraceabilityModalOpen = value;
                    });
                "
                :data-supervisor-traceability-open="openModal ? '1' : '0'"
                class="rounded-[26px] border {{ $sale->supervisor_validation_status === 'pendiente' ? 'border-blue-300 ring-1 ring-blue-200' : 'border-gray-200' }} bg-white p-3 shadow-sm"
            >
                <div class="flex flex-col gap-2 xl:flex-row xl:items-start xl:justify-between">
                    <div class="flex-1 space-y-1.5">
                        @if($sale->supervisor_validation_status === 'pendiente')
                            <div class="flex flex-wrap gap-1.5 text-[11px] font-semibold uppercase tracking-[0.14em]">
                                <span class="inline-flex items-center rounded-full border border-blue-300 bg-blue-100 px-2 py-0.5 text-blue-800">
                                    Pendiente de revisión
                                </span>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 gap-1.5 md:grid-cols-2 xl:grid-cols-4">
                            <div class="space-y-1.5 text-[13px]">
                                <div class="rounded-xl bg-gray-50 px-3 py-1.5">
                                    <div class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">RUC</div>
                                    <div class="mt-0.5 font-semibold leading-5 text-gray-900">{{ $sale->customer_ruc }}</div>
                                </div>
                                <div class="rounded-xl border border-gray-200 px-3 py-1.5">
                                    <div class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">Ejecutivo</div>
                                    <div class="mt-0.5 leading-5 text-gray-900">{{ $sale->executive->name ?? '-' }}</div>
                                </div>
                                <div class="rounded-xl bg-gray-50 px-3 py-1.5">
                                    <div class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">Estado supervisor</div>
                                    <div class="mt-0.5 font-semibold leading-5 text-gray-900">{{ $supervisorLabel }}</div>
                                </div>
                            </div>

                            <div class="space-y-1.5 text-[13px]">
                                <div class="rounded-xl bg-gray-50 px-3 py-1.5">
                                    <div class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">Razón social</div>
                                    <div class="mt-0.5 font-semibold leading-5 text-gray-900">{{ $sale->customer_business_name }}</div>
                                </div>
                                <div class="rounded-xl border border-gray-200 px-3 py-1.5">
                                    <div class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">Supervisor</div>
                                    <div class="mt-0.5 leading-5 text-gray-900">{{ $sale->supervisor->name ?? '-' }}</div>
                                </div>
                                <div class="rounded-xl bg-gray-50 px-3 py-1.5">
                                    <div class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">Postventa</div>
                                    <div class="mt-0.5 font-semibold leading-5 text-gray-900">{{ $postSaleLabel }}</div>
                                </div>
                            </div>

                            <div class="space-y-1.5 text-[13px]">
                                <div class="rounded-xl bg-gray-50 px-3 py-1.5">
                                    <div class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">Representante</div>
                                    <div class="mt-0.5 font-semibold leading-5 text-gray-900">{{ $sale->customer_representative_name }}</div>
                                </div>
                                <div class="rounded-xl border border-gray-200 px-3 py-1.5">
                                    <div class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">Campaña</div>
                                    <div class="mt-0.5 leading-5 text-gray-900">{{ $sale->campaign->name ?? '-' }}</div>
                                </div>
                                <div class="rounded-xl bg-gray-50 px-3 py-1.5">
                                    <div class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">Mesa de Control</div>
                                    <div class="mt-0.5 font-semibold leading-5 text-gray-900">{{ $validationLabel }}</div>
                                </div>
                            </div>

                            <div class="space-y-1.5 text-[13px]">
                                <div class="rounded-xl bg-gray-50 px-3 py-1.5">
                                    <div class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">Producto</div>
                                    <div class="mt-0.5 flex items-center justify-between gap-2">
                                        <div class="min-w-0 font-semibold leading-5 text-gray-900">{{ $productSummary }}</div>
                                        @if($productSummaryCount > 0)
                                            <span class="shrink-0 inline-flex items-center rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">
                                                {{ $productSummaryCount }} item(s)
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="rounded-xl border border-gray-200 px-3 py-1.5">
                                    <div class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">Contacto</div>
                                    <div class="mt-0.5 leading-5 text-gray-900">{{ $sale->customer_phone ?? '-' }}</div>
                                </div>
                                <div class="rounded-xl bg-gray-50 px-3 py-1.5">
                                    <div class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">Aceptado</div>
                                    <div class="mt-0.5 font-semibold leading-5 text-gray-900">{{ optional($sale->accepted_at)->format('d/m/Y H:i') ?: '-' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5 xl:min-w-[120px]">
                        <button @click="window.__supervisorTraceabilityModalOpen = true; openModal = true" class="inline-flex items-center justify-center rounded-xl border-2 border-gray-900 px-3 py-1.5 text-[13px] font-semibold text-gray-900 transition hover:bg-gray-50">
                            Trazabilidad
                        </button>

                        @if($sale->supervisor_validation_status === 'pendiente')
                            <a href="{{ route('supervisor.agreements.show', $sale) }}"
                               class="inline-flex items-center justify-center rounded-xl bg-black px-3 py-1.5 text-[13px] font-semibold text-white transition hover:bg-gray-800">
                                Revisar
                            </a>
                        @endif
                    </div>
                </div>

                <div x-show="openModal" style="display:none;" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 px-4 py-5">
                    <div @click.outside="
                            if (window.crmPdfPopup?.containsTarget?.($event.target)) {
                                return;
                            }

                            if (window.crmPdfPopup?.isOpen?.()) {
                                window.crmPdfPopup.close();
                                return;
                            }

                            window.__supervisorTraceabilityModalOpen = false;
                            openModal = false;
                        " class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-[28px] bg-white shadow-2xl">
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">Trazabilidad del acuerdo</h3>
                                <p class="mt-1 text-sm text-gray-500">Vista completa del acuerdo de tu equipo y sus pasos operativos.</p>
                            </div>
                            <button @click="window.__supervisorTraceabilityModalOpen = false; openModal = false" class="text-2xl font-bold text-gray-400 transition hover:text-red-500">&times;</button>
                        </div>

                        <div class="flex-1 overflow-y-auto p-5">
                            <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]">
                                <div class="space-y-4">
                                    <div class="rounded-3xl border border-gray-200 bg-gray-50 p-4">
                                        <h4 class="text-lg font-semibold text-gray-900">Ficha validada</h4>
                                        <div class="mt-3 grid grid-cols-1 gap-2 text-sm sm:grid-cols-2 xl:grid-cols-4">
                                            <div class="space-y-0.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5">
                                                <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">RUC</div>
                                                <div class="leading-5 text-gray-900">{{ $sale->customer_ruc }}</div>
                                            </div>
                                            <div class="space-y-0.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5">
                                                <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">Razón social</div>
                                                <div class="leading-5 text-gray-900">{{ $sale->customer_business_name }}</div>
                                            </div>
                                            <div class="space-y-0.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5">
                                                <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">DNI</div>
                                                <div class="leading-5 text-gray-900">{{ $sale->customer_dni }}</div>
                                            </div>
                                            <div class="space-y-0.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5">
                                                <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">Representante</div>
                                                <div class="leading-5 text-gray-900">{{ $sale->customer_representative_name }}</div>
                                            </div>
                                            <div class="space-y-0.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5">
                                                <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">Celular</div>
                                                <div class="leading-5 text-gray-900">{{ $sale->customer_phone }}</div>
                                            </div>
                                            <div class="space-y-0.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5">
                                                <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">Correo</div>
                                                <div class="break-all leading-5 text-gray-900">{{ $sale->customer_email }}</div>
                                            </div>
                                            <div class="space-y-0.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5 sm:col-span-2 xl:col-span-4">
                                                <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">Dirección</div>
                                                <div class="leading-5 text-gray-900">{{ $sale->customer_address }}</div>
                                            </div>
                                            <div class="space-y-0.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5 sm:col-span-2 xl:col-span-4">
                                                <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">Coordenadas</div>
                                                <div class="leading-5 text-gray-900">{{ $sale->customer_coordinates }}</div>
                                            </div>
                                            <div class="space-y-0.5 rounded-2xl border border-gray-200 bg-white px-3 py-2">
                                                <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">Plano</div>
                                                <div class="leading-5 text-gray-900">{{ $sale->plan_code ?? '-' }}</div>
                                            </div>
                                            @if(in_array($sale->product_type, ['fijo', 'movil_fijo'], true))
                                                <div class="space-y-0.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5">
                                                    <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">Soporte fija</div>
                                                    <div class="leading-5 text-gray-900">{{ collect($sale->fixed_agreement_supports ?? [])->map(fn ($item) => match ($item) { 'contrato_fijo' => 'Contrato fijo', 'grabacion_de_voz' => 'Grabación de voz', default => $item })->implode(', ') ?: '-' }}</div>
                                                </div>
                                            @endif
                                            @if($sale->product_type !== 'fijo')
                                                <div class="space-y-0.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5">
                                                    <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">Canal</div>
                                                    <div class="leading-5 text-gray-900">{{ $sale->service_channel === 'pdv' ? 'PDV' : ($sale->service_channel === 'centralizado' ? 'Centralizado' : '-') }}</div>
                                                </div>
                                                <div class="space-y-0.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5">
                                                    <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">Franja</div>
                                                    <div class="leading-5 text-gray-900">{{ $sale->attention_time_slot ?? '-' }}</div>
                                                </div>
                                                <div class="space-y-0.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5">
                                                    <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">Fecha</div>
                                                    <div class="leading-5 text-gray-900">{{ optional($sale->attention_date)->format('d/m/Y') ?? '-' }}</div>
                                                </div>
                                                <div class="space-y-0.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5">
                                                    <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">Operador</div>
                                                    <div class="leading-5 text-gray-900">{{ $sale->operator_name ?? '-' }}</div>
                                                </div>
                                            @endif
                                            <div class="space-y-0.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5">
                                                <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">Cantidad de líneas</div>
                                                <div class="leading-5 text-gray-900">{{ $sale->offered_line_count ?? 0 }}</div>
                                            </div>
                                            @if($sale->product_type !== 'fijo')
                                                <div class="space-y-0.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5">
                                                    <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">Entrega</div>
                                                    <div class="leading-5 text-gray-900">{{ ucfirst(str_replace('_', ' ', $sale->delivery_type ?? '-')) }}</div>
                                                </div>
                                            @endif
                                            <div class="space-y-0.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5">
                                                <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-gray-500">Campaña</div>
                                                <div class="leading-5 text-gray-900">{{ $sale->campaign->name ?? '-' }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    @if($sale->simDetails->isNotEmpty())
                                        <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-4 ring-1 ring-emerald-100">
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <h4 class="text-lg font-semibold text-emerald-900">SIM entregadas por Mesa de Control</h4>
                                                    <p class="mt-1 text-sm text-emerald-800">
                                                        Resultado operativo registrado al marcar el acuerdo como entregado.
                                                    </p>
                                                </div>
                                                @if($latestValidationWithSimDetails)
                                                    <div class="rounded-xl border border-emerald-200 bg-white px-3 py-2 text-xs text-emerald-800">
                                                        <div class="font-semibold">{{ optional($latestValidationWithSimDetails->created_at)->format('d/m/Y H:i') ?: '-' }}</div>
                                                        <div class="mt-1">{{ $latestValidationWithSimDetails->user->name ?? 'Sistema' }}</div>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="mt-3 max-h-[180px] overflow-y-auto pr-2">
                                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                    @foreach($sale->simDetails->sortBy('line_number') as $detail)
                                                        <div class="rounded-2xl border border-emerald-200 bg-white px-3 py-2.5 text-sm text-emerald-950">
                                                            <div class="flex items-center justify-between gap-3">
                                                                <div class="font-semibold">Línea {{ $detail->line_number }}</div>
                                                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-emerald-800">
                                                                    Entregado
                                                                </span>
                                                            </div>
                                                            <div class="mt-2 grid grid-cols-2 gap-3">
                                                                <div>
                                                                    <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-emerald-700">Serie SIM</div>
                                                                    <div class="mt-1 leading-5">{{ $detail->serial_number ?: '-' }}</div>
                                                                </div>
                                                                <div>
                                                                    <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-emerald-700">Número SIM</div>
                                                                    <div class="mt-1 leading-5">{{ $detail->sim_number ?: '-' }}</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="rounded-3xl border border-gray-200 p-5">
                                        <h4 class="text-lg font-semibold text-gray-900">Productos del acuerdo</h4>
                                        <div class="mt-4 max-h-[240px] overflow-y-auto pr-2">
                                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                            @forelse(($sale->products_snapshot ?? []) as $product)
                                                <div class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div class="min-w-0">
                                                            <div class="font-semibold text-gray-900">{{ $product['label'] ?? '-' }}</div>
                                                            <div class="mt-1 truncate text-gray-600">{{ $product['detail'] ?? '-' }}</div>
                                                        </div>
                                                        <div class="shrink-0 text-right text-xs text-gray-500">
                                                            Líneas: {{ $product['line_count'] ?? 0 }}
                                                        </div>
                                                    </div>
                                                    <div class="mt-2 text-sm font-medium text-gray-700">
                                                        Precio: {{ ($product['summary_value'] ?? null) ?: (isset($product['price']) && $product['price'] !== null ? 'S/ '.number_format((float) $product['price'], 2) : '-') }}
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="rounded-2xl border border-dashed border-gray-300 px-4 py-5 text-sm text-gray-500 sm:col-span-2">
                                                    No hay productos registrados.
                                                </div>
                                            @endforelse
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-3xl border border-gray-200 p-5">
                                        <h4 class="text-lg font-semibold text-gray-900">Adjuntos del acuerdo</h4>
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
                                                        class="flex h-40 w-full flex-col justify-between rounded-2xl border border-rose-200 bg-gradient-to-br from-rose-50 via-white to-slate-50 p-4 text-left transition hover:border-rose-300"
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
                                                    <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener noreferrer" class="overflow-hidden rounded-2xl border border-gray-200 bg-gray-50 transition hover:border-gray-300">
                                                        <img src="{{ $attachmentUrl }}" alt="Adjunto del acuerdo" class="h-40 w-full object-cover">
                                                    </a>
                                                @endif
                                                @empty
                                                    <div class="rounded-2xl border border-dashed border-gray-300 px-4 py-5 text-sm text-gray-500 sm:col-span-2">
                                                        No hay archivos adjuntos.
                                                    </div>
                                            @endforelse
                                        </div>
                                    </div>

                                </div>

                                <div class="space-y-4">
                                    <div class="rounded-3xl border border-gray-200 p-4">
                                        <h4 class="text-lg font-semibold text-gray-900">Seguimiento y feedbacks</h4>
                                        <div class="mt-3 space-y-3">
                                            <div class="rounded-2xl border border-gray-200 p-4">
                                                <h4 class="text-lg font-semibold text-gray-900">Historial postventa</h4>
                                                <div class="mt-3 max-h-[180px] space-y-2.5 overflow-y-auto pr-1">
                                                    @forelse($sale->postSaleUpdates->sortByDesc('created_at') as $history)
                                                        <div class="rounded-2xl border px-4 py-3 text-sm {{ $loop->first ? 'border-emerald-200 bg-emerald-50 ring-1 ring-emerald-100' : 'border-gray-200' }}">
                                                            <div class="flex items-start justify-between gap-3">
                                                                <div class="flex items-center gap-2">
                                                                    <div class="font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', $history->management_status)) }}</div>
                                                                    @if($loop->first)
                                                                        <span class="inline-flex items-center rounded-full border border-emerald-300 bg-white px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-emerald-700">Último</span>
                                                                    @endif
                                                                </div>
                                                                <div class="text-xs text-gray-500">{{ optional($history->created_at)->format('d/m/Y H:i') }}</div>
                                                            </div>
                                                            <div class="mt-1 text-xs text-gray-500">Usuario: {{ $history->user->name ?? 'Sistema' }}</div>
                                                            <div class="mt-2 whitespace-pre-line text-gray-600">{{ $history->feedback ?: 'Sin comentario registrado.' }}</div>
                                                        </div>
                                                    @empty
                                                        <div class="rounded-2xl border border-dashed border-gray-300 px-4 py-5 text-sm text-gray-500">
                                                            Sin historial de postventa.
                                                        </div>
                                                    @endforelse
                                                </div>
                                            </div>

                                            <div class="rounded-2xl border border-gray-200 p-4">
                                                <h4 class="text-lg font-semibold text-gray-900">Historial Mesa de Control</h4>
                                                <div class="mt-3 max-h-[180px] space-y-2.5 overflow-y-auto pr-1">
                                                    @forelse($sale->validationUpdates->sortByDesc('created_at') as $history)
                                                        <div class="rounded-2xl border px-4 py-3 text-sm {{ $loop->first ? 'border-emerald-200 bg-emerald-50 ring-1 ring-emerald-100' : 'border-gray-200' }}">
                                                            <div class="flex items-start justify-between gap-3">
                                                                <div class="flex items-center gap-2">
                                                                    <div class="font-semibold text-gray-900">
                                                                        {{ match ($history->sisac_status) {
                                                                            'en_evaluacion', 'pendiente_validacion', 'observado' => 'En evaluación',
                                                                            'activo', 'aprobado' => 'Activo',
                                                                            'rechazado' => 'Rechazado',
                                                                            'entregado' => 'Entregado',
                                                                            default => ucfirst(str_replace('_', ' ', $history->sisac_status)),
                                                                        } }}
                                                                    </div>
                                                                    @if($loop->first)
                                                                        <span class="inline-flex items-center rounded-full border border-emerald-300 bg-white px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-emerald-700">Último</span>
                                                                    @endif
                                                                </div>
                                                                <div class="text-xs text-gray-500">{{ optional($history->created_at)->format('d/m/Y H:i') }}</div>
                                                            </div>
                                                            <div class="mt-1 text-xs text-gray-500">Usuario: {{ $history->user->name ?? 'Sistema' }}</div>
                                                            <div class="mt-2 whitespace-pre-line text-gray-600">{{ $history->feedback ?: 'Sin comentario registrado.' }}</div>
                                                        </div>
                                                    @empty
                                                        <div class="rounded-2xl border border-dashed border-gray-300 px-4 py-5 text-sm text-gray-500">
                                                            Sin historial de Mesa de Control.
                                                        </div>
                                                    @endforelse
                                                </div>
                                            </div>

                                            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm ring-1 ring-emerald-100">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="text-base font-semibold text-gray-900">Feedback del ejecutivo</div>
                                                    @if($executiveFeedbackInteraction?->created_at)
                                                        <div class="text-xs text-gray-500">{{ optional($executiveFeedbackInteraction?->created_at)->format('d/m/Y H:i') }}</div>
                                                    @endif
                                                </div>
                                                <div class="mt-3 rounded-xl border border-gray-200 bg-white px-4 py-3">
                                                    <div class="font-semibold text-gray-900">{{ $executiveFeedbackInteraction?->user?->name ?? $sale->executive->name ?? 'Sin usuario' }}</div>
                                                    <div class="mt-2 whitespace-pre-line leading-5 text-gray-700">
                                                        {{ filled($executiveFeedbackInteraction?->call_detail) ? $executiveFeedbackInteraction->call_detail : 'Sin comentarios registrados aún.' }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="rounded-2xl border border-gray-200 p-4">
                                                <h4 class="text-lg font-semibold text-gray-900">Historial supervisor</h4>
                                                <div class="mt-3 max-h-[220px] space-y-2.5 overflow-y-auto pr-1">
                                            @forelse($sale->histories->sortByDesc('created_at') as $history)
                                                <div class="rounded-2xl border px-4 py-3 text-sm {{ $loop->first ? 'border-emerald-200 bg-emerald-50 ring-1 ring-emerald-100' : 'border-gray-200' }}">
                                                    <div class="flex items-start justify-between gap-3">
                                                        <div class="flex items-center gap-2">
                                                            <div class="font-semibold text-gray-900">{{ $history->notes ?: ucfirst(str_replace('_', ' ', $history->action)) }}</div>
                                                            @if($loop->first)
                                                                <span class="inline-flex items-center rounded-full border border-emerald-300 bg-white px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.12em] text-emerald-700">Último</span>
                                                            @endif
                                                        </div>
                                                        <div class="text-xs text-gray-500">{{ optional($history->created_at)->format('d/m/Y H:i') }}</div>
                                                    </div>
                                                    <div class="mt-1 text-xs text-gray-500">Usuario: {{ $history->user->name ?? 'Sistema' }}</div>

                                                    @if(!empty($history->changed_fields))
                                                        <div class="mt-3 space-y-2">
                                                            @foreach($history->changed_fields as $change)
                                                                @php
                                                                    $fieldName = $change['field'] ?? '';
                                                                    $oldValue = (string) ($change['old'] ?? '');
                                                                    $newValue = (string) ($change['new'] ?? '');
                                                                    $isOfferChange = $fieldName === 'products_snapshot';
                                                                    $isPortabilityChange = $fieldName === 'portability_phone_numbers_snapshot';
                                                                    $isAttachmentChange = $fieldName === 'attachment_paths';
                                                                    $oldItems = $isOfferChange
                                                                        ? $splitHistoryList($oldValue, ' || ')
                                                                        : ($isPortabilityChange
                                                                            ? $splitHistoryList($oldValue, ' | ')
                                                                            : ($isAttachmentChange ? $splitHistoryList($oldValue, ', ') : collect()));
                                                                    $newItems = $isOfferChange
                                                                        ? $splitHistoryList($newValue, ' || ')
                                                                        : ($isPortabilityChange
                                                                            ? $splitHistoryList($newValue, ' | ')
                                                                            : ($isAttachmentChange ? $splitHistoryList($newValue, ', ') : collect()));
                                                                    $addedAttachmentItems = $isAttachmentChange ? $newItems->diff($oldItems)->values() : collect();
                                                                    $removedAttachmentItems = $isAttachmentChange ? $oldItems->diff($newItems)->values() : collect();
                                                                @endphp

                                                                @if($isOfferChange || $isPortabilityChange || $isAttachmentChange)
                                                                    <div class="rounded-xl bg-gray-50 px-3 py-3 text-xs text-gray-700">
                                                                        <div class="font-semibold text-gray-900">{{ $change['label'] ?? $fieldName }}</div>

                                                                        @if($isAttachmentChange)
                                                                            <div class="mt-2 grid gap-2">
                                                                                <div class="rounded-xl border border-rose-200 bg-white px-3 py-2">
                                                                                    <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-rose-600">Antes</div>
                                                                                    <div class="mt-1 text-sm font-medium text-gray-700">{{ $summarizeAttachmentItems($oldItems) }}</div>
                                                                                </div>
                                                                                <div class="rounded-xl border border-emerald-200 bg-white px-3 py-2">
                                                                                    <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-600">Después</div>
                                                                                    <div class="mt-1 text-sm font-medium text-gray-700">{{ $summarizeAttachmentItems($newItems) }}</div>
                                                                                </div>
                                                                                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2">
                                                                                    <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-700">Añadidos</div>
                                                                                    <div class="mt-1 text-sm text-emerald-900">{{ $addedAttachmentItems->isNotEmpty() ? $addedAttachmentItems->implode(', ') : 'Sin archivos añadidos' }}</div>
                                                                                </div>
                                                                                <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2">
                                                                                    <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-rose-700">Quitados</div>
                                                                                    <div class="mt-1 text-sm text-rose-900">{{ $removedAttachmentItems->isNotEmpty() ? $removedAttachmentItems->implode(', ') : 'Sin archivos quitados' }}</div>
                                                                                </div>
                                                                            </div>
                                                                        @else
                                                                            <div class="mt-2 grid gap-2">
                                                                                <div class="rounded-xl border border-rose-200 bg-white px-3 py-2">
                                                                                    <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-rose-600">Antes</div>
                                                                                    @if($oldItems->isNotEmpty())
                                                                                        <div class="mt-2 space-y-1.5">
                                                                                            @foreach($oldItems as $item)
                                                                                                <div class="rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-2 text-sm text-gray-700">{{ $item }}</div>
                                                                                            @endforeach
                                                                                        </div>
                                                                                    @else
                                                                                        <div class="mt-1 text-sm text-gray-500">Vacío</div>
                                                                                    @endif
                                                                                </div>
                                                                                <div class="rounded-xl border border-emerald-200 bg-white px-3 py-2">
                                                                                    <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-emerald-600">Después</div>
                                                                                    @if($newItems->isNotEmpty())
                                                                                        <div class="mt-2 space-y-1.5">
                                                                                            @foreach($newItems as $item)
                                                                                                <div class="rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-2 text-sm text-gray-700">{{ $item }}</div>
                                                                                            @endforeach
                                                                                        </div>
                                                                                    @else
                                                                                        <div class="mt-1 text-sm text-gray-500">Vacío</div>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                @else
                                                                    <div class="rounded-xl bg-gray-50 px-3 py-2 text-xs text-gray-700">
                                                                        <div class="font-semibold text-gray-900">{{ $change['label'] ?? $change['field'] }}</div>
                                                                        <div class="mt-1 text-rose-600">Antes: {{ $change['old'] !== '' ? $change['old'] : 'Vacío' }}</div>
                                                                        <div class="mt-1 text-emerald-700">Después: {{ $change['new'] !== '' ? $change['new'] : 'Vacío' }}</div>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @empty
                                                <div class="rounded-2xl border border-dashed border-gray-300 px-4 py-5 text-sm text-gray-500">
                                                    Sin historial del supervisor.
                                                </div>
                                            @endforelse
                                                </div>
                                            </div>
                                        </div>
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
