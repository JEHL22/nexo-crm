@php
    $summaryCards = collect($payload['summary_cards'] ?? []);
    $liveRows = collect($payload['live_rows'] ?? []);
    $historicalRows = collect($payload['historical_rows'] ?? []);
    $recentEvents = collect($payload['recent_events'] ?? []);
    $recentEventGroups = collect($payload['recent_event_groups'] ?? []);
    $isManagementScope = $scope === 'management';
@endphp

<div class="space-y-6">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($summaryCards as $card)
            <div class="rounded-3xl bg-white px-5 py-6 text-center shadow-sm ring-1 ring-black/5">
                <div class="text-3xl font-black tracking-tight text-gray-900">{{ $card['value'] }}</div>
                <div class="mt-2 text-sm font-semibold text-gray-500">{{ $card['label'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-[28px] bg-white shadow-sm ring-1 ring-black/5">
        <div class="border-b border-gray-100 px-6 py-5">
            <h2 class="text-lg font-bold text-gray-900">Sesión actual por ejecutivo</h2>
            <p class="mt-1 text-sm text-gray-500">
                Estado vivo del equipo: módulo actual, pantalla, foco dentro del CRM y tiempo acumulado en la pantalla activa.
            </p>
        </div>

        @if($liveRows->isEmpty())
            <div class="px-6 py-12 text-center text-sm text-gray-500">
                No hay ejecutivos activos en este momento para los filtros seleccionados.
            </div>
        @else
            <div class="overflow-x-auto" data-preserve-scroll="live-table">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-950 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Estado</th>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Ejecutivo</th>
                            @if($isManagementScope)
                                <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Supervisor</th>
                            @endif
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Módulo</th>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Pantalla</th>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Última señal</th>
                            <th class="px-4 py-3 text-center font-semibold uppercase tracking-wide">Tiempo pantalla</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($liveRows as $row)
                            <tr class="{{ $row['is_focused'] ? ($loop->odd ? 'bg-white' : 'bg-gray-50/70') : 'bg-amber-50' }}">
                                <td class="px-4 py-3">
                                    @if($row['is_focused'])
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Dentro del CRM</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Fuera del CRM</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-semibold text-gray-900">{{ $row['executive_name'] }}</td>
                                @if($isManagementScope)
                                    <td class="px-4 py-3 text-gray-700">{{ $row['supervisor_name'] }}</td>
                                @endif
                                <td class="px-4 py-3 text-gray-700">{{ $row['module_label'] }}</td>
                                <td class="px-4 py-3 text-gray-700">
                                    <div class="font-medium text-gray-900">{{ $row['route_name'] }}</div>
                                    <div class="text-xs text-gray-500 break-all">{{ $row['page_url'] }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $row['last_seen_label'] }}</td>
                                <td class="px-4 py-3 text-center font-semibold text-gray-900">{{ gmdate($row['current_seconds'] >= 3600 ? 'H:i:s' : 'i:s', $row['current_seconds']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.08fr)_minmax(0,0.92fr)]">
        <div class="overflow-hidden rounded-[28px] bg-white shadow-sm ring-1 ring-black/5">
            <div class="border-b border-gray-100 px-6 py-5">
                <h2 class="text-lg font-bold text-gray-900">Resumen histórico por ejecutivo</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Vista acumulada de la sesión completa: tiempo total, tiempo fuera del CRM y distribución por módulo.
                </p>
            </div>

            @if($historicalRows->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gray-500">
                    No hay sesiones históricas en el rango consultado.
                </div>
            @else
                <div class="overflow-x-auto" data-preserve-scroll="historical-table">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-950 text-white">
                            <tr>
                                <th class="px-4 py-4 text-left font-semibold uppercase tracking-wide">Ejecutivo</th>
                                @if($isManagementScope)
                                    <th class="px-4 py-4 text-left font-semibold uppercase tracking-wide">Supervisor</th>
                                @endif
                                <th class="px-4 py-4 text-center font-semibold uppercase tracking-wide">Sesiones</th>
                                <th class="px-4 py-4 text-center font-semibold uppercase tracking-wide">Tiempo CRM</th>
                                <th class="px-4 py-4 text-center font-semibold uppercase tracking-wide">Tiempo fuera</th>
                                <th class="px-4 py-4 text-center font-semibold uppercase tracking-wide">A negociar</th>
                                <th class="px-4 py-4 text-center font-semibold uppercase tracking-wide">Mi chamba</th>
                                <th class="px-4 py-4 text-center font-semibold uppercase tracking-wide">Mis ventas</th>
                                <th class="px-4 py-4 text-center font-semibold uppercase tracking-wide">Mi cobertura</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach($historicalRows as $row)
                                <tr class="{{ $loop->odd ? 'bg-white' : 'bg-gray-50/70' }}">
                                    <td class="px-4 py-3 font-semibold text-gray-900">{{ $row['executive_name'] }}</td>
                                    @if($isManagementScope)
                                        <td class="px-4 py-3 text-gray-700">{{ $row['supervisor_label'] }}</td>
                                    @endif
                                    <td class="px-4 py-3 text-center text-gray-700">{{ number_format($row['session_count']) }}</td>
                                    <td class="px-4 py-3 text-center font-semibold text-gray-900">{{ gmdate($row['total_seconds'] >= 3600 ? 'H:i:s' : 'i:s', $row['total_seconds']) }}</td>
                                    <td class="px-4 py-3 text-center text-amber-700">{{ gmdate($row['blurred_seconds'] >= 3600 ? 'H:i:s' : 'i:s', $row['blurred_seconds']) }}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">{{ gmdate($row['module_totals']['a_negociar'] >= 3600 ? 'H:i:s' : 'i:s', $row['module_totals']['a_negociar']) }}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">{{ gmdate($row['module_totals']['mi_chamba'] >= 3600 ? 'H:i:s' : 'i:s', $row['module_totals']['mi_chamba']) }}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">{{ gmdate($row['module_totals']['mis_ventas'] >= 3600 ? 'H:i:s' : 'i:s', $row['module_totals']['mis_ventas']) }}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">{{ gmdate($row['module_totals']['mi_cobertura'] >= 3600 ? 'H:i:s' : 'i:s', $row['module_totals']['mi_cobertura']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="overflow-hidden rounded-[28px] bg-white shadow-sm ring-1 ring-black/5">
            <div class="border-b border-gray-100 px-6 py-5">
                <h2 class="text-lg font-bold text-gray-900">Últimas actividades</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Línea de tiempo reciente agrupada por ejecutivo, con entradas, salidas del CRM, cambios de pantalla y acciones enviadas.
                </p>
            </div>

            @if($recentEventGroups->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gray-500">
                    No hay eventos registrados en el periodo consultado.
                </div>
            @else
                <div id="activityRecentEventsPanel" class="max-h-[42rem] overflow-y-auto p-6" data-preserve-scroll="recent-events">
                    <div class="space-y-4">
                        @foreach($recentEventGroups as $group)
                            <div class="rounded-[24px] border border-slate-200 bg-white px-5 py-4 shadow-sm">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold text-slate-900">{{ $group['executive_name'] }}</div>
                                        <div class="mt-2 grid gap-2 text-xs text-slate-500 sm:grid-cols-2">
                                            <div class="rounded-full bg-slate-100 px-3 py-1.5">
                                                {{ number_format($group['event_count']) }} actividad(es)
                                            </div>
                                            <div class="rounded-full bg-slate-100 px-3 py-1.5">
                                                Última: {{ $group['latest_event_label'] }}
                                            </div>
                                            @if($isManagementScope && filled($group['supervisor_name']))
                                                <div class="rounded-full bg-slate-100 px-3 py-1.5 sm:col-span-2">
                                                    Supervisor: {{ $group['supervisor_name'] }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="inline-flex shrink-0 items-center rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-800"
                                        data-open-activity-drawer="{{ $group['group_key'] }}"
                                    >
                                        Ver detalle
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="hidden">
                    @foreach($recentEventGroups as $group)
                        <template data-activity-drawer-template="{{ $group['group_key'] }}">
                            <div class="flex h-full flex-col">
                                <div class="border-b border-slate-200 px-6 py-5">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <div class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Detalle ejecutivo</div>
                                            <h3 class="mt-2 text-xl font-bold text-slate-900">{{ $group['executive_name'] }}</h3>
                                            <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500">
                                                <span class="rounded-full bg-slate-100 px-3 py-1.5">{{ number_format($group['event_count']) }} actividad(es)</span>
                                                <span class="rounded-full bg-slate-100 px-3 py-1.5">Última: {{ $group['latest_event_label'] }}</span>
                                                @if($isManagementScope && filled($group['supervisor_name']))
                                                    <span class="rounded-full bg-slate-100 px-3 py-1.5">Supervisor: {{ $group['supervisor_name'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900"
                                            data-close-activity-drawer
                                        >
                                            <span class="sr-only">Cerrar detalle</span>
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="flex-1 overflow-y-auto bg-slate-50/70 px-5 py-5" data-activity-drawer-scroll>
                                    <div class="space-y-3">
                                        @foreach($group['events'] as $event)
                                            <div class="rounded-[24px] border border-slate-200 bg-white px-4 py-4 shadow-sm">
                                                <div class="flex items-start justify-between gap-4">
                                                    <div class="min-w-0">
                                                        <div class="text-sm font-semibold text-slate-900">{{ $event['label'] }}</div>
                                                        <div class="mt-1 text-xs text-slate-500">{{ $event['occurred_at_label'] }}</div>
                                                    </div>
                                                    <div class="shrink-0 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                                        {{ $event['module_label'] }}
                                                    </div>
                                                </div>

                                                <div class="mt-3 space-y-2 text-sm text-slate-700">
                                                    <div><span class="font-semibold text-slate-900">Pantalla:</span> {{ $event['route_name'] }}</div>
                                                    <div class="break-all"><span class="font-semibold text-slate-900">URL:</span> {{ $event['page_url'] }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </template>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
