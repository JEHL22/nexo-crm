<x-app-layout>
    <div class="py-8">
        <div class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('info'))
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    {{ session('info') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                <div class="crm-panel-hero border-b border-slate-200 px-6 py-8 text-white sm:px-8">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-2xl">
                            <p class="text-sm font-medium uppercase tracking-[0.22em] text-white/75">Panel ejecutivo</p>
                            <h1 class="mt-3 text-3xl font-semibold tracking-tight">Mi chamba</h1>
                            <p class="mt-2 text-sm text-white/80 sm:text-base">
                                Seguimiento comercial de los clientes que ya tuvieron una primera gestión y necesitan continuidad.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.18em] text-white/70">Registros</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $leads->total() }}</div>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.18em] text-white/70">Página</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $leads->currentPage() }}</div>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur sm:col-span-1 col-span-2">
                                <div class="text-xs uppercase tracking-[0.18em] text-white/70">Filtro</div>
                                <div class="mt-2 text-sm font-semibold">{{ $status ? ($statusOptions[$status] ?? $status) : 'Todos los estados' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 sm:p-8">
                <form method="GET" action="{{ route('my-work.index') }}" class="mb-6">
                    <div class="flex flex-col md:flex-row gap-3 md:items-center">
                        <div class="w-full md:w-56">
                            <select name="status" class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Estados</option>
                                @foreach(($statusOptions ?? []) as $key => $label)
                                    <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <a href="{{ route('my-work.base') }}"
                               class="crm-accent-outline-button inline-flex items-center justify-center px-5 py-2.5 rounded-xl font-medium transition">
                                Mi base
                            </a>
                        </div>

                        <div class="w-full md:w-64">
                            <input
                                type="text"
                                name="search"
                                value="{{ $search }}"
                                placeholder="Buscar : RUC"
                                class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                        <div class="flex gap-2">
                            <button type="submit"
                                class="crm-accent-button inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-sm font-medium transition">
                                Filtrar
                            </button>

                            <a href="{{ route('my-work.index') }}"
                               class="crm-accent-outline-button inline-flex items-center justify-center px-4 py-2.5 rounded-xl text-sm font-medium transition">
                                Limpiar
                            </a>
                        </div>
                    </div>
                </form>

                @if($leads->isEmpty())
                    <div class="border border-dashed border-gray-300 rounded-xl p-8 text-center text-gray-500">
                        No tienes registros en Mi chamba por ahora.
                    </div>
                @else
                    <div class="space-y-3">
                        <div class="hidden overflow-x-auto rounded-2xl border border-slate-200 lg:block">
                            <table class="min-w-full table-fixed border-collapse">
                                <colgroup>
                                    <col class="w-[22%]">
                                    <col class="w-[22%]">
                                    <col class="w-[18%]">
                                    <col class="w-[32%]">
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

                        @foreach($leads as $lead)
                            @php
                                $phone = optional($lead->phones->first())->phone;
                                $latestInteraction = $lead->interactions->sortByDesc('created_at')->first();

                                $statusLabel = [
                                    'reprogramado' => 'Reprogramado',
                                    'negociacion' => 'Negociación',
                                ][$lead->status_specific] ?? ucfirst(str_replace('_', ' ', $lead->status_specific ?? '-'));

                                $offers = $latestInteraction?->offers ?? collect();

                                $hasMovil = $offers->contains(fn ($offer) => $offer->product_type === 'movil');
                                $hasFijo = $offers->contains(fn ($offer) => $offer->product_type === 'fijo');

                                if ($hasMovil && $hasFijo) {
                                    $productLabel = 'Móvil + Fijo';
                                } elseif ($hasMovil) {
                                    $productLabel = 'Móvil';
                                } elseif ($hasFijo) {
                                    $productLabel = 'Fijo';
                                } else {
                                    $productLabel = '--------';
                                }

                                $mobileOffers = $offers->where('product_type', 'movil')->values();
                                $fixedOffer = $offers->firstWhere('product_type', 'fijo');
                                $offeredLineTotal = $offers->sum(
                                    fn ($offer) => (int) ($offer->portability_lines ?? 0) + (int) ($offer->new_lines ?? 0)
                                );
                                $offeredLineTotalLabel = $offeredLineTotal > 0 ? $offeredLineTotal : '-';

                                $offerCards = [];

                                if ($mobileOffers->isNotEmpty()) {
                                    foreach ($mobileOffers as $mobileOffer) {
                                        if (!is_null($mobileOffer->portability_lines)) {
                                            $offerCards[] = [
                                                'title' => 'Portabilidad',
                                                'meta' => 'Líneas: '.$mobileOffer->portability_lines,
                                                'detail' => !empty($mobileOffer->portability_promotion_name)
                                                    ? $mobileOffer->portability_promotion_name
                                                    : 'Sin promoción',
                                            ];
                                        }

                                        if (!is_null($mobileOffer->new_lines)) {
                                            $offerCards[] = [
                                                'title' => 'Alta nueva',
                                                'meta' => 'Líneas: '.$mobileOffer->new_lines,
                                                'detail' => !empty($mobileOffer->new_promotion_name)
                                                    ? $mobileOffer->new_promotion_name
                                                    : 'Sin promoción',
                                            ];
                                        }
                                    }
                                }

                                if ($fixedOffer) {
                                    $offerCards[] = [
                                        'title' => 'Fija',
                                        'meta' => $fixedOffer->internet_speed ?: 'Sin velocidad',
                                        'detail' => !is_null($fixedOffer->fixed_monthly)
                                            ? 'S/ '.number_format((float) $fixedOffer->fixed_monthly, 2)
                                            : 'Sin monto',
                                    ];
                                }

                                $offerCards = collect($offerCards)->values();
                                $visibleOfferCards = $offerCards->take(1)->values();
                                $hiddenOfferCards = $offerCards->slice(1)->values();
                            @endphp

                            <div x-data="{ openOfferSummary: false }" class="space-y-3">
                                <div class="hidden overflow-x-auto rounded-2xl border {{ $loop->first ? 'crm-accent-border' : 'border-gray-300' }} bg-white lg:block">
                                    <table class="min-w-full table-fixed border-collapse">
                                        <colgroup>
                                            <col class="w-[22%]">
                                            <col class="w-[22%]">
                                            <col class="w-[18%]">
                                            <col class="w-[32%]">
                                            <col class="w-[6%]">
                                        </colgroup>
                                        <tbody>
                                            <tr class="align-top text-sm text-slate-900">
                                                <td class="px-5 py-4">
                                                    <div class="space-y-2">
                                                        <div>
                                                            <span class="inline-flex items-center border-b-2 border-rose-400 pb-0.5 text-[14px] font-semibold leading-5 text-slate-900">
                                                                {{ $statusLabel }}
                                                            </span>
                                                        </div>
                                                        <div class="space-y-1 leading-5">
                                                            <div><span class="font-semibold">Ejecutivo:</span> {{ $latestInteraction?->user?->name ?? auth()->user()->name }}</div>
                                                            <div><span class="font-semibold">Operador:</span> {{ $lead->current_operator ?? '-' }}</div>
                                                            <div><span class="font-semibold">Última act.:</span> {{ optional($latestInteraction?->created_at)->format('d/m/Y H:i') ?: '-' }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-5 py-4">
                                                    <div class="space-y-1.5 leading-5">
                                                        <div><span class="font-semibold">RUC:</span> {{ $lead->ruc ?? '-' }}</div>
                                                        <div><span class="font-semibold">Razón social:</span> {{ $lead->business_name ?? '-' }}</div>
                                                        <div><span class="font-semibold">Campaña:</span> {{ $lead->campaign?->name ?? '-' }}</div>
                                                    </div>
                                                </td>
                                                <td class="px-5 py-4">
                                                    <div class="space-y-1.5 leading-5">
                                                        <div><span class="font-semibold">Teléfono:</span> {{ $phone ?? '-' }}</div>
                                                        <div><span class="font-semibold">Líneas ofrecidas:</span> {{ $offeredLineTotalLabel }}</div>
                                                        <div><span class="font-semibold">Producto:</span> {{ $productLabel }}</div>
                                                    </div>
                                                </td>
                                                <td class="px-5 py-4">
                                                    <div class="space-y-2">
                                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-900">
                                                            <span class="font-semibold">Oferta:</span>
                                                            @if($offerCards->count() > 1)
                                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                                                    {{ $offerCards->count() }} ofertas
                                                                </span>
                                                            @endif
                                                        </div>

                                                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                                            <div class="grid grid-cols-[minmax(0,1fr)_72px] gap-2 border-b border-slate-200 bg-white px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                                                <div>Oferta</div>
                                                                <div class="text-right">Líneas</div>
                                                            </div>

                                                            @forelse($visibleOfferCards as $offerCard)
                                                                <div class="{{ $loop->first ? '' : 'border-t border-slate-200' }} px-3 py-1.5">
                                                                    <div class="grid grid-cols-[minmax(0,1fr)_72px] items-center gap-2">
                                                                        <div class="min-w-0">
                                                                            <div class="truncate text-[12px] font-semibold leading-4 text-slate-900">{{ $offerCard['title'] }}</div>
                                                                            <div class="mt-0.5 truncate text-[11px] leading-4 text-slate-600">{{ $offerCard['detail'] }}</div>
                                                                        </div>
                                                                        <div class="text-right text-[11px] font-medium text-slate-700">{{ $offerCard['meta'] }}</div>
                                                                    </div>
                                                                </div>
                                                            @empty
                                                                <div class="px-3 py-2 text-sm text-slate-500">Sin datos de oferta.</div>
                                                            @endforelse

                                                            @if($hiddenOfferCards->isNotEmpty())
                                                                <div x-show="openOfferSummary" style="display: none;">
                                                                    @foreach($hiddenOfferCards as $offerCard)
                                                                        <div class="border-t border-slate-200 px-3 py-1.5">
                                                                            <div class="grid grid-cols-[minmax(0,1fr)_72px] items-center gap-2">
                                                                                <div class="min-w-0">
                                                                                    <div class="truncate text-[12px] font-semibold leading-4 text-slate-900">{{ $offerCard['title'] }}</div>
                                                                                    <div class="mt-0.5 truncate text-[11px] leading-4 text-slate-600">{{ $offerCard['detail'] }}</div>
                                                                                </div>
                                                                                <div class="text-right text-[11px] font-medium text-slate-700">{{ $offerCard['meta'] }}</div>
                                                                            </div>
                                                                        </div>
                                                                    @endforeach
                                                                </div>

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
                                                    <a href="{{ route('my-work.show', $lead->id) }}"
                                                       class="crm-accent-outline-button inline-flex min-w-[88px] items-center justify-center rounded-xl px-3 py-2 text-sm font-semibold transition">
                                                        Detalle
                                                    </a>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="overflow-hidden rounded-2xl border {{ $loop->first ? 'crm-accent-border' : 'border-gray-300' }} bg-white lg:hidden">
                                    <div class="grid gap-3 px-5 py-4">
                                        <div class="space-y-2 text-sm text-gray-900">
                                            <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Estado</div>
                                            <div>
                                                <span class="inline-flex items-center border-b-2 border-rose-400 pb-0.5 text-[14px] font-semibold leading-5 text-slate-900">
                                                    {{ $statusLabel }}
                                                </span>
                                            </div>
                                            <div class="space-y-1 leading-5">
                                                <div><span class="font-semibold">Ejecutivo:</span> {{ $latestInteraction?->user?->name ?? auth()->user()->name }}</div>
                                                <div><span class="font-semibold">Operador:</span> {{ $lead->current_operator ?? '-' }}</div>
                                                <div><span class="font-semibold">Última act.:</span> {{ optional($latestInteraction?->created_at)->format('d/m/Y H:i') ?: '-' }}</div>
                                            </div>
                                        </div>

                                        <div class="space-y-1.5 text-sm text-gray-900">
                                            <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Cliente</div>
                                            <div class="leading-5"><span class="font-semibold">RUC:</span> {{ $lead->ruc ?? '-' }}</div>
                                            <div class="leading-5"><span class="font-semibold">Razón social:</span> {{ $lead->business_name ?? '-' }}</div>
                                            <div class="leading-5"><span class="font-semibold">Campaña:</span> {{ $lead->campaign?->name ?? '-' }}</div>
                                        </div>

                                        <div class="space-y-1.5 text-sm text-gray-900">
                                            <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Contacto</div>
                                            <div class="leading-5"><span class="font-semibold">Teléfono:</span> {{ $phone ?? '-' }}</div>
                                            <div class="leading-5"><span class="font-semibold">Líneas ofrecidas:</span> {{ $offeredLineTotalLabel }}</div>
                                            <div class="leading-5"><span class="font-semibold">Producto:</span> {{ $productLabel }}</div>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Oferta comercial</div>
                                            @if($offerCards->count() > 1)
                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                                                    {{ $offerCards->count() }} ofertas
                                                </span>
                                            @endif

                                            <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                                                <div class="grid grid-cols-[minmax(0,1fr)_72px] gap-2 border-b border-slate-200 bg-white px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                                                    <div>Oferta</div>
                                                    <div class="text-right">Líneas</div>
                                                </div>

                                                @forelse($visibleOfferCards as $offerCard)
                                                    <div class="{{ $loop->first ? '' : 'border-t border-slate-200' }} px-3 py-1.5">
                                                        <div class="grid grid-cols-[minmax(0,1fr)_72px] items-center gap-2">
                                                            <div class="min-w-0">
                                                                <div class="truncate text-[12px] font-semibold leading-4 text-slate-900">{{ $offerCard['title'] }}</div>
                                                                <div class="mt-0.5 truncate text-[11px] leading-4 text-slate-600">{{ $offerCard['detail'] }}</div>
                                                            </div>
                                                            <div class="text-right text-[11px] font-medium text-slate-700">{{ $offerCard['meta'] }}</div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <div class="px-3 py-2 text-sm text-slate-500">Sin datos de oferta.</div>
                                                @endforelse

                                                @if($hiddenOfferCards->isNotEmpty())
                                                    <div x-show="openOfferSummary" style="display: none;">
                                                        @foreach($hiddenOfferCards as $offerCard)
                                                            <div class="border-t border-slate-200 px-3 py-1.5">
                                                                <div class="grid grid-cols-[minmax(0,1fr)_72px] items-center gap-2">
                                                                    <div class="min-w-0">
                                                                        <div class="truncate text-[12px] font-semibold leading-4 text-slate-900">{{ $offerCard['title'] }}</div>
                                                                        <div class="mt-0.5 truncate text-[11px] leading-4 text-slate-600">{{ $offerCard['detail'] }}</div>
                                                                    </div>
                                                                    <div class="text-right text-[11px] font-medium text-slate-700">{{ $offerCard['meta'] }}</div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>

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
                                            <a href="{{ route('my-work.show', $lead->id) }}"
                                               class="crm-accent-outline-button inline-flex w-full items-center justify-center rounded-xl px-3 py-2 text-sm font-semibold transition">
                                                Detalle
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $leads->links() }}
                    </div>
                @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
