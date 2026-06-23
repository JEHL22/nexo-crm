<x-app-layout>
    <div class="py-8">
        <div class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8">
            @php
                $phone = optional($record->phones->first())->phone;
                $repName = $record->representative_name ?: ($record->full_name ?: '-');
                $direccionFiscal = $record->fiscal_address ?: '-';
                $segmento = $record->segment ?: '-';
                $velocidadMax = $record->max_speed ?: '-';
                $paquete = $record->package ?: '-';
                $tecnologia = $record->technology ?: '-';
                $allPhones = $record->phones
                    ->sortByDesc(fn ($leadPhone) => (int) $leadPhone->is_primary)
                    ->pluck('phone')
                    ->filter()
                    ->values();

                $currentContactName = $record->last_contact_name ?? '-';
                $currentContactPhone = $record->last_contact_phone ?? ($phone ?? '-');
                $latestInteractionForSummary = $record->interactions->sortByDesc('created_at')->first();
                $offeredLineTotal = $latestInteractionForSummary?->offers
                    ? $latestInteractionForSummary->offers->sum(
                        fn ($offer) => (int) ($offer->portability_lines ?? 0) + (int) ($offer->new_lines ?? 0)
                    )
                    : 0;
                $offeredLineTotalLabel = $offeredLineTotal > 0 ? $offeredLineTotal : '-';

                $editContactName = $editData['contact_name'] ?? $record->last_contact_name ?? '';
                $editContactPhone = $editData['contact_phone'] ?? $record->last_contact_phone ?? ($phone ?? '');
                $agreementLocked = (bool) ($existingAgreement && in_array($existingAgreement->supervisor_validation_status, ['pendiente', 'validado'], true));
                $agreementErrors = $errors->hasAny([
                    'agreement',
                    'customer_ruc',
                    'customer_business_name',
                    'customer_dni',
                    'customer_representative_name',
                    'customer_phone',
                    'customer_address',
                    'customer_coordinates',
                    'plan_code',
                    'customer_email',
                    'service_channel',
                    'attention_time_slot',
                    'attention_date',
                    'operator_name',
                    'delivery_type',
                    'fixed_agreement_supports',
                    'fixed_agreement_supports.*',
                    'portability_phone_numbers',
                    'portability_phone_numbers.*',
                    'agreement_attachments',
                    'agreement_attachments.*',
                ]);
                $callFormMessages = collect([
                    'general_status',
                    'specific_status',
                    'next_contact_at',
                    'notes',
                    'contact_name',
                    'contact_phone',
                    'channel',
                    'mobile_mode',
                    'portability_lines',
                    'portability_promotion_name',
                    'portability_rows',
                    'new_lines',
                    'new_promotion_name',
                    'new_rows',
                    'internet_speed',
                    'fixed_monthly',
                ])->flatMap(fn ($field) => $errors->get($field))->filter()->values();
                $buildOfferRows = function ($lines, $promotions) {
                    $lines = is_array($lines) ? array_values($lines) : (filled($lines) ? [$lines] : []);
                    $promotions = is_array($promotions) ? array_values($promotions) : (filled($promotions) ? [$promotions] : []);
                    $total = max(count($lines), count($promotions), 1);
                    $rows = [];

                    for ($index = 0; $index < $total; $index++) {
                        $rows[] = [
                            'lines' => $lines[$index] ?? '',
                            'promotion_name' => $promotions[$index] ?? '',
                        ];
                    }

                    return $rows;
                };
                $editPortabilityRows = $buildOfferRows(
                    old('portability_lines', $editData['portability_lines'] ?? []),
                    old('portability_promotion_name', $editData['portability_promotion_name'] ?? [])
                );
                $editNewRows = $buildOfferRows(
                    old('new_lines', $editData['new_lines'] ?? []),
                    old('new_promotion_name', $editData['new_promotion_name'] ?? [])
                );
                $resolveOfferSectionError = function (string $groupKey, array $prefixes) use ($errors) {
                    if ($errors->has($groupKey)) {
                        return $errors->first($groupKey);
                    }

                    foreach ($errors->getMessages() as $field => $messages) {
                        foreach ($prefixes as $prefix) {
                            if (\Illuminate\Support\Str::startsWith($field, $prefix) && !empty($messages[0])) {
                                return $messages[0];
                            }
                        }
                    }

                    return null;
                };
                $portabilityOfferError = $resolveOfferSectionError('portability_rows', ['portability_lines_', 'portability_promotion_']);
                $newOfferError = $resolveOfferSectionError('new_rows', ['new_lines_', 'new_promotion_']);
                $serviceChannels = [
                    'pdv' => 'PDV',
                    'centralizado' => 'Centralizado',
                ];
                $attentionSlots = ['9 am - 11 am', '11 am - 1 pm', '2 pm - 4 pm', '4 pm - 6 pm'];
                $deliveryTypes = [
                    'regular' => 'Regular',
                    'express' => 'Express',
                    'almacen_propio' => 'Almacen Propio',
                ];
                $agreementProductType = collect($agreementProducts)->pluck('type')->unique()->values();
                $isFixedOnlyAgreement = $agreementProductType->count() === 1 && $agreementProductType->first() === 'fijo';
                $requiresFixedAgreementSupport = $agreementProductType->contains('fijo');
                $agreementSnapshotProducts = collect($existingAgreement?->products_snapshot ?? []);
                $agreementPortabilityRows = collect($agreementPortabilityRows ?? [])->values();
                $backUrl = $backRoute ?? route('my-work.index');
                $callUpdateUrl = $updateRoute ?? route('my-work.update', $record->id);
                $agreementSubmitUrl = $acceptAgreementRoute ?? route('my-work.accept-agreement', $record->id);
                $sisacSubmitUrl = $sisacUpdateRoute ?? null;
                $isSupervisorTeamBase = ($pageContext ?? 'mi_chamba') === 'supervisor_team_base';
                $canEditSisac = (bool) ($canEditSisac ?? false);
                $shouldOpenAgreementModal = $agreementErrors || session('open_agreement_modal');
                $sisacMessages = collect(['segment', 'max_speed', 'package', 'technology'])
                    ->flatMap(fn ($field) => $errors->get($field))
                    ->filter()
                    ->values();
            @endphp

            @if(session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('info'))
                <div class="mb-4 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    {{ session('info') }}
                </div>
            @endif

            <div class="space-y-6">
                <div class="bg-white p-6 rounded shadow">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold">{{ $isSupervisorTeamBase ? 'Mi base del equipo' : 'Detalle del registro' }}</h3>
                            @if($isSupervisorTeamBase)
                                <p class="mt-1 text-sm text-gray-500">Ejecutivo responsable: {{ $record->createdBy->name ?? $record->assignedTo->name ?? '-' }}</p>
                            @endif
                        </div>
                        <a href="{{ $backUrl }}"
                           class="crm-accent-outline-button rounded-xl px-4 py-2 text-sm font-medium transition">
                            Volver
                        </a>
                    </div>

                    <div class="{{ $isSupervisorTeamBase ? 'max-w-xl ml-auto' : 'grid grid-cols-1 gap-6 text-sm xl:grid-cols-[minmax(0,1.45fr)_minmax(0,1fr)]' }}">
                        @unless($isSupervisorTeamBase)
                            <div class="space-y-1">
                                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                    <div class="space-y-1">
                                        <div><span class="font-semibold">RUC:</span> {{ $record->ruc ?? '-' }}</div>
                                        <div><span class="font-semibold">Razón Social:</span> {{ $record->business_name ?? '-' }}</div>
                                        <div><span class="font-semibold">Nombre (Representante):</span> {{ $repName }}</div>
                                        <div><span class="font-semibold">DNI:</span> {{ $record->dni ?? '-' }}</div>
                                        <div><span class="font-semibold">Dirección fiscal:</span> {{ $direccionFiscal }}</div>
                                    </div>

                                    <div class="space-y-1">
                                        <div><span class="font-semibold">Nombre:</span> {{ $currentContactName }}</div>
                                        <div><span class="font-semibold">N° Teléfono principal:</span> {{ $currentContactPhone }}</div>
                                        <div>
                                            <span class="font-semibold">Celulares del lead:</span>
                                            @if($allPhones->isNotEmpty())
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    @foreach($allPhones as $leadPhone)
                                                        <span class="crm-neutral-chip inline-flex items-center rounded-full px-3 py-1 text-xs font-medium">
                                                            {{ $leadPhone }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </div>
                                        <div><span class="font-semibold">Líneas ofrecidas:</span> {{ $offeredLineTotalLabel }}</div>
                                        <div><span class="font-semibold">Operador actual:</span> {{ $record->current_operator ?? '-' }}</div>
                                        <div>
                                            <span class="font-semibold">Estado:</span>
                                            <span class="crm-neutral-chip inline-block rounded px-2 py-1">
                                                {{ $record->status_specific ?? '-' }}
                                            </span>
                                        </div>
                                        @if($existingAgreement)
                                            <div>
                                                <span class="font-semibold">Estado del acuerdo:</span>
                                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $existingAgreement->supervisor_validation_status === 'validado' ? 'crm-accent-chip' : 'crm-neutral-chip' }}">
                                                    {{ $existingAgreement->supervisor_validation_status === 'validado' ? 'Validado por supervisor' : 'Pendiente de supervisor' }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endunless

                        <div class="space-y-4">
                            @if($canEditSisac && $sisacSubmitUrl)
                                <form method="POST" action="{{ $sisacSubmitUrl }}" class="space-y-4 rounded-xl border border-indigo-100 bg-indigo-50/60 px-4 py-4">
                                    @csrf

                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-semibold text-indigo-900">Datos SISAC</div>
                                            <p class="mt-1 text-xs text-indigo-900/70">Este bloque solo lo completa el supervisor.</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-indigo-700">
                                            Editable
                                        </span>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Segmento</label>
                                        <input type="text" name="segment" value="{{ old('segment', $record->segment) }}" class="mt-1 block w-full rounded border-gray-300 bg-white">
                                    </div>

                                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-4">
                                        <div class="text-sm font-semibold text-slate-900">Datos fija</div>
                                        <div class="mt-3 space-y-3">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Velocidad max</label>
                                                <input type="text" name="max_speed" value="{{ old('max_speed', $record->max_speed) }}" class="mt-1 block w-full rounded border-gray-300">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Paquete</label>
                                                <input type="text" name="package" value="{{ old('package', $record->package) }}" class="mt-1 block w-full rounded border-gray-300">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Tecnología</label>
                                                <input type="text" name="technology" value="{{ old('technology', $record->technology) }}" class="mt-1 block w-full rounded border-gray-300">
                                            </div>
                                        </div>
                                    </div>

                                    @if($sisacMessages->isNotEmpty())
                                        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                            {{ $sisacMessages->implode(' | ') }}
                                        </div>
                                    @endif

                                    <button type="submit" class="inline-flex items-center justify-center rounded-full bg-black px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800">
                                        Guardar SISAC
                                    </button>
                                </form>
                            @else
                                <div class="crm-accent-soft-card rounded-xl px-4 py-3">
                                    <div class="text-sm font-semibold" style="color: color-mix(in srgb, var(--crm-primary) 72%, var(--crm-secondary));">SafeForce</div>
                                    <div class="mt-3 text-sm text-gray-800">
                                        <span class="font-semibold">Segmento:</span>
                                        {{ $segmento }}
                                    </div>
                                </div>

                                <div class="crm-soft-surface rounded-xl px-4 py-3">
                                    <div class="text-sm font-semibold text-slate-900">Datos fija</div>
                                    <div class="mt-3 space-y-1 text-sm text-gray-800">
                                        <div>
                                            <span class="font-semibold">Velocidad max:</span>
                                            {{ $velocidadMax }}
                                        </div>
                                        <div>
                                            <span class="font-semibold">Paquete:</span>
                                            {{ $paquete }}
                                        </div>
                                        <div>
                                            <span class="font-semibold">Tecnología:</span>
                                            {{ $tecnologia }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                @unless($isSupervisorTeamBase)
                <div class="grid gap-6 items-start" style="grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);">
                    <div class="bg-white p-6 rounded shadow">
                        <h3 class="text-lg font-semibold mb-4">Registro de la llamada</h3>

                        <form method="POST" action="{{ $callUpdateUrl }}" class="space-y-4" id="callForm">
                            @csrf
                            <input type="hidden" name="open_agreement_modal_after_save" id="openAgreementModalAfterSave" value="0">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nombre</label>
                                    <input
                                        type="text"
                                        name="contact_name"
                                        value="{{ old('contact_name', $editContactName) }}"
                                        class="mt-1 block w-full rounded border-gray-300"
                                        placeholder="Ingresa el nombre"
                                        maxlength="80"
                                        minlength="2"
                                        autocomplete="off"
                                        title="Solo letras, espacios, puntos, apóstrofes y guiones."
                                    >
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Número de teléfono</label>
                                    <input
                                        type="text"
                                        name="contact_phone"
                                        value="{{ old('contact_phone', $editContactPhone) }}"
                                        class="mt-1 block w-full rounded border-gray-300"
                                        placeholder="Ingresa el número de teléfono"
                                        inputmode="numeric"
                                        pattern="[0-9]{9}"
                                        maxlength="9"
                                        autocomplete="off"
                                    >
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Estado gen.</label>
                                    <select name="general_status" id="general_status" class="mt-1 block w-full rounded border-gray-300 bg-gray-100 text-gray-700 cursor-not-allowed" disabled>
                                        <option value="contactado" selected>Contactado</option>
                                    </select>
                                    <input type="hidden" name="general_status" value="contactado">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Estado Esp.</label>
                                    <select name="specific_status" id="specific_status" class="mt-1 block w-full rounded border-gray-300">
                                        <option value="">-- Selecciona --</option>
                                        @foreach($specificOptions as $k => $v)
                                            <option value="{{ $k }}" {{ old('specific_status', $editData['specific_status'] ?? '') === $k ? 'selected' : '' }}>
                                                {{ $v }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div id="rescheduleBlock" class="hidden border rounded p-4 bg-amber-50 border-amber-200">
                                <div class="text-sm font-medium mb-3 border-b border-amber-200 pb-2">Llamada reprogramada</div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
                                    <div>
                                        <label for="next_contact_at" class="block text-sm font-medium text-gray-700">Fecha y hora de devolución</label>
                                        <input
                                            type="datetime-local"
                                            name="next_contact_at"
                                            id="next_contact_at"
                                            class="mt-1 block w-full rounded border-gray-300"
                                            value="{{ old('next_contact_at', $editData['next_contact_at'] ?? '') }}"
                                        >
                                    </div>
                                    <p class="text-xs text-gray-600">
                                        El sistema te avisará 5 minutos antes de la hora programada mientras estés en esta pantalla.
                                    </p>
                                </div>
                            </div>

                            <div id="commercialBlock" class="hidden border rounded p-4 bg-gray-50">
                                <div class="text-sm font-medium mb-3 border-b pb-2">Datos de la Oferta</div>

                                <div class="mb-4">
                                    <div class="text-xs text-gray-500 mb-1">Producto</div>
                                    <div class="flex flex-wrap gap-4 text-sm">
                                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                            <input type="radio" name="channel" value="movil" {{ old('channel', $editData['channel'] ?? '') === 'movil' ? 'checked' : '' }}>
                                            Móvil
                                        </label>
                                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                            <input type="radio" name="channel" value="fijo" {{ old('channel', $editData['channel'] ?? '') === 'fijo' ? 'checked' : '' }}>
                                            Fijo
                                        </label>
                                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                            <input type="radio" name="channel" value="movil_fijo" {{ old('channel', $editData['channel'] ?? '') === 'movil_fijo' ? 'checked' : '' }}>
                                            Móvil + Fijo
                                        </label>
                                    </div>
                                </div>

                                <div id="mobileSection" class="hidden space-y-4 pl-3 border-l-2 border-blue-200 mb-4">
                                    <div id="mobileModeBlock" class="mt-3 mb-4 w-full md:w-1/2">
                                        <label for="mobile_mode" class="block text-xs font-medium text-gray-700">Tipo de gestión móvil</label>
                                        <select name="mobile_mode" id="mobile_mode" class="mt-1 block w-full rounded border-gray-300 text-sm">
                                            <option value="">-- Selecciona --</option>
                                            <option value="portabilidad" {{ old('mobile_mode', $editData['mobile_mode'] ?? '') === 'portabilidad' ? 'selected' : '' }}>Portabilidad</option>
                                            <option value="alta_nueva" {{ old('mobile_mode', $editData['mobile_mode'] ?? '') === 'alta_nueva' ? 'selected' : '' }}>Alta nueva</option>
                                            <option value="porta_alta" {{ old('mobile_mode', $editData['mobile_mode'] ?? '') === 'porta_alta' ? 'selected' : '' }}>Porta + Alta</option>
                                        </select>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div id="portabilityBlock" class="hidden space-y-3 bg-white p-3 rounded border shadow-sm">
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Portabilidad</div>
                                                <button type="button" id="addPortabilityRow" class="crm-accent-outline-button inline-flex items-center justify-center rounded-xl px-3 py-2 text-xs font-semibold transition">
                                                    Agregar fila
                                                </button>
                                            </div>

                                            <div id="portabilityRows" class="space-y-3">
                                                @foreach($editPortabilityRows as $row)
                                                    <div data-offer-row class="grid grid-cols-1 gap-3 md:grid-cols-[64px_minmax(0,1fr)_64px]">
                                                        <div>
                                                            <label class="flex min-h-[2.25rem] items-end text-xs font-medium leading-tight text-gray-700">Cant. lin Porta</label>
                                                            <input type="number" name="portability_lines[]" class="mt-1 block w-[4rem] max-w-full rounded border-gray-300 text-sm" min="1" max="999" step="1" value="{{ $row['lines'] }}">
                                                        </div>
                                                        <div class="min-w-0">
                                                            <label class="flex min-h-[2.25rem] items-end text-xs font-medium leading-tight text-gray-700">Promoción porta</label>
                                                            <select name="portability_promotion_name[]" class="mt-1 block w-full min-w-0 rounded border-gray-300 text-sm">
                                                                <option value="">-- Selecciona --</option>
                                                                @foreach(($promotionNames ?? collect()) as $promotionName)
                                                                    <option value="{{ $promotionName->name }}" @selected($row['promotion_name'] === $promotionName->name)>{{ $promotionName->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="md:pt-6">
                                                            <button type="button" data-remove-offer-row aria-label="Quitar fila" title="Quitar fila" class="inline-flex h-[42px] w-full items-center justify-center rounded-xl border border-red-200 bg-red-50 px-0 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100">
                                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16"/>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6"/>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 11v6"/>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12"/>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 7V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            @if($portabilityOfferError)
                                                <div class="text-sm text-red-600">
                                                    {{ $portabilityOfferError }}
                                                </div>
                                            @endif
                                        </div>

                                        <div id="newLinesBlock" class="hidden space-y-3 bg-white p-3 rounded border shadow-sm">
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Alta nueva</div>
                                                <button type="button" id="addNewRow" class="crm-accent-outline-button inline-flex items-center justify-center rounded-xl px-3 py-2 text-xs font-semibold transition">
                                                    Agregar fila
                                                </button>
                                            </div>

                                            <div id="newRows" class="space-y-3">
                                                @foreach($editNewRows as $row)
                                                    <div data-offer-row class="grid grid-cols-1 gap-3 md:grid-cols-[64px_minmax(0,1fr)_64px]">
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-700">Cant. Alta</label>
                                                            <input type="number" name="new_lines[]" class="mt-1 block w-[4rem] max-w-full rounded border-gray-300 text-sm" min="1" max="999" step="1" value="{{ $row['lines'] }}">
                                                        </div>
                                                        <div class="min-w-0">
                                                            <label class="block text-xs font-medium text-gray-700">Promoción alta</label>
                                                            <select name="new_promotion_name[]" class="mt-1 block w-full min-w-0 rounded border-gray-300 text-sm">
                                                                <option value="">-- Selecciona --</option>
                                                                @foreach(($promotionNames ?? collect()) as $promotionName)
                                                                    <option value="{{ $promotionName->name }}" @selected($row['promotion_name'] === $promotionName->name)>{{ $promotionName->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="md:pt-6">
                                                            <button type="button" data-remove-offer-row aria-label="Quitar fila" title="Quitar fila" class="inline-flex h-[42px] w-full items-center justify-center rounded-xl border border-red-200 bg-red-50 px-0 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100">
                                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16"/>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6"/>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 11v6"/>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12"/>
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 7V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            @if($newOfferError)
                                                <div class="text-sm text-red-600">
                                                    {{ $newOfferError }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div id="fixedBlock" class="hidden mt-3 pl-3 border-l-2 border-green-200">
                                    <div class="grid grid-cols-2 gap-4 bg-white p-3 rounded border shadow-sm w-full md:w-1/2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">Velocidad</label>
                                            <input type="text" name="internet_speed" class="mt-1 block w-full rounded border-gray-300 text-sm" placeholder="Ej: 100 Mbps"
                                                value="{{ old('internet_speed', $editData['internet_speed']) }}">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">Mensualidad Fijo S/</label>
                                            <input type="number" step="0.01" name="fixed_monthly" class="mt-1 block w-full rounded border-gray-300 text-sm" min="0"
                                                value="{{ old('fixed_monthly', $editData['fixed_monthly']) }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Detalle de la llamada</label>
                                <textarea
                                    name="notes"
                                    id="notes"
                                    rows="5"
                                    class="mt-1 block w-full rounded border-gray-300 resize-none overflow-y-auto min-h-[200px] max-h-[200px]"
                                    placeholder="Feedback de la llamada..."
                                >{{ old('notes', $editData['notes']) }}</textarea>
                            </div>

                            @if ($callFormMessages->isNotEmpty())
                                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                    {{ $callFormMessages->implode(' | ') }}
                                </div>
                            @endif

                            <div class="flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="crm-accent-soft-card rounded-2xl px-4 py-3 text-sm">
                                    <div class="font-semibold">Cierre de acuerdo</div>
                                    <div class="mt-1">
                                        El estado <span class="font-semibold">Acuerdo aceptado</span> ahora se registra desde una ficha especial y primero pasa por validación del supervisor.
                                        @if($agreementLocked)
                                            Este registro ya fue enviado y no volverá a mostrarse para evitar duplicados.
                                        @endif
                                    </div>
                                </div>

                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <button
                                        type="button"
                                        id="openAgreementModal"
                                        class="crm-accent-outline-button inline-flex items-center justify-center rounded-full px-4 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:border-gray-300 disabled:text-gray-400"
                                        {{ empty($agreementProducts) || $agreementLocked ? 'disabled' : '' }}
                                    >
                                        {{ $agreementLocked ? 'Acuerdo enviado' : 'Acuerdo aceptado' }}
                                    </button>

                                    <button type="submit" id="btnRegistrar"
                                        class="crm-accent-button rounded-full px-4 py-2 text-sm transition">
                                        Editar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="bg-white p-6 rounded shadow max-h-[780px] flex flex-col">
                        <div class="shrink-0">
                            <h3 class="text-lg font-semibold mb-4">Historial de interacciones</h3>

                            <div class="crm-soft-surface mb-4 rounded-lg px-4 py-3 text-sm">
                                <span class="font-semibold">Estado actual del registro:</span>
                                {{ $record->status_specific ?? '-' }}
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto pr-1">
                            @if($record->interactions->isEmpty())
                                <div class="border border-dashed border-gray-300 rounded-xl p-6 text-center text-gray-500">
                                    Este lead todavía no tiene historial visible.
                                </div>
                            @else
                                <div class="space-y-4">
                                    @foreach($record->interactions->sortByDesc('created_at') as $interaction)
                                        @php
                                            $offers = $interaction->offers ?? collect();

                                            $hasMovil = $offers->contains(fn ($offer) => $offer->product_type === 'movil');
                                            $hasFijo = $offers->contains(fn ($offer) => $offer->product_type === 'fijo');

                                            if ($hasMovil && $hasFijo) {
                                                $productLabel = 'Móvil + Fijo';
                                            } elseif ($hasMovil) {
                                                $productLabel = 'Móvil';
                                            } elseif ($hasFijo) {
                                                $productLabel = 'Fijo';
                                            } else {
                                                $productLabel = '--------';
                                            }

                                            $mobileOffers = $offers->where('product_type', 'movil')->values();
                                            $fixedOffer = $offers->firstWhere('product_type', 'fijo');
                                            $snapshotProductLabel = match ($interaction->product_type_offered) {
                                                'movil' => 'Móvil',
                                                'fijo' => 'Fijo',
                                                'movil_fijo' => 'Móvil + Fijo',
                                                default => null,
                                            };
                                            $agreementSnapshotLabel = $interaction->status_specific === 'acuerdo_aceptado' && $agreementSnapshotProducts->isNotEmpty()
                                                ? $agreementSnapshotProducts->pluck('label')->implode(' + ')
                                                : null;

                                            $interactionContactName = $interaction->contact_name ?? $record->last_contact_name ?? '-';
                                            $interactionContactPhone = $interaction->contact_phone ?? $record->last_contact_phone ?? ($phone ?? '-');
                                        @endphp

                                        <div class="crm-soft-surface rounded-xl p-4 space-y-3">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <div class="font-medium text-gray-900">
                                                        Estado: {{ $interaction->status_specific ?? '-' }}
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        Usuario: {{ $interaction->user->name ?? 'Sin usuario' }}
                                                    </div>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ optional($interaction->created_at)->format('d/m/Y H:i') }}
                                                </div>
                                            </div>

                                            <div class="text-sm text-gray-600 space-y-1">
                                                <div>{{ $interaction->call_detail ?? '-' }}</div>

                                                <div>
                                                    <span class="font-medium text-gray-700">Nombre:</span>
                                                    {{ $interactionContactName }}
                                                </div>

                                                <div>
                                                    <span class="font-medium text-gray-700">Número de teléfono:</span>
                                                    {{ $interactionContactPhone }}
                                                </div>
                                            </div>

                                            <div class="text-sm text-gray-800">
                                                <span class="font-semibold">Producto ofrecido:</span>
                                                {{ $productLabel !== '--------' ? $productLabel : ($snapshotProductLabel ?? $agreementSnapshotLabel ?? '--------') }}
                                            </div>

                                            @if($mobileOffers->isNotEmpty())
                                                <div class="crm-accent-soft-card rounded-lg px-3 py-3 text-sm">
                                                    <div class="mb-2 font-semibold text-gray-900">Datos de oferta móvil</div>

                                                    <div class="space-y-3">
                                                        @foreach($mobileOffers as $mobileOffer)
                                                            @php
                                                                $mobileModeLabel = match ($mobileOffer->mobile_mode) {
                                                                    'portabilidad' => 'Portabilidad',
                                                                    'alta_nueva' => 'Alta nueva',
                                                                    'porta_alta' => 'Porta + Alta',
                                                                    default => ucfirst(str_replace('_', ' ', $mobileOffer->mobile_mode ?? '')),
                                                                };
                                                            @endphp
                                                            <div class="rounded-lg border border-slate-200 bg-white/80 px-3 py-3">
                                                                <div class="mb-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                                                    {{ $mobileModeLabel ?: 'Oferta móvil' }}
                                                                </div>

                                                                <div class="grid grid-cols-1 gap-x-6 gap-y-2 md:grid-cols-2">
                                                                    @if(!is_null($mobileOffer->portability_lines))
                                                                        <div>
                                                                            <span class="font-medium">Cant. Porta:</span>
                                                                            {{ $mobileOffer->portability_lines }}
                                                                        </div>
                                                                    @endif

                                                                    @if(!empty($mobileOffer->portability_promotion_name))
                                                                        <div>
                                                                            <span class="font-medium">Promoción Porta:</span>
                                                                            {{ $mobileOffer->portability_promotion_name }}
                                                                        </div>
                                                                    @elseif(!is_null($mobileOffer->portability_monthly))
                                                                        <div>
                                                                            <span class="font-medium">Mensualidad Porta:</span>
                                                                            S/ {{ number_format((float) $mobileOffer->portability_monthly, 2) }}
                                                                        </div>
                                                                    @endif

                                                                    @if(!is_null($mobileOffer->new_lines))
                                                                        <div>
                                                                            <span class="font-medium">Cant. Alta:</span>
                                                                            {{ $mobileOffer->new_lines }}
                                                                        </div>
                                                                    @endif

                                                                    @if(!empty($mobileOffer->new_promotion_name))
                                                                        <div>
                                                                            <span class="font-medium">Promoción Alta:</span>
                                                                            {{ $mobileOffer->new_promotion_name }}
                                                                        </div>
                                                                    @elseif(!is_null($mobileOffer->new_monthly))
                                                                        <div>
                                                                            <span class="font-medium">Mensualidad Alta:</span>
                                                                            S/ {{ number_format((float) $mobileOffer->new_monthly, 2) }}
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            @if($fixedOffer)
                                                <div class="crm-accent-soft-card rounded-lg px-3 py-3 text-sm">
                                                    <div class="mb-2 font-semibold text-gray-900">Datos de oferta fijo</div>

                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                                        <div>
                                                            <span class="font-medium">Velocidad:</span>
                                                            {{ $fixedOffer->internet_speed ?: '--------' }}
                                                        </div>

                                                        @if(!is_null($fixedOffer->fixed_monthly))
                                                            <div>
                                                                <span class="font-medium">Mensualidad Fijo:<br></span>
                                                                S/ {{ number_format((float) $fixedOffer->fixed_monthly, 2) }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif

                                            @if($mobileOffers->isEmpty() && !$fixedOffer)
                                                <div class="text-sm text-gray-800">
                                                    <span class="font-semibold">Datos de la oferta:</span>
                                                    @if($snapshotProductLabel || $agreementSnapshotLabel)
                                                        {{ $snapshotProductLabel ?? $agreementSnapshotLabel }}
                                                        @if(!is_null($interaction->offered_line_count))
                                                            | Líneas: {{ $interaction->offered_line_count }}
                                                        @endif
                                                        @if(!is_null($interaction->monthly_payment))
                                                            | Pago mensual: S/ {{ number_format((float) $interaction->monthly_payment, 2) }}
                                                        @endif
                                                    @else
                                                        --------
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endunless
            </div>
        </div>
    </div>

    @include('partials.agreement-modal')

    <div id="pendingChangesModal" class="fixed inset-0 z-[60] hidden">
        <div id="pendingChangesModalBackdrop" class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

        <div class="relative flex min-h-full items-center justify-center px-4 py-6">
            <div class="w-full max-w-md rounded-[26px] bg-white shadow-2xl ring-1 ring-slate-200">
                <div class="border-b border-slate-200 px-5 py-4">
                    <div class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.08em] text-amber-700">
                        Hay datos editados sin guardar
                    </div>
                </div>

                <div class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:justify-end">
                    <button type="button" id="cancelPendingChangesModal" class="inline-flex items-center justify-center rounded-full border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                        Seguir editando
                    </button>
                    <button type="button" id="saveAndContinueButton" class="inline-flex items-center justify-center rounded-full bg-black px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800">
                        Guardar y continuar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="callReminderContainer" class="fixed top-4 right-4 z-50 w-full max-w-sm space-y-3 pointer-events-none"></div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const enableCommercial = @json($enableCommercial);
            const serverNowIso = @json(now()->setTimezone(config('app.timezone'))->toIso8601String());

            const specificSel = document.getElementById('specific_status');
            const commercialBlock = document.getElementById('commercialBlock');
            const rescheduleBlock = document.getElementById('rescheduleBlock');
            const contactNameInput = document.querySelector('input[name="contact_name"]');
            const contactPhoneInput = document.querySelector('input[name="contact_phone"]');
            const nextContactAtInput = document.getElementById('next_contact_at');
            const form = document.getElementById('callForm');
            const btn = document.getElementById('btnRegistrar');
            const agreementModal = document.getElementById('agreementModal');
            const agreementBackdrop = document.getElementById('agreementModalBackdrop');
            const openAgreementModalButton = document.getElementById('openAgreementModal');
            const closeAgreementModalButton = document.getElementById('closeAgreementModal');
            const cancelAgreementModalButton = document.getElementById('cancelAgreementModal');
            const agreementForm = document.getElementById('agreementForm');
            const agreementSubmitButton = document.getElementById('agreementSubmitButton');
            const agreementRequiredInputs = agreementForm ? agreementForm.querySelectorAll('.agreement-required') : [];
            const agreementExclusiveInputs = agreementForm ? agreementForm.querySelectorAll('.agreement-exclusive') : [];
            const shouldOpenAgreementModal = @json($shouldOpenAgreementModal);
            const isFixedOnlyAgreement = @json($isFixedOnlyAgreement);
            const requiresFixedAgreementSupport = @json($requiresFixedAgreementSupport);
            const openAgreementModalAfterSaveInput = document.getElementById('openAgreementModalAfterSave');
            const pendingChangesModal = document.getElementById('pendingChangesModal');
            const pendingChangesModalBackdrop = document.getElementById('pendingChangesModalBackdrop');
            const cancelPendingChangesModalButton = document.getElementById('cancelPendingChangesModal');
            const saveAndContinueButton = document.getElementById('saveAndContinueButton');
            let initialCallFormSnapshot = '';
            let submitCallFormForAgreementModal = false;

            const mobileSection = document.getElementById('mobileSection');
            const mobileModeSel = document.getElementById('mobile_mode');
            const portabilityBlock = document.getElementById('portabilityBlock');
            const newLinesBlock = document.getElementById('newLinesBlock');
            const fixedBlock = document.getElementById('fixedBlock');
            const portabilityRows = document.getElementById('portabilityRows');
            const newRows = document.getElementById('newRows');
            const addPortabilityRowBtn = document.getElementById('addPortabilityRow');
            const addNewRowBtn = document.getElementById('addNewRow');
            const internetSpeedInput = document.querySelector('input[name="internet_speed"]');
            const fixedMonthlyInput = document.querySelector('input[name="fixed_monthly"]');
            const serverLoadedAt = new Date(serverNowIso);
            const perfStart = window.performance.now();

            function getServerAlignedNowMs() {
                return serverLoadedAt.getTime() + (window.performance.now() - perfStart);
            }

            function formatDateTimeLocalFromMs(timestampMs) {
                const date = new Date(timestampMs);
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                const hours = String(date.getHours()).padStart(2, '0');
                const minutes = String(date.getMinutes()).padStart(2, '0');

                return `${year}-${month}-${day}T${hours}:${minutes}`;
            }

            if (contactPhoneInput) {
                contactPhoneInput.addEventListener('input', () => {
                    contactPhoneInput.value = contactPhoneInput.value.replace(/\D/g, '').slice(0, 9);
                });
            }

            if (contactNameInput) {
                contactNameInput.addEventListener('input', () => {
                    contactNameInput.value = contactNameInput.value
                        .replace(/[^\p{L}\s'.-]/gu, '')
                        .replace(/\s{2,}/g, ' ')
                        .slice(0, 80);
                });
            }

            agreementForm?.querySelectorAll('[data-digits-only]').forEach((input) => {
                input.addEventListener('input', (event) => {
                    const limit = Number(event.target.dataset.digitsOnly || 0) || undefined;
                    event.target.value = event.target.value.replace(/\D/g, '').slice(0, limit);
                });
            });

            ['customer_ruc', 'customer_dni'].forEach((fieldName) => {
                agreementForm?.querySelector(`input[name="${fieldName}"]`)?.addEventListener('input', (event) => {
                    event.target.value = event.target.value.replace(/\D/g, '');
                });
            });

            agreementForm?.querySelector('input[name="customer_representative_name"]')?.addEventListener('input', (event) => {
                event.target.value = event.target.value
                    .replace(/[^\p{L}\s'.-]/gu, '')
                    .replace(/\s{2,}/g, ' ')
                    .slice(0, 255);
            });

            function shouldEnableCommercial() {
                return enableCommercial.includes(specificSel.value);
            }

            function shouldEnableReschedule() {
                return specificSel.value === 'reprogramado';
            }

            function createOfferRowHtml(type) {
                const lineLabel = type === 'portability' ? 'Cant. lin Porta' : 'Cant. Alta';
                const lineName = type === 'portability' ? 'portability_lines[]' : 'new_lines[]';
                const promotionLabel = type === 'portability' ? 'Promoción porta' : 'Promoción alta';
                const promotionName = type === 'portability' ? 'portability_promotion_name[]' : 'new_promotion_name[]';
                const rowGridClass = 'md:grid-cols-[64px_minmax(0,1fr)_64px]';
                const lineInputClass = 'mt-1 block w-[4rem] max-w-full rounded border-gray-300 text-sm';
                const labelClass = type === 'portability'
                    ? 'flex min-h-[2.25rem] items-end text-xs font-medium leading-tight text-gray-700'
                    : 'block text-xs font-medium text-gray-700';
                const promotionSelectClass = 'mt-1 block w-full min-w-0 rounded border-gray-300 text-sm';

                return `
                    <div data-offer-row class="grid grid-cols-1 gap-3 ${rowGridClass}">
                        <div>
                            <label class="${labelClass}">${lineLabel}</label>
                            <input type="number" name="${lineName}" class="${lineInputClass}" min="1" max="999" step="1">
                        </div>
                        <div class="min-w-0">
                            <label class="${labelClass}">${promotionLabel}</label>
                            <select name="${promotionName}" class="${promotionSelectClass}">
                                <option value="">-- Selecciona --</option>
                                @foreach(($promotionNames ?? collect()) as $promotionName)
                                    <option value="{{ $promotionName->name }}">{{ $promotionName->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:pt-6">
                            <button type="button" data-remove-offer-row aria-label="Quitar fila" title="Quitar fila" class="inline-flex h-[42px] w-full items-center justify-center rounded-xl border border-red-200 bg-red-50 px-0 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 11v6"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 7V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
            }

            function ensureAtLeastOneRow(container, type) {
                if (!container) return;
                if (!container.querySelector('[data-offer-row]')) {
                    container.insertAdjacentHTML('beforeend', createOfferRowHtml(type));
                }
            }

            function refreshRemoveButtons(container) {
                if (!container) return;

                const rows = [...container.querySelectorAll('[data-offer-row]')];
                rows.forEach((row) => {
                    const removeBtn = row.querySelector('[data-remove-offer-row]');
                    const removeCell = removeBtn?.parentElement;
                    if (!removeBtn) return;

                    row.classList.remove('md:grid-cols-[64px_minmax(0,1fr)]', 'md:grid-cols-[64px_minmax(0,1fr)_64px]');

                    if (rows.length === 1) {
                        row.classList.add('md:grid-cols-[64px_minmax(0,1fr)]');
                        removeBtn.classList.add('hidden');
                        removeCell?.classList.add('hidden');
                    } else {
                        row.classList.add('md:grid-cols-[64px_minmax(0,1fr)_64px]');
                        removeBtn.classList.remove('hidden');
                        removeCell?.classList.remove('hidden');
                    }
                });
            }

            function getOfferSectionLabel(container) {
                return container === portabilityRows ? 'Portabilidad' : 'Alta nueva';
            }

            function getOfferPromotionDuplicateMessage(container) {
                return `No repitas la misma promoción en ${getOfferSectionLabel(container)}. Si deseas más líneas para ese plan, aumenta la cantidad en una sola fila.`;
            }

            function getOfferPromotionSelects(container) {
                if (!container) {
                    return [];
                }

                return [...container.querySelectorAll('[data-offer-row] select')];
            }

            function refreshOfferPromotionOptions(container) {
                const selects = getOfferPromotionSelects(container);

                selects.forEach((select) => {
                    const currentValue = (select.value || '').trim();
                    const selectedByOthers = new Set(
                        selects
                            .filter((item) => item !== select)
                            .map((item) => (item.value || '').trim())
                            .filter(Boolean)
                    );

                    [...select.options].forEach((option) => {
                        if (!option.value) {
                            option.disabled = false;
                            return;
                        }

                        option.disabled = option.value !== currentValue && selectedByOthers.has(option.value);
                    });

                    if (!selectedByOthers.has(currentValue)) {
                        select.setCustomValidity('');
                    }
                });
            }

            function ensureUniqueOfferPromotionsInContainer(container, report = false) {
                const selects = getOfferPromotionSelects(container);
                const seenPromotions = new Set();

                for (const select of selects) {
                    const selectedValue = (select.value || '').trim();
                    select.setCustomValidity('');

                    if (!selectedValue) {
                        continue;
                    }

                    const normalizedValue = selectedValue.toLowerCase();

                    if (seenPromotions.has(normalizedValue)) {
                        const message = getOfferPromotionDuplicateMessage(container);
                        select.setCustomValidity(message);

                        if (report) {
                            select.reportValidity();
                            select.focus();
                        }

                        return false;
                    }

                    seenPromotions.add(normalizedValue);
                }

                return true;
            }

            function ensureUniqueOfferPromotions(report = false) {
                refreshOfferPromotionOptions(portabilityRows);
                refreshOfferPromotionOptions(newRows);

                return ensureUniqueOfferPromotionsInContainer(portabilityRows, report)
                    && ensureUniqueOfferPromotionsInContainer(newRows, report);
            }

            function clearOfferSection(container) {
                if (!container) return;

                container.querySelectorAll('[data-offer-row]').forEach((row, index) => {
                    if (index === 0) {
                        row.querySelectorAll('input, select').forEach((field) => {
                            field.value = '';
                        });
                        return;
                    }

                    row.remove();
                });

                refreshRemoveButtons(container);
                refreshOfferPromotionOptions(container);
            }

            function isOfferSectionValid(container, lineInputName, promotionSelectName) {
                if (!container) {
                    return false;
                }

                const rows = [...container.querySelectorAll('[data-offer-row]')];
                let completedRows = 0;

                for (const row of rows) {
                    const linesValue = (row.querySelector(`input[name="${lineInputName}"]`)?.value || '').trim();
                    const promotionValue = (row.querySelector(`select[name="${promotionSelectName}"]`)?.value || '').trim();

                    if (!linesValue && !promotionValue) {
                        continue;
                    }

                    if (!linesValue || !promotionValue) {
                        return false;
                    }

                    completedRows += 1;
                }

                return completedRows > 0 && ensureUniqueOfferPromotionsInContainer(container);
            }

            function clearFixedFields() {
                if (internetSpeedInput) internetSpeedInput.value = '';
                if (fixedMonthlyInput) fixedMonthlyInput.value = '';
            }

            function updateDisplay() {
                const showReschedule = shouldEnableReschedule();

                rescheduleBlock.classList.toggle('hidden', !showReschedule);

                if (nextContactAtInput) {
                    nextContactAtInput.disabled = !showReschedule;
                }

                if (!shouldEnableCommercial()) {
                    commercialBlock.classList.add('hidden');

                    document.querySelectorAll('input[name="channel"]').forEach(radio => {
                        radio.checked = false;
                    });

                    if (mobileModeSel) {
                        mobileModeSel.value = '';
                    }

                    clearOfferSection(portabilityRows);
                    clearOfferSection(newRows);
                    clearFixedFields();
                    return;
                }

                commercialBlock.classList.remove('hidden');

                const channel = document.querySelector('input[name="channel"]:checked')?.value;
                const mobileMode = mobileModeSel.value;

                const showMobile = channel === 'movil' || channel === 'movil_fijo';
                const showFixed = channel === 'fijo' || channel === 'movil_fijo';

                mobileSection.classList.toggle('hidden', !showMobile);
                fixedBlock.classList.toggle('hidden', !showFixed);

                if (!showMobile) {
                    if (mobileModeSel) mobileModeSel.value = '';
                    clearOfferSection(portabilityRows);
                    clearOfferSection(newRows);
                    portabilityBlock.classList.add('hidden');
                    newLinesBlock.classList.add('hidden');
                } else {
                    const showPortability = mobileMode === 'portabilidad' || mobileMode === 'porta_alta';
                    const showNew = mobileMode === 'alta_nueva' || mobileMode === 'porta_alta';

                    portabilityBlock.classList.toggle('hidden', !showPortability);
                    newLinesBlock.classList.toggle('hidden', !showNew);
                    ensureAtLeastOneRow(portabilityRows, 'portability');
                    ensureAtLeastOneRow(newRows, 'new');
                    refreshRemoveButtons(portabilityRows);
                    refreshRemoveButtons(newRows);
                    refreshOfferPromotionOptions(portabilityRows);
                    refreshOfferPromotionOptions(newRows);

                    if (!showPortability) {
                        clearOfferSection(portabilityRows);
                    }

                    if (!showNew) {
                        clearOfferSection(newRows);
                    }
                }

                if (!showFixed) {
                    clearFixedFields();
                }
            }

            function openAgreementModal() {
                if (!agreementModal) return;

                agreementModal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeAgreementModal() {
                if (!agreementModal) return;

                agreementModal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            function openPendingChangesModal() {
                if (!pendingChangesModal) return;

                pendingChangesModal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closePendingChangesModal() {
                if (!pendingChangesModal) return;

                pendingChangesModal.classList.add('hidden');

                if (!agreementModal || agreementModal.classList.contains('hidden')) {
                    document.body.classList.remove('overflow-hidden');
                }
            }

            function updateExclusiveGroupState(groupName) {
                if (!agreementForm) return;

                const hiddenInput = agreementForm.querySelector(`#${groupName}`);
                const checkboxes = agreementForm.querySelectorAll(`.agreement-exclusive[data-target-input="${groupName}"]`);
                const selected = Array.from(checkboxes).find((checkbox) => checkbox.checked);

                if (hiddenInput) {
                    hiddenInput.value = selected ? selected.value : '';
                }

                checkboxes.forEach((checkbox) => {
                    checkbox.closest('.agreement-option')?.classList.toggle('border-emerald-400', checkbox.checked);
                    checkbox.closest('.agreement-option')?.classList.toggle('bg-emerald-50', checkbox.checked);
                });
            }

            function validateAgreementForm() {
                if (!agreementForm || !agreementSubmitButton) return;

                let ok = true;

                agreementRequiredInputs.forEach((input) => {
                    const value = (input.value || '').trim();
                    ok = ok && value.length > 0;

                    if (typeof input.checkValidity === 'function') {
                        ok = ok && input.checkValidity();
                    }
                });

                if (requiresFixedAgreementSupport) {
                    const fixedSupportsChecked = agreementForm.querySelectorAll('input[name="fixed_agreement_supports[]"]:checked').length;
                    ok = ok && fixedSupportsChecked === 1;
                }

                if (!isFixedOnlyAgreement) {
                    ['service_channel', 'delivery_type'].forEach((fieldName) => {
                        const hiddenInput = agreementForm.querySelector(`#${fieldName}`);
                        ok = ok && !!(hiddenInput?.value || '').trim();
                    });
                }

                agreementSubmitButton.disabled = !ok;
            }

            function validateForm() {
                let ok = true;

                if (contactNameInput) {
                    ok = ok && contactNameInput.value.trim().length > 0;
                }

                if (contactPhoneInput) {
                    ok = ok && contactPhoneInput.value.trim().length === 9;
                }

                const notes = document.getElementById('notes');
                if (notes) {
                    ok = ok && notes.value.trim().length > 0;
                }

                ok = ok && !!specificSel.value;

                if (shouldEnableReschedule()) {
                    const nextContactValue = (nextContactAtInput?.value || '').trim();
                    ok = ok && !!nextContactValue;

                    if (nextContactAtInput && nextContactValue) {
                        ok = ok && nextContactAtInput.checkValidity();
                    }
                }

                if (shouldEnableCommercial()) {
                    const channel = document.querySelector('input[name="channel"]:checked')?.value;
                    ok = ok && !!channel;

                    if (channel === 'movil' || channel === 'movil_fijo') {
                        ok = ok && mobileModeSel.value !== '';

                        if (mobileModeSel.value === 'portabilidad' || mobileModeSel.value === 'porta_alta') {
                            ok = ok && isOfferSectionValid(portabilityRows, 'portability_lines[]', 'portability_promotion_name[]');
                        }

                        if (mobileModeSel.value === 'alta_nueva' || mobileModeSel.value === 'porta_alta') {
                            ok = ok && isOfferSectionValid(newRows, 'new_lines[]', 'new_promotion_name[]');
                        }
                    }

                    if (channel === 'fijo' || channel === 'movil_fijo') {
                        ok = ok && internetSpeedInput.value.trim().length > 0;
                        ok = ok && !!fixedMonthlyInput.value;
                    }
                }

                btn.disabled = !ok;
                btn.classList.toggle('opacity-50', !ok);
                btn.classList.toggle('cursor-not-allowed', !ok);
            }

            addPortabilityRowBtn?.addEventListener('click', () => {
                portabilityRows.insertAdjacentHTML('beforeend', createOfferRowHtml('portability'));
                refreshRemoveButtons(portabilityRows);
                refreshOfferPromotionOptions(portabilityRows);
                validateForm();
            });

            addNewRowBtn?.addEventListener('click', () => {
                newRows.insertAdjacentHTML('beforeend', createOfferRowHtml('new'));
                refreshRemoveButtons(newRows);
                refreshOfferPromotionOptions(newRows);
                validateForm();
            });

            commercialBlock.addEventListener('click', (event) => {
                const removeButton = event.target.closest('[data-remove-offer-row]');
                if (!removeButton) {
                    return;
                }

                const row = removeButton.closest('[data-offer-row]');
                const container = row?.parentElement;

                if (!row || !container) {
                    return;
                }

                if (container.querySelectorAll('[data-offer-row]').length === 1) {
                    row.querySelectorAll('input, select').forEach((field) => {
                        field.value = '';
                    });
                } else {
                    row.remove();
                }

                refreshRemoveButtons(container);
                refreshOfferPromotionOptions(container);
                validateForm();
            });

            commercialBlock.addEventListener('change', (event) => {
                if (!event.target.matches('select[name="portability_promotion_name[]"], select[name="new_promotion_name[]"]')) {
                    return;
                }

                const currentSelect = event.target;
                const container = currentSelect.closest('#portabilityRows, #newRows');
                const selectedValue = (currentSelect.value || '').trim();

                if (!container) {
                    return;
                }

                if (selectedValue) {
                    const duplicateSelect = getOfferPromotionSelects(container).find((select) => {
                        return select !== currentSelect
                            && (select.value || '').trim().toLowerCase() === selectedValue.toLowerCase();
                    });

                    if (duplicateSelect) {
                        currentSelect.value = '';
                        currentSelect.setCustomValidity(getOfferPromotionDuplicateMessage(container));
                        currentSelect.reportValidity();
                        currentSelect.setCustomValidity('');
                    }
                }

                refreshOfferPromotionOptions(container);
                validateForm();
            });

            function normalizedFieldValue(field) {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    return field.checked ? field.value : '';
                }

                return field.value ?? '';
            }

            function getCallFormSnapshot() {
                if (!form) {
                    return '';
                }

                const fields = Array.from(form.querySelectorAll('input, select, textarea'))
                    .filter((field) => field.name && field.name !== '_token');

                return JSON.stringify(
                    fields.map((field) => [field.name, normalizedFieldValue(field)])
                );
            }

            function hasPendingCallFormChanges() {
                return getCallFormSnapshot() !== initialCallFormSnapshot;
            }

            function handleAgreementModalRequest() {
                if (!form) {
                    openAgreementModal();
                    return;
                }

                if (!hasPendingCallFormChanges()) {
                    if (openAgreementModalAfterSaveInput) {
                        openAgreementModalAfterSaveInput.value = '0';
                    }
                    openAgreementModal();
                    return;
                }

                openPendingChangesModal();
            }

            function saveAndContinueWithAgreement() {
                submitCallFormForAgreementModal = true;

                if (openAgreementModalAfterSaveInput) {
                    openAgreementModalAfterSaveInput.value = '1';
                }

                closePendingChangesModal();
                form.requestSubmit();
            }

            specificSel.addEventListener('change', updateDisplay);
            specificSel.addEventListener('change', validateForm);
            form.addEventListener('change', updateDisplay);
            form.addEventListener('change', validateForm);
            form.addEventListener('input', validateForm);
            form?.addEventListener('submit', (event) => {
                if (!ensureUniqueOfferPromotions(true)) {
                    event.preventDefault();
                    submitCallFormForAgreementModal = false;
                    return;
                }

                if (openAgreementModalAfterSaveInput && !submitCallFormForAgreementModal) {
                    openAgreementModalAfterSaveInput.value = '0';
                }

                submitCallFormForAgreementModal = false;
            });
            openAgreementModalButton?.addEventListener('click', handleAgreementModalRequest);
            closeAgreementModalButton?.addEventListener('click', closeAgreementModal);
            cancelAgreementModalButton?.addEventListener('click', closeAgreementModal);
            agreementBackdrop?.addEventListener('click', closeAgreementModal);
            cancelPendingChangesModalButton?.addEventListener('click', closePendingChangesModal);
            pendingChangesModalBackdrop?.addEventListener('click', closePendingChangesModal);
            saveAndContinueButton?.addEventListener('click', saveAndContinueWithAgreement);

            agreementExclusiveInputs.forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    const targetInput = checkbox.dataset.targetInput;
                    if (!targetInput || !agreementForm) return;

                    agreementForm.querySelectorAll(`.agreement-exclusive[data-target-input="${targetInput}"]`).forEach((item) => {
                        if (item !== checkbox) {
                            item.checked = false;
                        }
                    });

                    if (!checkbox.checked) {
                        const hiddenInput = agreementForm.querySelector(`#${targetInput}`);
                        if (hiddenInput) {
                            hiddenInput.value = '';
                        }
                    }

                    updateExclusiveGroupState(targetInput);
                    validateAgreementForm();
                });
            });

            agreementForm?.addEventListener('input', validateAgreementForm);
            agreementForm?.addEventListener('change', validateAgreementForm);

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && agreementModal && !agreementModal.classList.contains('hidden')) {
                    closeAgreementModal();
                }

                if (event.key === 'Escape' && pendingChangesModal && !pendingChangesModal.classList.contains('hidden')) {
                    closePendingChangesModal();
                }
            });

            if (nextContactAtInput) {
                nextContactAtInput.min = formatDateTimeLocalFromMs(getServerAlignedNowMs());
            }

            if (!isFixedOnlyAgreement) {
                updateExclusiveGroupState('service_channel');
                updateExclusiveGroupState('delivery_type');
            }

            if (requiresFixedAgreementSupport) {
                updateExclusiveGroupState('fixed_agreement_supports');
            }
            updateDisplay();
            validateForm();
            validateAgreementForm();
            initialCallFormSnapshot = getCallFormSnapshot();
            if (shouldOpenAgreementModal) {
                openAgreementModal();
            }
        });
    </script>

    @include('partials.executive-tmo-tracker', [
        'leadId' => $record->id,
        'moduleName' => 'mi_chamba',
        'routeName' => 'my-work.show',
    ])
</x-app-layout>
