<x-app-layout>
    <div class="py-6">
        <div class="mx-auto max-w-[1700px] space-y-5 px-3 sm:px-4 lg:px-5">
            <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(2,6,23,0.98),_rgba(30,41,59,0.95)_45%,_rgba(14,116,144,0.88))] px-4 py-4 text-white sm:px-5 sm:py-5">
                        <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                            <div class="max-w-3xl">
                                <p class="text-xs font-medium uppercase tracking-[0.22em] text-cyan-100/80">Supervisor</p>
                                <h1 class="mt-2 text-[2rem] font-semibold tracking-tight leading-tight">Dashboard del equipo</h1>
                                <p class="mt-1.5 max-w-2xl text-[13px] leading-5 text-cyan-50/80">
                                    Indicadores de gestión del mes y de la semana actual, escalados según los ejecutivos asignados a tu equipo.
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
                x-data='supervisorDashboard(@json($dashboardPayload))'
                x-init="init()"
                class="space-y-5"
            >
                <div class="overflow-hidden rounded-[28px] bg-white shadow-sm ring-1 ring-black/5">
                    <div class="border-b border-gray-100 px-4 py-3">
                        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">Vista del equipo</h2>
                            </div>

                            <div class="flex flex-col gap-2 lg:flex-row lg:items-end">
                                <form method="GET" action="{{ route('supervisor.dashboard.index') }}" class="grid gap-2 md:grid-cols-[minmax(160px,180px)_minmax(160px,180px)_auto]">
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

                                    <div class="flex items-end gap-2">
                                        <button type="submit" class="inline-flex h-[42px] items-center justify-center rounded-lg bg-black px-3 text-sm font-semibold text-white transition hover:bg-gray-800">
                                            Aplicar
                                        </button>
                                        <a href="{{ route('supervisor.dashboard.index') }}" class="inline-flex h-[42px] items-center justify-center rounded-lg border border-slate-300 px-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">
                                            Limpiar
                                        </a>
                                    </div>
                                </form>

                                <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Ver ejecutivo</label>
                                    <select
                                        x-model="selectedExecutiveId"
                                        @change="queueRenderCharts()"
                                        class="mt-1.5 w-full rounded-lg border-gray-300 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="all">Todos los ejecutivos</option>
                                        <template x-for="executive in executives" :key="executive.executive_user_id">
                                            <option :value="String(executive.executive_user_id)" x-text="executive.executive_name"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4">
                        <div x-show="selectedExecutiveId === 'all'" x-transition.opacity class="space-y-5">
                            <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white">
                                <div class="border-b border-gray-100 px-4 py-3">
                                    <h3 class="text-base font-bold text-gray-900">Todos los ejecutivos asignados</h3>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="bg-gray-950 text-white">
                                            <tr>
                                                <th class="px-4 py-1 text-left font-semibold uppercase tracking-wide">Ejecutivo</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Gestión total</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Contactado</th>
                                                <template x-for="status in statuses" :key="status.key">
                                                    <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide" x-text="status.label"></th>
                                                </template>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 bg-white">
                                            <template x-for="(executive, index) in executives" :key="executive.executive_user_id">
                                                <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50/60'">
                                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-900" x-text="executive.executive_name"></td>
                                                    <td class="px-4 py-3 text-center font-bold text-gray-900" x-text="formatNumber(executive.gestion_total || 0)"></td>
                                                    <td class="px-4 py-1 text-center">
                                                        <button
                                                            type="button"
                                                            @click="openStatusModal(executive.executive_user_id, 'contactado')"
                                                            class="inline-flex min-w-[4.25rem] items-center justify-center rounded-xl border px-3 py-1 text-sm font-semibold transition"
                                                            :class="[
                                                                statusButtonClass(executive, 'contactado', 'positive'),
                                                                Number(executive.contactado || 0) > 0 ? 'cursor-pointer' : 'cursor-default'
                                                            ]"
                                                            x-text="formatNumber(executive.contactado || 0)"
                                                        ></button>
                                                    </td>
                                                    <template x-for="status in statuses" :key="`${executive.executive_user_id}-${status.key}`">
                                                        <td class="px-4 py-1 text-center">
                                                            <button
                                                                type="button"
                                                                @click="openStatusModal(executive.executive_user_id, status.key)"
                                                                class="inline-flex min-w-[4.25rem] items-center justify-center rounded-xl border px-3 py-1 text-sm font-semibold transition"
                                                                :class="[
                                                                    statusButtonClass(executive, status.key, status.tone),
                                                                    Number(executive[status.key] || 0) > 0 ? 'cursor-pointer' : 'cursor-default'
                                                                ]"
                                                                x-text="formatNumber(executive[status.key] || 0)"
                                                            ></button>
                                                        </td>
                                                    </template>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white">
                                <button
                                    type="button"
                                    @click="toggleSection('dailyCarryover')"
                                    class="flex w-full items-center justify-between gap-4 border-b border-gray-100 px-4 py-3 text-left"
                                >
                                    <div>
                                        <h3 class="text-base font-bold text-gray-900">Arrastre diario por ejecutivo</h3>
                                    </div>
                                    <span
                                        class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition"
                                        :class="sectionOpen('dailyCarryover') ? 'rotate-180' : ''"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </button>

                                <div x-show="sectionOpen('dailyCarryover')" x-transition.opacity.duration.150ms class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="bg-gray-950 text-white">
                                            <tr>
                                                <th rowspan="2" class="px-4 py-2 text-left font-semibold uppercase tracking-wide">Ejecutivo</th>
                                                <th colspan="4" class="bg-sky-950/40 px-4 py-2 text-center font-semibold uppercase tracking-wide">Negociación</th>
                                                <th colspan="4" class="border-l-4 border-l-amber-400 bg-amber-950/40 px-4 py-2 text-center font-semibold uppercase tracking-wide">Reprogramado</th>
                                                <th colspan="4" class="border-l-4 border-l-emerald-400 bg-emerald-950/40 px-4 py-2 text-center font-semibold uppercase tracking-wide">Acuerdo aceptado</th>
                                            </tr>
                                            <tr>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Hoy</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Meta diaria</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Arrastre</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Meta vigente hoy</th>
                                                <th class="border-l-4 border-l-amber-400 px-4 py-1 text-center font-semibold uppercase tracking-wide">Hoy</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Meta diaria</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Arrastre</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Meta vigente hoy</th>
                                                <th class="border-l-4 border-l-emerald-400 px-4 py-1 text-center font-semibold uppercase tracking-wide">Hoy</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Meta diaria</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Arrastre</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Meta vigente hoy</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 bg-white">
                                            <template x-for="(executive, index) in executives" :key="`carryover-${executive.executive_user_id}`">
                                                <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50/60'">
                                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-900" x-text="executive.executive_name"></td>
                                                    <td class="px-4 py-3 text-center font-semibold text-slate-900" x-text="formatNumber(carryoverValue(executive, 'negociacion', 'today_actual'))"></td>
                                                    <td class="px-4 py-3 text-center text-slate-700" x-text="formatNumber(carryoverValue(executive, 'negociacion', 'daily_goal'))"></td>
                                                    <td class="px-4 py-3 text-center text-amber-700" x-text="formatNumber(carryoverValue(executive, 'negociacion', 'carryover_pending'))"></td>
                                                    <td class="px-4 py-3 text-center font-bold text-slate-900" x-text="formatNumber(carryoverValue(executive, 'negociacion', 'current_target'))"></td>
                                                    <td class="border-l-4 border-l-amber-100 px-4 py-3 text-center font-semibold text-slate-900" x-text="formatNumber(carryoverValue(executive, 'reprogramado', 'today_actual'))"></td>
                                                    <td class="px-4 py-3 text-center text-slate-700" x-text="formatNumber(carryoverValue(executive, 'reprogramado', 'daily_goal'))"></td>
                                                    <td class="px-4 py-3 text-center text-amber-700" x-text="formatNumber(carryoverValue(executive, 'reprogramado', 'carryover_pending'))"></td>
                                                    <td class="px-4 py-3 text-center font-bold text-slate-900" x-text="formatNumber(carryoverValue(executive, 'reprogramado', 'current_target'))"></td>
                                                    <td class="border-l-4 border-l-emerald-100 px-4 py-3 text-center font-semibold text-slate-900" x-text="formatNumber(carryoverValue(executive, 'acuerdo_aceptado', 'today_actual'))"></td>
                                                    <td class="px-4 py-3 text-center text-slate-700" x-text="formatNumber(carryoverValue(executive, 'acuerdo_aceptado', 'daily_goal'))"></td>
                                                    <td class="px-4 py-3 text-center text-amber-700" x-text="formatNumber(carryoverValue(executive, 'acuerdo_aceptado', 'carryover_pending'))"></td>
                                                    <td class="px-4 py-3 text-center font-bold text-slate-900" x-text="formatNumber(carryoverValue(executive, 'acuerdo_aceptado', 'current_target'))"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white">
                                <button
                                    type="button"
                                    @click="toggleSection('weeklyCarryover')"
                                    class="flex w-full items-center justify-between gap-4 border-b border-gray-100 px-4 py-3 text-left"
                                >
                                    <div>
                                        <h3 class="text-base font-bold text-gray-900">Arrastre semanal por ejecutivo</h3>
                                    </div>
                                    <span
                                        class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition"
                                        :class="sectionOpen('weeklyCarryover') ? 'rotate-180' : ''"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </button>

                                <div x-show="sectionOpen('weeklyCarryover')" x-transition.opacity.duration.150ms class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="bg-gray-950 text-white">
                                            <tr>
                                                <th rowspan="2" class="px-4 py-2 text-left font-semibold uppercase tracking-wide">Ejecutivo</th>
                                                <th colspan="4" class="bg-sky-950/40 px-4 py-2 text-center font-semibold uppercase tracking-wide">Negociación</th>
                                                <th colspan="4" class="border-l-4 border-l-amber-400 bg-amber-950/40 px-4 py-2 text-center font-semibold uppercase tracking-wide">Reprogramado</th>
                                                <th colspan="4" class="border-l-4 border-l-emerald-400 bg-emerald-950/40 px-4 py-2 text-center font-semibold uppercase tracking-wide">Acuerdo aceptado</th>
                                            </tr>
                                            <tr>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Esta semana</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Meta semanal</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Arrastre</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Meta vigente semana</th>
                                                <th class="border-l-4 border-l-amber-400 px-4 py-1 text-center font-semibold uppercase tracking-wide">Esta semana</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Meta semanal</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Arrastre</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Meta vigente semana</th>
                                                <th class="border-l-4 border-l-emerald-400 px-4 py-1 text-center font-semibold uppercase tracking-wide">Esta semana</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Meta semanal</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Arrastre</th>
                                                <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide">Meta vigente semana</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 bg-white">
                                            <template x-for="(executive, index) in executives" :key="`weekly-carryover-${executive.executive_user_id}`">
                                                <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50/60'">
                                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-900" x-text="executive.executive_name"></td>
                                                    <td class="px-4 py-3 text-center font-semibold text-slate-900" x-text="formatNumber(carryoverValue(executive, 'negociacion', 'current_week_actual', 'weekly_carryover'))"></td>
                                                    <td class="px-4 py-3 text-center text-slate-700" x-text="formatNumber(carryoverValue(executive, 'negociacion', 'weekly_goal', 'weekly_carryover'))"></td>
                                                    <td class="px-4 py-3 text-center text-amber-700" x-text="formatNumber(carryoverValue(executive, 'negociacion', 'carryover_pending', 'weekly_carryover'))"></td>
                                                    <td class="px-4 py-3 text-center font-bold text-slate-900" x-text="formatNumber(carryoverValue(executive, 'negociacion', 'current_target', 'weekly_carryover'))"></td>
                                                    <td class="border-l-4 border-l-amber-100 px-4 py-3 text-center font-semibold text-slate-900" x-text="formatNumber(carryoverValue(executive, 'reprogramado', 'current_week_actual', 'weekly_carryover'))"></td>
                                                    <td class="px-4 py-3 text-center text-slate-700" x-text="formatNumber(carryoverValue(executive, 'reprogramado', 'weekly_goal', 'weekly_carryover'))"></td>
                                                    <td class="px-4 py-3 text-center text-amber-700" x-text="formatNumber(carryoverValue(executive, 'reprogramado', 'carryover_pending', 'weekly_carryover'))"></td>
                                                    <td class="px-4 py-3 text-center font-bold text-slate-900" x-text="formatNumber(carryoverValue(executive, 'reprogramado', 'current_target', 'weekly_carryover'))"></td>
                                                    <td class="border-l-4 border-l-emerald-100 px-4 py-3 text-center font-semibold text-slate-900" x-text="formatNumber(carryoverValue(executive, 'acuerdo_aceptado', 'current_week_actual', 'weekly_carryover'))"></td>
                                                    <td class="px-4 py-3 text-center text-slate-700" x-text="formatNumber(carryoverValue(executive, 'acuerdo_aceptado', 'weekly_goal', 'weekly_carryover'))"></td>
                                                    <td class="px-4 py-3 text-center text-amber-700" x-text="formatNumber(carryoverValue(executive, 'acuerdo_aceptado', 'carryover_pending', 'weekly_carryover'))"></td>
                                                    <td class="px-4 py-3 text-center font-bold text-slate-900" x-text="formatNumber(carryoverValue(executive, 'acuerdo_aceptado', 'current_target', 'weekly_carryover'))"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white">
                                <button
                                    type="button"
                                    @click="toggleSection('monthlyFollowUp')"
                                    class="flex w-full items-center justify-between gap-4 border-b border-gray-100 px-4 py-3 text-left"
                                >
                                    <div>
                                        <h3 class="text-base font-bold text-gray-900">Seguimiento semanal acumulado del mes</h3>
                                    </div>
                                    <span
                                        class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition"
                                        :class="sectionOpen('monthlyFollowUp') ? 'rotate-180' : ''"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </button>

                                <div x-show="sectionOpen('monthlyFollowUp')" x-transition.opacity.duration.150ms class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="bg-gray-950 text-white">
                                            <tr>
                                                <th rowspan="2" class="px-4 py-2 text-left font-semibold uppercase tracking-wide">Ejecutivo</th>
                                                <th :colspan="monthWeeks.length" class="px-4 py-2 text-center font-semibold uppercase tracking-wide">Negociación</th>
                                                <th :colspan="monthWeeks.length" class="px-4 py-2 text-center font-semibold uppercase tracking-wide">Reprogramado</th>
                                                <th :colspan="monthWeeks.length" class="px-4 py-2 text-center font-semibold uppercase tracking-wide">Acuerdo aceptado</th>
                                            </tr>
                                            <tr>
                                                <template x-for="week in monthWeeks" :key="`negociacion-${week.week_key}`">
                                                    <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide" x-text="week.label"></th>
                                                </template>
                                                <template x-for="week in monthWeeks" :key="`reprogramado-${week.week_key}`">
                                                    <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide" x-text="week.label"></th>
                                                </template>
                                                <template x-for="week in monthWeeks" :key="`acuerdo-${week.week_key}`">
                                                    <th class="px-4 py-1 text-center font-semibold uppercase tracking-wide" x-text="week.label"></th>
                                                </template>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 bg-white">
                                            <template x-for="(executive, index) in executives" :key="`weekly-history-${executive.executive_user_id}`">
                                                <tr :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50/60'">
                                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-900" x-text="executive.executive_name"></td>
                                                    <template x-for="week in monthWeeks" :key="`negociacion-${executive.executive_user_id}-${week.week_key}`">
                                                        <td class="px-4 py-3 text-center">
                                                            <span
                                                                class="inline-flex min-w-[5.5rem] items-center justify-center rounded-xl border px-3 py-1 text-sm font-semibold"
                                                                :class="weeklyHistoryClass(executive, 'negociacion', week.week_key)"
                                                                x-text="weeklyHistoryValue(executive, 'negociacion', week.week_key, 'progress_label')"
                                                            ></span>
                                                        </td>
                                                    </template>
                                                    <template x-for="week in monthWeeks" :key="`reprogramado-${executive.executive_user_id}-${week.week_key}`">
                                                        <td class="px-4 py-3 text-center">
                                                            <span
                                                                class="inline-flex min-w-[5.5rem] items-center justify-center rounded-xl border px-3 py-1 text-sm font-semibold"
                                                                :class="weeklyHistoryClass(executive, 'reprogramado', week.week_key)"
                                                                x-text="weeklyHistoryValue(executive, 'reprogramado', week.week_key, 'progress_label')"
                                                            ></span>
                                                        </td>
                                                    </template>
                                                    <template x-for="week in monthWeeks" :key="`acuerdo-${executive.executive_user_id}-${week.week_key}`">
                                                        <td class="px-4 py-3 text-center">
                                                            <span
                                                                class="inline-flex min-w-[5.5rem] items-center justify-center rounded-xl border px-3 py-1 text-sm font-semibold"
                                                                :class="weeklyHistoryClass(executive, 'acuerdo_aceptado', week.week_key)"
                                                                x-text="weeklyHistoryValue(executive, 'acuerdo_aceptado', week.week_key, 'progress_label')"
                                                            ></span>
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
                                        <h3 class="text-base font-bold text-gray-900">Ejecutivos y estados</h3>
                                    </div>
                                    <div class="p-4">
                                        <div id="supervisorExecutiveStackedChart"></div>
                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white">
                                    <div class="border-b border-gray-100 px-4 py-3">
                                        <h3 class="text-base font-bold text-gray-900">Totales del equipo</h3>
                                    </div>
                                    <div class="p-4">
                                        <div id="supervisorTeamTotalsDonutChart"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="selectedExecutiveId !== 'all'" x-transition.opacity class="space-y-5" style="display: none;">
                            <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white">
                                <div class="border-b border-gray-100 px-4 py-3">
                                    <h3 class="text-base font-bold text-gray-900">Detalle del ejecutivo</h3>
                                </div>

                                <div class="p-4">
                                    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(300px,0.65fr)]">
                                        <div class="space-y-4">
                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Ejecutivo</div>
                                                <div class="mt-1 text-2xl font-bold text-slate-900" x-text="selectedExecutive ? selectedExecutive.executive_name : '-'"></div>
                                            </div>

                                            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                                <template x-for="status in statuses" :key="`detail-${status.key}`">
                                                    <button
                                                        type="button"
                                                        @click="selectedExecutive && openStatusModal(selectedExecutive.executive_user_id, status.key)"
                                                        class="rounded-2xl border px-3 py-2.5 text-left transition"
                                                        :class="[metricCardClass(status.tone), (selectedExecutive && Number(selectedExecutive[status.key] || 0) > 0) ? 'cursor-pointer hover:-translate-y-0.5 hover:shadow-sm' : 'cursor-default opacity-90']"
                                                    >
                                                        <div class="text-[11px] font-semibold uppercase tracking-[0.14em]" x-text="status.label"></div>
                                                        <div class="mt-1.5 text-[1.75rem] font-bold leading-none" x-text="selectedExecutive ? formatNumber(selectedExecutive[status.key] || 0) : '0'"></div>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>

                                        <div class="rounded-2xl border border-slate-200 bg-white px-3 py-3">
                                            <div id="supervisorExecutiveDetailDonutChart"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="statusModalOpen" x-transition.opacity style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4 py-6">
                    <div @click.outside="closeStatusModal()" class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-[28px] bg-white shadow-2xl">
                        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500" x-text="statusModal.executiveName || 'Ejecutivo'"></div>
                                <h3 class="mt-1 text-xl font-bold text-gray-900">
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
        function supervisorDashboard(payload) {
            return {
                payload,
                selectedExecutiveId: 'all',
                openSections: {
                    dailyCarryover: true,
                    weeklyCarryover: true,
                    monthlyFollowUp: true,
                },
                statusModalOpen: false,
                stackedChart: null,
                teamDonutChart: null,
                detailDonutChart: null,
                statusModalDate: '',
                statusModal: {
                    executiveName: '',
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
                sectionOpen(sectionKey) {
                    return Boolean(this.openSections?.[sectionKey]);
                },
                toggleSection(sectionKey) {
                    this.openSections = {
                        ...this.openSections,
                        [sectionKey]: !this.sectionOpen(sectionKey),
                    };
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
                get executives() {
                    return Array.isArray(this.payload.executives) ? this.payload.executives : [];
                },
                get statuses() {
                    return Array.isArray(this.payload.statuses) ? this.payload.statuses : [];
                },
                get monthWeeks() {
                    return Array.isArray(this.payload.month_weeks) ? this.payload.month_weeks : [];
                },
                get selectedExecutive() {
                    return this.executives.find((executive) => String(executive.executive_user_id) === String(this.selectedExecutiveId)) || null;
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
                carryoverValue(executive, statusKey, field, bucket = 'carryover') {
                    const metric = executive?.[bucket]?.[statusKey];

                    if (!metric || typeof metric !== 'object') {
                        return 0;
                    }

                    return Number(metric[field] || 0);
                },
                weeklyHistoryValue(executive, statusKey, weekKey, field) {
                    const metric = executive?.weekly_month_breakdown?.[statusKey]?.[weekKey];

                    if (!metric || typeof metric !== 'object') {
                        if (field === 'progress_label') {
                            return 'N/A';
                        }

                        return field === 'is_applicable' ? false : 0;
                    }

                    if (field === 'progress_label') {
                        return metric.progress_label || 'N/A';
                    }

                    if (field === 'met_goal' || field === 'is_applicable') {
                        return Boolean(metric[field]);
                    }

                    return Number(metric[field] || 0);
                },
                weeklyHistoryClass(executive, statusKey, weekKey) {
                    if (!this.weeklyHistoryValue(executive, statusKey, weekKey, 'is_applicable')) {
                        return 'border-slate-200 bg-white text-slate-400';
                    }

                    if (this.weeklyHistoryValue(executive, statusKey, weekKey, 'met_goal')) {
                        return '!border-emerald-400 !bg-emerald-600 !text-white';
                    }

                    return 'border-slate-200 bg-slate-50 text-slate-700';
                },
                usesGoalSemaphore(statusKey) {
                    return ['contactado', 'negociacion', 'acuerdo_aceptado'].includes(statusKey);
                },
                hasCompletedGoal(executive, statusKey) {
                    return Boolean(executive?.goal_completion?.[statusKey]);
                },
                goalCompletionClass(executive, statusKey, tone) {
                    if (this.hasCompletedGoal(executive, statusKey)) {
                        return '!border-emerald-400 !bg-emerald-600 !text-white hover:!border-emerald-500 hover:!bg-emerald-500';
                    }

                    return '!border-rose-400 !bg-rose-600 !text-white hover:!border-rose-500 hover:!bg-rose-500';
                },
                statusButtonClass(executive, statusKey, tone) {
                    if (this.usesGoalSemaphore(statusKey)) {
                        return this.goalCompletionClass(executive, statusKey, tone);
                    }

                    return this.badgeClass(tone);
                },
                openStatusModal(executiveId, statusKey) {
                    const executive = this.executives.find((item) => String(item.executive_user_id) === String(executiveId));

                    if (!executive) {
                        return;
                    }

                    const leads = executive.lead_details?.[statusKey] || [];

                    if (!Array.isArray(leads) || !leads.length) {
                        return;
                    }

                    this.statusModal = {
                        executiveName: executive.executive_name,
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
                    this.renderStackedChart();
                    this.renderTeamDonutChart();
                    this.renderDetailDonutChart();
                },
                renderStackedChart() {
                    const mountNode = document.querySelector('#supervisorExecutiveStackedChart');

                    if (!mountNode || !window.ApexCharts) {
                        return;
                    }

                    if (this.stackedChart) {
                        this.stackedChart.destroy();
                    }

                    const categories = this.executives.map((executive) => executive.executive_name);
                    const series = this.statuses.map((status) => ({
                        name: status.label,
                        data: this.executives.map((executive) => Number(executive[status.key] || 0)),
                    }));

                    this.stackedChart = new window.ApexCharts(mountNode, {
                        chart: {
                            type: 'bar',
                            height: Math.max(360, this.executives.length * 54),
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

                    this.stackedChart.render();
                },
                renderTeamDonutChart() {
                    const mountNode = document.querySelector('#supervisorTeamTotalsDonutChart');

                    if (!mountNode || !window.ApexCharts) {
                        return;
                    }

                    if (this.teamDonutChart) {
                        this.teamDonutChart.destroy();
                    }

                    const series = this.statuses.map((status) => {
                        return this.executives.reduce((sum, executive) => sum + Number(executive[status.key] || 0), 0);
                    });

                    this.teamDonutChart = new window.ApexCharts(mountNode, {
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
                                            label: 'Equipo',
                                            formatter: () => this.formatNumber(series.reduce((sum, value) => sum + Number(value || 0), 0)),
                                        },
                                    },
                                },
                            },
                        },
                    });

                    this.teamDonutChart.render();
                },
                renderDetailDonutChart() {
                    const mountNode = document.querySelector('#supervisorExecutiveDetailDonutChart');

                    if (!mountNode || !window.ApexCharts) {
                        return;
                    }

                    if (this.detailDonutChart) {
                        this.detailDonutChart.destroy();
                    }

                    if (!this.selectedExecutive || this.selectedExecutiveId === 'all') {
                        mountNode.innerHTML = '<div class="rounded-2xl border border-dashed border-gray-300 px-4 py-12 text-center text-sm text-gray-500">Selecciona un ejecutivo para ver su distribución.</div>';
                        return;
                    }

                    mountNode.innerHTML = '';

                    const series = this.statuses.map((status) => Number(this.selectedExecutive[status.key] || 0));

                    this.detailDonutChart = new window.ApexCharts(mountNode, {
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
                                            label: 'Total',
                                            formatter: () => this.formatNumber(this.selectedExecutive.total || 0),
                                        },
                                    },
                                },
                            },
                        },
                    });

                    this.detailDonutChart.render();
                },
            };
        }
    </script>
</x-app-layout>
