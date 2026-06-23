<x-app-layout>
    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ implode(' | ', $errors->all()) }}
                </div>
            @endif

            <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                <div class="crm-panel-hero px-6 py-8 sm:px-8">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-3xl">
                            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-white/70">Panel Promociones</p>
                            <h1 class="mt-3 text-3xl font-semibold tracking-tight">Nombres de promociones</h1>
                            <p class="mt-2 text-sm text-white/80 sm:text-base">
                                Administra el catálogo base de promociones que luego podremos reutilizar en selects y otros formularios del CRM.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.2em] text-white/60">Registros</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $totals['records'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $openCreatePromotion = $errors->has('name')
                        || $errors->has('monthly_price')
                        || filled(old('name'))
                        || filled(old('monthly_price'));
                @endphp

                <div x-data="{ createOpen: {{ $openCreatePromotion ? 'true' : 'false' }} }" class="space-y-4 p-5 sm:p-6">
                    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <button
                            type="button"
                            @click="createOpen = !createOpen"
                            class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left sm:px-5"
                        >
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">Nueva promoción</h2>
                                <p class="mt-1 text-sm text-slate-500">
                                    Registra una promoción con su costo para dejarla disponible en el catálogo general.
                                </p>
                            </div>

                            <span
                                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-slate-200 text-slate-500 transition"
                                :class="createOpen ? 'rotate-180' : ''"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </button>

                        <div x-show="createOpen" x-transition.opacity.duration.150ms style="display: none;" class="border-t border-slate-200 px-4 py-4 sm:px-5">
                            <div class="crm-soft-surface rounded-[26px] p-4">
                                <form method="POST" action="{{ route('promotion-admin.store') }}" class="space-y-3">
                                    @csrf

                                    <div class="grid gap-3 xl:grid-cols-[minmax(0,1.5fr)_210px_210px_auto] xl:items-end">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700">Nombre de la promoción</label>
                                            <input
                                                type="text"
                                                name="name"
                                                value="{{ old('name') }}"
                                                class="mt-2 w-full rounded-2xl border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900"
                                                maxlength="160"
                                                required
                                                placeholder="Ej. Internet Empresas Full 600 Mbps"
                                            >
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-slate-700">Costo mensual con IGV</label>
                                            <input
                                                type="number"
                                                name="monthly_price"
                                                value="{{ old('monthly_price') }}"
                                                min="0"
                                                step="0.01"
                                                class="mt-2 w-full rounded-2xl border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900"
                                                placeholder="Ej. 59.90"
                                                required
                                            >
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-slate-700">Orden</label>
                                            <input
                                                type="number"
                                                name="sort_order"
                                                value="{{ old('sort_order') }}"
                                                min="1"
                                                class="mt-2 w-full rounded-2xl border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900"
                                                placeholder="Automático"
                                            >
                                        </div>

                                        <button type="submit" class="crm-primary-button inline-flex w-full items-center justify-center rounded-2xl px-4 py-3 text-sm font-semibold transition xl:mb-[1px] xl:w-auto xl:min-w-[180px]">
                                            Crear promoción
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Promociones registradas</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                La edición se abre desde el ícono de lápiz. El orden siempre se mantiene consecutivo.
                            </p>
                        </div>

                        @forelse($promotionNames as $promotionName)
                            @once
                                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full border-collapse">
                                            <thead class="bg-slate-900 text-white">
                                                <tr class="text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-white/80">
                                                    <th class="px-4 py-3">Fecha</th>
                                                    <th class="px-4 py-3">Promoción</th>
                                                    <th class="px-4 py-3">Costo IGV</th>
                                                    <th class="px-4 py-3">Orden</th>
                                                    <th class="px-4 py-3">Estado</th>
                                                    <th class="px-4 py-3 text-right">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                            @endonce

                            <tr x-data="{ openEdit: false }" class="align-middle text-sm {{ $promotionName->is_active ? 'text-slate-700' : 'bg-slate-50/80 text-slate-500' }}">
                                <td class="whitespace-nowrap px-4 py-3">
                                    {{ optional($promotionName->created_at)->setTimezone(config('app.timezone'))->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-3 font-medium {{ $promotionName->is_active ? 'text-slate-900' : 'text-slate-500 line-through' }}">
                                    {{ $promotionName->name }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    S/ {{ number_format((float) $promotionName->monthly_price, 2) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    {{ $promotionName->sort_order }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $promotionName->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                        {{ $promotionName->is_active ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            @click="openEdit = true"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-50"
                                            title="Editar promoción"
                                            aria-label="Editar promoción"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.25 2.25 0 1 1 3.182 3.182L8.25 18.463 4 19.5l1.037-4.25L16.862 3.487Z"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <div
                                        x-show="openEdit"
                                        style="display: none;"
                                        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 px-4 py-6"
                                    >
                                        <div @click.outside="openEdit = false" class="w-full max-w-xl rounded-3xl bg-white shadow-2xl">
                                            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                                                <div>
                                                    <h3 class="text-lg font-semibold text-slate-900">Editar promoción</h3>
                                                    <p class="mt-1 text-sm text-slate-500">Ajusta nombre, costo y posición del registro.</p>
                                                </div>

                                                <button type="button" @click="openEdit = false" class="text-2xl font-bold leading-none text-slate-400 transition hover:text-red-500">&times;</button>
                                            </div>

                                            <form id="promotion-update-modal-{{ $promotionName->id }}" method="POST" action="{{ route('promotion-admin.update', $promotionName) }}" class="space-y-4 px-5 py-5">
                                                @csrf
                                                @method('PUT')

                                                <div>
                                                    <label class="block text-sm font-medium text-slate-700">Nombre de la promoción</label>
                                                    <input
                                                        type="text"
                                                        name="name"
                                                        value="{{ $promotionName->name }}"
                                                        class="mt-2 w-full rounded-2xl border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm focus:border-slate-900 focus:bg-white focus:ring-slate-900"
                                                        maxlength="160"
                                                        required
                                                    >
                                                </div>

                                                <div class="grid gap-4 sm:grid-cols-2">
                                                    <div>
                                                        <label class="block text-sm font-medium text-slate-700">Costo mensual con IGV</label>
                                                        <input
                                                            type="number"
                                                            name="monthly_price"
                                                            value="{{ number_format((float) $promotionName->monthly_price, 2, '.', '') }}"
                                                            min="0"
                                                            step="0.01"
                                                            class="mt-2 w-full rounded-2xl border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm focus:border-slate-900 focus:bg-white focus:ring-slate-900"
                                                            required
                                                        >
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium text-slate-700">Orden</label>
                                                        <input
                                                            type="number"
                                                            name="sort_order"
                                                            value="{{ $promotionName->sort_order }}"
                                                            min="1"
                                                            class="mt-2 w-full rounded-2xl border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm focus:border-slate-900 focus:bg-white focus:ring-slate-900"
                                                            required
                                                        >
                                                    </div>
                                                </div>

                                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-500">
                                                    Creada por {{ $promotionName->sender?->name ?? 'Sistema' }}
                                                    el {{ optional($promotionName->created_at)->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                                </div>

                                                <div class="flex items-center justify-between gap-3 pt-2">
                                                    <button type="submit" form="promotion-delete-modal-{{ $promotionName->id }}" class="inline-flex items-center justify-center rounded-2xl border {{ $promotionName->is_active ? 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100' : 'border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100' }} px-4 py-3 text-sm font-semibold transition">
                                                        {{ $promotionName->is_active ? 'Deshabilitar' : 'Reactivar' }}
                                                    </button>

                                                    <div class="flex items-center gap-3">
                                                        <button type="button" @click="openEdit = false" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                                            Cancelar
                                                        </button>

                                                        <button type="submit" class="crm-primary-button inline-flex items-center justify-center rounded-2xl px-4 py-3 text-sm font-semibold transition">
                                                            Guardar cambios
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>

                                            <form id="promotion-delete-modal-{{ $promotionName->id }}" method="POST" action="{{ route('promotion-admin.destroy', $promotionName) }}" onsubmit="return confirm('{{ $promotionName->is_active ? '¿Deshabilitar esta promoción del catálogo?' : '¿Reactivar esta promoción en el catálogo?' }}');" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            @if($loop->last)
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
                                <h3 class="text-lg font-semibold text-slate-900">Aún no hay nombres de promociones</h3>
                                <p class="mt-2 text-sm text-slate-500">
                                    Crea el primer registro y quedará listo para usarlo después en el select que me indiques.
                                </p>
                            </div>
                        @endforelse

                        @if ($promotionNames->hasPages())
                            <div class="pt-2">
                                {{ $promotionNames->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
