<x-app-layout>
    <div class="py-6">
        <div class="mx-auto max-w-[1700px] space-y-5 px-3 sm:px-4 lg:px-5">
            <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(2,6,23,0.98),_rgba(30,41,59,0.95)_45%,_rgba(8,145,178,0.84))] px-4 py-4 text-white sm:px-5 sm:py-5">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                        <div class="max-w-3xl">
                            <p class="text-xs font-medium uppercase tracking-[0.22em] text-cyan-100/80">Gerencia</p>
                            <h1 class="mt-2 text-[2rem] font-semibold tracking-tight leading-tight">Dashboard comercial por supervisor</h1>
                            <p class="mt-1.5 max-w-2xl text-[13px] leading-5 text-cyan-50/80">
                                Indicadores globales del mes y de la semana actual para todo el equipo comercial mapeado en gerencia.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-2 xl:grid-cols-3 xl:min-w-[760px]">
                            @foreach(collect($goalMetricCards)->chunk(2) as $group)
                                <div class="space-y-2">
                                    @foreach($group as $card)
                                        <div
                                            @class([
                                                'rounded-lg border px-3 py-2 backdrop-blur',
                                                'border-sky-200/25 bg-sky-300/10' => $card['tone'] === 'sky',
                                                'border-amber-200/25 bg-amber-300/10' => $card['tone'] === 'amber',
                                                'border-emerald-200/25 bg-emerald-300/10' => $card['tone'] === 'emerald',
                                            ])
                                        >
                                            <div class="text-[9px] font-semibold uppercase tracking-[0.16em] text-cyan-100/70">{{ $card['label'] }}</div>
                                            <div class="mt-1 text-[1.45rem] font-semibold leading-none">{{ $card['progress'] }}</div>
                                            <div class="mt-1.5 flex items-center justify-between gap-2 text-[10px] text-cyan-50/80">
                                                <span>Meta total</span>
                                                <span class="font-semibold text-white/90">{{ number_format($card['goal']) }}</span>
                                            </div>
                                            <div class="mt-0.5 text-[10px] leading-4 text-cyan-50/70">{{ $card['meta_label'] }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div
                x-data='managementDashboard(@json($dashboardPayload))'
                x-init="init()"
                class="space-y-5"
            >
                <div class="overflow-hidden rounded-[28px] bg-white shadow-sm ring-1 ring-black/5">
                    <div class="border-b border-gray-100 px-4 py-3">
                        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">Vista de gerencia</h2>
                            </div>

                            <div class="flex flex-col gap-2 lg:flex-row lg:items-end">
                                <form method="GET" action="{{ route('dashboard') }}" class="grid gap-2 md:grid-cols-[minmax(160px,180px)_minmax(160px,180px)_minmax(200px,240px)_auto]">
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de inicio</label>
                                        <input
                                            type="date"
                                            name="from"
                                            value="{{ $filters['from'] ?? '' }}"
                                            class="mt-1.5 w-full rounded-lg border-gray-300 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                    </div>

                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de fin</label>
                                        <input
                                            type="date"
                                            name="to"
                                            value="{{ $filters['to'] ?? '' }}"
                                            class="mt-1.5 w-full rounded-lg border-gray-300 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                    </div>

                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                        <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Ver equipo</label>
                                        <select
                                            x-model="selectedSupervisorId"
                                            name="focus_supervisor_user_id"
                                            @change="queueRenderCharts()"
                                            class="mt-1.5 w-full rounded-lg border-gray-300 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="all">Todos los supervisores</option>
                                            <template x-for="supervisor in supervisors" :key="supervisor.supervisor_user_id">
                                                <option :value="String(supervisor.supervisor_user_id)" x-text="supervisor.supervisor_name"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div class="flex items-end gap-2">
                                        <button type="submit" class="inline-flex h-[42px] items-center justify-center rounded-lg bg-black px-3 text-sm font-semibold text-white transition hover:bg-gray-800">
                                            Aplicar
                                        </button>
                                        <a href="{{ route('dashboard') }}" class="inline-flex h-[42px] items-center justify-center rounded-lg border border-slate-300 px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                            Limpiar
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="p-4">
                        <div x-show="selectedSupervisorId === 'all'" x-transition.opacity class="space-y-5">
                            <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white">
                                <div class="border-b border-gray-100 px-4 py-3">
                                    <h3 class="text-base font-bold text-gray-900">Todos los supervisores</h3>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="bg-gray-950 text-white">
                                            <tr>
                                                <th class="px-4 py-1 text-left font-semibold uppercase tracking-wide">Supervisor</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Gestión total</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Contactado</th>
                                                <template x-for="status in statuses" :key="status.key">
                                                    <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide" x-text="status.label"></th>
                                                </template>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 bg-white">
                                            <template x-for="(supervisor, index) in supervisors" :key="supervisor.supervisor_user_id">
                                                <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50/60'">
                                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-900" x-text="supervisor.supervisor_name"></td>
                                                    <td class="px-4 py-3 text-center font-bold text-gray-900" x-text="formatNumber(supervisor.gestion_total || 0)"></td>
                                                    <td class="px-4 py-1 text-center">
                                                        <button
                                                            type="button"
                                                            @click="openSupervisorStatusModal(supervisor.supervisor_user_id, 'contactado')"
                                                            :disabled="!Number(supervisor.contactado || 0)"
                                                            class="inline-flex min-w-[4.25rem] items-center justify-center rounded-xl border px-3 py-1 text-sm font-semibold transition"
                                                            :class="badgeClass('positive')"
                                                            x-text="formatNumber(supervisor.contactado || 0)"
                                                        ></button>
                                                    </td>
                                                    <template x-for="status in statuses" :key="`${supervisor.supervisor_user_id}-${status.key}`">
                                                        <td class="px-4 py-1 text-center">
                                                            <button
                                                                type="button"
                                                                @click="openSupervisorStatusModal(supervisor.supervisor_user_id, status.key)"
                                                                :disabled="!Number(supervisor[status.key] || 0)"
                                                                class="inline-flex min-w-[4.25rem] items-center justify-center rounded-xl border px-3 py-1 text-sm font-semibold transition"
                                                                :class="badgeClass(status.tone)"
                                                                x-text="formatNumber(supervisor[status.key] || 0)"
                                                            ></button>
                                                        </td>
                                                    </template>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(320px,0.65fr)]">
                                <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white">
                                    <div class="border-b border-gray-100 px-4 py-3">
                                        <h3 class="text-base font-bold text-gray-900">Supervisores y estados</h3>
                                    </div>
                                    <div class="p-4">
                                        <div id="managementSupervisorStackedChart"></div>
                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white">
                                    <div class="border-b border-gray-100 px-4 py-3">
                                        <h3 class="text-base font-bold text-gray-900">Totales de gerencia</h3>
                                    </div>
                                    <div class="p-4">
                                        <div id="managementTotalsDonutChart"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="selectedSupervisorId !== 'all'" x-transition.opacity class="space-y-5" style="display: none;">
                            <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white">
                                <div class="border-b border-gray-100 px-4 py-3">
                                    <h3 class="text-base font-bold text-gray-900">Detalle del supervisor</h3>
                                </div>

                                <div class="p-4">
                                    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(300px,0.65fr)]">
                                        <div class="space-y-4">
                                            <div class="grid gap-3 md:grid-cols-3">
                                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 md:col-span-2">
                                                    <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Supervisor</div>
                                                    <div class="mt-1 text-2xl font-bold text-slate-900" x-text="selectedSupervisor ? selectedSupervisor.supervisor_name : '-'"></div>
                                                </div>

                                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                                    <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Equipo</div>
                                                    <div class="mt-1 text-2xl font-bold text-slate-900" x-text="selectedSupervisor ? formatNumber(selectedSupervisor.executives_count || 0) : '0'"></div>
                                                    <div class="mt-1 text-xs text-slate-500">Ejecutivos asignados</div>
                                                </div>
                                            </div>

                                            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-left text-slate-900">
                                                    <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Gestión total</div>
                                                    <div class="mt-1.5 text-[1.75rem] font-bold leading-none" x-text="selectedSupervisor ? formatNumber(selectedSupervisor.gestion_total || 0) : '0'"></div>
                                                </div>
                                                <button
                                                    type="button"
                                                    @click="selectedSupervisor && openSupervisorStatusModal(selectedSupervisor.supervisor_user_id, 'contactado')"
                                                    class="rounded-2xl border px-3 py-2.5 text-left transition"
                                                    :class="['border-emerald-200 bg-emerald-50 text-emerald-900', (selectedSupervisor && Number(selectedSupervisor.contactado || 0) > 0) ? 'cursor-pointer hover:-translate-y-0.5 hover:shadow-sm' : 'cursor-default opacity-90']"
                                                >
                                                    <div class="text-[11px] font-semibold uppercase tracking-[0.14em]">Contactado</div>
                                                    <div class="mt-1.5 text-[1.75rem] font-bold leading-none" x-text="selectedSupervisor ? formatNumber(selectedSupervisor.contactado || 0) : '0'"></div>
                                                </button>
                                                <template x-for="status in statuses" :key="`supervisor-detail-${status.key}`">
                                                    <button
                                                        type="button"
                                                        @click="selectedSupervisor && openSupervisorStatusModal(selectedSupervisor.supervisor_user_id, status.key)"
                                                        class="rounded-2xl border px-3 py-2.5 text-left transition"
                                                        :class="[metricCardClass(status.tone), (selectedSupervisor && Number(selectedSupervisor[status.key] || 0) > 0) ? 'cursor-pointer hover:-translate-y-0.5 hover:shadow-sm' : 'cursor-default opacity-90']"
                                                    >
                                                        <div class="text-[11px] font-semibold uppercase tracking-[0.14em]" x-text="status.label"></div>
                                                        <div class="mt-1.5 text-[1.75rem] font-bold leading-none" x-text="selectedSupervisor ? formatNumber(selectedSupervisor[status.key] || 0) : '0'"></div>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>

                                        <div class="rounded-2xl border border-slate-200 bg-white px-3 py-3">
                                            <div id="managementTeamDonutChart"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white">
                                <div class="border-b border-gray-100 px-4 py-3">
                                    <h3 class="text-base font-bold text-gray-900">Ejecutivos del equipo</h3>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="bg-gray-950 text-white">
                                            <tr>
                                                <th class="px-4 py-1 text-left font-semibold uppercase tracking-wide">Ejecutivo</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Gestión total</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Contactado</th>
                                                <template x-for="status in statuses" :key="`team-header-${status.key}`">
                                                    <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide" x-text="status.label"></th>
                                                </template>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 bg-white">
                                            <template x-for="(executive, index) in selectedSupervisorExecutives" :key="executive.executive_user_id">
                                                <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50/60'">
                                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-900" x-text="executive.executive_name"></td>
                                                    <td class="px-4 py-3 text-center font-bold text-gray-900" x-text="formatNumber(executive.gestion_total || 0)"></td>
                                                    <td class="px-4 py-1 text-center">
                                                        <button
                                                            type="button"
                                                            @click="openExecutiveStatusModal(executive.executive_user_id, 'contactado')"
                                                            :disabled="!Number(executive.contactado || 0)"
                                                            class="inline-flex min-w-[4.25rem] items-center justify-center rounded-xl border px-3 py-1 text-sm font-semibold transition"
                                                            :class="badgeClass('positive')"
                                                            x-text="formatNumber(executive.contactado || 0)"
                                                        ></button>
                                                    </td>
                                                    <template x-for="status in statuses" :key="`${executive.executive_user_id}-team-${status.key}`">
                                                        <td class="px-4 py-1 text-center">
                                                            <button
                                                                type="button"
                                                                @click="openExecutiveStatusModal(executive.executive_user_id, status.key)"
                                                                :disabled="!Number(executive[status.key] || 0)"
                                                                class="inline-flex min-w-[4.25rem] items-center justify-center rounded-xl border px-3 py-1 text-sm font-semibold transition"
                                                                :class="badgeClass(status.tone)"
                                                                x-text="formatNumber(executive[status.key] || 0)"
                                                            ></button>
                                                        </td>
                                                    </template>
                                                </tr>
                                            </template>
                                            <tr x-show="selectedSupervisorExecutives.length === 0">
                                                <td :colspan="statuses.length + 3" class="px-4 py-8 text-center text-sm text-slate-500">
                                                    Este supervisor no tiene ejecutivos mapeados.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white">
                                <div class="border-b border-gray-100 px-4 py-3">
                                    <h3 class="text-base font-bold text-gray-900">Estados del equipo</h3>
                                </div>
                                <div class="p-4">
                                    <div id="managementExecutiveStackedChart"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="statusModalOpen" x-transition.opacity style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4 py-6">
                    <div @click.outside="closeStatusModal()" class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-[28px] bg-white shadow-2xl">
                        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <span x-text="statusModal.ownerLabel || 'Supervisor'"></span>
                                </div>
                                <h3 class="mt-1 text-xl font-bold text-gray-900">
                                    <span x-text="statusModal.ownerName || '-'"></span>
                                    <span class="text-slate-400"> · </span>
                                    <span x-text="statusModal.statusLabel || 'Estado'"></span>
                                </h3>
                            </div>

                            <button @click="closeStatusModal()" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-xl font-semibold text-slate-500 transition hover:border-slate-300 hover:text-slate-700">
                                ×
                            </button>
                        </div>

                        <div class="border-b border-gray-100 bg-slate-50 px-5 py-3">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div class="flex flex-wrap gap-2">
                                    <div class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700">
                                        Leads: <span class="ml-1" x-text="formatNumber(filteredStatusModalLeads.length)"></span>
                                    </div>
                                    <div class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-700">
                                        Estado: <span class="ml-1" x-text="statusModal.statusLabel || '-'"></span>
                                    </div>
                                </div>

                                <div class="flex items-end gap-2">
                                    <div class="min-w-[180px]">
                                        <label class="block text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">Filtrar por fecha</label>
                                        <input
                                            type="date"
                                            x-model="statusModalDate"
                                            class="mt-1 w-full rounded-lg border-gray-300 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                    </div>
                                    <button
                                        type="button"
                                        @click="statusModalDate = ''"
                                        class="inline-flex h-[42px] items-center justify-center rounded-lg border border-slate-300 px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                                    >
                                        Limpiar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto p-5">
                            <div class="overflow-x-auto rounded-[24px] border border-slate-200">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-950 text-white">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-[0.14em]">Empresa</th>
                                            <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-[0.14em]">Datos del lead</th>
                                            <th x-show="statusModalHasCommercialDetail" class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-[0.14em]">Oferta y llamada</th>
                                            <th class="px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-[0.14em]">Actualizado</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <template x-for="(lead, index) in filteredStatusModalLeads" :key="lead.id">
                                            <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-slate-50/70'">
                                                <td class="px-3 py-2.5 align-top">
                                                    <div class="text-sm font-semibold text-slate-900" x-text="lead.business_name"></div>
                                                    <div class="mt-1 text-xs text-slate-500">RUC: <span class="font-medium text-slate-700" x-text="lead.ruc"></span></div>
                                                </td>
                                                <td class="px-3 py-2.5 align-top">
                                                    <div class="space-y-1.5 text-xs text-slate-700">
                                                        <div><span class="font-semibold text-slate-900">Representante:</span> <span x-text="lead.representative_name"></span></div>
                                                        <div><span class="font-semibold text-slate-900">Teléfono:</span> <span x-text="lead.last_contact_phone"></span></div>
                                                        <div><span class="font-semibold text-slate-900">DNI:</span> <span x-text="lead.dni"></span></div>
                                                        <div>
                                                            <span class="font-semibold text-slate-900">Celulares del lead:</span>
                                                            <template x-if="lead.lead_phones?.length">
                                                                <span x-text="lead.lead_phones.join(', ')"></span>
                                                            </template>
                                                            <template x-if="!lead.lead_phones?.length">
                                                                <span>-</span>
                                                            </template>
                                                        </div>
                                                        <div><span class="font-semibold text-slate-900">Operador / líneas:</span> <span x-text="lead.current_operator"></span> · <span x-text="lead.current_line_count"></span></div>
                                                    </div>
                                                </td>
                                                <td x-show="statusModalHasCommercialDetail" class="px-3 py-2.5 align-top">
                                                    <template x-if="statusModalHasCommercialDetail && lead.show_commercial_detail">
                                                        <div class="space-y-1.5 text-xs text-slate-700">
                                                            <div><span class="font-semibold text-slate-900">Último contacto:</span> <span x-text="lead.last_contact_name"></span></div>
                                                            <div><span class="font-semibold text-slate-900">Teléfono:</span> <span x-text="lead.commercial_snapshot?.contact_phone || '-'"></span></div>
                                                            <div><span class="font-semibold text-slate-900">Registro:</span> <span x-text="lead.commercial_snapshot?.call_detail || '-'"></span></div>
                                                            <div>
                                                                <span class="font-semibold text-slate-900">Productos:</span>
                                                                <template x-if="lead.commercial_snapshot?.products?.length">
                                                                    <div class="mt-1 space-y-1">
                                                                        <template x-for="(product, productIndex) in lead.commercial_snapshot.products" :key="`${lead.id}-product-${productIndex}`">
                                                                            <div class="rounded-lg bg-emerald-50 px-2 py-1.5 text-xs">
                                                                                <span class="font-semibold text-emerald-800" x-text="product.label"></span>
                                                                                <span class="text-slate-700"> · </span>
                                                                                <span class="text-slate-700" x-text="product.detail"></span>
                                                                            </div>
                                                                        </template>
                                                                    </div>
                                                                </template>
                                                                <template x-if="!lead.commercial_snapshot?.products?.length">
                                                                    <div class="mt-1 text-xs text-slate-500">Sin productos registrados.</div>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </td>
                                                <td class="px-3 py-2.5 align-top">
                                                    <div class="text-xs font-medium text-slate-700" x-text="lead.updated_at_label"></div>
                                                </td>
                                            </tr>
                                        </template>
                                        <tr x-show="filteredStatusModalLeads.length === 0">
                                            <td :colspan="statusModalHasCommercialDetail ? 4 : 3" class="px-4 py-8 text-center text-sm text-slate-500">
                                                No hay leads para la fecha seleccionada.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function managementDashboard(payload) {
            return {
                payload,
                selectedSupervisorId: String(payload.initial_supervisor_id || 'all'),
                statusModalOpen: false,
                supervisorStackedChart: null,
                managementDonutChart: null,
                executiveStackedChart: null,
                teamDonutChart: null,
                statusModalDate: '',
                statusModal: {
                    ownerLabel: '',
                    ownerName: '',
                    statusKey: '',
                    statusLabel: '',
                    leads: [],
                },
                aggregateStatuses: {
                    contactado: {
                        label: 'Contactado',
                        tone: 'positive',
                    },
                },
                chartColors: {
                    reprogramado: '#f59e0b',
                    negociacion: '#6366f1',
                    acuerdo_aceptado: '#16a34a',
                    no_desea: '#ef4444',
                    no_contesta: '#94a3b8',
                    telefono_apagado: '#64748b',
                    no_existe: '#334155',
                },
                init() {
                    this.queueRenderCharts();
                },
                queueRenderCharts(attempt = 0) {
                    this.$nextTick(() => {
                        requestAnimationFrame(() => {
                            requestAnimationFrame(() => {
                                if (!window.ApexCharts) {
                                    if (attempt >= 20) {
                                        return;
                                    }

                                    window.setTimeout(() => this.queueRenderCharts(attempt + 1), 120);
                                    return;
                                }

                                this.renderCharts();
                            });
                        });
                    });
                },
                get supervisors() {
                    return Array.isArray(this.payload.supervisors) ? this.payload.supervisors : [];
                },
                get statuses() {
                    return Array.isArray(this.payload.statuses) ? this.payload.statuses : [];
                },
                get selectedSupervisor() {
                    return this.supervisors.find((supervisor) => String(supervisor.supervisor_user_id) === String(this.selectedSupervisorId)) || null;
                },
                get selectedSupervisorExecutives() {
                    return Array.isArray(this.selectedSupervisor?.executives) ? this.selectedSupervisor.executives : [];
                },
                get statusModalHasCommercialDetail() {
                    return ['negociacion', 'acuerdo_aceptado', 'contactado'].includes(this.statusModal.statusKey);
                },
                get filteredStatusModalLeads() {
                    if (!this.statusModalDate) {
                        return this.statusModal.leads;
                    }

                    return this.statusModal.leads.filter((lead) => lead.updated_at_date === this.statusModalDate);
                },
                getStatusLabel(statusKey) {
                    if (this.aggregateStatuses[statusKey]) {
                        return this.aggregateStatuses[statusKey].label;
                    }

                    return this.statuses.find((status) => status.key === statusKey)?.label || statusKey;
                },
                formatNumber(value) {
                    return new Intl.NumberFormat('es-MX').format(Number(value || 0));
                },
                openSupervisorStatusModal(supervisorId, statusKey) {
                    const supervisor = this.supervisors.find((item) => String(item.supervisor_user_id) === String(supervisorId));

                    if (!supervisor) {
                        return;
                    }

                    const leads = supervisor.lead_details?.[statusKey] || [];

                    if (!Array.isArray(leads) || !leads.length) {
                        return;
                    }

                    this.statusModal = {
                        ownerLabel: 'Supervisor',
                        ownerName: supervisor.supervisor_name,
                        statusKey,
                        statusLabel: this.getStatusLabel(statusKey),
                        leads,
                    };
                    this.statusModalDate = '';
                    this.statusModalOpen = true;
                },
                openExecutiveStatusModal(executiveId, statusKey) {
                    const executive = this.selectedSupervisorExecutives.find((item) => String(item.executive_user_id) === String(executiveId));

                    if (!executive) {
                        return;
                    }

                    const leads = executive.lead_details?.[statusKey] || [];

                    if (!Array.isArray(leads) || !leads.length) {
                        return;
                    }

                    this.statusModal = {
                        ownerLabel: 'Ejecutivo',
                        ownerName: executive.executive_name,
                        statusKey,
                        statusLabel: this.getStatusLabel(statusKey),
                        leads,
                    };
                    this.statusModalDate = '';
                    this.statusModalOpen = true;
                },
                closeStatusModal() {
                    this.statusModalOpen = false;
                    this.statusModalDate = '';
                },
                badgeClass(tone) {
                    if (tone === 'positive') {
                        return 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-emerald-300';
                    }

                    if (tone === 'negative') {
                        return 'border-rose-200 bg-rose-50 text-rose-700 hover:border-rose-300';
                    }

                    return 'border-amber-200 bg-amber-50 text-amber-700 hover:border-amber-300';
                },
                metricCardClass(tone) {
                    if (tone === 'positive') {
                        return 'border-emerald-200 bg-emerald-50 text-emerald-900';
                    }

                    if (tone === 'negative') {
                        return 'border-rose-200 bg-rose-50 text-rose-900';
                    }

                    return 'border-amber-200 bg-amber-50 text-amber-900';
                },
                statusColors() {
                    return this.statuses.map((status) => this.chartColors[status.key] || '#0f172a');
                },
                renderCharts() {
                    this.renderSupervisorStackedChart();
                    this.renderManagementDonutChart();
                    this.renderExecutiveStackedChart();
                    this.renderTeamDonutChart();
                },
                renderSupervisorStackedChart() {
                    const mountNode = document.querySelector('#managementSupervisorStackedChart');

                    if (!mountNode || !window.ApexCharts) {
                        return;
                    }

                    if (this.supervisorStackedChart) {
                        this.supervisorStackedChart.destroy();
                    }

                    const categories = this.supervisors.map((supervisor) => supervisor.supervisor_name);
                    const series = this.statuses.map((status) => ({
                        name: status.label,
                        data: this.supervisors.map((supervisor) => Number(supervisor[status.key] || 0)),
                    }));

                    this.supervisorStackedChart = new window.ApexCharts(mountNode, {
                        chart: {
                            type: 'bar',
                            height: Math.max(360, this.supervisors.length * 54),
                            stacked: true,
                            toolbar: { show: false },
                            fontFamily: 'inherit',
                        },
                        series,
                        colors: this.statusColors(),
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                borderRadius: 6,
                                barHeight: '62%',
                            },
                        },
                        dataLabels: {
                            enabled: false,
                        },
                        stroke: {
                            width: 1,
                            colors: ['#ffffff'],
                        },
                        grid: {
                            borderColor: '#e5e7eb',
                            strokeDashArray: 4,
                        },
                        xaxis: {
                            categories,
                            labels: {
                                style: {
                                    cssClass: 'text-[12px] text-gray-500',
                                },
                            },
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    cssClass: 'text-[12px] font-medium text-gray-700',
                                },
                            },
                        },
                        legend: {
                            position: 'top',
                            horizontalAlign: 'left',
                            fontSize: '12px',
                        },
                        tooltip: {
                            shared: false,
                            intersect: true,
                        },
                    });

                    this.supervisorStackedChart.render();
                },
                renderManagementDonutChart() {
                    const mountNode = document.querySelector('#managementTotalsDonutChart');

                    if (!mountNode || !window.ApexCharts) {
                        return;
                    }

                    if (this.managementDonutChart) {
                        this.managementDonutChart.destroy();
                    }

                    const series = this.statuses.map((status) => {
                        return this.supervisors.reduce((sum, supervisor) => sum + Number(supervisor[status.key] || 0), 0);
                    });

                    this.managementDonutChart = new window.ApexCharts(mountNode, {
                        chart: {
                            type: 'donut',
                            height: 340,
                            toolbar: { show: false },
                            fontFamily: 'inherit',
                        },
                        series,
                        labels: this.statuses.map((status) => status.label),
                        colors: this.statusColors(),
                        stroke: { width: 0 },
                        legend: {
                            position: 'bottom',
                            fontSize: '12px',
                        },
                        dataLabels: {
                            enabled: true,
                            formatter: (value) => `${Math.round(value)}%`,
                            style: {
                                colors: ['#ffffff'],
                                fontWeight: 700,
                            },
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '66%',
                                    labels: {
                                        show: true,
                                        total: {
                                            show: true,
                                            label: 'Gerencia',
                                            formatter: () => this.formatNumber(series.reduce((sum, value) => sum + Number(value || 0), 0)),
                                        },
                                    },
                                },
                            },
                        },
                    });

                    this.managementDonutChart.render();
                },
                renderExecutiveStackedChart() {
                    const mountNode = document.querySelector('#managementExecutiveStackedChart');

                    if (!mountNode || !window.ApexCharts) {
                        return;
                    }

                    if (this.executiveStackedChart) {
                        this.executiveStackedChart.destroy();
                    }

                    if (!this.selectedSupervisor || this.selectedSupervisorId === 'all') {
                        mountNode.innerHTML = '<div class="rounded-2xl border border-dashed border-gray-300 px-4 py-12 text-center text-sm text-gray-500">Selecciona un supervisor para ver su equipo.</div>';
                        return;
                    }

                    mountNode.innerHTML = '';

                    const categories = this.selectedSupervisorExecutives.map((executive) => executive.executive_name);
                    const series = this.statuses.map((status) => ({
                        name: status.label,
                        data: this.selectedSupervisorExecutives.map((executive) => Number(executive[status.key] || 0)),
                    }));

                    this.executiveStackedChart = new window.ApexCharts(mountNode, {
                        chart: {
                            type: 'bar',
                            height: Math.max(320, this.selectedSupervisorExecutives.length * 54),
                            stacked: true,
                            toolbar: { show: false },
                            fontFamily: 'inherit',
                        },
                        series,
                        colors: this.statusColors(),
                        plotOptions: {
                            bar: {
                                horizontal: true,
                                borderRadius: 6,
                                barHeight: '62%',
                            },
                        },
                        dataLabels: {
                            enabled: false,
                        },
                        stroke: {
                            width: 1,
                            colors: ['#ffffff'],
                        },
                        grid: {
                            borderColor: '#e5e7eb',
                            strokeDashArray: 4,
                        },
                        xaxis: {
                            categories,
                            labels: {
                                style: {
                                    cssClass: 'text-[12px] text-gray-500',
                                },
                            },
                        },
                        yaxis: {
                            labels: {
                                style: {
                                    cssClass: 'text-[12px] font-medium text-gray-700',
                                },
                            },
                        },
                        legend: {
                            position: 'top',
                            horizontalAlign: 'left',
                            fontSize: '12px',
                        },
                        tooltip: {
                            shared: false,
                            intersect: true,
                        },
                    });

                    this.executiveStackedChart.render();
                },
                renderTeamDonutChart() {
                    const mountNode = document.querySelector('#managementTeamDonutChart');

                    if (!mountNode || !window.ApexCharts) {
                        return;
                    }

                    if (this.teamDonutChart) {
                        this.teamDonutChart.destroy();
                    }

                    if (!this.selectedSupervisor || this.selectedSupervisorId === 'all') {
                        mountNode.innerHTML = '<div class="rounded-2xl border border-dashed border-gray-300 px-4 py-12 text-center text-sm text-gray-500">Selecciona un supervisor para ver su distribución.</div>';
                        return;
                    }

                    mountNode.innerHTML = '';

                    const series = this.statuses.map((status) => Number(this.selectedSupervisor[status.key] || 0));

                    this.teamDonutChart = new window.ApexCharts(mountNode, {
                        chart: {
                            type: 'donut',
                            height: 320,
                            toolbar: { show: false },
                            fontFamily: 'inherit',
                        },
                        series,
                        labels: this.statuses.map((status) => status.label),
                        colors: this.statusColors(),
                        stroke: { width: 0 },
                        legend: {
                            position: 'bottom',
                            fontSize: '12px',
                        },
                        dataLabels: {
                            enabled: true,
                            formatter: (value) => `${Math.round(value)}%`,
                            style: {
                                colors: ['#ffffff'],
                                fontWeight: 700,
                            },
                        },
                        plotOptions: {
                            pie: {
                                donut: {
                                    size: '68%',
                                    labels: {
                                        show: true,
                                        total: {
                                            show: true,
                                            label: 'Equipo',
                                            formatter: () => this.formatNumber(this.selectedSupervisor.total || 0),
                                        },
                                    },
                                },
                            },
                        },
                    });

                    this.teamDonutChart.render();
                },
            };
        }
    </script>
</x-app-layout>
