<x-app-layout>
    <div class="py-6">
        <div class="mx-auto max-w-[1600px] space-y-5 px-3 sm:px-4 lg:px-5">
            <div class="overflow-hidden rounded-[28px] bg-white shadow-sm ring-1 ring-black/5">
                <div class="border-b border-slate-100 bg-gradient-to-r from-slate-100 via-white to-cyan-50 px-5 py-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                        <div class="max-w-3xl">
                            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-slate-700">Seguimiento de Gerencia</p>
                            <h1 class="mt-2 text-3xl font-bold text-gray-900">Acuerdos consolidados</h1>
                            <p class="mt-1.5 text-sm text-gray-500">
                                Visualiza el flujo completo de cada acuerdo: validación del supervisor, postventa y Mesa de Control, con sus historiales en un solo módulo.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-2xl bg-white px-4 py-3 text-center shadow-sm ring-1 ring-black/5">
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Registros</div>
                                <div class="mt-2 text-2xl font-black text-gray-900">{{ $sales->total() }}</div>
                            </div>
                            <div class="rounded-2xl bg-white px-4 py-3 text-center shadow-sm ring-1 ring-black/5">
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Página</div>
                                <div class="mt-2 text-2xl font-black text-gray-900">{{ $sales->currentPage() }}</div>
                            </div>
                            <div class="rounded-2xl bg-white px-4 py-3 text-center shadow-sm ring-1 ring-black/5">
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Pend. supervisor</div>
                                <div class="mt-2 text-2xl font-black text-gray-900">{{ $sales->getCollection()->where('supervisor_validation_status', 'pendiente')->count() }}</div>
                            </div>
                            <div class="rounded-2xl bg-white px-4 py-3 text-center shadow-sm ring-1 ring-black/5">
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Validados</div>
                                <div class="mt-2 text-2xl font-black text-gray-900">{{ $sales->getCollection()->where('supervisor_validation_status', 'validado')->count() }}</div>
                            </div>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('management.agreements.index') }}" class="mt-5">
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                            <div class="rounded-2xl bg-white px-4 py-2.5 shadow-sm ring-1 ring-black/5">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">RUC</label>
                                <input type="text" name="ruc" value="{{ $filters['ruc'] }}" class="mt-1.5 block w-full rounded-xl border-gray-300 text-sm focus:border-slate-400 focus:ring-slate-400" placeholder="Buscar RUC">
                            </div>

                            <div class="rounded-2xl bg-white px-4 py-2.5 shadow-sm ring-1 ring-black/5">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Supervisor</label>
                                <select name="supervisor_validation_status" class="mt-1.5 block w-full rounded-xl border-gray-300 text-sm focus:border-slate-400 focus:ring-slate-400">
                                    <option value="">Todos</option>
                                    <option value="pendiente" @selected($filters['supervisor_validation_status'] === 'pendiente')>Pendiente</option>
                                    <option value="validado" @selected($filters['supervisor_validation_status'] === 'validado')>Validado</option>
                                </select>
                            </div>

                            <div class="rounded-2xl bg-white px-4 py-2.5 shadow-sm ring-1 ring-black/5">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Postventa</label>
                                <select name="management_status" class="mt-1.5 block w-full rounded-xl border-gray-300 text-sm focus:border-slate-400 focus:ring-slate-400">
                                    <option value="">Todos</option>
                                    @foreach(['pendiente_validacion' => 'Pendiente validación', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado', 'observado' => 'Observado', 'pendiente_supervision' => 'Pendiente supervisión'] as $value => $label)
                                        <option value="{{ $value }}" @selected($filters['management_status'] === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="rounded-2xl bg-white px-4 py-2.5 shadow-sm ring-1 ring-black/5">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Mesa de Control</label>
                                <select name="sisac_status" class="mt-1.5 block w-full rounded-xl border-gray-300 text-sm focus:border-slate-400 focus:ring-slate-400">
                                    <option value="">Todos</option>
                                    @foreach(['en_evaluacion' => 'En evaluación', 'activo' => 'Activo', 'rechazado' => 'Rechazado', 'entregado' => 'Entregado'] as $value => $label)
                                        <option value="{{ $value }}" @selected($filters['sisac_status'] === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-end gap-3">
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                                    Filtrar
                                </button>
                                <a href="{{ route('management.agreements.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                                    Limpiar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="p-5">
                    @if($sales->isEmpty())
                        <div class="rounded-2xl border border-dashed border-gray-300 px-6 py-16 text-center text-sm text-gray-500">
                            No hay acuerdos para los filtros seleccionados.
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
                                    $productSummary = collect($sale->products_snapshot ?? [])->pluck('label')->implode(' + ') ?: 'Sin producto';
                                    $hasPostSaleReview = $sale->postSaleUpdates->contains(fn ($update) => !is_null($update->user_id));
                                    $hasValidationReview = $sale->validationUpdates->contains(fn ($update) => !is_null($update->user_id));

                                    $pendingStageLabel = null;
                                    $pendingStageClasses = 'border-amber-300 bg-amber-100 text-amber-800';

                                    if ($sale->supervisor_validation_status === 'pendiente') {
                                        $pendingStageLabel = 'Nuevo en supervisor';
                                    } elseif (!$hasPostSaleReview) {
                                        $pendingStageLabel = 'Nuevo en Postventa y Mesa de Control';
                                    } elseif (!$hasValidationReview) {
                                        $pendingStageLabel = 'Nuevo en mesa de control';
                                        $pendingStageClasses = 'border-sky-300 bg-sky-100 text-sky-800';
                                    }
                                @endphp

                                <div x-data="{ openModal: false }" class="rounded-3xl border {{ $pendingStageLabel ? 'border-amber-300 ring-1 ring-amber-200' : 'border-gray-200' }} bg-white p-3.5 shadow-sm">
                                    <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                                        <div class="flex-1 space-y-4">
                                            @if($pendingStageLabel)
                                                <div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide">
                                                    <span class="inline-flex items-center rounded-full border px-3 py-1 {{ $pendingStageClasses }}">
                                                        {{ $pendingStageLabel }}
                                                    </span>
                                                </div>
                                            @endif

                                            <div class="grid grid-cols-1 gap-2.5 md:grid-cols-2 xl:grid-cols-4 text-sm">
                                                <div class="rounded-2xl bg-gray-50 px-4 py-2.5">
                                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">RUC</div>
                                                    <div class="mt-1.5 font-semibold text-gray-900">{{ $sale->customer_ruc }}</div>
                                                </div>
                                                <div class="rounded-2xl bg-gray-50 px-4 py-2.5">
                                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Razón social</div>
                                                    <div class="mt-1.5 font-semibold text-gray-900">{{ $sale->customer_business_name }}</div>
                                                </div>
                                                <div class="rounded-2xl bg-gray-50 px-4 py-2.5">
                                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Representante</div>
                                                    <div class="mt-1.5 font-semibold text-gray-900">{{ $sale->customer_representative_name }}</div>
                                                </div>
                                                <div class="rounded-2xl bg-gray-50 px-4 py-2.5">
                                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Producto</div>
                                                    <div class="mt-1.5 font-semibold text-gray-900">{{ $productSummary }}</div>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 gap-2.5 md:grid-cols-2 xl:grid-cols-4 text-sm">
                                                <div class="rounded-2xl border border-gray-200 px-4 py-2.5">
                                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Ejecutivo</div>
                                                    <div class="mt-1.5 text-gray-900">{{ $sale->executive->name ?? '-' }}</div>
                                                </div>
                                                <div class="rounded-2xl border border-gray-200 px-4 py-2.5">
                                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Supervisor</div>
                                                    <div class="mt-1.5 text-gray-900">{{ $sale->supervisor->name ?? '-' }}</div>
                                                </div>
                                                <div class="rounded-2xl border border-gray-200 px-4 py-2.5">
                                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Campaña</div>
                                                    <div class="mt-1.5 text-gray-900">{{ $sale->campaign->name ?? '-' }}</div>
                                                </div>
                                                <div class="rounded-2xl border border-gray-200 px-4 py-2.5">
                                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Contacto</div>
                                                    <div class="mt-1.5 text-gray-900">{{ $sale->customer_phone ?? '-' }}</div>
                                                </div>
                                            </div>
                                        </div>

                                                <button @click="openModal = true" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-base font-semibold text-slate-800 transition hover:bg-slate-50">
                                                    Ver detalle
                                                </button>
                                    </div>

                                    <div class="mt-3 grid grid-cols-1 gap-2.5 md:grid-cols-4 text-sm">
                                        <div class="rounded-2xl bg-gray-50 px-4 py-2.5">
                                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Supervisor</div>
                                            <div class="mt-1.5 font-semibold text-gray-900">{{ $supervisorLabel }}</div>
                                        </div>
                                        <div class="rounded-2xl bg-gray-50 px-4 py-2.5">
                                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Postventa</div>
                                            <div class="mt-1.5 font-semibold text-gray-900">{{ $postSaleLabel }}</div>
                                        </div>
                                        <div class="rounded-2xl bg-gray-50 px-4 py-2.5">
                                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Mesa de Control</div>
                                            <div class="mt-1.5 font-semibold text-gray-900">{{ $validationLabel }}</div>
                                        </div>
                                        <div class="rounded-2xl bg-gray-50 px-4 py-2.5">
                                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Aceptado</div>
                                            <div class="mt-1.5 font-semibold text-gray-900">{{ optional($sale->accepted_at)->format('d/m/Y H:i') ?: '-' }}</div>
                                        </div>
                                    </div>

                                    <div x-show="openModal" style="display:none;" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 px-4 py-6">
                                        <div @click.outside="
                                                if (window.crmPdfPopup?.containsTarget?.($event.target)) {
                                                    return;
                                                }

                                                if (window.crmPdfPopup?.isOpen?.()) {
                                                    window.crmPdfPopup.close();
                                                    return;
                                                }

                                                openModal = false;
                                            " class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-[28px] bg-white shadow-2xl">
                                            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
                                                <div>
                                                    <h3 class="text-xl font-bold text-gray-900">Trazabilidad del acuerdo</h3>
                                                    <p class="mt-1 text-sm text-gray-500">Vista completa del acuerdo validado y sus pasos operativos.</p>
                                                </div>
                                                <button @click="openModal = false" class="text-2xl font-bold text-gray-400 transition hover:text-slate-700">&times;</button>
                                            </div>

                                            <div class="flex-1 overflow-y-auto p-6">
                                                <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(320px,0.9fr)]">
                                                    <div class="space-y-6">
                                                        <div class="rounded-3xl border border-gray-200 bg-gray-50 p-5">
                                                            <h4 class="text-lg font-semibold text-gray-900">Ficha validada</h4>
                                                            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 text-sm">
                                                                <div><span class="font-semibold">RUC:</span> {{ $sale->customer_ruc }}</div>
                                                                <div><span class="font-semibold">Razón social:</span> {{ $sale->customer_business_name }}</div>
                                                                <div><span class="font-semibold">DNI:</span> {{ $sale->customer_dni }}</div>
                                                                <div><span class="font-semibold">Representante:</span> {{ $sale->customer_representative_name }}</div>
                                                                <div><span class="font-semibold">Celular:</span> {{ $sale->customer_phone }}</div>
                                                                <div><span class="font-semibold">Correo:</span> {{ $sale->customer_email }}</div>
                                                                <div class="md:col-span-2"><span class="font-semibold">Dirección:</span> {{ $sale->customer_address }}</div>
                                                                <div class="md:col-span-2"><span class="font-semibold">Coordenadas:</span> {{ $sale->customer_coordinates }}</div>
                                                                <div><span class="font-semibold">Plano:</span> {{ $sale->plan_code ?? '-' }}</div>
                                                                <div><span class="font-semibold">Canal:</span> {{ $sale->service_channel === 'pdv' ? 'PDV' : ($sale->service_channel === 'centralizado' ? 'Centralizado' : '-') }}</div>
                                                                <div><span class="font-semibold">Franja:</span> {{ $sale->attention_time_slot ?? '-' }}</div>
                                                                <div><span class="font-semibold">Fecha:</span> {{ optional($sale->attention_date)->format('d/m/Y') ?? '-' }}</div>
                                                                <div><span class="font-semibold">Operador:</span> {{ $sale->operator_name ?? '-' }}</div>
                                                                <div><span class="font-semibold">Cantidad de líneas:</span> {{ $sale->offered_line_count ?? 0 }}</div>
                                                                <div><span class="font-semibold">Entrega:</span> {{ ucfirst(str_replace('_', ' ', $sale->delivery_type ?? '-')) }}</div>
                                                                <div><span class="font-semibold">Campaña:</span> {{ $sale->campaign->name ?? '-' }}</div>
                                                            </div>
                                                        </div>

                                                        <div class="rounded-3xl border border-gray-200 p-5">
                                                            <h4 class="text-lg font-semibold text-gray-900">Productos del acuerdo</h4>
                                                            <div class="mt-4 space-y-3">
                                                                @forelse(($sale->products_snapshot ?? []) as $product)
                                                                    <div class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm">
                                                                        <div class="font-semibold text-gray-900">{{ $product['label'] ?? '-' }}</div>
                                                                        <div class="mt-1 text-gray-600">{{ $product['detail'] ?? '-' }}</div>
                                                                        <div class="mt-2 text-gray-600">Líneas: {{ $product['line_count'] ?? 0 }} | Precio: {{ ($product['summary_value'] ?? null) ?: (isset($product['price']) && $product['price'] !== null ? 'S/ '.number_format((float) $product['price'], 2) : '-') }}</div>
                                                                    </div>
                                                                @empty
                                                                    <div class="rounded-2xl border border-dashed border-gray-300 px-4 py-5 text-sm text-gray-500">
                                                                        No hay productos registrados.
                                                                    </div>
                                                                @endforelse
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

                                                    <div class="space-y-6">
                                                        <div class="rounded-3xl border border-gray-200 p-5">
                                                            <h4 class="text-lg font-semibold text-gray-900">Historial supervisor</h4>
                                                            <div class="mt-4 max-h-[280px] space-y-3 overflow-y-auto pr-2">
                                                                @forelse($sale->histories as $history)
                                                                    <div class="rounded-2xl border border-gray-200 px-4 py-3 text-sm">
                                                                        <div class="flex items-start justify-between gap-3">
                                                                            <div class="font-semibold text-gray-900">{{ $history->notes ?: ucfirst(str_replace('_', ' ', $history->action)) }}</div>
                                                                            <div class="text-xs text-gray-500">{{ optional($history->created_at)->format('d/m/Y H:i') }}</div>
                                                                        </div>
                                                                        <div class="mt-1 text-xs text-gray-500">Usuario: {{ $history->user->name ?? 'Sistema' }}</div>

                                                                        @if(!empty($history->changed_fields))
                                                                            <div class="mt-3 space-y-2">
                                                                                @foreach($history->changed_fields as $change)
                                                                                    <div class="rounded-xl bg-gray-50 px-3 py-2 text-xs text-gray-700">
                                                                                        <div class="font-semibold text-gray-900">{{ $change['label'] ?? $change['field'] }}</div>
                                                                                        <div class="mt-1 text-rose-600">Antes: {{ $change['old'] !== '' ? $change['old'] : 'Vacío' }}</div>
                                                                                        <div class="mt-1 text-emerald-700">Después: {{ $change['new'] !== '' ? $change['new'] : 'Vacío' }}</div>
                                                                                    </div>
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

                                                        <div class="rounded-3xl border border-gray-200 p-5">
                                                            <h4 class="text-lg font-semibold text-gray-900">Historial postventa</h4>
                                                            <div class="mt-4 max-h-[240px] space-y-3 overflow-y-auto pr-2">
                                                                @forelse($sale->postSaleUpdates->sortByDesc('created_at') as $history)
                                                                    <div class="rounded-2xl border border-gray-200 px-4 py-3 text-sm">
                                                                        <div class="flex items-start justify-between gap-3">
                                                                            <div class="font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', $history->management_status)) }}</div>
                                                                            <div class="text-xs text-gray-500">{{ optional($history->created_at)->format('d/m/Y H:i') }}</div>
                                                                        </div>
                                                                        <div class="mt-1 text-xs text-gray-500">Usuario: {{ $history->user->name ?? 'Sistema' }}</div>
                                                                        <div class="mt-2 text-gray-600 whitespace-pre-line">{{ $history->feedback ?: 'Sin comentario registrado.' }}</div>
                                                                    </div>
                                                                @empty
                                                                    <div class="rounded-2xl border border-dashed border-gray-300 px-4 py-5 text-sm text-gray-500">
                                                                        Sin historial de postventa.
                                                                    </div>
                                                                @endforelse
                                                            </div>
                                                        </div>

                                                        <div class="rounded-3xl border border-gray-200 p-5">
                                                            <h4 class="text-lg font-semibold text-gray-900">Historial Mesa de Control</h4>
                                                            <div class="mt-4 max-h-[240px] space-y-3 overflow-y-auto pr-2">
                                                                @forelse($sale->validationUpdates->sortByDesc('created_at') as $history)
                                                                    <div class="rounded-2xl border border-gray-200 px-4 py-3 text-sm">
                                                                        <div class="flex items-start justify-between gap-3">
                                                                            <div class="font-semibold text-gray-900">
                                                                                {{ match ($history->sisac_status) {
                                                                                    'en_evaluacion', 'pendiente_validacion', 'observado' => 'En evaluación',
                                                                                    'activo', 'aprobado' => 'Activo',
                                                                                    'rechazado' => 'Rechazado',
                                                                                    'entregado' => 'Entregado',
                                                                                    default => ucfirst(str_replace('_', ' ', $history->sisac_status)),
                                                                                } }}
                                                                            </div>
                                                                            <div class="text-xs text-gray-500">{{ optional($history->created_at)->format('d/m/Y H:i') }}</div>
                                                                        </div>
                                                                        <div class="mt-1 text-xs text-gray-500">Usuario: {{ $history->user->name ?? 'Sistema' }}</div>
                                                                        <div class="mt-2 text-gray-600 whitespace-pre-line">{{ $history->feedback ?: 'Sin comentario registrado.' }}</div>
                                                                    </div>
                                                                @empty
                                                                    <div class="rounded-2xl border border-dashed border-gray-300 px-4 py-5 text-sm text-gray-500">
                                                                        Sin historial de Mesa de Control.
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
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $sales->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
