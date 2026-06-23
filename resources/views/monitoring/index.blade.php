<x-app-layout>
    <div class="py-6">
        <div class="mx-auto max-w-[1600px] space-y-5 px-3 sm:px-4 lg:px-5">
            @php
                $isManagementScope = $scope === 'management';
            @endphp

            <div class="rounded-[28px] {{ $isManagementScope ? 'bg-gradient-to-r from-slate-100 via-white to-cyan-50' : 'border border-slate-200 bg-white' }} p-5 shadow-sm ring-1 ring-black/5">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div class="space-y-1.5">
                        <p class="text-sm font-semibold uppercase tracking-[0.25em] {{ $isManagementScope ? 'text-slate-700' : 'text-blue-700' }}">
                            {{ $isManagementScope ? 'Seguimiento de Gerencia' : 'Monitor Supervisor' }}
                        </p>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 sm:text-3xl">TMO en vivo e histórico</h1>
                            <p class="mt-1.5 max-w-3xl text-sm text-gray-500">
                                Monitorea la gestión activa por lead, detecta ejecutivos que superan 1 minuto en una misma atención
                                y revisa el tiempo histórico que toma convertir un lead en acuerdo aceptado.
                            </p>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white/90 px-4 py-3 text-sm text-gray-500 shadow-sm ring-1 ring-black/5">
                        <div class="font-semibold text-gray-900">Actualización automática</div>
                        <div class="mt-1">Cada 10 segundos sin recargar la pantalla.</div>
                        <div class="mt-2 text-xs text-gray-400" id="tmoUpdatedAtLabel">
                            Estado inicial cargado.
                        </div>
                    </div>
                </div>

                <form method="GET" action="{{ $pageRoute }}" class="mt-5">
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-{{ $isManagementScope ? '5' : '4' }}">
                        <div class="rounded-2xl bg-white px-4 py-2.5 shadow-sm ring-1 ring-black/5">
                            <label for="date_from" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Desde</label>
                            <input
                                id="date_from"
                                type="date"
                                name="date_from"
                                value="{{ $filters['date_from'] }}"
                                class="mt-1.5 block w-full rounded-xl border-gray-300 text-sm {{ $isManagementScope ? 'focus:border-slate-400 focus:ring-slate-400' : 'focus:border-indigo-400 focus:ring-indigo-400' }}"
                            >
                        </div>

                        <div class="rounded-2xl bg-white px-4 py-2.5 shadow-sm ring-1 ring-black/5">
                            <label for="date_to" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Hasta</label>
                            <input
                                id="date_to"
                                type="date"
                                name="date_to"
                                value="{{ $filters['date_to'] }}"
                                class="mt-1.5 block w-full rounded-xl border-gray-300 text-sm {{ $isManagementScope ? 'focus:border-slate-400 focus:ring-slate-400' : 'focus:border-indigo-400 focus:ring-indigo-400' }}"
                            >
                        </div>

                        @if($isManagementScope)
                            <div class="rounded-2xl bg-white px-4 py-2.5 shadow-sm ring-1 ring-black/5">
                                <label for="supervisor_user_id" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Supervisor</label>
                                <select
                                    id="supervisor_user_id"
                                    name="supervisor_user_id"
                                    class="mt-1.5 block w-full rounded-xl border-gray-300 text-sm focus:border-slate-400 focus:ring-slate-400"
                                >
                                    <option value="">Todos</option>
                                    @foreach($supervisors as $supervisor)
                                        <option value="{{ $supervisor->id }}" {{ (string) $filters['supervisor_user_id'] === (string) $supervisor->id ? 'selected' : '' }}>
                                            {{ $supervisor->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="rounded-2xl bg-white px-4 py-2.5 shadow-sm ring-1 ring-black/5">
                            <label for="executive_user_id" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Ejecutivo</label>
                            <select
                                id="executive_user_id"
                                name="executive_user_id"
                                class="mt-1.5 block w-full rounded-xl border-gray-300 text-sm {{ $isManagementScope ? 'focus:border-slate-400 focus:ring-slate-400' : 'focus:border-indigo-400 focus:ring-indigo-400' }}"
                            >
                                <option value="">Todos</option>
                                @foreach($executives as $executive)
                                    <option value="{{ $executive->id }}" {{ (string) $filters['executive_user_id'] === (string) $executive->id ? 'selected' : '' }}>
                                        {{ $executive->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-end gap-2">
                            <button
                                type="submit"
                                class="inline-flex w-full items-center justify-center rounded-2xl {{ $isManagementScope ? 'bg-slate-900 hover:bg-slate-800' : 'bg-indigo-600 hover:bg-indigo-500' }} px-4 py-3 text-sm font-semibold text-white transition"
                            >
                                Actualizar
                            </button>

                            <a
                                href="{{ $pageRoute }}"
                                class="inline-flex items-center justify-center rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                            >
                                Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div id="tmoMonitoringBoard">
                @include('monitoring.partials.board', ['scope' => $scope, 'payload' => $payload])
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const board = document.getElementById('tmoMonitoringBoard');
            const updatedAtLabel = document.getElementById('tmoUpdatedAtLabel');
            const pulseUrl = @json($pulseRoute);

            if (!board || !pulseUrl) {
                return;
            }

            let pulseInFlight = false;

            function getOpenAgreementDetails() {
                return Array.from(board.querySelectorAll('[data-agreement-details][open]'))
                    .map((element) => element.getAttribute('data-agreement-details'))
                    .filter(Boolean);
            }

            function captureBoardState() {
                return {
                    windowScrollY: window.scrollY,
                    windowScrollX: window.scrollX,
                    openAgreementDetails: getOpenAgreementDetails(),
                    preservedScrolls: Array.from(board.querySelectorAll('[data-preserve-scroll]'))
                        .map((element) => ({
                            key: element.dataset.preserveScroll,
                            top: element.scrollTop,
                            left: element.scrollLeft,
                        })),
                };
            }

            function restoreOpenAgreementDetails(openIds) {
                if (!Array.isArray(openIds) || openIds.length === 0) {
                    return;
                }

                openIds.forEach((id) => {
                    const element = board.querySelector(`[data-agreement-details="${id}"]`);

                    if (element) {
                        element.setAttribute('open', 'open');
                    }
                });
            }

            function restoreBoardState(state) {
                if (!state) {
                    return;
                }

                restoreOpenAgreementDetails(state.openAgreementDetails);

                (state.preservedScrolls || []).forEach((savedScroll) => {
                    const element = board.querySelector(`[data-preserve-scroll="${savedScroll.key}"]`);
                    if (!element) {
                        return;
                    }

                    element.scrollTop = savedScroll.top || 0;
                    element.scrollLeft = savedScroll.left || 0;
                });

                window.scrollTo({
                    top: state.windowScrollY || 0,
                    left: state.windowScrollX || 0,
                    behavior: 'auto',
                });
            }

            async function pollTmoBoard() {
                if (pulseInFlight || document.hidden) {
                    return;
                }

                pulseInFlight = true;
                const boardState = captureBoardState();

                try {
                    const response = await fetch(pulseUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error('No se pudo refrescar el tablero TMO.');
                    }

                    const payload = await response.json();

                    if (payload.board_html) {
                        board.innerHTML = payload.board_html;
                        restoreBoardState(boardState);
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

            window.setInterval(pollTmoBoard, 10000);
        });
    </script>
</x-app-layout>
