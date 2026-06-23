<x-app-layout>
    <style>
        .my-sales-wide-table {
            display: none;
        }

        .my-sales-compact-card {
            display: block;
        }

        @media (min-width: 1500px) {
            .my-sales-wide-table {
                display: block;
            }

            .my-sales-compact-card {
                display: none;
            }
        }
    </style>
    <div class="py-8">
        <div class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                <div class="crm-panel-hero border-b border-slate-200 px-6 py-8 sm:px-8">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-2xl">
                            <p class="text-sm font-medium uppercase tracking-[0.22em] text-emerald-100/80">Cierre comercial</p>
                            <h1 class="mt-3 text-3xl font-semibold tracking-tight">Mis ventas</h1>
                            <p class="mt-2 text-sm text-slate-200 sm:text-base">
                                Visualiza acuerdos aceptados, su estado interno y las observaciones de validación.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.18em] text-emerald-100/70">Ventas</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $sales->total() }}</div>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.18em] text-emerald-100/70">Página</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $sales->currentPage() }}</div>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur sm:col-span-1 col-span-2">
                                <div class="text-xs uppercase tracking-[0.18em] text-emerald-100/70">Filtro RUC</div>
                                <div class="mt-2 text-sm font-semibold">{{ $filters['ruc'] ?? 'Sin filtro' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 sm:p-8">
                <form method="GET" action="{{ route('my-sales.index') }}" class="mb-6">
                    <div class="flex flex-col md:flex-row gap-3 md:items-center">
                        <div class="w-full md:w-48">
                            <select name="management_status" class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" onchange="this.form.submit()">
                                <option value="">Estado de gestión</option>
                                @foreach($managementStatusOptions as $key => $label)
                                    <option value="{{ $key }}" {{ ($filters['management_status'] ?? '') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="w-full md:w-48">
                            <select name="sisac_status" class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" onchange="this.form.submit()">
                                <option value="">Estado de M.C</option>
                                @foreach($sisacFilterOptions as $key => $label)
                                    <option value="{{ $key }}" {{ ($filters['sisac_status'] ?? '') === $key ? 'selected' : '' }}>
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
                                class="crm-accent-button inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-medium transition">
                                Filtrar
                            </button>

                            @if(array_filter($filters))
                                <a href="{{ route('my-sales.index') }}"
                                   class="crm-accent-outline-button inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-medium transition">
                                    Limpiar
                                </a>
                            @endif
                        </div>
                    </div>
                </form>

                @if($sales->isEmpty())
                    <div class="border border-dashed border-gray-300 rounded-xl p-8 text-center text-gray-500">
                        No se encontraron ventas con estado "Acuerdo aceptado" que coincidan con la búsqueda.
                    </div>
                @else
                    <div class="space-y-3">
                        <div class="my-sales-wide-table rounded-2xl border border-slate-200">
                            <table class="min-w-full table-fixed border-collapse">
                                <colgroup>
                                    <col class="w-[22%]">
                                    <col class="w-[18%]">
                                    <col class="w-[16%]">
                                    <col class="w-[38%]">
                                    <col class="w-[6%]">
                                </colgroup>
                                <thead class="bg-slate-900 text-white">
                                    <tr class="text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-white/75">
                                        <th class="px-5 py-3">Estado</th>
                                        <th class="px-5 py-3">Cliente</th>
                                        <th class="px-5 py-3">Contacto</th>
                                        <th class="px-5 py-3">Oferta comercial</th>
                                        <th class="px-5 py-3 text-right">Acción</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        @foreach($sales as $sale)
                            @php
                                $lead = $sale->lead;
                                $latestPostSale = $sale->postSaleUpdates->sortByDesc('created_at')->first();
                                $latestValidation = $sale->validationUpdates->sortByDesc('created_at')->first();
                                $latestSupervisorHistory = $sale->histories->sortByDesc('created_at')->first();
                                $productsSnapshot = collect($sale->products_snapshot ?? []);
                                $isFixedOnlyAgreement = $sale->product_type === 'fijo';
                                $requiresFixedAgreementSupport = in_array($sale->product_type, ['fijo', 'movil_fijo'], true);
                                $supervisorLabel = $sale->supervisor_validation_status === 'validado' ? 'Validado por supervisor' : 'Pendiente de supervisor';

                                $productType = match ($sale->product_type) {
                                    'movil' => 'Móvil',
                                    'fijo' => 'Fijo',
                                    'movil_fijo' => 'Móvil + Fijo',
                                    default => 'No especificado',
                                };

                                $offerCards = $productsSnapshot
                                    ->map(function ($product) use ($promotionPriceMap) {
                                        $lineCount = (int) ($product['line_count'] ?? 0);
                                        $summaryValue = trim((string) ($product['summary_value'] ?? ''));
                                        $unitPrice = (float) ($product['price'] ?? 0);
                                        if (($product['type'] ?? null) === 'movil' && $unitPrice <= 0 && $summaryValue !== '') {
                                            $unitPrice = (float) ($promotionPriceMap[$summaryValue] ?? 0);
                                        }
                                        $lineTotal = (float) ($product['line_total'] ?? ($lineCount > 0 ? $unitPrice * $lineCount : $unitPrice));
                                        $isFixed = ($product['type'] ?? null) === 'fijo';

                                        return [
                                            'title' => ($product['label'] ?? 'Producto').($isFixed ? '' : ' · '.($product['detail'] ?? '-')),
                                            'detail' => $isFixed
                                                ? (($product['detail'] ?? '-') !== '' ? 'Plan: '.($product['detail'] ?? '-') : 'Plan no definido')
                                                : 'Promoción: '.(($product['summary_value'] ?? null) ?: 'Sin promoción'),
                                            'lines_label' => $isFixed ? 'Cargo fijo' : $lineCount.' lín.',
                                            'unit_label' => $isFixed ? null : 'S/ '.number_format($unitPrice, 2).' c/u',
                                            'total_label' => 'Total: S/ '.number_format($lineTotal, 2),
                                            'is_fixed' => $isFixed,
                                        ];
                                    })
                                    ->values();
                                $visibleOfferCards = $offerCards->take(1)->values();
                                $hiddenOfferCards = $offerCards->slice(1)->values();
                                $mobileProduct = $productsSnapshot->firstWhere('type', 'movil');
                                $grossMonthlyPayment = $productsSnapshot->sum(function ($product) use ($promotionPriceMap) {
                                    $lineCount = (int) ($product['line_count'] ?? 0);
                                    $summaryValue = trim((string) ($product['summary_value'] ?? ''));
                                    $unitPrice = (float) ($product['price'] ?? 0);
                                    if (($product['type'] ?? null) === 'movil' && $unitPrice <= 0 && $summaryValue !== '') {
                                        $unitPrice = (float) ($promotionPriceMap[$summaryValue] ?? 0);
                                    }

                                    return (float) ($product['line_total'] ?? ($lineCount > 0 ? $unitPrice * $lineCount : $unitPrice));
                                });
                                $netMonthlyPayment = $grossMonthlyPayment > 0 ? ($grossMonthlyPayment / 1.18) : null;

                                $managementLabel = $managementStatusLabels[$sale->management_status] ?? ucfirst(str_replace('_', ' ', $sale->management_status));
                                $sisacLabel = $sisacStatusLabels[$sale->sisac_status] ?? ucfirst(str_replace('_', ' ', $sale->sisac_status));
                                $serviceChannelLabel = match ($sale->service_channel) {
                                    'pdv' => 'PDV',
                                    'centralizado' => 'Centralizado',
                                    default => '-',
                                };
                                $deliveryTypeLabel = match ($sale->delivery_type) {
                                    'regular' => 'Regular',
                                    'express' => 'Express',
                                    'almacen_propio' => 'Almacén propio',
                                    default => '-',
                                };
                                $fixedSupportLabel = collect($sale->fixed_agreement_supports ?? [])
                                    ->map(fn ($support) => match ($support) {
                                        'contrato_fijo' => 'Contrato fijo',
                                        'grabacion_de_voz' => 'Grabación de voz',
                                        default => $support,
                                    })
                                    ->implode(', ');
                            @endphp
                            @php
                                $executiveFeedbackInteraction = $sale->lead?->interactions
                                    ?->first(fn ($interaction) => !$interaction->is_agreement && filled($interaction->call_detail));
                            @endphp

                            <div x-data="{ openModal: false, openOfferSummary: false }" class="space-y-3">
                                <div class="my-sales-wide-table rounded-2xl border {{ $loop->first ? 'crm-accent-border' : 'border-gray-300' }} bg-white">
                                    <table class="min-w-full table-fixed border-collapse">
                                        <colgroup>
                                            <col class="w-[22%]">
                                            <col class="w-[18%]">
                                            <col class="w-[16%]">
                                            <col class="w-[38%]">
                                            <col class="w-[6%]">
                                        </colgroup>
                                        <tbody>
                                            <tr class="align-top text-sm text-slate-900">
                                                <td class="px-5 py-4">
                                                    <div class="space-y-2">
                                                        <div class="text-[14px] font-semibold leading-5 text-slate-900">
                                                            {{ ucfirst(str_replace('_', ' ', $sale->status)) }}
                                                        </div>
                                                        <div class="flex flex-wrap gap-1.5 text-[12px] leading-tight">
                                                            <span class="crm-neutral-chip font-medium px-2 py-0.5 rounded-lg whitespace-nowrap">Gestión: {{ $managementLabel }}</span>
                                                            <span class="crm-neutral-chip font-medium px-2 py-0.5 rounded-lg whitespace-nowrap">M.C: {{ $sisacLabel }}</span>
                                                            <span class="crm-neutral-chip font-medium px-2 py-0.5 rounded-lg whitespace-nowrap">{{ $supervisorLabel }}</span>
                                                        </div>
                                                        <div class="space-y-1 leading-5">
                                                            <div><span class="font-semibold">Ejecutivo:</span> {{ $sale->executive->name ?? '-' }}</div>
                                                            <div><span class="font-semibold">Supervisor:</span> {{ $sale->supervisor->name ?? '-' }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-5 py-4">
                                                    <div class="space-y-1.5 leading-5">
                                                        <div><span class="font-semibold">RUC:</span> {{ $sale->customer_ruc ?? $lead->ruc ?? '-' }}</div>
                                                        <div><span class="font-semibold">Razón social:</span> {{ $sale->customer_business_name ?? $lead->business_name ?? '-' }}</div>
                                                        <div><span class="font-semibold">Representante:</span> {{ $sale->customer_representative_name ?? $lead->last_contact_name ?? '-' }}</div>
                                                        <div><span class="font-semibold">Campaña:</span> {{ $sale->campaign->name ?? '-' }}</div>
                                                    </div>
                                                </td>
                                                <td class="px-5 py-4">
                                                    <div class="space-y-1.5 leading-5">
                                                        <div><span class="font-semibold">Teléfono:</span> {{ $sale->customer_phone ?? $lead->last_contact_phone ?? '-' }}</div>
                                                        <div><span class="font-semibold">Líneas:</span> {{ $sale->offered_line_count ?? '-' }}</div>
                                                        <div><span class="font-semibold">Pago mensual:</span> {{ !is_null($netMonthlyPayment) ? 'S/ '.number_format((float) $netMonthlyPayment, 2) : '-' }}</div>
                                                    </div>
                                                </td>
                                                <td class="max-w-0 overflow-hidden px-5 py-4">
                                                    <div class="w-full max-w-full space-y-2 overflow-hidden">
                                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-900">
                                                            <span class="font-semibold">Producto:</span>
                                                            <span>{{ $productType }}</span>
                                                            @if($offerCards->count() > 1)
                                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                                                    {{ $offerCards->count() }} ofertas
                                                                </span>
                                                            @endif
                                                        </div>

                                                        <div class="w-full max-w-full overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                                            <div class="w-full max-w-full overflow-x-auto">
                                                                <div style="min-width: 450px;">
                                                                    <div class="grid grid-cols-[minmax(140px,1fr)_58px_80px_88px] gap-2 border-b border-slate-200 bg-white px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                                                        <div>Oferta</div>
                                                                        <div class="text-right whitespace-nowrap">Líneas</div>
                                                                        <div class="text-right whitespace-nowrap">Unitario</div>
                                                                        <div class="text-right whitespace-nowrap">Total</div>
                                                                    </div>

                                                                    @forelse($visibleOfferCards as $offerCard)
                                                                        <div class="{{ $loop->first ? '' : 'border-t border-slate-200' }} px-3 py-1.5">
                                                                            <div class="grid grid-cols-[minmax(140px,1fr)_58px_80px_88px] items-center gap-2">
                                                                                <div class="min-w-0">
                                                                                    <div class="truncate text-[12px] font-semibold leading-4 text-slate-900">{{ $offerCard['title'] }}</div>
                                                                                    <div class="mt-0.5 truncate text-[11px] leading-4 text-slate-600">{{ $offerCard['detail'] }}</div>
                                                                                </div>
                                                                                <div class="text-right text-[11px] font-medium text-slate-700 whitespace-nowrap">{{ $offerCard['lines_label'] }}</div>
                                                                                <div class="text-right text-[11px] font-medium text-slate-700 whitespace-nowrap">{{ $offerCard['unit_label'] ?? '-' }}</div>
                                                                                <div class="text-right text-[11px] font-semibold text-slate-900 whitespace-nowrap">{{ $offerCard['total_label'] }}</div>
                                                                            </div>
                                                                        </div>
                                                                    @empty
                                                                        <div class="px-3 py-2 text-sm text-slate-500">Sin datos de oferta.</div>
                                                                    @endforelse

                                                                    @if($hiddenOfferCards->isNotEmpty())
                                                                        <div x-show="openOfferSummary" style="display: none;">
                                                                            @foreach($hiddenOfferCards as $offerCard)
                                                                                <div class="border-t border-slate-200 px-3 py-1.5">
                                                                                    <div class="grid grid-cols-[minmax(140px,1fr)_58px_80px_88px] items-center gap-2">
                                                                                        <div class="min-w-0">
                                                                                            <div class="truncate text-[12px] font-semibold leading-4 text-slate-900">{{ $offerCard['title'] }}</div>
                                                                                            <div class="mt-0.5 truncate text-[11px] leading-4 text-slate-600">{{ $offerCard['detail'] }}</div>
                                                                                        </div>
                                                                                        <div class="text-right text-[11px] font-medium text-slate-700 whitespace-nowrap">{{ $offerCard['lines_label'] }}</div>
                                                                                        <div class="text-right text-[11px] font-medium text-slate-700 whitespace-nowrap">{{ $offerCard['unit_label'] ?? '-' }}</div>
                                                                                        <div class="text-right text-[11px] font-semibold text-slate-900 whitespace-nowrap">{{ $offerCard['total_label'] }}</div>
                                                                                    </div>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            @if($hiddenOfferCards->isNotEmpty())
                                                                <button
                                                                    type="button"
                                                                    @click="openOfferSummary = !openOfferSummary"
                                                                    class="flex w-full items-center justify-between border-t border-slate-200 bg-white px-3 py-1.5 text-[11px] font-medium text-slate-600 transition hover:bg-slate-50"
                                                                >
                                                                    <span x-text="openOfferSummary ? 'Ocultar detalle' : 'Ver {{ $hiddenOfferCards->count() }} oferta(s) más'"></span>
                                                                    <svg class="h-3.5 w-3.5 transition" :class="openOfferSummary ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                                                    </svg>
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-5 py-4 text-right">
                                                    <button @click="openModal = true"
                                                        class="crm-accent-outline-button inline-flex min-w-[88px] items-center justify-center rounded-xl px-3 py-2 text-sm font-semibold transition">
                                                        Detalle
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="my-sales-compact-card overflow-hidden rounded-2xl border {{ $loop->first ? 'crm-accent-border' : 'border-gray-300' }} bg-white">
                                <div class="grid gap-3 px-5 py-4">
                                    <div class="space-y-2 text-sm text-gray-900">
                                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Estado</div>
                                        <div class="text-[14px] font-semibold leading-5 text-slate-900">
                                            {{ ucfirst(str_replace('_', ' ', $sale->status)) }}
                                        </div>
                                        <div class="flex flex-wrap gap-1.5 text-[12px] leading-tight">
                                            <span class="crm-neutral-chip font-medium px-2 py-0.5 rounded-lg whitespace-nowrap">Gestión: {{ $managementLabel }}</span>
                                            <span class="crm-neutral-chip font-medium px-2 py-0.5 rounded-lg whitespace-nowrap">M.C: {{ $sisacLabel }}</span>
                                            <span class="crm-neutral-chip font-medium px-2 py-0.5 rounded-lg whitespace-nowrap">{{ $supervisorLabel }}</span>
                                        </div>
                                        <div class="space-y-1 leading-5">
                                            <div><span class="font-semibold">Ejecutivo:</span> {{ $sale->executive->name ?? '-' }}</div>
                                            <div><span class="font-semibold">Supervisor:</span> {{ $sale->supervisor->name ?? '-' }}</div>
                                        </div>
                                    </div>

                                    <div class="space-y-1.5 text-sm text-gray-900">
                                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Cliente</div>
                                        <div class="leading-5"><span class="font-semibold">RUC:</span> {{ $sale->customer_ruc ?? $lead->ruc ?? '-' }}</div>
                                        <div class="leading-5"><span class="font-semibold">Razón social:</span> {{ $sale->customer_business_name ?? $lead->business_name ?? '-' }}</div>
                                        <div class="leading-5"><span class="font-semibold">Representante:</span> {{ $sale->customer_representative_name ?? $lead->last_contact_name ?? '-' }}</div>
                                        <div class="leading-5"><span class="font-semibold">Campaña:</span> {{ $sale->campaign->name ?? '-' }}</div>
                                    </div>

                                    <div class="space-y-1.5 text-sm text-gray-900">
                                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Contacto</div>
                                        <div class="leading-5"><span class="font-semibold">Teléfono:</span> {{ $sale->customer_phone ?? $lead->last_contact_phone ?? '-' }}</div>
                                        <div class="leading-5"><span class="font-semibold">Líneas:</span> {{ $sale->offered_line_count ?? '-' }}</div>
                                        <div class="leading-5"><span class="font-semibold">Pago mensual:</span> {{ !is_null($netMonthlyPayment) ? 'S/ '.number_format((float) $netMonthlyPayment, 2) : '-' }}</div>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Oferta comercial</div>
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-900">
                                            <span class="font-semibold">Producto:</span>
                                            <span>{{ $productType }}</span>
                                            @if($offerCards->count() > 1)
                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                                    {{ $offerCards->count() }} ofertas
                                                </span>
                                            @endif
                                        </div>

                                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                            <div class="overflow-x-auto">
                                                <div style="min-width: 520px;">
                                                    <div class="grid grid-cols-[minmax(140px,1fr)_58px_80px_88px] gap-2 border-b border-slate-200 bg-white px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                                        <div>Oferta</div>
                                                        <div class="text-right whitespace-nowrap">Líneas</div>
                                                        <div class="text-right whitespace-nowrap">Unitario</div>
                                                        <div class="text-right whitespace-nowrap">Total</div>
                                                    </div>

                                                    @forelse($visibleOfferCards as $offerCard)
                                                        <div class="{{ $loop->first ? '' : 'border-t border-slate-200' }} px-3 py-1.5">
                                                            <div class="grid grid-cols-[minmax(140px,1fr)_58px_80px_88px] items-center gap-2">
                                                                <div class="min-w-0">
                                                                    <div class="truncate text-[12px] font-semibold leading-4 text-slate-900">{{ $offerCard['title'] }}</div>
                                                                    <div class="mt-0.5 truncate text-[11px] leading-4 text-slate-600">{{ $offerCard['detail'] }}</div>
                                                                </div>
                                                                <div class="text-right text-[11px] font-medium text-slate-700 whitespace-nowrap">{{ $offerCard['lines_label'] }}</div>
                                                                <div class="text-right text-[11px] font-medium text-slate-700 whitespace-nowrap">{{ $offerCard['unit_label'] ?? '-' }}</div>
                                                                <div class="text-right text-[11px] font-semibold text-slate-900 whitespace-nowrap">{{ $offerCard['total_label'] }}</div>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="px-3 py-2 text-sm text-slate-500">Sin datos de oferta.</div>
                                                    @endforelse

                                                    @if($hiddenOfferCards->isNotEmpty())
                                                        <div x-show="openOfferSummary" style="display: none;">
                                                            @foreach($hiddenOfferCards as $offerCard)
                                                                <div class="border-t border-slate-200 px-3 py-1.5">
                                                                    <div class="grid grid-cols-[minmax(140px,1fr)_58px_80px_88px] items-center gap-2">
                                                                        <div class="min-w-0">
                                                                            <div class="truncate text-[12px] font-semibold leading-4 text-slate-900">{{ $offerCard['title'] }}</div>
                                                                            <div class="mt-0.5 truncate text-[11px] leading-4 text-slate-600">{{ $offerCard['detail'] }}</div>
                                                                        </div>
                                                                        <div class="text-right text-[11px] font-medium text-slate-700 whitespace-nowrap">{{ $offerCard['lines_label'] }}</div>
                                                                        <div class="text-right text-[11px] font-medium text-slate-700 whitespace-nowrap">{{ $offerCard['unit_label'] ?? '-' }}</div>
                                                                        <div class="text-right text-[11px] font-semibold text-slate-900 whitespace-nowrap">{{ $offerCard['total_label'] }}</div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            @if($hiddenOfferCards->isNotEmpty())
                                                <button
                                                    type="button"
                                                    @click="openOfferSummary = !openOfferSummary"
                                                    class="flex w-full items-center justify-between border-t border-slate-200 bg-white px-3 py-1.5 text-[11px] font-medium text-slate-600 transition hover:bg-slate-50"
                                                >
                                                    <span x-text="openOfferSummary ? 'Ocultar detalle' : 'Ver {{ $hiddenOfferCards->count() }} oferta(s) más'"></span>
                                                    <svg class="h-3.5 w-3.5 transition" :class="openOfferSummary ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex items-start justify-start">
                                        <button @click="openModal = true"
                                            class="crm-accent-outline-button inline-flex w-full min-w-[88px] items-center justify-center rounded-xl px-3 py-2 text-sm font-semibold transition">
                                            Detalle
                                        </button>
                                    </div>
                                </div>
                                </div>

                                <div x-show="openModal"
                                     style="display: none;"
                                     class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/60 px-3 py-3">
                                    <div @click.outside="openModal = false" class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-[26px] bg-white shadow-2xl">
                                        <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-4 py-2.5">
                                            <div class="min-w-0">
                                                <h3 class="text-lg font-bold text-gray-900">Detalle del acuerdo</h3>
                                            </div>
                                            <button @click="openModal = false" class="shrink-0 text-2xl font-bold leading-none text-gray-400 transition hover:text-red-500">&times;</button>
                                        </div>

                                        <div class="flex-1 overflow-y-auto px-3 py-2.5 sm:px-4">
                                            <div class="space-y-2.5 text-sm text-gray-900">
                                                <div class="grid grid-cols-2 gap-1.5 lg:grid-cols-4">
                                                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5">
                                                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500">Gestión</div>
                                                        <div class="mt-0.5 font-semibold text-gray-900">{{ $managementLabel }}</div>
                                                    </div>
                                                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5">
                                                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500">Mesa de Control</div>
                                                        <div class="mt-0.5 font-semibold text-gray-900">{{ $sisacLabel }}</div>
                                                    </div>
                                                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5">
                                                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500">Supervisor</div>
                                                        <div class="mt-0.5 font-semibold text-gray-900">{{ $supervisorLabel }}</div>
                                                    </div>
                                                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5">
                                                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-gray-500">Última act.</div>
                                                        <div class="mt-0.5 font-semibold text-gray-900">{{ optional($sale->updated_at)->format('d/m/Y H:i') ?: '-' }}</div>
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-1 gap-2.5 xl:grid-cols-[minmax(0,1.2fr)_minmax(300px,0.8fr)]">
                                                    <div class="space-y-2.5">
                                                        <div class="rounded-2xl border border-gray-200 p-2.5">
                                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                                <div>
                                                                    <h4 class="text-base font-semibold text-gray-900">Ficha actualizada</h4>
                                                                </div>
                                                                @if($latestSupervisorHistory)
                                                                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[11px] text-emerald-800">
                                                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 sm:flex-nowrap">
                                                                            <span class="font-semibold whitespace-nowrap">Último ajuste</span>
                                                                            <span class="whitespace-nowrap">{{ optional($latestSupervisorHistory->created_at)->format('d/m/Y H:i') ?: '-' }}</span>
                                                                            <span class="whitespace-nowrap">{{ $latestSupervisorHistory->user->name ?? 'Sistema' }}</span>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            </div>

                                                            <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-2">
                                                                <div class="space-y-1">
                                                                    <div class="leading-5"><span class="font-semibold">RUC:</span> {{ $sale->customer_ruc ?: ($lead->ruc ?? '-') }}</div>
                                                                    <div class="leading-5"><span class="font-semibold">Representante:</span> {{ $sale->customer_representative_name ?: ($lead->representative_name ?? $lead->full_name ?? '-') }}</div>
                                                                    <div class="leading-5"><span class="font-semibold">Celular:</span> {{ $sale->customer_phone ?: ($lead->last_contact_phone ?? '-') }}</div>
                                                                    <div class="leading-5"><span class="font-semibold">Dirección:</span> {{ $sale->customer_address ?: '-' }}</div>
                                                                    <div class="leading-5"><span class="font-semibold">Coordenadas:</span> {{ $sale->customer_coordinates ?: '-' }}</div>
                                                                </div>
                                                                <div class="space-y-1">
                                                                    <div class="leading-5"><span class="font-semibold">Razón social:</span> {{ $sale->customer_business_name ?: ($lead->business_name ?? '-') }}</div>
                                                                    <div class="leading-5"><span class="font-semibold">DNI:</span> {{ $sale->customer_dni ?: ($lead->dni ?? '-') }}</div>
                                                                    <div class="leading-5 break-all"><span class="font-semibold">Correo:</span> {{ $sale->customer_email ?: '-' }}</div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="rounded-2xl border border-gray-200 p-2.5">
                                                            <h4 class="text-base font-semibold text-gray-900">Condiciones del acuerdo</h4>
                                                            <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                                                                <div class="space-y-1">
                                                                    <div class="leading-5"><span class="font-semibold">Producto:</span> {{ $productType }}</div>
                                                                    <div class="leading-5"><span class="font-semibold">Pago mensual:</span> {{ !is_null($netMonthlyPayment) ? 'S/ '.number_format((float) $netMonthlyPayment, 2) : '-' }}</div>
                                                                    @unless($isFixedOnlyAgreement)
                                                                        <div class="leading-5"><span class="font-semibold">Operador:</span> {{ $sale->operator_name ?? '-' }}</div>
                                                                        <div class="leading-5"><span class="font-semibold">Fecha:</span> {{ optional($sale->attention_date)->format('d/m/Y') ?: '-' }}</div>
                                                                    @endunless
                                                                    @if($requiresFixedAgreementSupport)
                                                                        <div class="leading-5"><span class="font-semibold">Soporte fija:</span> {{ $fixedSupportLabel ?: '-' }}</div>
                                                                    @endif
                                                                    <div class="leading-5"><span class="font-semibold">Supervisor:</span> {{ $sale->supervisor->name ?? '-' }}</div>
                                                                </div>
                                                                <div class="space-y-1">
                                                                    <div class="leading-5"><span class="font-semibold">Detalle móvil:</span> {{ $mobileProduct['detail'] ?? '-' }}</div>
                                                                    <div class="leading-5"><span class="font-semibold">Campaña:</span> {{ $sale->campaign->name ?? '-' }}</div>
                                                                    @unless($isFixedOnlyAgreement)
                                                                        <div class="leading-5"><span class="font-semibold">Canal:</span> {{ $serviceChannelLabel }}</div>
                                                                        <div class="leading-5"><span class="font-semibold">Entrega:</span> {{ $deliveryTypeLabel }}</div>
                                                                    @endunless
                                                                </div>
                                                                <div class="space-y-1">
                                                                    <div class="leading-5"><span class="font-semibold">Líneas:</span> {{ $sale->offered_line_count ?? 0 }}</div>
                                                                    <div class="leading-5"><span class="font-semibold">Plano:</span> {{ $sale->plan_code ?? '-' }}</div>
                                                                    <div class="leading-5"><span class="font-semibold">Código aprobación:</span> {{ $sale->approval_code ?? '-' }}</div>
                                                                    @unless($isFixedOnlyAgreement)
                                                                        <div class="leading-5"><span class="font-semibold">Franja:</span> {{ $sale->attention_time_slot ?? '-' }}</div>
                                                                    @endunless
                                                                    <div class="leading-5"><span class="font-semibold">Ejecutivo:</span> {{ $sale->executive->name ?? '-' }}</div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="space-y-2.5">
                                                        <div class="rounded-2xl border border-gray-200 p-2.5">
                                                            <h4 class="text-base font-semibold text-gray-900">Feedback operativo</h4>
                                                            <div class="mt-2 space-y-2">
                                                                <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                                                                    <div class="flex items-start justify-between gap-3">
                                                                        <span class="font-semibold text-gray-900">Postventa</span>
                                                                        @if($latestPostSale)
                                                                            <span class="text-xs text-gray-500">{{ optional($latestPostSale->created_at)->format('d/m/Y H:i') }}</span>
                                                                        @endif
                                                                    </div>
                                                                    <div class="mt-1.5 whitespace-pre-line leading-5 text-gray-700">{{ $latestPostSale?->feedback ?: 'Sin comentarios registrados aún.' }}</div>
                                                                </div>

                                                                <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                                                                    <div class="flex items-start justify-between gap-3">
                                                                        <span class="font-semibold text-gray-900">Mesa de Control</span>
                                                                        @if($latestValidation)
                                                                            <span class="text-xs text-gray-500">{{ optional($latestValidation->created_at)->format('d/m/Y H:i') }}</span>
                                                                        @endif
                                                                    </div>
                                                                    <div class="mt-1.5 whitespace-pre-line leading-5 text-gray-700">{{ $latestValidation?->feedback ?: 'Sin comentarios registrados aún.' }}</div>
                                                                </div>

                                                                <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2">
                                                                    <div class="flex items-start justify-between gap-3">
                                                                        <span class="font-semibold text-gray-900">Ejecutivo</span>
                                                                        @if($executiveFeedbackInteraction?->created_at)
                                                                            <span class="text-xs text-gray-500">{{ optional($executiveFeedbackInteraction?->created_at)->format('d/m/Y H:i') }}</span>
                                                                        @endif
                                                                    </div>
                                                                    <div class="mt-1 text-xs text-gray-500">
                                                                        {{ $executiveFeedbackInteraction?->user?->name ?? $sale->executive->name ?? 'Sin usuario' }}
                                                                    </div>
                                                                    <div class="mt-1.5 whitespace-pre-line leading-5 text-gray-700">
                                                                        {{ filled($executiveFeedbackInteraction?->call_detail) ? $executiveFeedbackInteraction->call_detail : 'Sin comentarios registrados aún.' }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        @if($sale->simDetails->isNotEmpty())
                                                            @php
                                                                $simTableCopyText = collect($sale->simDetails)
                                                                    ->map(function ($detail) {
                                                                        return implode("\t", [
                                                                            (string) ($detail->serial_number ?? '-'),
                                                                            (string) ($detail->sim_number ?? '-'),
                                                                        ]);
                                                                    })
                                                                    ->prepend("Número de serie\tNúmero del SIM")
                                                                    ->implode("\n");
                                                            @endphp
                                                            <div class="rounded-2xl p-2.5" style="border: 1px solid #86efac; background: linear-gradient(180deg, #f0fdf4 0%, #dcfce7 100%);">
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <div>
                                                                        <h4 class="text-base font-semibold" style="color: #14532d;">SIM entregadas</h4>
                                                                        <p class="mt-0.5 text-xs" style="color: #166534;">Mesa de Control ya registró el resultado operativo de este acuerdo.</p>
                                                                    </div>
                                                                    <button
                                                                        type="button"
                                                                        class="inline-flex items-center justify-center rounded-xl bg-white px-3 py-1.5 text-xs font-semibold transition"
                                                                        style="border: 1px solid #86efac; color: #166534;"
                                                                        data-copy-sim-table
                                                                        data-copy-target="sim-table-copy-{{ $sale->id }}"
                                                                    >
                                                                        Copiar tabla
                                                                    </button>
                                                                </div>
                                                                <textarea id="sim-table-copy-{{ $sale->id }}" class="hidden" tabindex="-1" aria-hidden="true">{{ $simTableCopyText }}</textarea>
                                                                <div class="mt-2 space-y-1.5">
                                                                    <div class="grid grid-cols-2 gap-2 text-sm font-semibold" style="color: #14532d;">
                                                                        <div class="rounded-xl bg-white px-3 py-1.5">Número de serie</div>
                                                                        <div class="rounded-xl bg-white px-3 py-1.5">Número del SIM</div>
                                                                    </div>
                                                                    <div class="max-h-[220px] space-y-1.5 overflow-y-auto pr-1">
                                                                    @foreach($sale->simDetails as $detail)
                                                                        <div class="grid grid-cols-2 gap-2 text-sm" style="color: #14532d;">
                                                                            <div class="rounded-xl bg-white px-3 py-1.5" style="border: 1px solid #bbf7d0;">
                                                                                {{ $detail->serial_number ?? '-' }}
                                                                            </div>
                                                                            <div class="rounded-xl bg-white px-3 py-1.5" style="border: 1px solid #bbf7d0;">
                                                                                {{ $detail->sim_number ?? '-' }}
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                @if($sale->histories->isNotEmpty())
                                                    <div class="rounded-2xl border border-gray-200 p-2.5">
                                                        <h4 class="text-base font-semibold text-gray-900">Historial de ajustes</h4>
                                                        <div class="mt-2 max-h-[220px] space-y-1.5 overflow-y-auto pr-1">
                                                            @foreach($sale->histories->sortByDesc('created_at') as $history)
                                                                <div class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                                                                    <div class="flex items-start justify-between gap-3">
                                                                        <div class="font-semibold text-gray-900">{{ $history->notes ?: ucfirst(str_replace('_', ' ', $history->action)) }}</div>
                                                                        <div class="text-xs text-gray-500">{{ optional($history->created_at)->format('d/m/Y H:i') }}</div>
                                                                    </div>
                                                                    <div class="mt-1 text-xs text-gray-500">Usuario: {{ $history->user->name ?? 'Sistema' }}</div>

                                                                    @if(!empty($history->changed_fields))
                                                                        <div class="mt-2 grid grid-cols-1 gap-1.5 xl:grid-cols-2">
                                                                            @foreach($history->changed_fields as $change)
                                                                                <div class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-700">
                                                                                    <div class="font-semibold text-gray-900">{{ $change['label'] ?? ($change['field'] ?? 'Campo') }}</div>
                                                                                    <div class="mt-1 text-rose-600">Antes: {{ ($change['old'] ?? '') !== '' ? $change['old'] : 'Vacío' }}</div>
                                                                                    <div class="mt-1 text-emerald-700">Después: {{ ($change['new'] ?? '') !== '' ? $change['new'] : 'Vacío' }}</div>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex justify-end border-t border-gray-200 px-4 py-2.5">
                                            <button @click="openModal = false" class="rounded-xl bg-black px-4 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                                                Cerrar
                                            </button>
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fallbackCopyText = (text) => {
                const tempTextarea = document.createElement('textarea');
                tempTextarea.value = text;
                tempTextarea.setAttribute('readonly', '');
                tempTextarea.style.position = 'fixed';
                tempTextarea.style.top = '-9999px';
                tempTextarea.style.left = '-9999px';
                document.body.appendChild(tempTextarea);
                tempTextarea.focus();
                tempTextarea.select();

                let copied = false;

                try {
                    copied = document.execCommand('copy');
                } catch (error) {
                    copied = false;
                }

                document.body.removeChild(tempTextarea);

                return copied;
            };

            const setCopySuccessState = (button, originalText) => {
                button.textContent = 'Copiado';
                window.setTimeout(() => {
                    button.textContent = originalText;
                }, 1600);
            };

            document.addEventListener('click', async (event) => {
                const copyButton = event.target.closest('[data-copy-sim-table]');

                if (!copyButton) {
                    return;
                }

                const targetId = copyButton.dataset.copyTarget || '';
                const copyText = targetId ? (document.getElementById(targetId)?.value || '') : '';
                const originalText = copyButton.textContent.trim();

                if (!copyText) {
                    return;
                }

                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(copyText);
                        setCopySuccessState(copyButton, originalText);
                        return;
                    }

                    if (fallbackCopyText(copyText)) {
                        setCopySuccessState(copyButton, originalText);
                        return;
                    }

                    console.error('No se pudo copiar la tabla de SIM entregadas.');
                } catch (error) {
                    if (fallbackCopyText(copyText)) {
                        setCopySuccessState(copyButton, originalText);
                        return;
                    }

                    console.error('No se pudo copiar la tabla de SIM entregadas.', error);
                }
            });
        });
    </script>
</x-app-layout>
