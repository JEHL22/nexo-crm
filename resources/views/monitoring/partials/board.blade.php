@php
    $activeSessions = collect($payload['active_sessions'] ?? []);
    $executiveStatuses = collect($payload['executive_statuses'] ?? []);
    $historicalRows = collect($payload['historical_rows'] ?? []);
    $agreementRows = collect($payload['agreement_rows'] ?? []);
    $summaryCards = collect($payload['summary_cards'] ?? []);
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
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Monitoreo por ejecutivo</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Vista compacta del equipo completo. Se muestra en qué apartado está cada ejecutivo y se resalta si supera 1 minuto en una misma gestión.
                </p>
            </div>
        </div>

        @if($executiveStatuses->isEmpty())
            <div class="px-6 py-16 text-center text-sm text-gray-500">
                No hay ejecutivos disponibles para este filtro.
            </div>
        @else
            <div class="overflow-x-auto" data-preserve-scroll="tmo-live-table">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-950 text-white">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Estado</th>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Ejecutivo</th>
                            @if($isManagementScope)
                                <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Supervisor</th>
                            @endif
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Apartado</th>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Lead</th>
                            <th class="px-4 py-3 text-left font-semibold uppercase tracking-wide">Estado lead</th>
                            <th class="px-4 py-3 text-center font-semibold uppercase tracking-wide">TMO en vivo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($executiveStatuses as $row)
                            <tr class="{{ $row['is_over_threshold'] ? 'bg-amber-50' : ($loop->odd ? 'bg-white' : 'bg-gray-50/70') }}">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex h-2.5 w-2.5 rounded-full {{ $row['is_active'] ? ($row['is_over_threshold'] ? 'bg-amber-500' : 'bg-emerald-500') : 'bg-slate-300' }}"></span>
                                        @if($row['is_over_threshold'])
                                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">+1 min</span>
                                        @elseif($row['is_active'])
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Activo</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">En espera</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-semibold text-gray-900">{{ $row['executive_name'] }}</td>
                                @if($isManagementScope)
                                    <td class="px-4 py-3 text-gray-700">{{ $row['supervisor_name'] }}</td>
                                @endif
                                <td class="px-4 py-3 text-gray-700">{{ $row['module_label'] }}</td>
                                <td class="px-4 py-3 text-gray-700">
                                    <div class="font-medium text-gray-900">{{ $row['lead_label'] }}</div>
                                    <div class="text-xs text-gray-500">RUC: {{ $row['lead_ruc'] }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $row['lead_status'] }}</td>
                                <td class="px-4 py-3 text-center font-semibold {{ $row['is_over_threshold'] ? 'text-amber-700' : 'text-gray-900' }}">
                                    {{ $row['is_active'] ? gmdate($row['elapsed_seconds'] >= 3600 ? 'H:i:s' : 'i:s', $row['elapsed_seconds']) : '--:--' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
        <div class="overflow-hidden rounded-[28px] bg-white shadow-sm ring-1 ring-black/5">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">TMO histórico por ejecutivo</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Acumulado del periodo consultado para medir carga operativa y promedio por lead.
                    </p>
                </div>
            </div>

            @if($historicalRows->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gray-500">
                    No hay sesiones históricas para los filtros seleccionados.
                </div>
            @else
                <div class="overflow-x-auto" data-preserve-scroll="tmo-historical-table">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-950 text-white">
                            <tr>
                                <th class="px-4 py-4 text-left font-semibold uppercase tracking-wide">Ejecutivo</th>
                                @if($isManagementScope)
                                    <th class="px-4 py-4 text-left font-semibold uppercase tracking-wide">Supervisor</th>
                                @endif
                                <th class="px-4 py-4 text-center font-semibold uppercase tracking-wide">Sesiones</th>
                                <th class="px-4 py-4 text-center font-semibold uppercase tracking-wide">Leads totales</th>
                                <th class="px-4 py-4 text-center font-semibold uppercase tracking-wide">A negociar</th>
                                <th class="px-4 py-4 text-center font-semibold uppercase tracking-wide">TMO A negociar</th>
                                <th class="px-4 py-4 text-center font-semibold uppercase tracking-wide">Mi chamba</th>
                                <th class="px-4 py-4 text-center font-semibold uppercase tracking-wide">TMO Mi chamba</th>
                                <th class="px-4 py-4 text-center font-semibold uppercase tracking-wide">TMO total</th>
                                <th class="px-4 py-4 text-center font-semibold uppercase tracking-wide">Promedio por lead</th>
                                <th class="px-4 py-4 text-center font-semibold uppercase tracking-wide">Pico por lead</th>
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
                                    <td class="px-4 py-3 text-center text-gray-700">{{ number_format($row['lead_count']) }}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">{{ number_format($row['a_negociar_lead_count']) }}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">{{ gmdate($row['a_negociar_total_seconds'] >= 3600 ? 'H:i:s' : 'i:s', $row['a_negociar_total_seconds']) }}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">{{ number_format($row['mi_chamba_lead_count']) }}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">{{ gmdate($row['mi_chamba_total_seconds'] >= 3600 ? 'H:i:s' : 'i:s', $row['mi_chamba_total_seconds']) }}</td>
                                    <td class="px-4 py-3 text-center font-semibold text-gray-900">{{ gmdate($row['total_seconds'] >= 3600 ? 'H:i:s' : 'i:s', $row['total_seconds']) }}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">{{ gmdate($row['avg_seconds_per_lead'] >= 3600 ? 'H:i:s' : 'i:s', $row['avg_seconds_per_lead']) }}</td>
                                    <td class="px-4 py-3 text-center text-gray-700">{{ gmdate($row['max_lead_seconds'] >= 3600 ? 'H:i:s' : 'i:s', $row['max_lead_seconds']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="overflow-hidden rounded-[28px] bg-white shadow-sm ring-1 ring-black/5">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-5">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Tiempo a acuerdo aceptado</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Snapshot histórico del TMO que tomó cerrar un lead como acuerdo aceptado.
                    </p>
                </div>
            </div>

            @if($agreementRows->isEmpty())
                <div class="px-6 py-12 text-center text-sm text-gray-500">
                    Todavía no hay acuerdos con TMO registrado para este periodo.
                </div>
            @else
                <div class="max-h-[38rem] overflow-y-auto bg-slate-50/60 p-4" data-preserve-scroll="tmo-agreements-panel">
                    <div class="space-y-3">
                        @foreach($agreementRows as $row)
                            <div class="rounded-[24px] border border-slate-200 bg-white px-4 py-4 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-slate-900">{{ $row['lead_label'] }}</div>
                                        <div class="mt-1 text-xs text-slate-500">RUC: {{ $row['lead_ruc'] }}</div>
                                    </div>
                                    <div class="shrink-0 rounded-full bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white">
                                        {{ $row['tmo_to_agreement_label'] }}
                                    </div>
                                </div>

                                <div class="mt-3 grid gap-2 rounded-2xl bg-slate-50 px-3 py-3 text-sm text-slate-700 sm:grid-cols-2">
                                    <div><span class="font-semibold text-slate-900">Ejecutivo:</span> {{ $row['executive_name'] }}</div>
                                    @if($isManagementScope)
                                        <div><span class="font-semibold text-slate-900">Supervisor:</span> {{ $row['supervisor_label'] }}</div>
                                    @endif
                                    <div><span class="font-semibold text-slate-900">Aceptado:</span> {{ $row['accepted_at_label'] }}</div>
                                </div>

                                @if(!empty($row['timeline']))
                                    <details data-agreement-details="{{ $row['sale_id'] }}" class="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70">
                                        <summary class="cursor-pointer list-none px-4 py-3 text-sm font-semibold text-slate-900">
                                            Ver detalle por estado
                                        </summary>
                                        <div class="border-t border-slate-200 bg-white px-3 py-3">
                                            <div class="space-y-2.5">
                                                @foreach($row['timeline'] as $timelineRow)
                                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-700">
                                                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                                            <div>
                                                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Estado</div>
                                                                <div class="mt-1 font-semibold text-slate-900">{{ $timelineRow['status_label'] }}</div>
                                                            </div>
                                                            <div>
                                                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Apartado</div>
                                                                <div class="mt-1">{{ $timelineRow['module_label'] }}</div>
                                                            </div>
                                                            <div>
                                                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Tiempo en estado</div>
                                                                <div class="mt-1">{{ $timelineRow['duration_label'] }}</div>
                                                            </div>
                                                            <div>
                                                                <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Acumulado</div>
                                                                <div class="mt-1">{{ $timelineRow['cumulative_label'] }}</div>
                                                            </div>
                                                        </div>
                                                        <div class="mt-3 border-t border-slate-200 pt-3">
                                                            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Ingresó</div>
                                                            <div class="mt-1">{{ $timelineRow['entered_at_label'] }}</div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </details>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
