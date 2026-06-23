<x-app-layout>
    <div class="pt-3 pb-8">
        <div class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8">
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

            {{-- LÓGICA DE PANTALLA VACÍA: Si no se encontró un lead para el usuario, se muestra esto --}}
            @if(!$lead)
                <div class="bg-white p-6 rounded shadow">
                    <p class="text-gray-700">No hay más clientes disponibles en tu campaña por ahora.</p>
                </div>
            @else
                <div class="mb-3 rounded-xl border border-gray-200 bg-white px-2 py-1.5 shadow">
                    <div class="crm-executive-metrics">
                        <div class="crm-executive-metrics__sales">
                            <div class="sales-gauge" data-gauge-angle="{{ $salesGauge['angle'] }}">
                                <div class="sales-gauge__arc">
                                    <div class="sales-gauge__needle"></div>
                                    <div class="sales-gauge__center"></div>
                                </div>
                                <div class="sales-gauge__body">
                                    <div class="crm-executive-metrics__hero-value">{{ $salesGauge['value'] }}</div>
                                    <div class="crm-executive-metrics__gauge-label">{{ $salesGauge['label'] }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="crm-executive-metrics__middle">
                            <div class="crm-executive-metrics__middle-shell">
                                <div class="status-gauge-board">
                                    @foreach($dailyStatusGauges as $state)
                                        <div class="status-gauge-board__item">
                                            <div class="sales-gauge sales-gauge--mini" data-gauge-angle="{{ $state['angle'] }}">
                                                <div class="sales-gauge__arc">
                                                    <div class="sales-gauge__needle"></div>
                                                    <div class="sales-gauge__center"></div>
                                                </div>
                                                <div class="sales-gauge__body">
                                                    <div class="status-gauge-board__value">{{ $state['value'] }}</div>
                                                </div>
                                            </div>
                                            <div class="status-gauge-board__label">{{ $state['label'] }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="crm-executive-metrics__weekly-board">
                                @foreach($weeklyStatusGauges as $state)
                                    <div class="crm-executive-metrics__weekly-item">
                                        <div class="sales-gauge sales-gauge--weekly" data-gauge-angle="{{ $state['angle'] }}">
                                            <div class="sales-gauge__arc">
                                                <div class="sales-gauge__needle"></div>
                                                <div class="sales-gauge__center"></div>
                                            </div>
                                            <div class="sales-gauge__body">
                                                <div class="crm-executive-metrics__weekly-value">{{ $state['value'] }}</div>
                                                <div class="crm-executive-metrics__gauge-label crm-executive-metrics__gauge-label--weekly">{{ $state['label'] }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="crm-executive-metrics__profile">
                            <div class="flex h-full items-center justify-center">
                                <div class="crm-work-top-panel rounded-2xl border border-gray-200 bg-white px-2 py-2 text-center shadow-sm">
                                    <div class="mx-auto flex h-[84px] w-[84px] items-center justify-center overflow-hidden rounded-2xl border border-gray-200 bg-slate-100 shadow-sm">
                                        @if($headerProfilePhotoUrl)
                                            <img src="{{ $headerProfilePhotoUrl }}" alt="Foto de perfil" class="h-full w-full object-cover">
                                        @else
                                            <span class="crm-profile-avatar flex h-full w-full items-center justify-center rounded-2xl text-[1.35rem] font-semibold uppercase">
                                                {{ $headerUserInitials }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="mt-1 max-w-[104px] truncate text-sm font-medium leading-tight text-slate-900">
                                        {{ $headerUser?->name ?? 'Usuario' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="grid gap-6 items-start" style="grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);">

                    {{-- IZQUIERDA (2/3 de la pantalla): Formulario de captura y datos del cliente --}}
                    <div class="space-y-6">

                        {{-- BLOQUE INFORMATIVO: Muestra los datos principales del Lead obtenidos de la BD --}}
                        <div class="bg-white p-6 rounded shadow">
                            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                <div>
                                    <h3 class="text-lg font-semibold mb-4">Nuevo Cliente</h3>
                                    <div class="space-y-1 text-sm">
                                        <div><span class="font-semibold">RUC:</span> {{ $lead->ruc ?? '-' }}</div>
                                        <div><span class="font-semibold">Razón Social:</span> {{ $lead->business_name ?? '-' }}</div>
                                        <div><span class="font-semibold">Nombre (Representante):</span> {{ $repName }}</div>
                                        <div><span class="font-semibold">DNI:</span> {{ $lead->dni ?? '-' }}</div>
                                        <div><span class="font-semibold">Dirección fiscal:</span> {{ $direccionFiscal }}</div>
                                        <div class="space-y-2">
                                            <div><span class="font-semibold">N° Teléfono principal:</span> {{ $phone ?? '-' }}</div>
                                            <div>
                                                <div class="font-semibold">Celulares del lead:</div>
                                                @if ($phones->isEmpty())
                                                    <div class="text-gray-600">-</div>
                                                @else
                                                    <div class="mt-2 space-y-2">
                                                        @foreach ($phones as $index => $leadPhone)
                                                            <div class="flex flex-col gap-2 rounded-lg border border-gray-200 px-3 py-2 md:flex-row md:items-center md:justify-between">
                                                                <div class="flex items-center gap-2 text-sm text-gray-900">
                                                                    <span class="font-medium">#{{ $index + 1 }}</span>
                                                                    <span>{{ $leadPhone->phone }}</span>
                                                                    @if ($leadPhone->is_primary)
                                                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700">
                                                                            Principal
                                                                        </span>
                                                                    @endif
                                                                </div>

                                                                <button type="button"
                                                                        class="crm-accent-outline-button inline-flex items-center justify-center rounded-full px-3 py-1 text-xs font-medium transition"
                                                                        data-phone-select="{{ $leadPhone->phone }}">
                                                                    Usar en llamada
                                                                </button>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div><span class="font-semibold"># Líneas:</span> {{ $lead->current_line_count ?? '-' }}</div>
                                        <div><span class="font-semibold">Operador actual:</span> {{ $lead->current_operator ?? '-' }}</div>
                                        <div class="pt-2">
                                            <span class="font-semibold">Estado:</span>
                                            <span class="inline-block px-2 py-1 rounded bg-gray-100">
                                                {{ $lead->status_final ?? 'sin_gestion' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-gray-200 pt-6 lg:border-l lg:border-t-0 lg:pl-6 lg:pt-0">
                                    <div class="flex items-start justify-between gap-3 mb-4">
                                        <div>
                                            <h3 class="text-lg font-semibold">Datos SISAC</h3>
                                        </div>
                                    </div>

                                    <div class="space-y-1 text-sm">
                                        <div>
                                            <span class="font-semibold">Semáforo:</span>
                                            {{ $sisacData?->semaforo ? ucfirst($sisacData->semaforo) : '-' }}
                                        </div>
                                        <div>
                                            <span class="font-semibold">Resultado:</span>
                                            {{ $sisacData?->resultado ?? '-' }}
                                        </div>
                                        <div>
                                            <span class="font-semibold">Cantidad Líneas a ofrecer:</span>
                                            {{ $sisacData?->cantidad_lineas_ofrecer ?? '-' }}
                                        </div>
                                        <div>
                                            <span class="font-semibold">Depósito Garantía:</span>
                                            {{ $sisacData?->deposito_garantia ?? '-' }}
                                        </div>
                                        <div>
                                            <span class="font-semibold">Rango LC disponible:</span>
                                            {{ $sisacData?->rango_lc_disponible ?? '-' }}
                                        </div>
                                    </div>

                                    <div class="mt-6 space-y-4">
                                        <div class="crm-accent-soft-card rounded-xl border px-4 py-3">
                                            <div class="text-sm font-semibold" style="color: var(--crm-primary);">SafeForce</div>
                                            <div class="mt-3 text-sm text-gray-800">
                                                <span class="font-semibold">Segmento:</span>
                                                {{ $segmento }}
                                            </div>
                                        </div>

                                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
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
                                                    <span class="font-semibold">Tecnologia:</span>
                                                    {{ $tecnologia }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Registro de la llamada --}}
                        <div class="bg-white p-6 rounded shadow">
                            <h3 class="text-lg font-semibold mb-4">Registro de la llamada</h3>

                            {{-- Nota: Postea directamente al WorkController@store apuntando al ID del lead --}}
                            <form method="POST" action="{{ route('work.store', ['lead' => $lead->id]) }}" class="space-y-4" id="callForm">
                                @csrf
                                <input type="hidden" name="submit_intent" id="submit_intent" value="register">

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Nombre</label>
                                        {{-- Se deja vacio para que el ejecutivo escriba el dato manualmente antes de guardar. --}}
                                        <input
                                            type="text"
                                            name="contact_name"
                                            id="contact_name"
                                            class="mt-1 block w-full rounded border-gray-300"
                                            value="{{ old('contact_name') }}"
                                            placeholder="Ingresa el nombre"
                                            maxlength="80"
                                            minlength="2"
                                            autocomplete="off"
                                            title="Solo letras, espacios, puntos, apóstrofes y guiones."
                                        >
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Numero de telefono</label>
                                        {{-- Se deja vacio para preparar el formulario para 
                                        el futuro cambio de BD sin autocompletar datos actuales. --}}
                                        <input
                                            type="text"
                                            name="contact_phone"
                                            id="contact_phone"
                                            class="mt-1 block w-full rounded border-gray-300"
                                            value="{{ old('contact_phone') }}"
                                            placeholder="Ingresa el numero de telefono"
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
                                        <select name="general_status" id="general_status" class="mt-1 block w-full rounded border-gray-300">
                                            <option value="">-- Selecciona --</option>
                                            @foreach($generalOptions as $k => $v)
                                                <option value="{{ $k }}" @selected(old('general_status') === $k)>{{ $v }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Estado Esp.</label>
                                        <select name="specific_status" id="specific_status" class="mt-1 block w-full rounded border-gray-300" disabled>
                                            <option value="">-- Selecciona primero Estado gen. --</option>
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
                                                value="{{ old('next_contact_at') }}"
                                                min="{{ $serverNowLocalValue }}"
                                            >
                                        </div>
                                        <p class="text-xs text-gray-600">
                                            El sistema te avisará 5 minutos antes de la hora programada mientras estés en esta pantalla.
                                        </p>
                                    </div>
                                </div>

                                {{-- BLOQUE COMERCIAL DINÁMICO: 
                                     Esta sección entera permanece oculta (display:none) hasta que el JS determina que el
                                     estado seleccionado amerita recopilar info comercial (ej, si eligió "Negociación").
                                --}}
                                <div id="commercialBlock" class="hidden border rounded p-4 bg-gray-50">
                                    <div class="text-sm font-medium mb-3 border-b pb-2">Datos de la Oferta</div>

                                    {{-- Selección de Producto --}}
                                    <div class="mb-4">
                                        <div class="text-xs text-gray-500 mb-1">Producto</div>
                                        <div class="flex flex-wrap gap-4 text-sm">
                                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="channel" value="movil"> Móvil
                                            </label>
                                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="channel" value="fijo"> Fijo
                                            </label>
                                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                                <input type="radio" name="channel" value="movil_fijo"> Móvil + Fijo
                                            </label>
                                        </div>
                                    </div>

                                    {{-- Sección MÓVIL --}}
                                    <div id="mobileSection" class="hidden space-y-4 pl-3 border-l-2 border-blue-200 mb-4">
                                        <div id="mobileModeBlock" class="mb-4 w-full md:w-1/2">
                                            <label for="mobile_mode" class="block text-xs font-medium text-gray-700">Tipo de gestión móvil</label>
                                            <select name="mobile_mode" id="mobile_mode" class="mt-1 block w-full rounded border-gray-300 text-sm">
                                                <option value="">-- Selecciona --</option>
                                                <option value="portabilidad">Portabilidad</option>
                                                <option value="alta_nueva">Alta nueva</option>
                                                <option value="porta_alta">Porta + Alta</option>
                                            </select>
                                        </div>

                                        {{-- Sub-bloques de Móvil --}}
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div id="portabilityBlock" class="hidden space-y-3 bg-white p-3 rounded border shadow-sm">
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Portabilidad</div>
                                                    <button type="button" id="addPortabilityRow" class="crm-accent-outline-button inline-flex items-center justify-center rounded-xl px-3 py-2 text-xs font-semibold transition">
                                                        Agregar fila
                                                    </button>
                                                </div>

                                                <div id="portabilityRows" class="space-y-3">
                                                    @foreach($oldPortabilityRows as $row)
                                                        <div data-offer-row class="grid grid-cols-1 gap-3 md:grid-cols-[64px_minmax(0,1fr)_64px]">
                                                            <div>
                                                                <label class="flex min-h-[2.25rem] items-end text-xs font-medium leading-tight text-gray-700">Cant. lin Porta</label>
                                                                <input type="number" name="portability_lines[]" class="mt-1 block w-[4rem] max-w-full rounded border-gray-300 text-sm" min="1" max="999" value="{{ $row['lines'] }}">
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
                                                    @foreach($oldNewRows as $row)
                                                        <div data-offer-row class="grid grid-cols-1 gap-3 md:grid-cols-[64px_minmax(0,1fr)_64px]">
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-700">Cant. Alta</label>
                                                                <input type="number" name="new_lines[]" class="mt-1 block w-[4rem] max-w-full rounded border-gray-300 text-sm" min="1" max="999" value="{{ $row['lines'] }}">
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

                                        @if(($promotionNames ?? collect())->isEmpty())
                                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                                                Aún no hay promociones cargadas en el catálogo. Pide al administrador de promociones que registre opciones para habilitar estos selects.
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Sección FIJO --}}
                                    <div id="fixedBlock" class="hidden pl-3 border-l-2 border-green-200">
                                        <div class="grid grid-cols-2 gap-4 bg-white p-3 rounded border shadow-sm w-full md:w-1/2">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700">Velocidad</label>
                                                <input type="text" name="internet_speed" class="mt-1 block w-full rounded border-gray-300 text-sm" placeholder="Ej: 100 Mbps">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700">Mensualidad Fijo S/</label>
                                                <input type="number" step="0.01" name="fixed_monthly" class="mt-1 block w-full rounded border-gray-300 text-sm" min="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Detalle de la llamada</label>
                                    <textarea name="notes" id="notes" rows="4" class="mt-1 block w-full rounded border-gray-300 resize-none overflow-y-auto min-h-[200px] max-h-[200px]" placeholder="Feedback de la llamada..." required></textarea>
                                </div>

                                <div class="flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                        El atajo de <span class="font-semibold">Acuerdo aceptado</span> guarda primero la negociación y abre la ficha de cierre sin salir de esta pantalla.
                                    </div>

                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                        <button type="submit" id="btnRegistrar"
                                            class="crm-accent-button px-3 py-1 rounded-full text-sm transition disabled:opacity-50 disabled:cursor-not-allowed"
                                            disabled>
                                            Registrar
                                        </button>

                                        <button
                                            type="button"
                                            id="btnAgreementShortcut"
                                            class="crm-accent-outline-button hidden px-3 py-1 rounded-full text-sm font-semibold transition disabled:cursor-not-allowed disabled:border-gray-300 disabled:text-gray-400"
                                            {{ $agreementLocked ? 'disabled' : '' }}
                                        >
                                            {{ $agreementLocked ? 'Acuerdo enviado' : 'Acuerdo aceptado' }}
                                        </button>
                                    </div>
                                </div>

                                @if ($errors->any() && !$agreementErrors)
                                    <div class="text-red-600 text-sm mt-2 font-medium">
                                        {{ implode(' | ', $errors->all()) }}
                                    </div>
                                @endif
                            </form>
                        </div>

                    </div>

                    {{-- DERECHA (1/3 de la pantalla): Promociones --}}
                    <div>
                        <div
                            class="bg-white p-6 rounded shadow"
                            x-data="{
                                activeTab: 'promos',
                                activeSpeechCase: '{{ $initialSpeechCaseKey }}',
                                openSpeechStages: { '{{ $initialSpeechCaseKey }}': 0 },
                                switchSpeechCase(caseKey) {
                                    this.activeSpeechCase = caseKey;

                                    if (!(caseKey in this.openSpeechStages)) {
                                        this.openSpeechStages[caseKey] = 0;
                                    }
                                },
                                toggleSpeechStage(caseKey, stageIndex) {
                                    if (!(caseKey in this.openSpeechStages)) {
                                        this.openSpeechStages[caseKey] = 0;
                                    }

                                    this.openSpeechStages[caseKey] = this.openSpeechStages[caseKey] === stageIndex ? null : stageIndex;
                                }
                            }"
                        >
                            <div class="flex items-end gap-1 border-b border-gray-200">
                                <button type="button"
                                        @click="activeTab = 'promos'"
                                        :class="activeTab === 'promos' ? 'crm-tab-active' : 'crm-tab-muted border-transparent'"
                                        class="rounded-t-xl border px-4 py-2 text-sm font-semibold transition">
                                    Promociones
                                </button>
                                <button type="button"
                                        @click="activeTab = 'tips'"
                                        :class="activeTab === 'tips' ? 'crm-tab-active' : 'crm-tab-muted border-transparent'"
                                        class="rounded-t-xl border px-4 py-2 text-sm font-semibold transition">
                                    Speech
                                </button>
                            </div>

                            <div x-show="activeTab === 'promos'" x-transition.opacity.duration.150ms class="pt-4">
                                @if(collect($promoDocuments ?? [])->isEmpty())
                                    <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center text-sm text-gray-500">
                                        El administrador aún no ha cargado promociones PDF para este módulo.
                                    </div>
                                @else
                                    <div class="space-y-2">
                                        @foreach ($promoDocuments as $promoDocument)
                                            <button
                                                type="button"
                                                data-pdf-popup-url="{{ asset($promoDocument->pdf_path) }}"
                                                data-pdf-popup-title="{{ $promoDocument->title }}"
                                                class="crm-promo-card group w-full rounded-xl border bg-white px-3 py-3 text-left shadow-sm transition"
                                            >
                                                <div class="flex items-center justify-between gap-3">
                                                    <div class="min-w-0">
                                                        <div class="crm-promo-card__badge text-[10px] font-semibold uppercase tracking-[0.18em]">
                                                            {{ $promoDocument->badge ?: 'Promoción PDF' }}
                                                        </div>
                                                        <div class="crm-promo-card__title mt-1 truncate text-base font-semibold leading-snug">
                                                            {{ $promoDocument->title }}
                                                        </div>
                                                        <div class="crm-promo-card__hint mt-1 text-xs">
                                                            Haz clic para abrir el PDF en una ventana movible y ajustable.
                                                        </div>
                                                    </div>

                                                    <span class="crm-promo-card__action inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border transition">
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

                            <div x-show="activeTab === 'tips'" x-transition.opacity.duration.150ms class="pt-4" x-cloak>
                                <div class="space-y-4">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($speechCases as $speechCase)
                                            <button
                                                type="button"
                                                @click="switchSpeechCase('{{ $speechCase['key'] }}')"
                                                data-tone="{{ $speechCase['tone'] }}"
                                                :class="activeSpeechCase === '{{ $speechCase['key'] }}' ? 'crm-speech-case-button--active' : 'crm-speech-case-button--idle'"
                                                class="crm-speech-case-button inline-flex items-center rounded-full border px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.14em] transition"
                                            >
                                                {{ $speechCase['label'] }}
                                            </button>
                                        @endforeach
                                    </div>

                                    @foreach ($speechCases as $speechCase)
                                        <div
                                            x-show="activeSpeechCase === '{{ $speechCase['key'] }}'"
                                            x-transition.opacity.duration.150ms
                                            class="space-y-3"
                                            x-cloak
                                        >
                                            <div class="crm-speech-summary rounded-2xl border px-4 py-4 crm-speech-summary--{{ $speechCase['tone'] }}">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="crm-speech-summary__pill inline-flex items-center rounded-full px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em]">
                                                        {{ $speechCase['label'] }}
                                                    </span>
                                                    <span class="text-xs font-medium text-slate-500">
                                                        {{ count($speechCase['stages']) }} etapas clave
                                                    </span>
                                                </div>

                                                <div class="mt-3">
                                                    <div class="text-sm font-semibold text-slate-900">Cómo leer este caso</div>
                                                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ $speechCase['description'] }}</p>
                                                </div>
                                            </div>

                                            <div class="space-y-2">
                                                @foreach ($speechCase['stages'] as $stageIndex => $speechStage)
                                                    <div class="crm-reference-card crm-reference-card--soft overflow-hidden rounded-xl border shadow-sm transition">
                                                        <button
                                                            type="button"
                                                            @click="toggleSpeechStage('{{ $speechCase['key'] }}', {{ $stageIndex }})"
                                                            :class="openSpeechStages['{{ $speechCase['key'] }}'] === {{ $stageIndex }} ? 'crm-reference-card__toggle--active' : 'crm-reference-card__toggle--idle'"
                                                            class="crm-reference-card__toggle flex w-full items-center justify-between gap-2 border-b px-3 py-2.5 text-left transition"
                                                        >
                                                            <div class="min-w-0">
                                                                <div class="crm-reference-card__label text-[10px] font-semibold uppercase tracking-[0.14em]">
                                                                    Etapa {{ $stageIndex + 1 }}
                                                                </div>
                                                                <div class="crm-reference-card__title mt-0.5 text-sm font-bold leading-snug">
                                                                    {{ $speechStage['label'] }}
                                                                </div>
                                                                <div class="crm-reference-card__summary mt-1 text-xs leading-5 text-slate-500">
                                                                    {{ $speechStage['focus'] }}
                                                                </div>
                                                            </div>

                                                            <span
                                                                class="crm-reference-card__chevron flex h-6 w-6 shrink-0 items-center justify-center rounded-full border transition"
                                                                :class="openSpeechStages['{{ $speechCase['key'] }}'] === {{ $stageIndex }} ? 'rotate-180' : ''"
                                                            >
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                                                </svg>
                                                            </span>
                                                        </button>

                                                        <div
                                                            x-show="openSpeechStages['{{ $speechCase['key'] }}'] === {{ $stageIndex }}"
                                                            x-transition.opacity.duration.150ms
                                                            class="crm-reference-card__panel border-t p-2.5"
                                                        >
                                                            <div class="space-y-2">
                                                                @foreach ($speechStage['conversation'] as $line)
                                                                    @php
                                                                        $speakerTone = $line['speaker'] === 'Asesor' ? 'advisor' : 'client';
                                                                    @endphp
                                                                    <div class="crm-speech-line crm-speech-line--{{ $speakerTone }} rounded-xl border px-3 py-2.5">
                                                                        <div class="flex items-start gap-3">
                                                                            <span class="crm-speech-line__speaker inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.14em]">
                                                                                {{ $line['speaker'] }}
                                                                            </span>
                                                                            <p class="min-w-0 text-sm leading-6 text-slate-700">{{ $line['text'] }}</p>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- SCRIPTS JS --}}
    <style>
        .crm-executive-metrics {
            display: flex;
            flex-direction: column;
            gap: 14px;
            align-items: stretch;
        }

        .crm-executive-metrics__sales,
        .crm-executive-metrics__profile {
            display: flex;
            justify-content: center;
        }

        .crm-executive-metrics__middle {
            display: flex;
            flex-wrap: wrap;
            align-items: stretch;
            justify-content: center;
            gap: 90px;
            min-width: 0;
        }

        .crm-executive-metrics__middle-shell {
            flex: 0.50 1 220px;
            min-width: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #ffffff;
            padding: 14px 22px;
        }

        .crm-executive-metrics__weekly-board {
            display: flex;
            flex-wrap: wrap;
            align-items: flex-end;
            justify-content: center;
            gap: 50px;
            flex: 0 0 auto;
        }

        .crm-executive-metrics__weekly-item {
            display: flex;
            justify-content: center;
            flex: 0 0 94px;
        }

        .crm-executive-metrics__hero-value {
            font-size: 34px;
            font-weight: 700;
            line-height: 1;
            color: #0f172a;
        }

        .crm-executive-metrics__weekly-value {
            font-size: 28px;
            font-weight: 700;
            line-height: 1;
            color: #0f172a;
        }

        .crm-executive-metrics__gauge-label {
            margin-top: 4px;
            font-size: 10px;
            font-weight: 500;
            line-height: 1.35;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: #64748b;
        }

        .crm-executive-metrics__gauge-label--weekly {
            margin-top: 3px;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.2;
            text-transform: none;
            letter-spacing: 0.02em;
        }

        @media (min-width: 1280px) {
            .crm-executive-metrics {
                display: grid;
                grid-template-columns: 170px minmax(0, 1fr) 130px;
                align-items: center;
            }

            .crm-executive-metrics__middle {
                flex-wrap: nowrap;
                justify-content: center;
            }

            .crm-executive-metrics__middle-shell {
                min-height: 88px;
            }

            .crm-executive-metrics__weekly-board {
                flex-wrap: nowrap;
                justify-content: flex-start;
            }

            .status-gauge-board {
                gap: 64px;
            }
        }

        .crm-work-top-panel,
        .status-gauge-board,
        .status-gauge-board__item,
        .sales-gauge,
        .sales-gauge__arc,
        .sales-gauge__body {
            position: relative;
            z-index: 0;
        }

        .crm-work-top-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }

        .sales-gauge {
            width: 110px;
        }

        .sales-gauge__arc {
            height: 56px;
            overflow: hidden;
        }

        .sales-gauge__arc::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 9999px 9999px 0 0;
            background: conic-gradient(from 180deg, #22c7f2 0deg, #3b82f6 92deg, #7c3aed 144deg, #d946ef 180deg);
        }

        .sales-gauge__arc::after {
            content: '';
            position: absolute;
            left: 13px;
            right: 13px;
            top: 13px;
            bottom: 0;
            border-radius: 9999px 9999px 0 0;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.95), rgba(15, 23, 42, 1));
        }

        .sales-gauge__needle {
            position: absolute;
            left: 50%;
            bottom: 0;
            width: 2px;
            height: 44px;
            border-radius: 9999px;
            background: linear-gradient(180deg, #ffffff, #bfdbfe);
            transform-origin: bottom center;
            transform: translateX(-50%) rotate(-90deg);
            transition: transform 1.1s cubic-bezier(0.22, 1, 0.36, 1);
            z-index: 1;
        }

        .sales-gauge.is-ready .sales-gauge__needle {
            transform: translateX(-50%) rotate(var(--gauge-angle, -90deg));
        }

        .sales-gauge__center {
            position: absolute;
            left: 50%;
            bottom: -2px;
            width: 13px;
            height: 13px;
            border-radius: 9999px;
            background: #ffffff;
            border: 3px solid #0f172a;
            transform: translateX(-50%);
            z-index: 1;
        }

        .sales-gauge__body {
            margin-top: 2px;
            text-align: center;
        }

        .sales-gauge--mini {
            width: 90px;
        }

        .sales-gauge--weekly {
            width: 94px;
        }

        .sales-gauge--weekly .sales-gauge__arc {
            height: px;
        }

        .sales-gauge--mini .sales-gauge__arc {
            height: 50px;
        }

        .sales-gauge--mini .sales-gauge__arc::after {
            left: 9px;
            right: 9px;
            top: 9px;
        }

        .sales-gauge--mini .sales-gauge__needle {
            height: 30px;
        }

        .sales-gauge--mini .sales-gauge__center {
            bottom: -1px;
            width: 10px;
            height: 10px;
            border-width: 2px;
        }

        .sales-gauge--mini .sales-gauge__body {
            margin-top: 0;
        }

        .crm-reference-card {
            border-color: color-mix(in srgb, var(--crm-secondary) 24%, #cbd5e1);
            background: linear-gradient(180deg, color-mix(in srgb, var(--crm-primary) 3%, #ffffff), #ffffff);
        }

        .crm-reference-card__toggle--idle {
            background: transparent;
        }

        .crm-reference-card__toggle--active {
            background: linear-gradient(135deg, color-mix(in srgb, var(--crm-primary) 10%, #ffffff), color-mix(in srgb, var(--crm-secondary) 14%, #ffffff));
        }

        .crm-reference-card__label {
            color: color-mix(in srgb, var(--crm-primary) 72%, #475569);
        }

        .crm-reference-card__title,
        .crm-reference-card__summary {
            color: #0f172a;
        }

        .crm-reference-card__chevron {
            border-color: color-mix(in srgb, var(--crm-secondary) 34%, #cbd5e1);
            background: #ffffff;
            color: color-mix(in srgb, var(--crm-primary) 74%, var(--crm-secondary));
        }

        .crm-reference-card__panel {
            border-color: color-mix(in srgb, var(--crm-secondary) 24%, #e2e8f0);
            background: linear-gradient(180deg, color-mix(in srgb, var(--crm-secondary) 7%, #ffffff), #ffffff);
        }

        .crm-reference-card__inner {
            border-color: color-mix(in srgb, var(--crm-primary) 20%, #dbeafe);
            background: rgba(255, 255, 255, 0.94);
        }

        .crm-reference-card__closing {
            color: #334155;
        }

        .crm-speech-case-button {
            border-color: #dbe2ea;
            background: #ffffff;
            color: #334155;
        }

        .crm-speech-case-button--idle:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
        }

        .crm-speech-case-button--active[data-tone="emerald"] {
            border-color: #a7f3d0;
            background: #ecfdf5;
            color: #047857;
        }

        .crm-speech-case-button--active[data-tone="amber"] {
            border-color: #fcd34d;
            background: #fffbeb;
            color: #b45309;
        }

        .crm-speech-case-button--active[data-tone="slate"] {
            border-color: #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
        }

        .crm-speech-summary {
            background: linear-gradient(180deg, #ffffff, #f8fafc);
        }

        .crm-speech-summary--emerald {
            border-color: #a7f3d0;
            background: linear-gradient(180deg, #f0fdf4, #ffffff);
        }

        .crm-speech-summary--amber {
            border-color: #fde68a;
            background: linear-gradient(180deg, #fffbeb, #ffffff);
        }

        .crm-speech-summary--slate {
            border-color: #cbd5e1;
            background: linear-gradient(180deg, #f8fafc, #ffffff);
        }

        .crm-speech-summary__pill {
            background: rgba(255, 255, 255, 0.78);
            color: #0f172a;
        }

        .crm-speech-summary--emerald .crm-speech-summary__pill {
            color: #047857;
        }

        .crm-speech-summary--amber .crm-speech-summary__pill {
            color: #b45309;
        }

        .crm-speech-summary--slate .crm-speech-summary__pill {
            color: #0f172a;
        }

        .crm-speech-line {
            background: rgba(255, 255, 255, 0.9);
        }

        .crm-speech-line--advisor {
            border-color: #bfdbfe;
            background: linear-gradient(180deg, #eff6ff, #ffffff);
        }

        .crm-speech-line--client {
            border-color: #e2e8f0;
            background: linear-gradient(180deg, #f8fafc, #ffffff);
        }

        .crm-speech-line__speaker {
            background: rgba(255, 255, 255, 0.86);
        }

        .crm-speech-line--advisor .crm-speech-line__speaker {
            color: #1d4ed8;
        }

        .crm-speech-line--client .crm-speech-line__speaker {
            color: #475569;
        }

        .crm-reference-pill {
            background: color-mix(in srgb, var(--crm-secondary) 12%, #ffffff);
            color: color-mix(in srgb, var(--crm-primary) 74%, #0f172a);
        }

        .crm-promo-card {
            border-color: color-mix(in srgb, var(--crm-secondary) 28%, #e9d5ff);
            background: linear-gradient(180deg, color-mix(in srgb, var(--crm-primary) 3%, #ffffff), #ffffff);
        }

        .crm-promo-card:hover,
        .crm-promo-card:focus {
            border-color: color-mix(in srgb, var(--crm-secondary) 48%, var(--crm-primary));
            background: linear-gradient(135deg, color-mix(in srgb, var(--crm-primary) 6%, #ffffff), color-mix(in srgb, var(--crm-secondary) 10%, #ffffff));
        }

        .crm-promo-card__badge {
            color: color-mix(in srgb, var(--crm-secondary) 72%, var(--crm-primary));
        }

        .crm-promo-card__title {
            color: #0f172a;
        }

        .crm-promo-card__hint {
            color: #64748b;
        }

        .crm-promo-card__action {
            border-color: color-mix(in srgb, var(--crm-secondary) 30%, #d8b4fe);
            color: color-mix(in srgb, var(--crm-secondary) 78%, var(--crm-primary));
            background: color-mix(in srgb, var(--crm-primary) 4%, #ffffff);
        }

        .crm-promo-card:hover .crm-promo-card__action,
        .crm-promo-card:focus .crm-promo-card__action {
            border-color: color-mix(in srgb, var(--crm-secondary) 50%, var(--crm-primary));
            background: color-mix(in srgb, var(--crm-primary) 10%, #ffffff);
        }

        .crm-executive-metrics__middle-shell .status-gauge-board {
            display: grid;
            grid-template-columns: repeat(2, minmax(200px, 140px));
            align-items: end;
            justify-content: center;
            gap: 18px;
            width: auto;
            margin: 0 auto;
        }

        .crm-executive-metrics__middle-shell .status-gauge-board__item {
            display: flex;
            width: 100%;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            min-width: 0;
        }

        .status-gauge-board__value {
            font-size: 19px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1;
        }

        .status-gauge-board__label {
            text-align: center;
            font-size: 12px;
            font-weight: 500;
            color: #475569;
            line-height: 1.25;
        }

        body.crm-dark-theme .crm-reference-card {
            border-color: rgba(148, 163, 184, 0.16);
            background: linear-gradient(180deg, rgba(17, 28, 52, 0.96), rgba(11, 19, 35, 0.98));
        }

        body.crm-dark-theme .crm-reference-card__toggle--active {
            background: linear-gradient(135deg, color-mix(in srgb, var(--crm-primary) 18%, #0f172a), color-mix(in srgb, var(--crm-secondary) 22%, #0f172a));
        }

        body.crm-dark-theme .crm-reference-card__title,
        body.crm-dark-theme .crm-reference-card__summary,
        body.crm-dark-theme .crm-reference-card__closing {
            color: #f8fbff;
        }

        body.crm-dark-theme .crm-reference-card__chevron {
            border-color: rgba(148, 163, 184, 0.16);
            background: rgba(8, 17, 34, 0.96);
            color: #f8fbff;
        }

        body.crm-dark-theme .crm-reference-card__panel {
            border-color: rgba(148, 163, 184, 0.12);
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.7), rgba(8, 17, 34, 0.9));
        }

        body.crm-dark-theme .crm-reference-card__inner {
            border-color: rgba(148, 163, 184, 0.16);
            background: rgba(10, 18, 34, 0.95);
        }

        body.crm-dark-theme .crm-speech-case-button {
            border-color: rgba(148, 163, 184, 0.18);
            background: rgba(15, 23, 42, 0.64);
            color: #e2e8f0;
        }

        body.crm-dark-theme .crm-speech-case-button--idle:hover {
            background: rgba(30, 41, 59, 0.9);
        }

        body.crm-dark-theme .crm-speech-case-button--active[data-tone="emerald"] {
            border-color: rgba(16, 185, 129, 0.45);
            background: rgba(6, 78, 59, 0.72);
            color: #d1fae5;
        }

        body.crm-dark-theme .crm-speech-case-button--active[data-tone="amber"] {
            border-color: rgba(245, 158, 11, 0.44);
            background: rgba(120, 53, 15, 0.78);
            color: #fde68a;
        }

        body.crm-dark-theme .crm-speech-case-button--active[data-tone="slate"] {
            border-color: rgba(148, 163, 184, 0.24);
            background: rgba(30, 41, 59, 0.92);
            color: #f8fafc;
        }

        body.crm-dark-theme .crm-speech-summary {
            border-color: rgba(148, 163, 184, 0.14);
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.72), rgba(8, 17, 34, 0.9));
        }

        body.crm-dark-theme .crm-speech-summary--emerald {
            border-color: rgba(16, 185, 129, 0.3);
            background: linear-gradient(180deg, rgba(6, 78, 59, 0.5), rgba(8, 17, 34, 0.92));
        }

        body.crm-dark-theme .crm-speech-summary--amber {
            border-color: rgba(245, 158, 11, 0.34);
            background: linear-gradient(180deg, rgba(120, 53, 15, 0.46), rgba(8, 17, 34, 0.92));
        }

        body.crm-dark-theme .crm-speech-summary--slate {
            border-color: rgba(148, 163, 184, 0.18);
            background: linear-gradient(180deg, rgba(30, 41, 59, 0.86), rgba(8, 17, 34, 0.92));
        }

        body.crm-dark-theme .crm-speech-summary__pill {
            background: rgba(255, 255, 255, 0.08);
            color: #f8fafc;
        }

        body.crm-dark-theme .crm-speech-line {
            background: rgba(8, 17, 34, 0.92);
        }

        body.crm-dark-theme .crm-speech-line--advisor {
            border-color: rgba(96, 165, 250, 0.34);
            background: linear-gradient(180deg, rgba(30, 41, 59, 0.92), rgba(8, 17, 34, 0.96));
        }

        body.crm-dark-theme .crm-speech-line--client {
            border-color: rgba(148, 163, 184, 0.18);
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.88), rgba(8, 17, 34, 0.96));
        }

        body.crm-dark-theme .crm-speech-line__speaker {
            background: rgba(255, 255, 255, 0.06);
        }

        body.crm-dark-theme .crm-speech-line--advisor .crm-speech-line__speaker {
            color: #bfdbfe;
        }

        body.crm-dark-theme .crm-speech-line--client .crm-speech-line__speaker {
            color: #cbd5e1;
        }

        body.crm-dark-theme .crm-executive-metrics__daily-panel {
            border-color: rgba(148, 163, 184, 0.16);
            background: rgba(15, 23, 42, 0.68);
        }

        body.crm-dark-theme .crm-executive-metrics__hero-value,
        body.crm-dark-theme .crm-executive-metrics__weekly-value {
            color: #f8fbff;
        }

        body.crm-dark-theme .crm-executive-metrics__gauge-label {
            color: #dbeafe;
        }

        body.crm-dark-theme .crm-reference-pill {
            background: rgba(255, 255, 255, 0.08);
            color: #dbeafe;
        }

        body.crm-dark-theme .status-gauge-board__value {
            color: #f8fbff;
        }

        body.crm-dark-theme .status-gauge-board__label {
            color: #dbeafe;
        }
    </style>

    @if($lead)
        @include('partials.agreement-modal')
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const specificOptions = @json($specificOptions);
            const enableCommercial = @json($enableCommercial);
            const oldGeneralStatus = @json(old('general_status'));
            const oldSpecificStatus = @json(old('specific_status'));
            const serverNowIso = @json($serverNowIso);
            const shouldOpenAgreementModal = @json($shouldOpenAgreementModal ?? false);
            const isFocusedLeadMode = @json($isFocusedLeadMode ?? false);
            const agreementModalExitUrl = @json($agreementModalExitUrl ?? route('work.show'));
            const agreementLocked = @json($agreementLocked ?? false);
            const isFixedOnlyAgreement = @json($isFixedOnlyAgreement ?? false);
            const requiresFixedAgreementSupport = @json($requiresFixedAgreementSupport ?? false);

            const callForm = document.getElementById('callForm');
            const generalSel = document.getElementById('general_status');
            const specificSel = document.getElementById('specific_status');
            const notes = document.getElementById('notes');
            const contactNameInput = document.getElementById('contact_name');
            const contactPhoneInput = document.getElementById('contact_phone');
            const commercialBlock = document.getElementById('commercialBlock');
            const rescheduleBlock = document.getElementById('rescheduleBlock');
            const nextContactAtInput = document.getElementById('next_contact_at');
            const btn = document.getElementById('btnRegistrar');
            const agreementShortcutButton = document.getElementById('btnAgreementShortcut');
            const submitIntentInput = document.getElementById('submit_intent');
            const agreementModal = document.getElementById('agreementModal');
            const agreementBackdrop = document.getElementById('agreementModalBackdrop');
            const closeAgreementModalButton = document.getElementById('closeAgreementModal');
            const cancelAgreementModalButton = document.getElementById('cancelAgreementModal');
            const agreementForm = document.getElementById('agreementForm');
            const agreementSubmitButton = document.getElementById('agreementSubmitButton');
            const agreementRequiredInputs = agreementForm ? agreementForm.querySelectorAll('.agreement-required') : [];
            const agreementExclusiveInputs = agreementForm ? agreementForm.querySelectorAll('.agreement-exclusive') : [];

            // Referencias dinámicas
            const mobileSection = document.getElementById('mobileSection');
            const mobileModeSel = document.getElementById('mobile_mode');
            const portabilityBlock = document.getElementById('portabilityBlock');
            const newLinesBlock = document.getElementById('newLinesBlock');
            const fixedBlock = document.getElementById('fixedBlock');
            const portabilityRows = document.getElementById('portabilityRows');
            const newRows = document.getElementById('newRows');
            const addPortabilityRowBtn = document.getElementById('addPortabilityRow');
            const addNewRowBtn = document.getElementById('addNewRow');
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

                document.querySelectorAll('[data-phone-select]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const phone = (button.dataset.phoneSelect || '').replace(/\D/g, '').slice(0, 9);
                        contactPhoneInput.value = phone;
                        contactPhoneInput.dispatchEvent(new Event('input', { bubbles: true }));
                        contactPhoneInput.focus();
                    });
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

            agreementForm?.querySelector('input[name="customer_phone"]')?.addEventListener('input', (event) => {
                event.target.value = event.target.value.replace(/\D/g, '').slice(0, 9);
            });

            ['customer_ruc', 'customer_dni'].forEach((fieldName) => {
                agreementForm?.querySelector(`input[name="${fieldName}"]`)?.addEventListener('input', (event) => {
                    event.target.value = event.target.value.replace(/\D/g, '');
                });
            });

            agreementForm?.querySelectorAll('[data-digits-only]').forEach((input) => {
                const limit = Number(input.dataset.digitsOnly || 0);

                input.addEventListener('input', (event) => {
                    event.target.value = event.target.value.replace(/\D/g, '').slice(0, limit || undefined);
                });
            });

            agreementForm?.querySelector('input[name="customer_representative_name"]')?.addEventListener('input', (event) => {
                event.target.value = event.target.value
                    .replace(/[^\p{L}\s'.-]/gu, '')
                    .replace(/\s{2,}/g, ' ')
                    .slice(0, 255);
            });

            if (!generalSel || !specificSel) return; 

            function resetSpecific() {
                specificSel.innerHTML = '<option value="">-- Selecciona --</option>';
                specificSel.disabled = true;
            }

            function fillSpecific(general) {
                resetSpecific();
                if (!general || !specificOptions[general]) return;

                for (const [val, label] of Object.entries(specificOptions[general])) {
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = label;
                    specificSel.appendChild(opt);
                }
                specificSel.disabled = false; 
            }
            /*
             * LÓGICA DE HABILITACIÓN COMERCIAL: 
             * Revisa si el Estado General es "Contactado" y si el Estado Específico forma parte de
             * los estados permitidos que vienen desde Laravel ($enableCommercial)
             */
            function shouldEnableCommercial() {
                return (generalSel.value === 'contactado' && enableCommercial.includes(specificSel.value));
            }

            function shouldEnableReschedule() {
                return generalSel.value === 'contactado' && specificSel.value === 'reprogramado';
            }

            function shouldShowAgreementShortcut() {
                return generalSel.value === 'contactado'
                    && specificSel.value === 'negociacion';
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

            function dismissAgreementModal() {
                if (isFocusedLeadMode) {
                    window.location.href = agreementModalExitUrl;
                    return;
                }

                closeAgreementModal();
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

                    if (input.type === 'email') {
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
                            <input type="number" name="${lineName}" class="${lineInputClass}" min="1" max="999">
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

            function updateDisplay() {
                rescheduleBlock.classList.toggle('hidden', !shouldEnableReschedule());

                if (!shouldEnableCommercial()) {
                    commercialBlock.classList.add('hidden');
                } else {
                    commercialBlock.classList.remove('hidden');

                    const channel = document.querySelector('input[name="channel"]:checked')?.value;
                    const mobileMode = mobileModeSel.value; // <-- Ahora lee directamente del select

                    mobileSection.classList.toggle('hidden', !(channel === 'movil' || channel === 'movil_fijo'));
                    fixedBlock.classList.toggle('hidden', !(channel === 'fijo' || channel === 'movil_fijo'));

                    if (channel === 'movil' || channel === 'movil_fijo') {
                        portabilityBlock.classList.toggle('hidden', !(mobileMode === 'portabilidad' || mobileMode === 'porta_alta'));
                        newLinesBlock.classList.toggle('hidden', !(mobileMode === 'alta_nueva' || mobileMode === 'porta_alta'));
                        ensureAtLeastOneRow(portabilityRows, 'portability');
                        ensureAtLeastOneRow(newRows, 'new');
                        refreshRemoveButtons(portabilityRows);
                        refreshRemoveButtons(newRows);
                        refreshOfferPromotionOptions(portabilityRows);
                        refreshOfferPromotionOptions(newRows);
                    } else {
                        portabilityBlock.classList.add('hidden');
                        newLinesBlock.classList.add('hidden');
                    }
                }

                if (agreementShortcutButton) {
                    agreementShortcutButton.classList.toggle('hidden', !shouldShowAgreementShortcut());
                }
            }

            /*
             * LÓGICA DE VALIDACIÓN DEL FORMULARIO:
             * Valida a tiempo real todos los campos para determinar si habilita o deshabilita el botón "Registrar".
             */
            function validateForm() {
                const g = generalSel.value;
                const s = specificSel.value;
                const n = (notes.value || '').trim();
                const cName = (document.getElementById('contact_name').value || '').trim();
                const cPhone = (document.getElementById('contact_phone').value || '').trim();
                //let ok = !!g && !!s && n.length > 0;
                let ok = !!g && !!s && n.length > 0 && cName.length > 0 && cPhone.length > 0;

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
                        const mobileMode = mobileModeSel.value; // <-- Lee del selectt
                        ok = ok && mobileMode !== ""; // Valida que no esté en "-- Selecciona --"

                        if (mobileMode === 'portabilidad' || mobileMode === 'porta_alta') {
                            ok = ok && isOfferSectionValid(portabilityRows, 'portability_lines[]', 'portability_promotion_name[]');
                        }
                        if (mobileMode === 'alta_nueva' || mobileMode === 'porta_alta') {
                            ok = ok && isOfferSectionValid(newRows, 'new_lines[]', 'new_promotion_name[]');
                        }
                    }

                    if (channel === 'fijo' || channel === 'movil_fijo') {
                        ok = ok && document.querySelector('input[name="internet_speed"]').value.trim().length > 0;
                        ok = ok && !!document.querySelector('input[name="fixed_monthly"]').value;
                    }
                }

                btn.disabled = !ok;
                btn.classList.toggle('opacity-50', !ok);
                btn.classList.toggle('cursor-not-allowed', !ok);

                if (agreementShortcutButton) {
                    agreementShortcutButton.disabled = agreementLocked || !ok || !shouldShowAgreementShortcut();
                }
            }

            // Listeners principales
            generalSel.addEventListener('change', () => {
                fillSpecific(generalSel.value);
                // Limpiar solo el canal y el select
                document.querySelectorAll('input[name="channel"]').forEach(r => r.checked = false);
                mobileModeSel.value = ""; 
                updateDisplay();
                validateForm();
            });

            specificSel.addEventListener('change', () => {
                updateDisplay();
                validateForm();
            });

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
                if (event.target.matches('select[name="portability_promotion_name[]"], select[name="new_promotion_name[]"]')) {
                    const currentSelect = event.target;
                    const container = currentSelect.closest('#portabilityRows, #newRows');
                    const selectedValue = (currentSelect.value || '').trim();

                    if (container && selectedValue) {
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

                        refreshOfferPromotionOptions(container);
                    }
                }

                updateDisplay();
                validateForm();
            });

            callForm?.addEventListener('input', validateForm);
            callForm?.addEventListener('submit', (event) => {
                if (!ensureUniqueOfferPromotions(true)) {
                    event.preventDefault();
                    return;
                }

                if (submitIntentInput && submitIntentInput.value !== 'agreement_shortcut') {
                    submitIntentInput.value = 'register';
                }
            });

            agreementShortcutButton?.addEventListener('click', () => {
                if (!callForm || !submitIntentInput) {
                    return;
                }

                submitIntentInput.value = 'agreement_shortcut';
                callForm.requestSubmit();
            });

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

            closeAgreementModalButton?.addEventListener('click', dismissAgreementModal);
            cancelAgreementModalButton?.addEventListener('click', dismissAgreementModal);
            agreementBackdrop?.addEventListener('click', dismissAgreementModal);

            // Inicializar por si hay datos cacheados
            resetSpecific();
            if (oldGeneralStatus) {
                fillSpecific(oldGeneralStatus);
                generalSel.value = oldGeneralStatus;
            }
            if (oldSpecificStatus && !specificSel.disabled) {
                specificSel.value = oldSpecificStatus;
            }
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

            if (shouldOpenAgreementModal) {
                openAgreementModal();
            }

            document.querySelectorAll('[data-gauge-angle]').forEach((gauge) => {
                const angle = Number(gauge.dataset.gaugeAngle || -90);
                gauge.style.setProperty('--gauge-angle', `${angle}deg`);

                window.requestAnimationFrame(() => {
                    gauge.classList.add('is-ready');
                });
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && agreementModal && !agreementModal.classList.contains('hidden')) {
                    dismissAgreementModal();
                }
            });
        });
    </script>

    @if($lead)
        @include('partials.executive-tmo-tracker', [
            'leadId' => $lead->id,
            'moduleName' => 'a_negociar',
            'routeName' => 'work.show',
        ])
    @endif
</x-app-layout>
