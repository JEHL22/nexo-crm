<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ implode(' | ', $errors->all()) }}
                </div>
            @endif

            <div class="bg-white shadow rounded-lg p-6">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Gestión</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Revisión de ventas aceptadas y actualización del estado de gestión de postventa.
                    </p>
                </div>

                <form method="GET" action="{{ route('post-sale.index') }}" class="mb-6">
                    <div class="flex flex-col md:flex-row gap-3 md:items-center">
                        <div class="w-full md:w-56">
                            <select name="management_status" class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Estado de gestión</option>
                                @foreach($statusOptions as $key => $label)
                                    <option value="{{ $key }}" {{ ($filters['management_status'] ?? '') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="w-full md:w-56">
                            <input
                                type="text"
                                name="ruc"
                                value="{{ $filters['ruc'] ?? '' }}"
                                placeholder="Buscar : RUC"
                                class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                        <div class="flex gap-2">
                            <button type="submit"
                                class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-black text-white text-sm font-medium hover:bg-gray-800 transition">
                                Filtrar
                            </button>

                            @if(array_filter($filters))
                                <a href="{{ route('post-sale.index') }}"
                                   class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                    Limpiar
                                </a>
                            @endif
                        </div>
                    </div>
                </form>

                @if($sales->isEmpty())
                    <div class="border border-dashed border-gray-300 rounded-xl p-8 text-center text-gray-500">
                        No se encontraron registros de postventa con los filtros actuales.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($sales as $sale)
                            @php
                                $lead = $sale->lead;
                                $latestPostSale = $sale->postSaleUpdates->sortByDesc('created_at')->first();
                                $hasPostSaleReview = $sale->postSaleUpdates->contains(fn ($update) => !is_null($update->user_id));
                                $supervisorValidationLabel = $sale->supervisor_validation_status === 'validado' ? 'Validado por supervisor' : 'Pendiente de supervisor';
                                $productsSnapshot = collect($sale->products_snapshot ?? []);
                                $isNewForPostSale = !$hasPostSaleReview;

                                $productLabel = match ($sale->product_type) {
                                    'movil' => 'Móvil',
                                    'fijo' => 'Fijo',
                                    'movil_fijo' => 'Móvil + Fijo',
                                    default => 'No especificado',
                                };

                                $offerLines = $productsSnapshot
                                    ->map(fn ($product) => ($product['label'] ?? 'Producto').': '.($product['detail'] ?? '-').' | Líneas: '.($product['line_count'] ?? 0).' | Precio: '.(($product['summary_value'] ?? null) ?: (isset($product['price']) && $product['price'] !== null ? 'S/ '.number_format((float) $product['price'], 2) : '-')))
                                    ->values();

                                $managementLabel = $statusOptions[$sale->management_status] ?? ucfirst(str_replace('_', ' ', $sale->management_status));
                                $sisacLabel = match ($sale->sisac_status) {
                                    'en_evaluacion', 'pendiente_validacion', 'observado' => 'En evaluación',
                                    'activo', 'aprobado' => 'Activo',
                                    'rechazado' => 'Rechazado',
                                    'entregado' => 'Entregado',
                                    default => ucfirst(str_replace('_', ' ', $sale->sisac_status)),
                                };
                            @endphp

                            <div x-data="{ openModal: false }" class="rounded-2xl border {{ $isNewForPostSale ? 'border-amber-400 bg-amber-50/50 ring-1 ring-amber-200' : ($loop->first ? 'border-blue-500' : 'border-gray-300') }} bg-white px-5 py-3">
                                <div class="space-y-3">
                                    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_max-content] gap-x-4 gap-y-2 items-start">
                                        <div class="min-w-0 rounded-lg bg-gray-50 border border-gray-200 px-2 py-1">
                                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[13px] text-gray-900 leading-tight">
                                                @if($isNewForPostSale)
                                                    <span class="font-semibold border border-amber-300 bg-amber-100 px-2 py-0.5 rounded-lg text-amber-800 whitespace-nowrap">
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
                                                <span class="font-semibold">Código aprobación:</span>
                                                <span>{{ $sale->approval_code ?? '-' }}</span>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <div>
                                                <span class="font-semibold">Producto ofrecido:</span>
                                                <span>{{ $productLabel }}</span>
                                            </div>

                                            <div class="flex flex-col">
                                                <span class="font-semibold">Datos de la oferta:</span>
                                                @forelse($offerLines as $line)
                                                    <span class="mt-0.5">{{ $line }}</span>
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
                                    <div @click.outside="openModal = false" class="bg-white rounded-2xl shadow-xl w-full max-w-5xl max-h-[92vh] overflow-y-auto p-6">
                                        <div class="flex justify-between items-center mb-5 pb-3 border-b border-gray-200">
                                            <h3 class="text-xl font-bold text-gray-900">Detalle de gestión</h3>
                                            <button @click="openModal = false" class="text-gray-400 hover:text-red-500 font-bold text-2xl">&times;</button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-900 mb-6">
                                            <div class="space-y-2">
                                                <div><span class="font-semibold">RUC:</span> {{ $sale->customer_ruc ?? $lead->ruc ?? '-' }}</div>
                                                <div><span class="font-semibold">Razón Social:</span> {{ $sale->customer_business_name ?? $lead->business_name ?? '-' }}</div>
                                                <div><span class="font-semibold">Nombre del representante:</span> {{ $sale->customer_representative_name ?? $lead->representative_name ?? $lead->full_name ?? '-' }}</div>
                                                <div><span class="font-semibold">DNI:</span> {{ $sale->customer_dni ?? $lead->dni ?? '-' }}</div>
                                                <div><span class="font-semibold">Celular del representante:</span> {{ $sale->customer_phone ?? '-' }}</div>
                                                <div><span class="font-semibold">Correo del representante:</span> {{ $sale->customer_email ?? '-' }}</div>
                                                <div><span class="font-semibold">Dirección:</span> {{ $sale->customer_address ?? '-' }}</div>
                                            </div>

                                            <div class="space-y-2">
                                                <div><span class="font-semibold">Ejecutivo:</span> {{ $sale->executive->name ?? '-' }}</div>
                                                <div><span class="font-semibold">Supervisor:</span> {{ $sale->supervisor->name ?? '-' }}</div>
                                                <div><span class="font-semibold">Estado gestión actual:</span> {{ $managementLabel }}</div>
                                                <div><span class="font-semibold">Estado Sisac:</span> {{ $sisacLabel }}</div>
                                                <div><span class="font-semibold">Código aprobación:</span> {{ $sale->approval_code ?? '-' }}</div>
                                                @if(in_array($sale->product_type, ['fijo', 'movil_fijo'], true))
                                                    <div><span class="font-semibold">Soporte fija:</span> {{ collect($sale->fixed_agreement_supports ?? [])->map(fn ($item) => match ($item) { 'contrato_fijo' => 'Contrato fijo', 'grabacion_de_voz' => 'Grabación de voz', default => $item })->implode(', ') ?: '-' }}</div>
                                                @endif
                                                @if($sale->product_type !== 'fijo')
                                                    <div><span class="font-semibold">Canal:</span> {{ $sale->service_channel === 'pdv' ? 'PDV' : ($sale->service_channel === 'centralizado' ? 'Centralizado' : '-') }}</div>
                                                    <div><span class="font-semibold">Franja:</span> {{ $sale->attention_time_slot ?? '-' }}</div>
                                                    <div><span class="font-semibold">Entrega:</span> {{ ucfirst(str_replace('_', ' ', $sale->delivery_type ?? '-')) }}</div>
                                                @endif
                                                <div><span class="font-semibold">Fecha acuerdo:</span> {{ optional($sale->accepted_at)->format('d/m/Y H:i') ?? '-' }}</div>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)] gap-6 border-t border-gray-200 pt-5">
                                            <form method="POST" action="{{ route('post-sale.update', $sale) }}" class="space-y-4">
                                                @csrf

                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Estado de gestión</label>
                                                    <select name="management_status" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                                        @foreach($statusOptions as $key => $label)
                                                            <option value="{{ $key }}" {{ $sale->management_status === $key ? 'selected' : '' }}>
                                                                {{ $label }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Feed-back de postventa</label>
                                                    <textarea
                                                        name="feedback"
                                                        rows="6"
                                                        class="mt-1 block w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                                        placeholder="Escribe el detalle de postventa..."
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
                                                        Guardar gestión
                                                    </button>
                                                </div>
                                            </form>

                                            <div class="min-h-[360px] flex flex-col">
                                                <div class="shrink-0">
                                                    <h4 class="text-lg font-semibold text-gray-900 mb-3">Historial de feedbacks</h4>

                                                    <div class="mb-4 rounded-lg bg-gray-50 border px-4 py-3 text-sm">
                                                        <span class="font-semibold">Estado actual del registro:</span>
                                                        {{ $managementLabel }}
                                                    </div>
                                                </div>

                                                <div class="flex-1 overflow-y-auto pr-1 space-y-4 max-h-[420px]">
                                                    @php
                                                        $postSaleHistory = $sale->postSaleUpdates->sortByDesc('created_at');
                                                    @endphp

                                                    @forelse($postSaleHistory as $history)
                                                        <div class="border rounded-xl p-4 space-y-3">
                                                            <div class="flex items-start justify-between gap-4">
                                                                <div>
                                                                    <div class="font-medium text-gray-900">
                                                                        Estado: {{ $statusOptions[$history->management_status] ?? ucfirst(str_replace('_', ' ', $history->management_status)) }}
                                                                    </div>
                                                                    <div class="text-sm text-gray-500">
                                                                        Usuario: {{ $history->user->name ?? 'Sin usuario' }}
                                                                    </div>
                                                                </div>

                                                                <div class="text-sm text-gray-500">
                                                                    {{ optional($history->created_at)->format('d/m/Y H:i') }}
                                                                </div>
                                                            </div>

                                                            <div class="text-sm text-gray-700 whitespace-pre-line">
                                                                {{ $history->feedback ?: 'Sin comentario registrado.' }}
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="border border-dashed border-gray-300 rounded-xl p-6 text-center text-gray-500">
                                                            Este registro todavía no tiene historial de postventa.
                                                        </div>
                                                    @endforelse
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
</x-app-layout>
