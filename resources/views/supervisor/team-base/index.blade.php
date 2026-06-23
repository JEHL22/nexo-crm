<x-app-layout>
    <div class="py-8">
        <div class="mx-auto max-w-[1800px] space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(2,6,23,0.98),_rgba(30,41,59,0.95)_45%,_rgba(8,145,178,0.88))] px-6 py-8 text-white sm:px-8">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-3xl">
                            <p class="text-sm font-medium uppercase tracking-[0.22em] text-cyan-100/80">Supervisor</p>
                            <h1 class="mt-3 text-3xl font-semibold tracking-tight">Mi base del equipo</h1>
                            <p class="mt-2 text-sm text-slate-200 sm:text-base">
                                Consulta los registros de prospección propia de tus ejecutivos, entra al detalle y completa los datos SISAC cuando haga falta.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.18em] text-cyan-100/70">Registros</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $leads->total() }}</div>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.18em] text-cyan-100/70">Página</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $leads->currentPage() }}</div>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.18em] text-cyan-100/70">Filtro</div>
                                <div class="mt-2 text-sm font-semibold">{{ $search !== '' ? $search : 'Sin filtro' }}</div>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.18em] text-cyan-100/70">Ejecutivo</div>
                                <div class="mt-2 text-sm font-semibold">{{ optional($executives->firstWhere('id', $executiveUserId))->name ?: 'Todos' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 sm:p-8">
                    <form method="GET" action="{{ route('supervisor.team-base.index') }}" class="mb-6">
                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(0,220px)_minmax(0,260px)_auto]">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Ejecutivo</label>
                                <select name="executive_user_id" class="mt-2 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Todos</option>
                                    @foreach($executives as $executive)
                                        <option value="{{ $executive->id }}" @selected((int) $executiveUserId === (int) $executive->id)>{{ $executive->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">RUC o razón social</label>
                                <input
                                    type="text"
                                    name="search"
                                    value="{{ $search }}"
                                    placeholder="Buscar lead"
                                    class="mt-2 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                >
                            </div>

                            <div class="flex items-end gap-3">
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-black px-4 py-3 text-sm font-semibold text-white transition hover:bg-gray-800">
                                    Filtrar
                                </button>
                                <a href="{{ route('supervisor.team-base.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-gray-300 px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                                    Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    @if($leads->isEmpty())
                        <div class="rounded-2xl border border-dashed border-gray-300 px-6 py-16 text-center text-sm text-gray-500">
                            No hay registros de Mi base para tu equipo con los filtros actuales.
                        </div>
                    @else
                        <div class="space-y-4">
                            @foreach($leads as $lead)
                                @php
                                    $primaryPhone = optional($lead->phones->sortByDesc('is_primary')->first())->phone;
                                    $latestInteraction = $lead->interactions->sortByDesc('created_at')->first();
                                    $executiveName = $lead->createdBy->name ?? $lead->assignedTo->name ?? '-';
                                    $segmentLabel = $lead->segment ?: 'Pendiente SISAC';
                                    $offers = $latestInteraction?->offers ?? collect();
                                    $productSummary = $offers->pluck('product_type')->map(function ($type) {
                                        return match ($type) {
                                            'movil' => 'Móvil',
                                            'fijo' => 'Fijo',
                                            default => ucfirst((string) $type),
                                        };
                                    })->unique()->implode(' + ') ?: 'Sin oferta reciente';
                                @endphp

                                <div class="rounded-[24px] border {{ $loop->first ? 'border-cyan-300 ring-1 ring-cyan-200' : 'border-gray-200' }} bg-white p-4 shadow-sm">
                                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                        <div class="grid flex-1 grid-cols-1 gap-3 lg:grid-cols-2 xl:grid-cols-4">
                                            <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm">
                                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Lead</div>
                                                <div class="mt-2 font-semibold text-slate-900">{{ $lead->business_name ?: '-' }}</div>
                                                <div class="mt-1 text-slate-600">RUC: {{ $lead->ruc ?: '-' }}</div>
                                            </div>

                                            <div class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Ejecutivo</div>
                                                <div class="mt-2 font-semibold text-slate-900">{{ $executiveName }}</div>
                                                <div class="mt-1 text-slate-600">Teléfono: {{ $primaryPhone ?: '-' }}</div>
                                            </div>

                                            <div class="rounded-2xl bg-slate-50 px-4 py-3 text-sm">
                                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">SISAC</div>
                                                <div class="mt-2 font-semibold {{ $lead->segment ? 'text-slate-900' : 'text-amber-700' }}">{{ $segmentLabel }}</div>
                                                <div class="mt-1 text-slate-600">Velocidad: {{ $lead->max_speed ?: '-' }}</div>
                                            </div>

                                            <div class="rounded-2xl border border-slate-200 px-4 py-3 text-sm">
                                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Seguimiento</div>
                                                <div class="mt-2 font-semibold text-slate-900">{{ ucfirst(str_replace('_', ' ', $lead->status_specific ?? 'sin_gestion')) }}</div>
                                                <div class="mt-1 text-slate-600">{{ $productSummary }}</div>
                                            </div>
                                        </div>

                                        <div x-data="{ openModal: {{ (string) old('sisac_lead_id') === (string) $lead->id ? 'true' : 'false' }} }" class="xl:min-w-[140px]">
                                            <button
                                                type="button"
                                                @click="openModal = true"
                                                class="inline-flex w-full items-center justify-center rounded-xl border-2 border-gray-900 px-4 py-2 text-sm font-semibold text-gray-900 transition hover:bg-gray-50"
                                            >
                                                Abrir detalle
                                            </button>

                                            <div
                                                x-show="openModal"
                                                x-transition
                                                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                                                style="display: none;"
                                            >
                                                <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="openModal = false"></div>

                                                <div class="relative flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-2xl">
                                                    <div class="border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(79,70,229,0.98),_rgba(99,102,241,0.96)_45%,_rgba(129,140,248,0.9))] px-6 py-6 text-white sm:px-8">
                                                        <div class="flex items-start justify-between gap-4">
                                                            <div class="min-w-0">
                                                                <p class="text-xs font-medium uppercase tracking-[0.24em] text-indigo-100/80">Mi base del equipo</p>
                                                                <h2 class="mt-2 text-2xl font-semibold tracking-tight">Datos SISAC</h2>
                                                                <p class="mt-2 text-sm text-indigo-50/90">{{ $lead->business_name ?: '-' }} | RUC: {{ $lead->ruc ?: '-' }}</p>
                                                                <p class="mt-1 text-sm text-indigo-100/80">Ejecutivo responsable: {{ $executiveName }}</p>
                                                            </div>

                                                            <button
                                                                type="button"
                                                                @click="openModal = false"
                                                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/20 text-white transition hover:bg-white/10"
                                                            >
                                                                <span class="sr-only">Cerrar modal</span>
                                                                <span class="text-xl leading-none">&times;</span>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div class="overflow-y-auto px-6 py-6 sm:px-8">
                                                        <form method="POST" action="{{ route('supervisor.team-base.sisac.update', $lead->id) }}" class="space-y-4">
                                                            @csrf
                                                            <input type="hidden" name="sisac_lead_id" value="{{ $lead->id }}">

                                                            <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 px-4 py-4">
                                                                <div class="flex items-center justify-between gap-3">
                                                                    <div>
                                                                        <div class="text-sm font-semibold text-indigo-900">Datos SISAC</div>
                                                                        <p class="mt-1 text-xs text-indigo-900/70">Este bloque solo lo completa el supervisor.</p>
                                                                    </div>
                                                                    <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-indigo-700">
                                                                        Editable
                                                                    </span>
                                                                </div>

                                                                <div class="mt-4">
                                                                    <label class="block text-sm font-medium text-gray-700">Segmento</label>
                                                                    <input type="text" name="segment" value="{{ (string) old('sisac_lead_id') === (string) $lead->id ? old('segment') : $lead->segment }}" class="mt-1 block w-full rounded border-gray-300 bg-white">
                                                                </div>

                                                                <div class="mt-4 rounded-xl border border-slate-200 bg-white px-4 py-4">
                                                                    <div class="text-sm font-semibold text-slate-900">Datos fija</div>
                                                                    <div class="mt-3 space-y-3">
                                                                        <div>
                                                                            <label class="block text-sm font-medium text-gray-700">Velocidad max</label>
                                                                            <input type="text" name="max_speed" value="{{ (string) old('sisac_lead_id') === (string) $lead->id ? old('max_speed') : $lead->max_speed }}" class="mt-1 block w-full rounded border-gray-300">
                                                                        </div>
                                                                        <div>
                                                                            <label class="block text-sm font-medium text-gray-700">Paquete</label>
                                                                            <input type="text" name="package" value="{{ (string) old('sisac_lead_id') === (string) $lead->id ? old('package') : $lead->package }}" class="mt-1 block w-full rounded border-gray-300">
                                                                        </div>
                                                                        <div>
                                                                            <label class="block text-sm font-medium text-gray-700">Tecnología</label>
                                                                            <input type="text" name="technology" value="{{ (string) old('sisac_lead_id') === (string) $lead->id ? old('technology') : $lead->technology }}" class="mt-1 block w-full rounded border-gray-300">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            @if((string) old('sisac_lead_id') === (string) $lead->id && $errors->hasAny(['segment', 'max_speed', 'package', 'technology']))
                                                                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                                                    {{ collect(['segment', 'max_speed', 'package', 'technology'])->flatMap(fn ($field) => $errors->get($field))->filter()->implode(' | ') }}
                                                                </div>
                                                            @endif

                                                            <div class="flex justify-end gap-3">
                                                                <button
                                                                    type="button"
                                                                    @click="openModal = false"
                                                                    class="inline-flex items-center justify-center rounded-full border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                                                >
                                                                    Cancelar
                                                                </button>
                                                                <button type="submit" class="inline-flex items-center justify-center rounded-full bg-black px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800">
                                                                    Guardar SISAC
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
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
