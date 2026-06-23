<x-app-layout>
    <div class="py-6">
        <div class="mx-auto max-w-[1600px] space-y-5 px-3 sm:px-4 lg:px-5">
            @php
                $isManagementScope = $scope === 'management';
            @endphp

            <div class="rounded-[28px] {{ $isManagementScope ? 'bg-gradient-to-r from-slate-100 via-white to-cyan-50' : 'border border-slate-200 bg-white' }} p-5 shadow-sm ring-1 ring-black/5">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                    <div class="space-y-2">
                        <p class="text-sm font-semibold uppercase tracking-[0.25em] {{ $isManagementScope ? 'text-slate-700' : 'text-blue-700' }}">
                            {{ $isManagementScope ? 'Seguimiento de Gerencia' : 'Monitor Supervisor' }}
                        </p>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">Actividad Ejecutiva Continua</h1>
                            <p class="mt-2 max-w-3xl text-sm text-gray-500">
                                Monitorea la sesión completa del ejecutivo desde login hasta logout, con tiempo por módulo, salidas del CRM y trazabilidad de acciones registradas.
                            </p>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white/90 px-4 py-3 text-sm text-gray-500 shadow-sm ring-1 ring-black/5">
                        <div class="font-semibold text-gray-900">Actualización automática</div>
                        <div class="mt-1">Cada 5 segundos sin recargar la pantalla.</div>
                        <div class="mt-2 text-xs text-gray-400" id="activityUpdatedAtLabel">
                            Estado inicial cargado.
                        </div>
                    </div>
                </div>

                <form method="GET" action="{{ $pageRoute }}" class="mt-5">
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-{{ $isManagementScope ? '5' : '4' }}">
                        <div class="rounded-2xl bg-white px-4 py-2.5 shadow-sm ring-1 ring-black/5">
                            <label for="date_from" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Desde</label>
                            <input id="date_from" type="date" name="date_from" value="{{ $filters['date_from'] }}" class="mt-1.5 block w-full rounded-xl border-gray-300 text-sm {{ $isManagementScope ? 'focus:border-slate-400 focus:ring-slate-400' : 'focus:border-indigo-400 focus:ring-indigo-400' }}">
                        </div>

                        <div class="rounded-2xl bg-white px-4 py-2.5 shadow-sm ring-1 ring-black/5">
                            <label for="date_to" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Hasta</label>
                            <input id="date_to" type="date" name="date_to" value="{{ $filters['date_to'] }}" class="mt-1.5 block w-full rounded-xl border-gray-300 text-sm {{ $isManagementScope ? 'focus:border-slate-400 focus:ring-slate-400' : 'focus:border-indigo-400 focus:ring-indigo-400' }}">
                        </div>

                        @if($isManagementScope)
                            <div class="rounded-2xl bg-white px-4 py-2.5 shadow-sm ring-1 ring-black/5">
                                <label for="supervisor_user_id" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Supervisor</label>
                                <select id="supervisor_user_id" name="supervisor_user_id" class="mt-1.5 block w-full rounded-xl border-gray-300 text-sm focus:border-slate-400 focus:ring-slate-400">
                                    <option value="">Todos</option>
                                    @foreach($supervisors as $supervisor)
                                        <option value="{{ $supervisor->id }}" {{ (string) $filters['supervisor_user_id'] === (string) $supervisor->id ? 'selected' : '' }}>{{ $supervisor->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="rounded-2xl bg-white px-4 py-2.5 shadow-sm ring-1 ring-black/5">
                            <label for="executive_user_id" class="block text-xs font-semibold uppercase tracking-wide text-gray-500">Ejecutivo</label>
                            <select id="executive_user_id" name="executive_user_id" class="mt-1.5 block w-full rounded-xl border-gray-300 text-sm {{ $isManagementScope ? 'focus:border-slate-400 focus:ring-slate-400' : 'focus:border-indigo-400 focus:ring-indigo-400' }}">
                                <option value="">Todos</option>
                                @foreach($executives as $executive)
                                    <option value="{{ $executive->id }}" {{ (string) $filters['executive_user_id'] === (string) $executive->id ? 'selected' : '' }}>{{ $executive->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-end gap-3">
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl {{ $isManagementScope ? 'bg-slate-900 hover:bg-slate-800' : 'bg-indigo-600 hover:bg-indigo-500' }} px-4 py-3 text-sm font-semibold text-white transition">
                                Actualizar
                            </button>
                            <a href="{{ $pageRoute }}" class="inline-flex items-center justify-center rounded-2xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                                Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div id="activityMonitoringBoard">
                @include('activity-monitoring.partials.board', ['scope' => $scope, 'payload' => $payload])
            </div>
        </div>
    </div>

    <div id="activityDrawerShell" class="pointer-events-none fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-slate-950/40 opacity-0 transition-opacity duration-200" data-activity-drawer-overlay></div>
        <div class="absolute inset-y-0 right-0 flex w-full max-w-2xl translate-x-full transition-transform duration-200">
            <div class="flex h-full w-full flex-col overflow-hidden rounded-l-[32px] bg-white shadow-2xl ring-1 ring-black/5" data-activity-drawer-panel>
                <div id="activityDrawerContent" class="flex-1 overflow-hidden"></div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const board = document.getElementById('activityMonitoringBoard');
            const updatedAtLabel = document.getElementById('activityUpdatedAtLabel');
            const pulseUrl = @json($pulseRoute);
            const drawerShell = document.getElementById('activityDrawerShell');
            const drawerOverlay = drawerShell?.querySelector('[data-activity-drawer-overlay]');
            const drawerPanel = drawerShell?.querySelector('[data-activity-drawer-panel]');
            const drawerContent = document.getElementById('activityDrawerContent');

            if (!board || !pulseUrl || !drawerShell || !drawerOverlay || !drawerPanel || !drawerContent) {
                return;
            }

            let pulseInFlight = false;
            let activeDrawerGroupKey = null;

            function getDrawerTemplate(groupKey) {
                if (!groupKey) {
                    return null;
                }

                return board.querySelector(`[data-activity-drawer-template="${groupKey}"]`);
            }

            function closeActivityDrawer() {
                activeDrawerGroupKey = null;
                drawerOverlay.classList.remove('opacity-100');
                drawerPanel.parentElement.classList.remove('translate-x-0');
                drawerPanel.parentElement.classList.add('translate-x-full');
                drawerShell.classList.add('pointer-events-none');
                window.setTimeout(() => {
                    if (!activeDrawerGroupKey) {
                        drawerShell.classList.add('hidden');
                        drawerContent.innerHTML = '';
                    }
                }, 200);
            }

            function openActivityDrawer(groupKey, options = {}) {
                const template = getDrawerTemplate(groupKey);
                if (!template) {
                    return;
                }

                activeDrawerGroupKey = groupKey;
                drawerContent.innerHTML = template.innerHTML;
                drawerShell.classList.remove('hidden', 'pointer-events-none');

                requestAnimationFrame(() => {
                    drawerOverlay.classList.add('opacity-100');
                    drawerPanel.parentElement.classList.remove('translate-x-full');
                    drawerPanel.parentElement.classList.add('translate-x-0');

                    const scrollable = drawerContent.querySelector('[data-activity-drawer-scroll]');
                    if (scrollable) {
                        scrollable.scrollTop = options.scrollTop || 0;
                    }
                });
            }

            function captureBoardState() {
                const preservedScrolls = Array.from(board.querySelectorAll('[data-preserve-scroll]'))
                    .map((element) => ({
                        key: element.dataset.preserveScroll,
                        top: element.scrollTop,
                        left: element.scrollLeft,
                    }));

                const drawerScrollable = drawerContent.querySelector('[data-activity-drawer-scroll]');

                return {
                    windowScrollY: window.scrollY,
                    windowScrollX: window.scrollX,
                    preservedScrolls,
                    activeDrawerGroupKey,
                    drawerScrollTop: drawerScrollable ? drawerScrollable.scrollTop : 0,
                };
            }

            function restoreBoardState(state) {
                if (!state) {
                    return;
                }

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

                if (state.activeDrawerGroupKey) {
                    openActivityDrawer(state.activeDrawerGroupKey, {
                        scrollTop: state.drawerScrollTop || 0,
                    });
                }
            }

            async function pollBoard() {
                if (pulseInFlight || document.hidden) return;

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
                        throw new Error('No se pudo refrescar el monitoreo de actividad.');
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

            window.setInterval(pollBoard, 5000);

            board.addEventListener('click', (event) => {
                const trigger = event.target.closest('[data-open-activity-drawer]');
                if (!trigger) {
                    return;
                }

                openActivityDrawer(trigger.dataset.openActivityDrawer);
            });

            drawerShell.addEventListener('click', (event) => {
                if (
                    event.target.closest('[data-close-activity-drawer]') ||
                    event.target === drawerOverlay
                ) {
                    closeActivityDrawer();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && activeDrawerGroupKey) {
                    closeActivityDrawer();
                }
            });
        });
    </script>
</x-app-layout>
