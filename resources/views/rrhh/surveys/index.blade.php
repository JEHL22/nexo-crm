<x-app-layout>
    <style>
        body.crm-dark-theme .rrhh-page-shell {
            color: #e5eefb;
        }

        body.crm-dark-theme .rrhh-page-panel {
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.96), rgba(11, 19, 36, 0.98));
            border: 1px solid rgba(71, 85, 105, 0.35);
            box-shadow: 0 28px 60px -42px rgba(2, 6, 23, 0.95);
        }

        body.crm-dark-theme .rrhh-page-hero {
            border-bottom-color: rgba(71, 85, 105, 0.3);
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.98), rgba(22, 33, 62, 0.96), rgba(190, 24, 93, 0.25));
        }

        body.crm-dark-theme .rrhh-page-hero-label,
        body.crm-dark-theme .rrhh-card-label {
            color: #ff8fb6 !important;
        }

        body.crm-dark-theme .rrhh-page-hero-title,
        body.crm-dark-theme .rrhh-block-title,
        body.crm-dark-theme .rrhh-survey-title,
        body.crm-dark-theme .rrhh-modal-title {
            color: #f8fbff !important;
        }

        body.crm-dark-theme .rrhh-page-hero-copy,
        body.crm-dark-theme .rrhh-block-copy,
        body.crm-dark-theme .rrhh-meta-text {
            color: #9fb4cf !important;
        }

        body.crm-dark-theme .rrhh-stat-card,
        body.crm-dark-theme .rrhh-block-card,
        body.crm-dark-theme .rrhh-survey-card,
        body.crm-dark-theme .rrhh-modal-card,
        body.crm-dark-theme .rrhh-recipient-card {
            background: rgba(15, 23, 42, 0.92) !important;
            border-color: rgba(71, 85, 105, 0.38) !important;
            color: #e5eefb !important;
            box-shadow: 0 22px 44px -38px rgba(2, 6, 23, 0.9);
        }

        body.crm-dark-theme .rrhh-stat-value {
            color: #f8fbff !important;
        }

        body.crm-dark-theme .rrhh-field-label {
            color: #dbe7f5 !important;
        }

        body.crm-dark-theme .rrhh-text-input,
        body.crm-dark-theme .rrhh-select-input,
        body.crm-dark-theme .rrhh-textarea-input {
            background: rgba(10, 15, 28, 0.95) !important;
            border-color: rgba(71, 85, 105, 0.5) !important;
            color: #f8fbff !important;
        }

        body.crm-dark-theme .rrhh-text-input::placeholder,
        body.crm-dark-theme .rrhh-textarea-input::placeholder {
            color: #6f88a8 !important;
        }

        body.crm-dark-theme .rrhh-soft-card,
        body.crm-dark-theme .rrhh-summary-card {
            background: rgba(10, 18, 34, 0.96) !important;
            border-color: rgba(244, 114, 182, 0.3) !important;
        }

        body.crm-dark-theme .rrhh-recipient-option {
            background: rgba(10, 18, 34, 0.96) !important;
            border-color: rgba(71, 85, 105, 0.42) !important;
        }

        body.crm-dark-theme .rrhh-chip-neutral {
            background: rgba(30, 41, 59, 0.95) !important;
            color: #c9d7ea !important;
            border-color: rgba(71, 85, 105, 0.34) !important;
        }

        body.crm-dark-theme .rrhh-chip-active {
            background: rgba(20, 83, 45, 0.88) !important;
            color: #dcfce7 !important;
        }

        body.crm-dark-theme .rrhh-chip-pending {
            background: rgba(146, 64, 14, 0.85) !important;
            color: #fde68a !important;
        }

        body.crm-dark-theme .rrhh-primary-button {
            background: linear-gradient(135deg, #f43f5e, #ec4899) !important;
            border-color: transparent !important;
            color: #fff !important;
        }

        body.crm-dark-theme .rrhh-primary-button:hover {
            filter: brightness(1.05);
        }

        body.crm-dark-theme .rrhh-secondary-button {
            background: rgba(15, 23, 42, 0.92) !important;
            border-color: rgba(71, 85, 105, 0.45) !important;
            color: #f8fbff !important;
        }

        body.crm-dark-theme .rrhh-accent-button {
            background: rgba(10, 18, 34, 0.96) !important;
            border-color: rgba(244, 114, 182, 0.4) !important;
            color: #fda4af !important;
        }

        body.crm-dark-theme .rrhh-chart-button {
            background: rgba(10, 18, 34, 0.96) !important;
            border-color: rgba(56, 189, 248, 0.35) !important;
            color: #7dd3fc !important;
        }

        body.crm-dark-theme .rrhh-empty-state {
            border-color: rgba(71, 85, 105, 0.5) !important;
            color: #9fb4cf !important;
            background: rgba(15, 23, 42, 0.55) !important;
        }

        body.crm-dark-theme .rrhh-table-shell {
            border-color: rgba(71, 85, 105, 0.38) !important;
            background: rgba(15, 23, 42, 0.92) !important;
        }

        body.crm-dark-theme .rrhh-table-head {
            background: rgba(8, 15, 28, 0.96) !important;
        }

        body.crm-dark-theme .rrhh-table-row {
            border-color: rgba(71, 85, 105, 0.26) !important;
            color: #dbe7f5 !important;
        }

        body.crm-dark-theme .rrhh-table-row td {
            color: inherit !important;
        }

        body.crm-dark-theme .rrhh-table-chip {
            background: rgba(30, 41, 59, 0.95) !important;
            border-color: rgba(71, 85, 105, 0.34) !important;
            color: #dbe7f5 !important;
        }
    </style>
    @php
        $openSurveyBuilder = $errors->any()
            || filled(old('title'))
            || filled(old('prompt'))
            || filled(old('options_text'))
            || filled(old('detail_placeholder'))
            || !empty(old('recipient_user_ids', []));
    @endphp
    <div class="py-8">
        <div
            class="rrhh-page-shell mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8"
            x-init="window.rrhhSurveyEditor = $data"
            x-data="{
                builderOpen: @js($openSurveyBuilder),
                selectedRecipients: @js(collect(old('recipient_user_ids', []))->map(fn ($id) => (int) $id)->values()->all()),
                responseType: @js(old('response_type', 'option_with_detail')),
                formAction: @js(route('rrhh.surveys.store')),
                formMethod: 'POST',
                editingSurveyId: null,
                formTitle: @js(old('title', '')),
                formPrompt: @js(old('prompt', '')),
                formOptionsText: @js(old('options_text', '')),
                formDetailPlaceholder: @js(old('detail_placeholder', 'Cuéntanos un breve detalle...')),
                responseTypeLabel() {
                    switch (this.responseType) {
                        case 'option_only':
                            return 'Solo opciones marcadas';
                        case 'text_only':
                            return 'Solo respuesta escrita';
                        default:
                            return 'Opciones + detalle';
                    }
                },
                toggleAll(ids) {
                    const everySelected = ids.every((id) => this.selectedRecipients.includes(id));
                    if (everySelected) {
                        this.selectedRecipients = this.selectedRecipients.filter((id) => !ids.includes(id));
                        return;
                    }

                    this.selectedRecipients = Array.from(new Set([...this.selectedRecipients, ...ids]));
                },
                startEditing(survey) {
                    this.editingSurveyId = survey.id || null;
                    this.builderOpen = true;
                    this.formAction = survey.update_url || @js(route('rrhh.surveys.store'));
                    this.formMethod = 'PUT';
                    this.formTitle = survey.raw_title || '';
                    this.formPrompt = survey.prompt || '';
                    this.responseType = survey.response_type || 'option_with_detail';
                    this.formOptionsText = survey.options_text || '';
                    this.formDetailPlaceholder = survey.detail_placeholder || 'Cuéntanos un breve detalle...';
                    this.selectedRecipients = Array.isArray(survey.recipient_user_ids)
                        ? survey.recipient_user_ids.map((id) => Number(id))
                        : [];
                    this.$nextTick(() => {
                        this.$refs.surveyDesigner?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                },
                resetForm() {
                    this.editingSurveyId = null;
                    this.builderOpen = true;
                    this.formAction = @js(route('rrhh.surveys.store'));
                    this.formMethod = 'POST';
                    this.formTitle = '';
                    this.formPrompt = '';
                    this.responseType = 'option_with_detail';
                    this.formOptionsText = '';
                    this.formDetailPlaceholder = 'Cuéntanos un breve detalle...';
                    this.selectedRecipients = [];
                }
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

            <div class="rrhh-page-panel overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm ring-1 ring-black/5">
                <div class="rrhh-page-hero border-b border-rose-100 bg-gradient-to-r from-slate-900 via-slate-800 to-rose-700 px-6 py-8 text-white sm:px-8">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-3xl">
                            <p class="rrhh-page-hero-label text-sm font-semibold uppercase tracking-[0.25em] text-white/75">Panel RRHH</p>
                            <h1 class="rrhh-page-hero-title mt-3 text-3xl font-semibold tracking-tight text-white">Formularios breves</h1>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="rrhh-stat-card rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.2em] text-white/60">Ejecutivos</div>
                                <div class="mt-2 text-2xl font-semibold text-white">{{ $executives->count() }}</div>
                            </div>
                            <div class="rrhh-stat-card rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.2em] text-white/60">Formularios</div>
                                <div class="mt-2 text-2xl font-semibold text-white">{{ $surveys->count() }}</div>
                            </div>
                            <div class="rrhh-stat-card rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.2em] text-white/60">Pendientes</div>
                                <div class="mt-2 text-2xl font-semibold text-white">{{ $surveys->sum(fn ($survey) => $survey->recipients->whereNull('answered_at')->count()) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 p-5 sm:p-6">
                    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between gap-3 px-4 py-3 sm:px-5">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900" x-text="editingSurveyId ? 'Editar y reenviar formulario' : 'Nuevo formulario'"></h2>
                            </div>

                            <div class="flex items-center gap-3">
                                <button
                                    x-show="editingSurveyId"
                                    x-transition
                                    type="button"
                                    @click.stop="resetForm()"
                                    class="rrhh-secondary-button inline-flex items-center justify-center rounded-2xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                >
                                    Nuevo formulario
                                </button>

                                <span
                                    role="button"
                                    tabindex="0"
                                    @click="builderOpen = !builderOpen"
                                    @keydown.enter.prevent="builderOpen = !builderOpen"
                                    @keydown.space.prevent="builderOpen = !builderOpen"
                                    class="inline-flex h-8 w-8 shrink-0 cursor-pointer items-center justify-center rounded-full border border-slate-200 text-slate-500 transition"
                                    :class="builderOpen ? 'rotate-180' : ''"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </div>
                        </div>

                        <div x-show="builderOpen" x-transition.opacity.duration.150ms style="display: none;" class="border-t border-slate-200 px-4 py-4 sm:px-5">
                            <div class="crm-soft-surface rounded-[26px] p-4 sm:p-5">
                                <form method="POST" :action="formAction" class="grid gap-5 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)] xl:items-start">
                                    @csrf
                                    <template x-if="formMethod === 'PUT'">
                                        <input type="hidden" name="_method" value="PUT">
                                    </template>

                                    <div class="space-y-5">
                                        <div x-ref="surveyDesigner" class="rrhh-block-card rounded-3xl border border-gray-200 bg-gray-50 p-5">
                                            <h3 class="rrhh-block-title text-lg font-bold text-gray-900">Diseñar formulario</h3>
                                            <p class="rrhh-block-copy mt-1 text-sm text-gray-500">Puedes pedir solo una marca, marca con detalle o una respuesta escrita.</p>

                                            <div class="mt-4 grid gap-4">
                                                <div>
                                                    <label class="rrhh-field-label block text-sm font-medium text-gray-700">Título</label>
                                                    <input type="text" name="title" x-model="formTitle" class="rrhh-text-input mt-1 block w-full rounded-xl border-gray-300 text-sm focus:border-rose-400 focus:ring-rose-400" maxlength="120" placeholder="Ej: Estado de ánimo, confirmación rápida">
                                                </div>

                                                <div>
                                                    <label class="rrhh-field-label block text-sm font-medium text-gray-700">Pregunta o mensaje</label>
                                                    <textarea name="prompt" x-model="formPrompt" rows="4" class="rrhh-textarea-input mt-1 block w-full rounded-xl border-gray-300 text-sm focus:border-rose-400 focus:ring-rose-400" maxlength="800" required placeholder="Escribe la consulta que verá el ejecutivo..."></textarea>
                                                </div>

                                                <div class="grid gap-4 lg:grid-cols-2">
                                                    <div>
                                                        <label class="rrhh-field-label block text-sm font-medium text-gray-700">Tipo de respuesta</label>
                                                        <select name="response_type" x-model="responseType" class="rrhh-select-input mt-1 block w-full rounded-xl border-gray-300 text-sm focus:border-rose-400 focus:ring-rose-400" required>
                                                            <option value="option_with_detail">Opciones + detalle</option>
                                                            <option value="option_only">Solo opciones marcadas</option>
                                                            <option value="text_only">Solo respuesta escrita</option>
                                                        </select>
                                                    </div>

                                                    <div x-show="responseType !== 'option_only'" x-transition>
                                                        <label class="rrhh-field-label block text-sm font-medium text-gray-700">Placeholder del detalle</label>
                                                        <input type="text" name="detail_placeholder" x-model="formDetailPlaceholder" class="rrhh-text-input mt-1 block w-full rounded-xl border-gray-300 text-sm focus:border-rose-400 focus:ring-rose-400" maxlength="180">
                                                    </div>
                                                </div>

                                                <div x-show="responseType !== 'text_only'">
                                                    <label class="rrhh-field-label block text-sm font-medium text-gray-700">Opciones</label>
                                                    <textarea name="options_text" x-model="formOptionsText" rows="5" class="rrhh-textarea-input mt-1 block w-full rounded-xl border-gray-300 text-sm focus:border-rose-400 focus:ring-rose-400" placeholder="Una opción por línea&#10;Sí, me siento bien&#10;Necesito apoyo&#10;Prefiero que me contacten luego"></textarea>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="rrhh-block-card rounded-3xl border border-gray-200 bg-white p-5">
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                <div>
                                                    <h3 class="rrhh-block-title text-lg font-bold text-gray-900">Ejecutivos destinatarios</h3>
                                                    <p class="rrhh-block-copy mt-1 text-sm text-gray-500">El formulario emergente se mostrará solo a los ejecutivos seleccionados.</p>
                                                </div>
                                                <button type="button" @click="toggleAll(@js($executives->pluck('id')->values()))" class="rrhh-secondary-button inline-flex items-center justify-center rounded-full border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-100">
                                                    Seleccionar todos
                                                </button>
                                            </div>

                                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                                @foreach($executives as $executive)
                                                    <label class="rrhh-recipient-option flex cursor-pointer items-start gap-3 rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 transition hover:border-gray-300 hover:bg-white">
                                                        <input
                                                            type="checkbox"
                                                            name="recipient_user_ids[]"
                                                            value="{{ $executive->id }}"
                                                            class="mt-1 rounded border-gray-300 text-rose-600 focus:ring-rose-500"
                                                            x-model.number="selectedRecipients"
                                                            @checked(in_array($executive->id, old('recipient_user_ids', []), true))
                                                        >
                                                        <span class="min-w-0">
                                                            <span class="rrhh-survey-title block text-sm font-semibold text-gray-900">{{ $executive->name }}</span>
                                                            <span class="rrhh-meta-text mt-1 block truncate text-xs text-gray-500">{{ $executive->email }}</span>
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-5">
                                        <div class="rrhh-block-card rounded-3xl border border-gray-200 bg-white p-5">
                                            <h3 class="rrhh-block-title text-lg font-bold text-gray-900">Enviar</h3>
                                            <p class="rrhh-block-copy mt-1 text-sm text-gray-500">El ejecutivo verá un popup breve y podrá responderlo desde el mismo CRM.</p>

                                            <div class="rrhh-summary-card mt-4 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-4 text-sm text-rose-900">
                                                <div class="font-semibold">Resumen</div>
                                                <div class="mt-2">Ejecutivos elegidos: <span class="font-semibold" x-text="selectedRecipients.length"></span></div>
                                                <div class="mt-1">Formato: <span x-text="responseTypeLabel()"></span></div>
                                            </div>

                                            <div class="mt-4 flex justify-end">
                                                <button type="submit" class="rrhh-primary-button inline-flex items-center justify-center rounded-2xl bg-rose-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-rose-500">
                                                    <span x-text="editingSurveyId ? 'Guardar y reenviar' : 'Enviar formulario'"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Respuestas recientes</h2>
                        </div>

                        <div id="rrhhRecentResponsesTableWrap" class="rrhh-table-shell hidden overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                            <div class="overflow-x-auto">
                                <table class="min-w-full border-collapse">
                                    <thead class="rrhh-table-head bg-slate-900 text-white">
                                        <tr class="text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-white/80">
                                            <th class="px-3 py-2.5">Fecha</th>
                                            <th class="px-3 py-2.5">Título</th>
                                            <th class="px-3 py-2.5">Pregunta</th>
                                            <th class="px-3 py-2.5">Respondieron</th>
                                            <th class="px-3 py-2.5">Pendientes</th>
                                            <th class="px-3 py-2.5">Tipo</th>
                                            <th class="px-3 py-2.5">Editar</th>
                                            <th class="px-3 py-2.5">Visualizar</th>
                                            <th class="px-3 py-2.5">Ver detalle</th>
                                        </tr>
                                    </thead>
                                    <tbody id="rrhhRecentResponsesTableBody" class="divide-y divide-slate-100"></tbody>
                                </table>
                            </div>
                        </div>

                        <div id="rrhhRecentResponsesEmpty" class="rrhh-empty-state hidden rounded-2xl border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-500">
                            Aún no has enviado formularios desde RRHH.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            id="rrhhSurveyDetailModal"
            class="fixed inset-0 z-[80] hidden items-center justify-center p-4"
        >
            <div class="absolute inset-0 bg-slate-950/45" data-close-survey-detail></div>

            <div class="rrhh-modal-card relative w-full max-w-4xl rounded-[30px] bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div id="rrhhSurveyDetailTitle" class="rrhh-card-label text-sm font-semibold uppercase tracking-[0.2em] text-rose-500"></div>
                        <h2 id="rrhhSurveyDetailPrompt" class="rrhh-modal-title mt-2 text-2xl font-bold text-gray-900"></h2>
                        <p class="rrhh-meta-text mt-2 text-sm text-gray-500">
                            Enviado el <span id="rrhhSurveyDetailCreatedAt"></span>
                        </p>
                    </div>

                    <button
                        type="button"
                        data-close-survey-detail
                        class="rrhh-secondary-button inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-300 text-gray-600 transition hover:bg-gray-50"
                    >
                        ✕
                    </button>
                </div>

                <div class="rrhh-summary-card mt-4 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm text-rose-900">
                    <span id="rrhhSurveyDetailAnsweredCount" class="font-semibold"></span>/<span id="rrhhSurveyDetailRecipientCount" class="font-semibold"></span> respondieron
                </div>

                <div id="rrhhSurveyDetailRecipients" class="mt-5 max-h-[60vh] space-y-3 overflow-y-auto pr-1"></div>
            </div>
        </div>

        <div
            id="rrhhSurveyChartModal"
            class="fixed inset-0 z-[81] hidden items-center justify-center p-4"
        >
            <div class="absolute inset-0 bg-slate-950/45" data-close-survey-chart></div>

            <div class="rrhh-modal-card relative w-full max-w-3xl rounded-[30px] bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="rrhh-card-label text-sm font-semibold uppercase tracking-[0.2em] text-sky-500">Visualizar resultados</div>
                        <h2 id="rrhhSurveyChartTitle" class="rrhh-modal-title mt-2 text-2xl font-bold text-gray-900"></h2>
                        <p id="rrhhSurveyChartPrompt" class="rrhh-meta-text mt-2 text-sm text-gray-500"></p>
                    </div>

                    <button
                        type="button"
                        data-close-survey-chart
                        class="rrhh-secondary-button inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-300 text-gray-600 transition hover:bg-gray-50"
                    >
                        ✕
                    </button>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="rrhh-block-card rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="rrhh-meta-text text-xs font-semibold uppercase tracking-wide text-slate-500">Respondidos</div>
                        <div id="rrhhSurveyChartAnswered" class="rrhh-stat-value mt-2 text-3xl font-black text-slate-900"></div>
                    </div>
                    <div class="rrhh-block-card rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4">
                        <div class="rrhh-meta-text text-xs font-semibold uppercase tracking-wide text-amber-600">Pendientes</div>
                        <div id="rrhhSurveyChartPending" class="rrhh-stat-value mt-2 text-3xl font-black text-amber-700"></div>
                    </div>
                    <div class="rrhh-block-card rounded-2xl border border-sky-200 bg-sky-50 px-4 py-4">
                        <div class="rrhh-meta-text text-xs font-semibold uppercase tracking-wide text-sky-600">Con detalle</div>
                        <div id="rrhhSurveyChartDetailCount" class="rrhh-stat-value mt-2 text-3xl font-black text-sky-700"></div>
                    </div>
                </div>

                <div id="rrhhSurveyChartBars" class="mt-5"></div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const surveysFeedUrl = @json(route('rrhh.surveys.feed'));
                const initialSurveys = @json($surveysPayload);
                const recentResponsesTableWrap = document.getElementById('rrhhRecentResponsesTableWrap');
                const recentResponsesTableBody = document.getElementById('rrhhRecentResponsesTableBody');
                const recentResponsesEmpty = document.getElementById('rrhhRecentResponsesEmpty');
                const modal = document.getElementById('rrhhSurveyDetailModal');
                const recipientsContainer = document.getElementById('rrhhSurveyDetailRecipients');
                const titleNode = document.getElementById('rrhhSurveyDetailTitle');
                const promptNode = document.getElementById('rrhhSurveyDetailPrompt');
                const createdAtNode = document.getElementById('rrhhSurveyDetailCreatedAt');
                const answeredCountNode = document.getElementById('rrhhSurveyDetailAnsweredCount');
                const recipientCountNode = document.getElementById('rrhhSurveyDetailRecipientCount');
                const chartModal = document.getElementById('rrhhSurveyChartModal');
                const chartTitleNode = document.getElementById('rrhhSurveyChartTitle');
                const chartPromptNode = document.getElementById('rrhhSurveyChartPrompt');
                const chartAnsweredNode = document.getElementById('rrhhSurveyChartAnswered');
                const chartPendingNode = document.getElementById('rrhhSurveyChartPending');
                const chartDetailCountNode = document.getElementById('rrhhSurveyChartDetailCount');
                const chartBarsNode = document.getElementById('rrhhSurveyChartBars');
                let surveyChart = null;
                let currentDetailSurveyId = null;
                let currentChartSurveyId = null;
                let latestSurveys = Array.isArray(initialSurveys) ? initialSurveys : [];
                const isDarkTheme = document.body.classList.contains('crm-dark-theme');

                if (
                    !recentResponsesTableWrap ||
                    !recentResponsesTableBody ||
                    !recentResponsesEmpty ||
                    !modal ||
                    !recipientsContainer ||
                    !titleNode ||
                    !promptNode ||
                    !createdAtNode ||
                    !answeredCountNode ||
                    !recipientCountNode ||
                    !chartModal ||
                    !chartTitleNode ||
                    !chartPromptNode ||
                    !chartAnsweredNode ||
                    !chartPendingNode ||
                    !chartDetailCountNode ||
                    !chartBarsNode
                ) {
                    return;
                }

                function closeSurveyDetailModal() {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    recipientsContainer.innerHTML = '';
                }

                function escapeHtml(value) {
                    return String(value ?? '')
                        .replaceAll('&', '&amp;')
                        .replaceAll('<', '&lt;')
                        .replaceAll('>', '&gt;')
                        .replaceAll('"', '&quot;')
                        .replaceAll("'", '&#039;');
                }

                function responseTypeLabel(type) {
                    switch (type) {
                        case 'option_only':
                            return 'Solo opciones';
                        case 'text_only':
                            return 'Respuesta escrita';
                        default:
                            return 'Opciones + detalle';
                    }
                }

                function buildRecipientCard(recipient) {
                    const card = document.createElement('div');
                    card.className = 'rrhh-recipient-card rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4';

                    const statusClasses = recipient.answered_at
                        ? 'rrhh-chip-active bg-green-100 text-green-700'
                        : 'rrhh-chip-pending bg-amber-100 text-amber-700';

                    const metaText = recipient.answered_at
                        ? `Respondió el ${recipient.answered_at}`
                        : recipient.displayed_at
                            ? `Visto el ${recipient.displayed_at}, pendiente de respuesta`
                            : 'Aún no visualizado';

                    card.innerHTML = `
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="rrhh-survey-title text-sm font-semibold text-gray-900">${escapeHtml(recipient.name || 'Ejecutivo')}</div>
                                <div class="rrhh-meta-text mt-1 text-xs text-gray-500">${escapeHtml(metaText)}</div>
                            </div>
                            <div class="rounded-full px-3 py-1 text-xs font-semibold ${statusClasses}">${escapeHtml(recipient.status_label || 'Pendiente')}</div>
                        </div>
                        ${(recipient.selected_option || recipient.answer_detail) ? `
                            <div class="mt-3 text-sm ${isDarkTheme ? 'text-slate-200' : 'text-gray-700'}">
                                ${recipient.selected_option ? `<div><span class="font-semibold ${isDarkTheme ? 'text-slate-50' : 'text-gray-900'}">Marcó:</span> ${escapeHtml(recipient.selected_option)}</div>` : ''}
                                ${recipient.answer_detail ? `<div class="mt-1"><span class="font-semibold ${isDarkTheme ? 'text-slate-50' : 'text-gray-900'}">Detalle:</span> ${escapeHtml(recipient.answer_detail)}</div>` : ''}
                            </div>
                        ` : ''}
                    `;

                    return card;
                }

                function openSurveyDetailModal(survey) {
                    currentDetailSurveyId = survey.id ?? null;
                    titleNode.textContent = survey.title || 'Consulta de RRHH';
                    promptNode.textContent = survey.prompt || '';
                    createdAtNode.textContent = survey.created_at || '-';
                    answeredCountNode.textContent = String(survey.answered_count ?? 0);
                    recipientCountNode.textContent = String(survey.recipient_count ?? 0);
                    recipientsContainer.innerHTML = '';

                    (survey.recipients || []).forEach((recipient) => {
                        recipientsContainer.appendChild(buildRecipientCard(recipient));
                    });

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }

                function closeSurveyChartModal() {
                    currentChartSurveyId = null;
                    chartModal.classList.add('hidden');
                    chartModal.classList.remove('flex');
                    chartBarsNode.innerHTML = '';

                    if (surveyChart) {
                        surveyChart.destroy();
                        surveyChart = null;
                    }
                }

                function openSurveyChartModal(survey) {
                    currentChartSurveyId = survey.id ?? null;
                    chartTitleNode.textContent = survey.title || 'Consulta de RRHH';
                    chartPromptNode.textContent = survey.prompt || '';
                    chartAnsweredNode.textContent = String(survey.answered_count ?? 0);
                    chartPendingNode.textContent = String(survey.pending_count ?? 0);
                    chartDetailCountNode.textContent = String(survey.detail_count ?? 0);
                    chartBarsNode.innerHTML = '';

                    const optionCounts = Array.isArray(survey.option_counts) ? survey.option_counts : [];

                    if (optionCounts.length) {
                        const chartMount = document.createElement('div');
                        chartMount.id = 'rrhhSurveyChartInstance';
                        chartBarsNode.appendChild(chartMount);

                        if (surveyChart) {
                            surveyChart.destroy();
                        }

                        surveyChart = new window.ApexCharts(chartMount, {
                            chart: {
                                type: 'donut',
                                height: 340,
                                toolbar: {
                                    show: false,
                                },
                                fontFamily: 'inherit',
                            },
                            series: optionCounts.map((item) => Number(item.count || 0)),
                            labels: optionCounts.map((item) => item.label || 'Sin etiqueta'),
                            colors: ['#fb7185', '#ec4899', '#d946ef', '#8b5cf6', '#3b82f6', '#06b6d4', '#14b8a6'],
                            plotOptions: {
                                pie: {
                                    donut: {
                                        size: '64%',
                                        labels: {
                                            show: true,
                                            name: {
                                                color: isDarkTheme ? '#e5eefb' : '#334155',
                                            },
                                            value: {
                                                color: isDarkTheme ? '#f8fbff' : '#0f172a',
                                            },
                                            total: {
                                                show: true,
                                                color: isDarkTheme ? '#f8fbff' : '#0f172a',
                                                label: 'Respuestas',
                                                formatter: () => String(optionCounts.reduce((sum, item) => sum + Number(item.count || 0), 0)),
                                                style: {
                                                    color: isDarkTheme ? '#f8fbff' : '#0f172a',
                                                },
                                            },
                                        },
                                    },
                                },
                            },
                            dataLabels: {
                                enabled: true,
                                formatter: (value) => `${Math.round(value)}%`,
                                style: {
                                    colors: ['#ffffff'],
                                    fontWeight: 700,
                                },
                            },
                            stroke: {
                                width: 0,
                            },
                            legend: {
                                position: 'bottom',
                                fontSize: '13px',
                                labels: {
                                    colors: isDarkTheme ? '#dbe7f5' : '#334155',
                                },
                            },
                            tooltip: {
                                theme: isDarkTheme ? 'dark' : 'light',
                            },
                        });

                        surveyChart.render();
                    } else {
                        const emptyState = document.createElement('div');
                        emptyState.className = 'rrhh-empty-state rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500';
                        emptyState.textContent = 'Esta encuesta no usa opciones marcadas. Revisa los detalles en "Ver detalle".';
                        chartBarsNode.appendChild(emptyState);
                    }

                    chartModal.classList.remove('hidden');
                    chartModal.classList.add('flex');
                }

                function buildSurveyRow(survey) {
                    const row = document.createElement('tr');
                    row.className = 'rrhh-table-row align-top text-[13px] text-slate-700';
                    row.innerHTML = `
                        <td class="whitespace-nowrap px-3 py-2.5">${escapeHtml(survey.created_at || '-')}</td>
                        <td class="whitespace-nowrap px-3 py-2.5 font-medium text-slate-900">${escapeHtml(survey.title || 'Consulta de RRHH')}</td>
                        <td class="px-3 py-2.5">
                            <div class="max-w-[260px] truncate">${escapeHtml(survey.prompt || '')}</div>
                        </td>
                        <td class="whitespace-nowrap px-3 py-2.5">
                            <span class="rrhh-table-chip inline-flex items-center rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-semibold text-slate-700">
                                ${Number(survey.answered_count || 0)}/${Number(survey.recipient_count || 0)}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-2.5">${Number(survey.pending_count || 0)}</td>
                        <td class="whitespace-nowrap px-3 py-2.5">
                            <span class="rrhh-table-chip inline-flex items-center rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-semibold text-slate-700">
                                ${escapeHtml(responseTypeLabel(survey.response_type))}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-2.5">
                            <button type="button" class="rrhh-secondary-button inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50" data-edit-survey>
                                Editar
                            </button>
                        </td>
                        <td class="whitespace-nowrap px-3 py-2.5">
                            <button type="button" class="rrhh-chart-button inline-flex items-center justify-center rounded-xl border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 transition hover:bg-sky-100" data-open-survey-chart>
                                Visualizar
                            </button>
                        </td>
                        <td class="whitespace-nowrap px-3 py-2.5">
                            <button type="button" class="rrhh-accent-button inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-100" data-open-survey-detail>
                                Ver detalle
                            </button>
                        </td>
                    `;

                    row.querySelector('[data-edit-survey]')?.addEventListener('click', () => {
                        window.rrhhSurveyEditor?.startEditing?.(survey);
                    });
                    row.querySelector('[data-open-survey-detail]')?.addEventListener('click', () => openSurveyDetailModal(survey));
                    row.querySelector('[data-open-survey-chart]')?.addEventListener('click', () => openSurveyChartModal(survey));

                    return row;
                }

                function renderRecentResponses(surveys) {
                    latestSurveys = Array.isArray(surveys) ? surveys : [];
                    recentResponsesTableBody.innerHTML = '';

                    if (!latestSurveys.length) {
                        recentResponsesTableWrap.classList.add('hidden');
                        recentResponsesEmpty.classList.remove('hidden');
                        return;
                    }

                    recentResponsesTableWrap.classList.remove('hidden');
                    recentResponsesEmpty.classList.add('hidden');
                    latestSurveys.forEach((survey) => {
                        recentResponsesTableBody.appendChild(buildSurveyRow(survey));
                    });

                    if (currentDetailSurveyId) {
                        const currentSurvey = latestSurveys.find((survey) => Number(survey.id) === Number(currentDetailSurveyId));
                        if (currentSurvey) {
                            openSurveyDetailModal(currentSurvey);
                        }
                    }

                    if (currentChartSurveyId) {
                        const currentSurvey = latestSurveys.find((survey) => Number(survey.id) === Number(currentChartSurveyId));
                        if (currentSurvey) {
                            openSurveyChartModal(currentSurvey);
                        }
                    }
                }

                async function pollRecentResponses() {
                    try {
                        const response = await window.fetch(surveysFeedUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            cache: 'no-store',
                        });

                        if (!response.ok) {
                            return;
                        }

                        const payload = await response.json();
                        renderRecentResponses(payload.surveys || []);
                    } catch (error) {
                        console.error('No se pudo refrescar las respuestas de RRHH.', error);
                    }
                }

                modal.querySelectorAll('[data-close-survey-detail]').forEach((element) => {
                    element.addEventListener('click', () => {
                        currentDetailSurveyId = null;
                        closeSurveyDetailModal();
                    });
                });

                chartModal.querySelectorAll('[data-close-survey-chart]').forEach((element) => {
                    element.addEventListener('click', closeSurveyChartModal);
                });

                renderRecentResponses(initialSurveys);
                window.setInterval(pollRecentResponses, 10000);
            });
        </script>
    </div>
</x-app-layout>
