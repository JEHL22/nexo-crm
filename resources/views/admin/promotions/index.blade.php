<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @php
                $isPromotionAdminPanel = request()->routeIs('promotion-admin.documents.*');
                $promotionDocumentIndexRoute = $isPromotionAdminPanel ? route('promotion-admin.documents.index') : route('admin.promotions.index');
            @endphp

            @if(session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ implode(' | ', $errors->all()) }}
                </div>
            @endif

            @if(($tableMissing ?? false) === true)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    La tabla <span class="font-semibold">promo_documents</span> no existe en esta base de datos. Ejecuta las migraciones antes de usar este módulo.
                </div>
            @endif

            <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-800 px-6 py-8 text-white sm:px-8">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-3xl">
                            <p class="text-sm font-medium uppercase tracking-[0.22em] text-slate-300">Panel admin</p>
                            <h1 class="mt-3 text-3xl font-semibold tracking-tight">Promociones PDF</h1>
                            <p class="mt-2 text-sm text-slate-300 sm:text-base">
                                Administra los botones de promociones que verá el ejecutivo en el módulo A negociar.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Total</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $totals['documents'] }}</div>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Activas</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $totals['active'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 p-6 sm:p-8 xl:grid-cols-[360px_minmax(0,1fr)]">
                    <div class="rounded-3xl border border-slate-200 bg-slate-50/80 p-5">
                        <div class="mb-5">
                            <h2 class="text-lg font-semibold text-slate-900">Nueva promoción</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Crea un botón nuevo con su PDF para que aparezca en A negociar.
                            </p>
                        </div>

                        <form method="POST" action="{{ $promotionDocumentIndexRoute }}" enctype="multipart/form-data" class="space-y-4">
                            @csrf

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Etiqueta corta</label>
                                <input type="text" name="badge" class="mt-2 w-full rounded-2xl border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900" maxlength="60" placeholder="Ej. Promo 1 Sol">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Título del botón</label>
                                <input type="text" name="title" class="mt-2 w-full rounded-2xl border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900" maxlength="160" required placeholder="Ej. Internet 1 Play 400 Mbps">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">PDF</label>
                                <input type="file" name="pdf_file" accept="application/pdf" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Orden</label>
                                <input type="number" name="sort_order" min="0" value="0" class="mt-2 w-full rounded-2xl border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900">
                            </div>

                            <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-slate-900 focus:ring-slate-900" checked>
                                <span class="text-sm text-slate-700">Mostrar esta promoción en A negociar</span>
                            </label>

                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                                Crear promoción
                            </button>
                        </form>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Promociones registradas</h2>
                            <p class="mt-1 text-sm text-slate-500">La edición se abre desde el ícono de lápiz. El PDF puede previsualizarse desde el mismo modal.</p>
                        </div>

                        @forelse($documents as $document)
                            @once
                                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full border-collapse">
                                            <thead class="bg-slate-900 text-white">
                                                <tr class="text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-white/80">
                                                    <th class="px-4 py-3">Fecha</th>
                                                    <th class="px-4 py-3">Etiqueta</th>
                                                    <th class="px-4 py-3">Título</th>
                                                    <th class="px-4 py-3">Archivo PDF</th>
                                                    <th class="px-4 py-3">Orden</th>
                                                    <th class="px-4 py-3">Estado</th>
                                                    <th class="px-4 py-3 text-right">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100">
                            @endonce

                            <tr x-data="{ openEdit: false }" class="align-middle text-sm text-slate-700">
                                <td class="whitespace-nowrap px-4 py-3">
                                    {{ optional($document->created_at)->setTimezone(config('app.timezone'))->format('d/m/Y') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    {{ $document->badge ?: '-' }}
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ $document->title }}
                                </td>
                                <td class="max-w-[240px] px-4 py-3 text-slate-500">
                                    <span class="block truncate" title="{{ basename($document->pdf_path) }}">
                                        {{ basename($document->pdf_path) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    {{ $document->sort_order ?? 0 }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $document->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                        {{ $document->is_active ? 'Activa' : 'Oculta' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            @click="openEdit = true"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-300 bg-white text-slate-700 transition hover:bg-slate-50"
                                            title="Editar promoción PDF"
                                            aria-label="Editar promoción PDF"
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
                                        <div @click.outside="openEdit = false" class="w-full max-w-2xl rounded-3xl bg-white shadow-2xl">
                                            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                                                <div>
                                                    <h3 class="text-lg font-semibold text-slate-900">Editar promoción PDF</h3>
                                                    <p class="mt-1 text-sm text-slate-500">Ajusta etiqueta, título, orden, estado y reemplaza el archivo si hace falta.</p>
                                                </div>

                                                <button type="button" @click="openEdit = false" class="text-2xl font-bold leading-none text-slate-400 transition hover:text-red-500">&times;</button>
                                            </div>

                                            <form method="POST" action="{{ $isPromotionAdminPanel ? route('promotion-admin.documents.update', $document) : route('admin.promotions.update', $document) }}" enctype="multipart/form-data" class="space-y-4 px-5 py-5">
                                                @csrf
                                                @method('PUT')

                                                <div class="grid gap-4 sm:grid-cols-2">
                                                    <div>
                                                        <label class="block text-sm font-medium text-slate-700">Etiqueta corta</label>
                                                        <input type="text" name="badge" value="{{ $document->badge }}" class="mt-2 w-full rounded-2xl border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm focus:border-slate-900 focus:bg-white focus:ring-slate-900" maxlength="60">
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium text-slate-700">Orden</label>
                                                        <input type="number" name="sort_order" min="0" value="{{ $document->sort_order ?? 0 }}" class="mt-2 w-full rounded-2xl border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm focus:border-slate-900 focus:bg-white focus:ring-slate-900">
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-slate-700">Título del botón</label>
                                                    <input type="text" name="title" value="{{ $document->title }}" class="mt-2 w-full rounded-2xl border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm focus:border-slate-900 focus:bg-white focus:ring-slate-900" maxlength="160" required>
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-slate-700">Reemplazar PDF</label>
                                                    <input type="file" name="pdf_file" accept="application/pdf" class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700">
                                                </div>

                                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-500">
                                                    Archivo actual: {{ basename($document->pdf_path) }}
                                                    <br>
                                                    Creada por {{ $document->sender?->name ?? 'Sistema' }}
                                                    el {{ optional($document->created_at)->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                                </div>

                                                <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                                                    <label class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                                        <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-slate-900 focus:ring-slate-900" {{ $document->is_active ? 'checked' : '' }}>
                                                        <span class="text-sm text-slate-700">Visible para el ejecutivo</span>
                                                    </label>

                                                    <div class="flex items-center gap-3">
                                                        <button
                                                            type="button"
                                                            data-pdf-popup-url="{{ asset($document->pdf_path) }}"
                                                            data-pdf-popup-title="{{ $document->title }}"
                                                            class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                                        >
                                                            Ver PDF
                                                        </button>

                                                        <button type="button" @click="openEdit = false" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                                            Cancelar
                                                        </button>

                                                        <button type="submit" class="crm-primary-button inline-flex items-center justify-center rounded-2xl px-4 py-3 text-sm font-semibold transition">
                                                            Guardar cambios
                                                        </button>
                                                    </div>
                                                </div>
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
                                <h3 class="text-lg font-semibold text-slate-900">Aún no hay promociones PDF</h3>
                                <p class="mt-2 text-sm text-slate-500">
                                    Crea la primera promoción desde el panel lateral y aparecerá como botón en A negociar.
                                </p>
                            </div>
                        @endforelse

                        @if ($documents->hasPages())
                            <div class="pt-2">
                                {{ $documents->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
