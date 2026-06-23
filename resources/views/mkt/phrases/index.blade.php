<x-app-layout>
    <style>
        body.crm-dark-theme .mkt-page-shell {
            color: #e5eefb;
        }

        body.crm-dark-theme .mkt-page-panel {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.96), rgba(11, 19, 36, 0.98));
            border: 1px solid rgba(71, 85, 105, 0.35);
            box-shadow: 0 28px 60px -42px rgba(2, 6, 23, 0.95);
        }

        body.crm-dark-theme .mkt-page-hero {
            border-bottom-color: rgba(71, 85, 105, 0.3);
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.98), rgba(22, 33, 62, 0.96), rgba(88, 28, 135, 0.35));
        }

        body.crm-dark-theme .mkt-page-hero-label,
        body.crm-dark-theme .mkt-card-label {
            color: #8fdcff !important;
        }

        body.crm-dark-theme .mkt-page-hero-title,
        body.crm-dark-theme .mkt-block-title,
        body.crm-dark-theme .mkt-phrase-text,
        body.crm-dark-theme .mkt-modal-title {
            color: #f8fbff !important;
        }

        body.crm-dark-theme .mkt-page-hero-copy,
        body.crm-dark-theme .mkt-block-copy,
        body.crm-dark-theme .mkt-meta-text {
            color: #9fb4cf !important;
        }

        body.crm-dark-theme .mkt-stat-card,
        body.crm-dark-theme .mkt-block-card,
        body.crm-dark-theme .mkt-phrase-card,
        body.crm-dark-theme .mkt-modal-card {
            background: rgba(15, 23, 42, 0.92) !important;
            border-color: rgba(71, 85, 105, 0.38) !important;
            color: #e5eefb !important;
            box-shadow: 0 22px 44px -38px rgba(2, 6, 23, 0.9);
        }

        body.crm-dark-theme .mkt-stat-value {
            color: #f8fbff !important;
        }

        body.crm-dark-theme .mkt-field-label {
            color: #dbe7f5 !important;
        }

        body.crm-dark-theme .mkt-text-input,
        body.crm-dark-theme .mkt-select-input,
        body.crm-dark-theme .mkt-textarea-input {
            background: rgba(10, 15, 28, 0.95) !important;
            border-color: rgba(71, 85, 105, 0.5) !important;
            color: #f8fbff !important;
        }

        body.crm-dark-theme .mkt-text-input::placeholder,
        body.crm-dark-theme .mkt-textarea-input::placeholder {
            color: #6f88a8 !important;
        }

        body.crm-dark-theme .mkt-toggle-card,
        body.crm-dark-theme .mkt-publish-card {
            background: rgba(10, 18, 34, 0.96) !important;
            border-color: rgba(14, 165, 233, 0.35) !important;
        }

        body.crm-dark-theme .mkt-chip-neutral {
            background: rgba(30, 41, 59, 0.95) !important;
            color: #c9d7ea !important;
        }

        body.crm-dark-theme .mkt-chip-active {
            background: rgba(20, 83, 45, 0.9) !important;
            color: #dcfce7 !important;
        }

        body.crm-dark-theme .mkt-primary-button {
            background: linear-gradient(135deg, var(--crm-primary, #22c7f2), var(--crm-secondary, #d946ef)) !important;
            border-color: transparent !important;
            color: #fff !important;
        }

        body.crm-dark-theme .mkt-primary-button:hover {
            filter: brightness(1.05);
        }

        body.crm-dark-theme .mkt-secondary-button {
            background: rgba(15, 23, 42, 0.92) !important;
            border-color: rgba(71, 85, 105, 0.45) !important;
            color: #f8fbff !important;
        }

        body.crm-dark-theme .mkt-accent-button {
            background: rgba(10, 18, 34, 0.96) !important;
            border-color: color-mix(in srgb, var(--crm-primary, #22c7f2) 45%, transparent) !important;
            color: color-mix(in srgb, var(--crm-primary, #22c7f2) 72%, white) !important;
        }

        body.crm-dark-theme .mkt-empty-state {
            border-color: rgba(71, 85, 105, 0.5) !important;
            color: #9fb4cf !important;
            background: rgba(15, 23, 42, 0.55) !important;
        }

        body.crm-dark-theme .mkt-table-shell {
            border-color: rgba(71, 85, 105, 0.38) !important;
            background: rgba(15, 23, 42, 0.92) !important;
        }

        body.crm-dark-theme .mkt-table-head {
            background: rgba(8, 15, 28, 0.96) !important;
        }

        body.crm-dark-theme .mkt-table-row {
            border-color: rgba(71, 85, 105, 0.26) !important;
            color: #dbe7f5 !important;
        }

        body.crm-dark-theme .mkt-table-row td {
            color: inherit !important;
        }

        body.crm-dark-theme .mkt-table-chip {
            background: rgba(30, 41, 59, 0.95) !important;
            border-color: rgba(71, 85, 105, 0.34) !important;
            color: #dbe7f5 !important;
        }
    </style>
    @php
        $openCreatePhrase = $errors->any()
            || filled(old('title'))
            || filled(old('phrase'))
            || filled(old('scheduled_for'));
    @endphp
    <div class="py-8">
        <div
            class="mkt-page-shell mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8"
            x-data="{
                createOpen: @js($openCreatePhrase),
                deliveryMode: @js(old('delivery_mode', 'immediate')),
                publishActive: @js((bool) old('is_active', true)),
            }"
        >
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

            <div class="mkt-page-panel overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm ring-1 ring-black/5">
                <div class="mkt-page-hero border-b border-cyan-100 bg-gradient-to-r from-slate-900 via-cyan-900 to-fuchsia-700 px-6 py-8 text-white sm:px-8">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-3xl">
                            <p class="mkt-page-hero-label text-sm font-semibold uppercase tracking-[0.25em] text-white/75">Panel MKT</p>
                            <h1 class="mkt-page-hero-title mt-3 text-3xl font-semibold tracking-tight text-white">Frases motivadoras</h1>
                            <p class="mkt-page-hero-copy mt-2 text-sm text-white/80 sm:text-base">
                                Publica mensajes cortos para el popup superior del CRM y administra el historial desde una sola tabla operativa.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="mkt-stat-card rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.2em] text-white/60">Frases</div>
                                <div class="mt-2 text-2xl font-semibold text-white">{{ $phrases->count() }}</div>
                            </div>
                            <div class="mkt-stat-card rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.2em] text-white/60">Activas</div>
                                <div class="mt-2 text-2xl font-semibold text-white">{{ $phrases->where('is_active', true)->count() }}</div>
                            </div>
                            <div class="mkt-stat-card rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.2em] text-white/60">Última</div>
                                <div class="mt-2 text-sm font-bold text-white">{{ $phrases->first()?->created_at?->setTimezone(config('app.timezone'))?->format('d/m H:i') ?: '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 p-5 sm:p-6">
                    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">Nueva frase</h2>
                                <p class="mt-1 text-sm text-slate-500">
                                    Publica una frase ahora, prográmala o déjala lista para el primer ingreso del día.
                                </p>
                            </div>

                            <span
                                role="button"
                                tabindex="0"
                                @click="createOpen = !createOpen"
                                @keydown.enter.prevent="createOpen = !createOpen"
                                @keydown.space.prevent="createOpen = !createOpen"
                                class="inline-flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-full border border-slate-200 text-slate-500 transition"
                                :class="createOpen ? 'rotate-180' : ''"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </div>

                        <div x-show="createOpen" x-transition.opacity.duration.150ms style="display: none;" class="border-t border-slate-200 px-4 py-4 sm:px-5">
                            <div class="crm-soft-surface rounded-[26px] p-4 sm:p-5">
                                <form method="POST" action="{{ route('mkt.phrases.store') }}" class="space-y-5">
                                    @csrf

                                    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.1fr)_320px]">
                                        <div class="mkt-block-card rounded-3xl border border-gray-200 bg-gray-50 p-5">
                                            <h3 class="mkt-block-title text-lg font-bold text-gray-900">Contenido</h3>
                                            <p class="mkt-block-copy mt-1 text-sm text-gray-500">Una frase breve y clara funciona mejor en el popup superior del CRM.</p>

                                            <div class="mt-4 grid gap-4">
                                                <div>
                                                    <label class="mkt-field-label block text-sm font-medium text-gray-700">Título</label>
                                                    <input type="text" name="title" value="{{ old('title') }}" class="mkt-text-input mt-1 block w-full rounded-xl border-gray-300 text-sm focus:border-cyan-400 focus:ring-cyan-400" maxlength="120" placeholder="Ej: Impulso del día">
                                                </div>

                                                <div>
                                                    <label class="mkt-field-label block text-sm font-medium text-gray-700">Frase</label>
                                                    <textarea name="phrase" rows="5" class="mkt-textarea-input mt-1 block w-full rounded-xl border-gray-300 text-sm focus:border-cyan-400 focus:ring-cyan-400" maxlength="255" required placeholder="Escribe una frase breve y potente...">{{ old('phrase') }}</textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-5">
                                            <div class="mkt-block-card rounded-3xl border border-gray-200 bg-white p-5">
                                                <h3 class="mkt-block-title text-lg font-bold text-gray-900">Publicación</h3>
                                                <p class="mkt-block-copy mt-1 text-sm text-gray-500">Solo una frase puede quedar activa al mismo tiempo.</p>

                                                <label class="mkt-toggle-card mt-4 flex items-center gap-3 rounded-2xl border border-cyan-100 bg-white px-4 py-3">
                                                    <input type="checkbox" name="is_active" value="1" x-model="publishActive" class="rounded border-gray-300 text-cyan-600 focus:ring-cyan-500" @checked(old('is_active', true))>
                                                    <span class="mkt-field-label text-sm text-gray-700">Dejar esta frase activa para su publicación</span>
                                                </label>

                                                <div x-show="publishActive" x-transition class="mkt-publish-card mt-4 space-y-4 rounded-2xl border border-cyan-100 bg-cyan-50/60 p-4">
                                                    <div>
                                                        <label class="mkt-field-label block text-sm font-medium text-gray-700">Modo de publicación</label>
                                                        <select name="delivery_mode" x-model="deliveryMode" class="mkt-select-input mt-1 block w-full rounded-xl border-gray-300 text-sm focus:border-cyan-400 focus:ring-cyan-400" :required="publishActive">
                                                            <option value="immediate">Publicar ahora</option>
                                                            <option value="scheduled">Programar fecha y hora</option>
                                                            <option value="daily_login">Primer ingreso del día</option>
                                                        </select>
                                                    </div>

                                                    <div x-show="deliveryMode === 'scheduled'">
                                                        <label class="mkt-field-label block text-sm font-medium text-gray-700">Fecha y hora exacta</label>
                                                        <input type="datetime-local" name="scheduled_for" value="{{ old('scheduled_for') }}" class="mkt-text-input mt-1 block w-full rounded-xl border-gray-300 text-sm focus:border-cyan-400 focus:ring-cyan-400" :required="publishActive && deliveryMode === 'scheduled'">
                                                    </div>
                                                </div>

                                                <div class="mt-4 flex justify-end">
                                                    <button type="submit" class="mkt-primary-button inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                                                        Guardar frase
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Historial de frases</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Consulta el historial operativo, su modo de publicación y vuelve a lanzar o configurar cada frase desde la tabla.
                            </p>
                        </div>

                        @if($phrases->isNotEmpty())
                            <div class="mkt-table-shell overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full border-collapse">
                                        <thead class="mkt-table-head bg-slate-900 text-white">
                                            <tr class="text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-white/80">
                                                <th class="px-3 py-2.5">Fecha</th>
                                                <th class="px-3 py-2.5">Título</th>
                                                <th class="px-3 py-2.5">Frase</th>
                                                <th class="px-3 py-2.5">Publicación</th>
                                                <th class="px-3 py-2.5">Inicio</th>
                                                <th class="px-3 py-2.5">Estado</th>
                                                <th class="px-3 py-2.5">Configurar</th>
                                                <th class="px-3 py-2.5">Volver a lanzar</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($phrases as $phrase)
                                                @php
                                                    $phraseConfig = [
                                                        'action' => route('mkt.phrases.update', $phrase),
                                                        'title' => $phrase->title ?: 'Impulso del día',
                                                        'delivery_mode' => $phrase->delivery_mode ?: 'immediate',
                                                        'is_active' => (bool) $phrase->is_active,
                                                        'scheduled_for' => $phrase->starts_at?->setTimezone(config('app.timezone'))?->format('Y-m-d\TH:i'),
                                                    ];
                                                @endphp
                                                <tr class="mkt-table-row align-top text-[13px] text-slate-700">
                                                    <td class="whitespace-nowrap px-3 py-2.5">
                                                        {{ optional($phrase->created_at)->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-2.5 font-medium text-slate-900">
                                                        {{ $phrase->title ?: 'Impulso del día' }}
                                                    </td>
                                                    <td class="px-3 py-2.5">
                                                        <div class="max-w-[280px] truncate">{{ $phrase->phrase }}</div>
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-2.5">
                                                        <span class="mkt-table-chip inline-flex items-center rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-semibold text-slate-700">
                                                            {{ match ($phrase->delivery_mode) {
                                                                'scheduled' => 'Programada',
                                                                'daily_login' => 'Primer ingreso',
                                                                default => 'Inmediata',
                                                            } }}
                                                        </span>
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-2.5">
                                                        {{ $phrase->starts_at?->setTimezone(config('app.timezone'))->format('d/m/Y H:i') ?: '-' }}
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-2.5">
                                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold {{ $phrase->is_active ? 'border-emerald-200 bg-emerald-100 text-emerald-700' : 'border-slate-200 bg-slate-100 text-slate-600' }}">
                                                            {{ $phrase->is_active ? 'Activa' : 'Guardada' }}
                                                        </span>
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-2.5">
                                                        <button
                                                            type="button"
                                                            data-open-phrase-config
                                                            data-config='@json($phraseConfig)'
                                                            onclick="window.openMarketingPhraseConfig(this.dataset.config)"
                                                            class="mkt-secondary-button inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                                        >
                                                            Configurar
                                                        </button>
                                                    </td>
                                                    <td class="whitespace-nowrap px-3 py-2.5">
                                                        <form method="POST" action="{{ route('mkt.phrases.toggle', $phrase) }}">
                                                            @csrf
                                                            <button type="submit" class="mkt-accent-button inline-flex items-center justify-center rounded-xl border border-cyan-200 bg-white px-3 py-1.5 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-50">
                                                                {{ $phrase->is_active ? 'Volver a lanzar' : 'Re-publicar ahora' }}
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <div class="mkt-empty-state rounded-2xl border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-500">
                                Aún no hay frases motivadoras registradas.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div
            id="mktPhraseConfigModal"
            class="fixed inset-0 z-[70] hidden items-center justify-center p-4"
        >
            <div class="absolute inset-0 bg-slate-950/40" data-close-phrase-config onclick="window.closeMarketingPhraseConfig()"></div>

            <div class="mkt-modal-card relative w-full max-w-xl rounded-[30px] bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="mkt-card-label text-sm font-semibold uppercase tracking-[0.2em] text-cyan-600">Configurar frase</div>
                        <h2 id="mktPhraseConfigTitle" class="mkt-modal-title mt-2 text-2xl font-bold text-gray-900"></h2>
                    </div>

                    <button
                        type="button"
                        data-close-phrase-config
                        onclick="window.closeMarketingPhraseConfig()"
                        class="mkt-secondary-button inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-300 text-gray-600 transition hover:bg-gray-50"
                    >
                        ✕
                    </button>
                </div>

                <form id="mktPhraseConfigForm" method="POST" class="mt-5 space-y-4">
                    @csrf
                    @method('PUT')

                    <label class="mkt-toggle-card flex items-center gap-3 rounded-2xl border border-cyan-100 bg-white px-4 py-3">
                        <input id="mktPhraseConfigActive" type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-cyan-600 focus:ring-cyan-500">
                        <span class="mkt-field-label text-sm text-gray-700">Dejar esta frase activa para su publicación</span>
                    </label>

                    <div id="mktPhraseConfigPublishBlock" class="mkt-publish-card space-y-4 rounded-2xl border border-cyan-100 bg-cyan-50/60 p-4">
                        <div>
                            <label class="mkt-field-label block text-sm font-medium text-gray-700">Modo de publicación</label>
                            <select id="mktPhraseConfigDeliveryMode" name="delivery_mode" class="mkt-select-input mt-1 block w-full rounded-xl border-gray-300 text-sm focus:border-cyan-400 focus:ring-cyan-400">
                                <option value="immediate">Publicar ahora</option>
                                <option value="scheduled">Programar fecha y hora</option>
                                <option value="daily_login">Primer ingreso del día</option>
                            </select>
                        </div>

                        <div id="mktPhraseConfigScheduledWrap">
                            <label class="mkt-field-label block text-sm font-medium text-gray-700">Fecha y hora exacta</label>
                            <input id="mktPhraseConfigScheduledFor" type="datetime-local" name="scheduled_for" class="mkt-text-input mt-1 block w-full rounded-xl border-gray-300 text-sm focus:border-cyan-400 focus:ring-cyan-400">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            data-close-phrase-config
                            onclick="window.closeMarketingPhraseConfig()"
                            class="mkt-secondary-button inline-flex items-center justify-center rounded-2xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                        >
                            Cancelar
                        </button>
                        <button type="submit" class="mkt-primary-button inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Guardar configuración
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modal = document.getElementById('mktPhraseConfigModal');
                const form = document.getElementById('mktPhraseConfigForm');
                const titleNode = document.getElementById('mktPhraseConfigTitle');
                const activeInput = document.getElementById('mktPhraseConfigActive');
                const publishBlock = document.getElementById('mktPhraseConfigPublishBlock');
                const deliveryModeInput = document.getElementById('mktPhraseConfigDeliveryMode');
                const scheduledWrap = document.getElementById('mktPhraseConfigScheduledWrap');
                const scheduledForInput = document.getElementById('mktPhraseConfigScheduledFor');

                if (!modal || !form || !titleNode || !activeInput || !publishBlock || !deliveryModeInput || !scheduledWrap || !scheduledForInput) {
                    return;
                }

                function syncConfigVisibility() {
                    const publishActive = activeInput.checked;
                    publishBlock.classList.toggle('hidden', !publishActive);
                    deliveryModeInput.required = publishActive;

                    const isScheduled = publishActive && deliveryModeInput.value === 'scheduled';
                    scheduledWrap.classList.toggle('hidden', !isScheduled);
                    scheduledForInput.required = isScheduled;
                }

                function closeConfigModal() {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }

                function openConfigModal(config) {
                    form.action = config.action || '';
                    titleNode.textContent = config.title || 'Impulso del dia';
                    activeInput.checked = Boolean(config.is_active);
                    deliveryModeInput.value = config.delivery_mode || 'immediate';
                    scheduledForInput.value = config.scheduled_for || '';
                    syncConfigVisibility();
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }

                window.openMarketingPhraseConfig = (rawConfig) => {
                    try {
                        const config = typeof rawConfig === 'string'
                            ? JSON.parse(rawConfig || '{}')
                            : (rawConfig || {});
                        openConfigModal(config);
                    } catch (error) {
                        console.error('No se pudo abrir la configuracion de la frase.', error);
                    }
                };

                window.closeMarketingPhraseConfig = closeConfigModal;

                activeInput.addEventListener('change', syncConfigVisibility);
                deliveryModeInput.addEventListener('change', syncConfigVisibility);
                syncConfigVisibility();
            });
        </script>
    </div>
</x-app-layout>
