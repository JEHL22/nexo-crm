<x-app-layout>
    <div class="py-8">
        <div class="mx-auto max-w-[1800px] px-4 sm:px-6 lg:px-8 space-y-6">
            @if(($tableMissing ?? false) === true)
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    La tabla <span class="font-semibold">promo_documents</span> no existe todavía en esta base de datos.
                </div>
            @endif

            <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                <div class="crm-panel-hero border-b border-slate-200 px-6 py-7 text-white sm:px-8">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-3xl">
                            <p class="text-sm font-medium uppercase tracking-[0.22em] text-white/75">{{ $panelLabel ?? 'Panel Ejecutivo' }}</p>
                            <h1 class="mt-3 text-3xl font-semibold tracking-tight">Promociones PDF</h1>
                            <p class="mt-2 text-sm text-white/80 sm:text-base">
                                {{ $panelDescription ?? 'Consulta todas las promociones activas desde una sola pantalla y visualiza el PDF sin salir del módulo.' }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.18em] text-white/70">Activas</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $documents->count() }}</div>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.18em] text-white/70">Visor</div>
                                <div class="mt-2 text-sm font-semibold">{{ $selectedDocument ? 'Disponible' : 'Sin PDF' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="grid gap-6 p-6 sm:p-8 xl:grid-cols-[360px_minmax(0,1fr)]"
                    x-data="{
                        activeUrl: @js($selectedDocument ? asset($selectedDocument->pdf_path) : null),
                        activeTitle: @js($selectedDocument->title ?? 'Selecciona una promoción'),
                        setDocument(url, title) {
                            this.activeUrl = url;
                            this.activeTitle = title;
                        }
                    }"
                >
                    <div class="space-y-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Promociones disponibles</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Selecciona una promoción para ver el PDF en el panel derecho.
                            </p>
                        </div>

                        @if($documents->isEmpty())
                            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center text-sm text-slate-500">
                                No hay promociones PDF activas para mostrar.
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($documents as $document)
                                    @php
                                        $documentUrl = asset($document->pdf_path);
                                    @endphp
                                    <button
                                        type="button"
                                        @click="setDocument(@js($documentUrl), @js($document->title))"
                                        class="crm-promo-library-card w-full rounded-2xl border px-4 py-4 text-left shadow-sm transition"
                                        :class="activeUrl === @js($documentUrl) ? 'crm-promo-library-card--active' : ''"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="crm-promo-library-card__badge text-[10px] font-semibold uppercase tracking-[0.18em]">
                                                    {{ $document->badge ?: 'Promoción PDF' }}
                                                </div>
                                                <div class="crm-promo-library-card__title mt-1 text-base font-semibold leading-snug">
                                                    {{ $document->title }}
                                                </div>
                                            </div>

                                            <span class="crm-promo-library-card__icon inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" />
                                                </svg>
                                            </span>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        </div>

                    <div class="space-y-4">
                        <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                            <template x-if="activeUrl">
                                <iframe
                                    :src="`${activeUrl}#view=FitH`"
                                    :title="activeTitle"
                                    class="block h-[72vh] min-h-[620px] w-full bg-white"
                                    loading="eager"
                                ></iframe>
                            </template>

                            <template x-if="!activeUrl">
                                <div class="flex h-[72vh] min-h-[620px] items-center justify-center bg-slate-50 px-6 text-center text-sm text-slate-500">
                                    Selecciona una promoción para visualizar su PDF aquí.
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .crm-promo-library-card {
            border-color: color-mix(in srgb, var(--crm-secondary) 28%, #e2e8f0);
            background: linear-gradient(180deg, color-mix(in srgb, var(--crm-primary) 3%, #ffffff), #ffffff);
        }

        .crm-promo-library-card:hover,
        .crm-promo-library-card:focus,
        .crm-promo-library-card--active {
            border-color: color-mix(in srgb, var(--crm-secondary) 48%, var(--crm-primary));
            background: linear-gradient(135deg, color-mix(in srgb, var(--crm-primary) 7%, #ffffff), color-mix(in srgb, var(--crm-secondary) 10%, #ffffff));
        }

        .crm-promo-library-card__badge {
            color: color-mix(in srgb, var(--crm-secondary) 74%, var(--crm-primary));
        }

        .crm-promo-library-card__title {
            color: #0f172a;
        }

        .crm-promo-library-card__hint {
            color: #64748b;
        }

        .crm-promo-library-card__icon {
            border-color: color-mix(in srgb, var(--crm-secondary) 30%, #d8b4fe);
            color: color-mix(in srgb, var(--crm-secondary) 78%, var(--crm-primary));
            background: color-mix(in srgb, var(--crm-primary) 4%, #ffffff);
        }
    </style>
</x-app-layout>
