<x-app-layout>
    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                <div class="crm-panel-hero border-b border-slate-200 px-6 py-8 text-white sm:px-8">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-2xl">
                            <p class="text-sm font-medium uppercase tracking-[0.22em] text-white/75">Supervisor</p>
                            <h1 class="mt-3 text-3xl font-semibold tracking-tight">Acuerdos del equipo</h1>
                            <p class="mt-2 text-sm text-white/80 sm:text-base">
                                Consulta todos los acuerdos de tus ejecutivos, revisa su trazabilidad completa y entra a validar los casos que sigan pendientes.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.18em] text-white/70">Acuerdos</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $sales->total() }}</div>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.18em] text-white/70">Página</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $sales->currentPage() }}</div>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.18em] text-white/70">Pendientes</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $sales->getCollection()->where('supervisor_validation_status', 'pendiente')->count() }}</div>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.18em] text-white/70">Validados</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $sales->getCollection()->where('supervisor_validation_status', 'validado')->count() }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 sm:p-8">
                    <form method="GET" action="{{ route('supervisor.agreements.index') }}" class="mb-6">
                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">RUC</label>
                                <input
                                    type="text"
                                    name="ruc"
                                    value="{{ $filters['ruc'] ?? '' }}"
                                    placeholder="Buscar por RUC"
                                    class="mt-2 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                >
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Supervisor</label>
                                <select name="supervisor_validation_status" class="mt-2 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Todos</option>
                                    @foreach($supervisorStatusOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(($filters['supervisor_validation_status'] ?? '') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Postventa</label>
                                <select name="management_status" class="mt-2 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Todos</option>
                                    @foreach($managementStatusOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(($filters['management_status'] ?? '') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Mesa de Control</label>
                                <select name="sisac_status" class="mt-2 w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Todos</option>
                                    @foreach($sisacStatusOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(($filters['sisac_status'] ?? '') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex items-end gap-3">
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-black px-4 py-3 text-sm font-semibold text-white transition hover:bg-gray-800">
                                    Filtrar
                                </button>
                                <a href="{{ route('supervisor.agreements.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-gray-300 px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                                    Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    <div id="supervisorAgreementsList">
                        @include('supervisor.agreements.partials.list', [
                            'sales' => $sales,
                            'openTraceabilitySaleId' => $openTraceabilitySaleId ?? null,
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const listContainer = document.getElementById('supervisorAgreementsList');
            const updatedAtLabel = document.getElementById('supervisorAgreementsUpdatedAtLabel');
            const pulseUrl = @json($pulseRoute);
            let currentPulseUrl = pulseUrl;

            if (!listContainer || !pulseUrl) {
                return;
            }

            let pulseInFlight = false;

            function clearTraceabilityAutoOpenState() {
                try {
                    const currentUrl = new URL(window.location.href);

                    if (currentUrl.searchParams.has('traceability_sale')) {
                        currentUrl.searchParams.delete('traceability_sale');
                        window.history.replaceState({}, '', currentUrl.toString());
                    }

                    const nextPulseUrl = new URL(currentPulseUrl, window.location.origin);

                    if (nextPulseUrl.searchParams.has('traceability_sale')) {
                        nextPulseUrl.searchParams.delete('traceability_sale');
                        currentPulseUrl = `${nextPulseUrl.pathname}${nextPulseUrl.search}${nextPulseUrl.hash}`;
                    }
                } catch (error) {
                    console.error('No se pudo limpiar el estado de apertura automática de trazabilidad.', error);
                }
            }

            if (window.location.search.includes('traceability_sale=')) {
                window.setTimeout(clearTraceabilityAutoOpenState, 0);
            }

            function hasOpenTraceabilityModal() {
                return Boolean(window.__supervisorTraceabilityModalOpen) || Boolean(document.querySelector('[data-supervisor-traceability-open="1"]'));
            }

            async function refreshAgreementsBoard() {
                if (pulseInFlight || document.hidden || hasOpenTraceabilityModal()) {
                    return;
                }

                pulseInFlight = true;
                const previousScrollY = window.scrollY;
                const previousScrollX = window.scrollX;

                try {
                    const response = await window.fetch(currentPulseUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error('No se pudo refrescar la vista de acuerdos del supervisor.');
                    }

                    const payload = await response.json();

                    if (payload.list_html && !hasOpenTraceabilityModal()) {
                        listContainer.innerHTML = payload.list_html;
                        window.scrollTo({
                            top: previousScrollY,
                            left: previousScrollX,
                            behavior: 'auto',
                        });
                    }

                    if (updatedAtLabel && payload.updated_at_label) {
                        updatedAtLabel.textContent = `Actualizado: ${payload.updated_at_label}`;
                    }
                } catch (error) {
                    console.error(error);
                } finally {
                    pulseInFlight = false;
                }
            }

            refreshAgreementsBoard();
            window.setInterval(refreshAgreementsBoard, 5000);
            window.addEventListener('focus', refreshAgreementsBoard);
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    refreshAgreementsBoard();
                }
            });
        });
    </script>
</x-app-layout>
