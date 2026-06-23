<x-app-layout>
    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @php
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
                $isFixedOnlyAgreement = $sale->product_type === 'fijo';
                $currentServiceChannel = old('service_channel', $sale->service_channel);
                $showApprovalCode = !$isFixedOnlyAgreement && $currentServiceChannel === 'centralizado';
                $requiresFixedAgreementSupport = in_array($sale->product_type, ['fijo', 'movil_fijo'], true);
                $keptAttachmentPaths = collect(old('kept_attachment_paths', $sale->attachment_paths ?? []))->values();
                $splitHistoryList = function (?string $value, string $separator) {
                    return collect(explode($separator, (string) $value))
                        ->map(fn ($item) => trim((string) $item))
                        ->filter()
                        ->values();
                };
                $summarizeAttachmentItems = function ($items) {
                    $items = collect($items)->filter()->values();

                    if ($items->isEmpty()) {
                        return 'Sin archivos';
                    }

                    $extensions = $items
                        ->map(fn ($item) => strtolower((string) pathinfo($item, PATHINFO_EXTENSION)))
                        ->filter()
                        ->countBy()
                        ->map(fn ($count, $extension) => $count.' '.strtoupper($extension))
                        ->values()
                        ->implode(' | ');

                    return $items->count().' archivo(s)'.($extensions !== '' ? ' | '.$extensions : '');
                };
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
            @endphp

            @if(session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ implode(' | ', $errors->all()) }}
                </div>
            @endif

            <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                <div class="crm-panel-hero border-b border-slate-200 px-5 py-6 text-white sm:px-6">
                    <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                        <div class="max-w-3xl">
                            <p class="text-sm font-medium uppercase tracking-[0.22em] text-white/75">Supervisor</p>
                            <h1 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">Validación de acuerdo</h1>
                            <p class="mt-2 max-w-2xl text-sm text-white/80">
                                Revisa la ficha final enviada por el ejecutivo, ajusta la información si hace falta y valida el caso para que continúe con el flujo operativo.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 xl:min-w-[420px]">
                            <div class="min-w-0 rounded-2xl border border-white/10 bg-white/10 px-3 py-2.5 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.18em] text-white/70">RUC</div>
                                <div class="mt-1.5 break-words text-sm font-semibold leading-5">{{ $sale->customer_ruc }}</div>
                            </div>
                            <div class="min-w-0 rounded-2xl border border-white/10 bg-white/10 px-3 py-2.5 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.18em] text-white/70">Ejecutivo</div>
                                <div class="mt-1.5 break-words text-sm font-semibold leading-5">{{ $sale->executive->name ?? '-' }}</div>
                            </div>
                            <div class="min-w-0 rounded-2xl border border-white/10 bg-white/10 px-3 py-2.5 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.18em] text-white/70">Campaña</div>
                                <div class="mt-1.5 break-words text-sm font-semibold leading-5">{{ $sale->campaign->name ?? '-' }}</div>
                            </div>
                            <div class="min-w-0 rounded-2xl border border-white/10 bg-white/10 px-3 py-2.5 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.18em] text-white/70">Estado</div>
                                <div class="mt-1.5 break-words text-sm font-semibold leading-5">{{ $sale->supervisor_validation_status === 'validado' ? 'Validado' : 'Pendiente' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-5 lg:p-6">
                    <div class="mb-4 flex justify-end">
                        <a href="{{ route('supervisor.agreements.index') }}"
                           class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-3.5 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                            Volver
                        </a>
                    </div>

                    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(340px,0.8fr)] xl:items-start">
                        <div class="space-y-4">
                            <div class="rounded-2xl border border-blue-100 bg-gradient-to-r from-blue-50 via-white to-slate-50 p-4">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <h2 class="text-lg font-semibold text-slate-900">Datos operativos clave</h2>
                                    </div>
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-blue-700">
                                        Actualizable por supervisor
                                    </span>
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-2 lg:grid-cols-6">
                                    <div class="rounded-2xl border border-white bg-white px-3 py-2.5 shadow-sm">
                                        <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Plano</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $sale->plan_code ?: '-' }}</div>
                                    </div>
                                    <div id="approvalCodeSummaryCard" class="rounded-2xl border border-white bg-white px-3 py-2.5 shadow-sm {{ $showApprovalCode ? '' : 'hidden' }}">
                                        <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Aprobación</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $sale->approval_code ?: '-' }}</div>
                                    </div>
                                    <div class="rounded-2xl border border-white bg-white px-3 py-2.5 shadow-sm">
                                        <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $isFixedOnlyAgreement ? 'Producto' : 'Fecha' }}</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $isFixedOnlyAgreement ? 'Fijo' : (optional($sale->attention_date)->format('d/m/Y') ?: '-') }}</div>
                                    </div>
                                    <div class="rounded-2xl border border-white bg-white px-3 py-2.5 shadow-sm">
                                        <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $isFixedOnlyAgreement ? 'Soportes' : 'Operador' }}</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $isFixedOnlyAgreement ? max(count($sale->fixed_agreement_supports ?? []), 0) : ($sale->operator_name ?: '-') }}</div>
                                    </div>
                                    <div class="rounded-2xl border border-white bg-white px-3 py-2.5 shadow-sm">
                                        <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Líneas</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ $sale->offered_line_count ?? 0 }}</div>
                                    </div>
                                    <div class="rounded-2xl border border-white bg-white px-3 py-2.5 shadow-sm">
                                        <div class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Adjuntos</div>
                                        <div class="mt-1 text-sm font-semibold text-slate-900">{{ count($sale->attachment_paths ?? []) }}</div>
                                    </div>
                                </div>
                            </div>

                            @if($executiveFeedbackInteraction)
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3.5">
                                    <div class="mb-2.5">
                                        <h2 class="text-lg font-semibold text-slate-900">Feedback del ejecutivo</h2>
                                        <p class="mt-1 text-sm text-slate-500">
                                            Gestión registrada por {{ $executiveFeedbackInteraction->user->name ?? ($sale->executive->name ?? 'Ejecutivo') }}
                                            el {{ optional($executiveFeedbackInteraction->created_at)->format('d/m/Y H:i') ?: '-' }}.
                                        </p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-white px-3 py-3 text-sm leading-6 text-slate-700">
                                        {{ $executiveFeedbackInteraction->call_detail }}
                                    </div>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('supervisor.agreements.update', $sale) }}" class="space-y-4" id="agreementUpdateForm" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="validate_after_save" id="validateAfterSaveInput" value="0">

                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3.5">
                                    <div class="mb-2.5">
                                        <h2 class="text-lg font-semibold text-slate-900">Datos del cliente</h2>
                                    </div>

                                    <div class="grid grid-cols-1 gap-2.5 md:grid-cols-2">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700">RUC</label>
                                            <input type="text" name="customer_ruc" value="{{ old('customer_ruc', $sale->customer_ruc) }}" class="mt-1 block w-full rounded-xl border-gray-300" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700">Razón social</label>
                                            <input type="text" name="customer_business_name" value="{{ old('customer_business_name', $sale->customer_business_name) }}" class="mt-1 block w-full rounded-xl border-gray-300" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700">DNI</label>
                                            <input type="text" name="customer_dni" value="{{ old('customer_dni', $sale->customer_dni) }}" class="mt-1 block w-full rounded-xl border-gray-300" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-amber-900">Nombre completo del representante autorizado</label>
                                            <input type="text" name="customer_representative_name" value="{{ old('customer_representative_name', $sale->customer_representative_name) }}" class="mt-1 block w-full rounded-xl border-gray-300" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-amber-900">Celular del representante autorizado</label>
                                            <input type="text" name="customer_phone" value="{{ old('customer_phone', $sale->customer_phone) }}" class="mt-1 block w-full rounded-xl border-gray-300" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-amber-900">Correo electrónico del representante autorizado</label>
                                            <input type="email" name="customer_email" value="{{ old('customer_email', $sale->customer_email) }}" class="mt-1 block w-full rounded-xl border-gray-300" required>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-slate-700">Dirección</label>
                                            <input type="text" name="customer_address" value="{{ old('customer_address', $sale->customer_address) }}" class="mt-1 block w-full rounded-xl border-gray-300" required>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-slate-700">Coordenadas</label>
                                            <input type="text" name="customer_coordinates" value="{{ old('customer_coordinates', $sale->customer_coordinates) }}" class="mt-1 block w-full rounded-xl border-gray-300" required>
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-slate-700">Plano</label>
                                            <input type="text" name="plan_code" value="{{ old('plan_code', $sale->plan_code) }}" class="mt-1 block w-full rounded-xl border-gray-300" required>
                                        </div>
                                        <div id="approvalCodeFieldWrapper" class="md:col-span-2 {{ $showApprovalCode ? '' : 'hidden' }}">
                                            <label class="block text-sm font-medium text-slate-700">Código de aprobación</label>
                                            <input
                                                type="text"
                                                name="approval_code"
                                                id="approvalCodeInput"
                                                value="{{ old('approval_code', $sale->approval_code) }}"
                                                class="mt-1 block w-full rounded-xl border-gray-300"
                                                maxlength="120"
                                                autocomplete="off"
                                                placeholder="Código asignado al validar"
                                            >
                                            <p class="mt-1 text-xs text-slate-500">El supervisor debe completarlo antes de marcar el acuerdo como validado.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-slate-200 p-3.5">
                                    <div class="mb-3">
                                        <h2 class="text-lg font-semibold text-slate-900">Condiciones operativas</h2>
                                        <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5">
                                                <span class="font-medium text-slate-700">Resumen actual</span>
                                                <span><span class="font-semibold text-slate-900">Plano:</span> {{ $sale->plan_code ?: '-' }}</span>
                                                <span><span class="font-semibold text-slate-900">Líneas:</span> {{ $sale->offered_line_count ?? 0 }}</span>
                                                <span><span class="font-semibold text-slate-900">Adjuntos:</span> {{ count($sale->attachment_paths ?? []) }}</span>
                                                @if($requiresFixedAgreementSupport)
                                                    <span><span class="font-semibold text-slate-900">Soporte:</span> {{ collect($sale->fixed_agreement_supports ?? [])->map(fn ($item) => ($fixedAgreementSupportOptions ?? [])[$item] ?? $item)->implode(', ') ?: '-' }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-3">
                                        @if(!$isFixedOnlyAgreement)
                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                                <div class="text-sm font-medium text-slate-700">Canal y programación</div>
                                                <div class="mt-2.5 grid grid-cols-1 gap-2.5 md:grid-cols-2 xl:grid-cols-4">
                                                    <div>
                                                        <label class="block text-sm font-medium text-slate-700">Canal</label>
                                                        <select name="service_channel" id="serviceChannelInput" class="mt-1 block w-full rounded-xl border-gray-300" required>
                                                            <option value="">Selecciona</option>
                                                            @foreach($serviceChannels as $value => $label)
                                                                <option value="{{ $value }}" @selected(old('service_channel', $sale->service_channel) === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-slate-700">Franja de atención</label>
                                                        <select name="attention_time_slot" class="mt-1 block w-full rounded-xl border-gray-300" required>
                                                            <option value="">Selecciona</option>
                                                            @foreach($attentionSlots as $slot)
                                                                <option value="{{ $slot }}" @selected(old('attention_time_slot', $sale->attention_time_slot) === $slot)>{{ $slot }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-slate-700">Fecha</label>
                                                        <input type="date" name="attention_date" value="{{ old('attention_date', optional($sale->attention_date)->format('Y-m-d')) }}" class="mt-1 block w-full rounded-xl border-gray-300" required>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-slate-700">Operador</label>
                                                        <select name="operator_name" class="mt-1 block w-full rounded-xl border-gray-300" required>
                                                            <option value="">Selecciona</option>
                                                            @foreach(['Entel', 'Bitel', 'Claro', 'Movistar'] as $operator)
                                                                <option value="{{ $operator }}" @selected(old('operator_name', $sale->operator_name) === $operator)>{{ $operator }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                                <label class="block text-sm font-medium text-slate-700">Tipo de entrega</label>
                                                <select name="delivery_type" class="mt-1.5 block w-full rounded-xl border-gray-300" required>
                                                    <option value="">Selecciona</option>
                                                    @foreach($deliveryTypes as $value => $label)
                                                        <option value="{{ $value }}" @selected(old('delivery_type', $sale->delivery_type) === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <p class="mt-2 text-xs text-slate-500">Se usará en el seguimiento operativo y de validación.</p>
                                            </div>
                                        @endif

                                        @if($requiresFixedAgreementSupport)
                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
                                                <div class="text-sm font-medium text-slate-700">Soporte del acuerdo fija</div>
                                                <div class="mt-2.5 grid grid-cols-1 gap-2 md:grid-cols-2">
                                                    @foreach(($fixedAgreementSupportOptions ?? []) as $value => $label)
                                                        <label class="flex cursor-pointer items-start gap-2.5 rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700">
                                                            <input type="checkbox" name="fixed_agreement_supports[]" value="{{ $value }}" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" {{ in_array($value, old('fixed_agreement_supports', $sale->fixed_agreement_supports ?? []), true) ? 'checked' : '' }} onclick="if(this.checked){document.querySelectorAll('input[name=&quot;fixed_agreement_supports[]&quot;]').forEach(item => { if(item !== this) item.checked = false; });}">
                                                            <span>
                                                                <span class="block font-semibold text-slate-900">{{ $label }}</span>
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" class="inline-flex items-center justify-center rounded-full border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                                        Guardar cambios
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="space-y-4">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3.5">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h2 class="text-lg font-semibold text-slate-900">Productos ofrecidos</h2>
                                        <p class="mt-1 text-sm text-slate-500">El supervisor puede corregir promociones, líneas y datos de fija antes de validar.</p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">
                                        {{ count($sale->products_snapshot ?? []) }} producto{{ count($sale->products_snapshot ?? []) === 1 ? '' : 's' }}
                                    </span>
                                </div>

                                <div class="mt-3 space-y-2.5">
                                    @if(!empty($agreementEditData['portability_lines']) || !empty($agreementEditData['portability_promotion_name']))
                                        <div class="rounded-2xl border border-slate-200 bg-white p-3.5">
                                            <div class="mb-3 flex items-center justify-between gap-3">
                                                <div class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-600">Portabilidad</div>
                                                <button type="button" id="supervisorAddPortabilityRow" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                                    Agregar fila
                                                </button>
                                            </div>

                                            <div id="supervisorPortabilityRows" class="space-y-3">
                                                @foreach(($agreementEditData['portability_lines'] ?? []) as $index => $lineValue)
                                                    <div data-supervisor-offer-row data-supervisor-offer-key="portability-{{ $index }}" class="grid gap-3 md:grid-cols-[110px_minmax(0,1fr)_64px]">
                                                        <div>
                                                            <label class="block text-xs font-medium text-slate-700">Cant. líneas</label>
                                                            <input
                                                                type="number"
                                                                name="portability_lines[]"
                                                                value="{{ old('portability_lines.'.$index, $lineValue) }}"
                                                                class="mt-1 block w-full rounded-xl border-gray-300 text-sm"
                                                                min="1"
                                                                max="999"
                                                                step="1"
                                                                form="agreementUpdateForm"
                                                            >
                                                        </div>

                                                        <div>
                                                            <label class="block text-xs font-medium text-slate-700">Promoción</label>
                                                            <select
                                                                name="portability_promotion_name[]"
                                                                class="mt-1 block w-full rounded-xl border-gray-300 text-sm"
                                                                form="agreementUpdateForm"
                                                            >
                                                                <option value="">-- Selecciona --</option>
                                                                @foreach(($promotionNames ?? collect()) as $promotionName)
                                                                    <option value="{{ $promotionName->name }}" @selected(old('portability_promotion_name.'.$index, $agreementEditData['portability_promotion_name'][$index] ?? '') === $promotionName->name)>
                                                                        {{ $promotionName->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="md:pt-6">
                                                            <button type="button" data-remove-supervisor-offer-row aria-label="Quitar fila" title="Quitar fila" class="inline-flex h-[42px] w-full items-center justify-center rounded-xl border border-red-200 bg-red-50 px-0 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100">
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
                                                <div class="mt-3 text-sm text-red-600">
                                                    {{ $portabilityOfferError }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    @if(!empty($agreementEditData['new_lines']) || !empty($agreementEditData['new_promotion_name']))
                                        <div class="rounded-2xl border border-slate-200 bg-white p-3.5">
                                            <div class="mb-3 flex items-center justify-between gap-3">
                                                <div class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-600">Alta nueva</div>
                                                <button type="button" id="supervisorAddNewRow" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                                                    Agregar fila
                                                </button>
                                            </div>

                                            <div id="supervisorNewRows" class="space-y-3">
                                                @foreach(($agreementEditData['new_lines'] ?? []) as $index => $lineValue)
                                                    <div data-supervisor-offer-row data-supervisor-offer-key="new-{{ $index }}" class="grid gap-3 md:grid-cols-[110px_minmax(0,1fr)_64px]">
                                                        <div>
                                                            <label class="block text-xs font-medium text-slate-700">Cant. líneas</label>
                                                            <input
                                                                type="number"
                                                                name="new_lines[]"
                                                                value="{{ old('new_lines.'.$index, $lineValue) }}"
                                                                class="mt-1 block w-full rounded-xl border-gray-300 text-sm"
                                                                min="1"
                                                                max="999"
                                                                step="1"
                                                                form="agreementUpdateForm"
                                                            >
                                                        </div>

                                                        <div>
                                                            <label class="block text-xs font-medium text-slate-700">Promoción</label>
                                                            <select
                                                                name="new_promotion_name[]"
                                                                class="mt-1 block w-full rounded-xl border-gray-300 text-sm"
                                                                form="agreementUpdateForm"
                                                            >
                                                                <option value="">-- Selecciona --</option>
                                                                @foreach(($promotionNames ?? collect()) as $promotionName)
                                                                    <option value="{{ $promotionName->name }}" @selected(old('new_promotion_name.'.$index, $agreementEditData['new_promotion_name'][$index] ?? '') === $promotionName->name)>
                                                                        {{ $promotionName->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="md:pt-6">
                                                            <button type="button" data-remove-supervisor-offer-row aria-label="Quitar fila" title="Quitar fila" class="inline-flex h-[42px] w-full items-center justify-center rounded-xl border border-red-200 bg-red-50 px-0 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100">
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
                                                <div class="mt-3 text-sm text-red-600">
                                                    {{ $newOfferError }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    @if($agreementEditData['has_fixed'])
                                        <div class="rounded-2xl border border-slate-200 bg-white p-3.5">
                                            <div class="mb-3 flex items-center justify-between gap-3">
                                                <div class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-600">Fija</div>
                                            </div>

                                            <div class="grid gap-3 md:grid-cols-2">
                                                <div>
                                                    <label class="block text-xs font-medium text-slate-700">Velocidad</label>
                                                    <input
                                                        type="text"
                                                        name="internet_speed"
                                                        value="{{ old('internet_speed', $agreementEditData['internet_speed']) }}"
                                                        class="mt-1 block w-full rounded-xl border-gray-300 text-sm"
                                                        form="agreementUpdateForm"
                                                    >
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-medium text-slate-700">Mensualidad</label>
                                                    <input
                                                        type="number"
                                                        name="fixed_monthly"
                                                        value="{{ old('fixed_monthly', $agreementEditData['fixed_monthly']) }}"
                                                        class="mt-1 block w-full rounded-xl border-gray-300 text-sm"
                                                        min="0"
                                                        max="999999.99"
                                                        step="0.01"
                                                        form="agreementUpdateForm"
                                                    >
                                                </div>
                                            </div>

                                            @if($errors->has('internet_speed') || $errors->has('fixed_monthly'))
                                                <div class="mt-3 text-sm text-red-600">
                                                    {{ $errors->first('internet_speed') ?: $errors->first('fixed_monthly') }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    @if(empty($agreementEditData['portability_lines']) && empty($agreementEditData['new_lines']) && !$agreementEditData['has_fixed'])
                                        <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">
                                            No hay productos registrados en este acuerdo.
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-3 rounded-2xl border border-dashed border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700">
                                    <span class="font-semibold">Cantidad de líneas total:</span>
                                    <span id="supervisorOfferedLineCount">
                                        {{
                                            collect((array) old('portability_lines', $agreementEditData['portability_lines'] ?? []))->filter(fn ($value) => filled($value))->map(fn ($value) => (int) $value)->sum()
                                            + collect((array) old('new_lines', $agreementEditData['new_lines'] ?? []))->filter(fn ($value) => filled($value))->map(fn ($value) => (int) $value)->sum()
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h2 class="text-lg font-semibold text-slate-900">Adjuntos del acuerdo</h2>
                                        <p class="mt-1 text-sm text-slate-500">Puedes quitar adjuntos actuales o agregar nuevos antes de validar.</p>
                                    </div>
                                    <span id="supervisorAttachmentsCount" class="inline-flex items-center rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">
                                        {{ $keptAttachmentPaths->count() }} archivo{{ $keptAttachmentPaths->count() === 1 ? '' : 's' }}
                                    </span>
                                </div>

                                <div id="supervisorExistingAttachments" class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        @forelse($keptAttachmentPaths as $attachmentIndex => $attachmentPath)
                                            @php
                                                $attachmentUrl = route('agreements.attachments.show', ['sale' => $sale->id, 'filename' => basename($attachmentPath)]);
                                                $attachmentName = basename($attachmentPath);
                                                $attachmentExtension = strtolower(pathinfo($attachmentPath, PATHINFO_EXTENSION));
                                                $isPdfAttachment = $attachmentExtension === 'pdf';
                                            @endphp
                                            <div class="relative" data-existing-attachment-card>
                                                <input
                                                    type="hidden"
                                                    name="kept_attachment_paths[]"
                                                    value="{{ $attachmentPath }}"
                                                    id="kept_attachment_{{ $attachmentIndex }}"
                                                    form="agreementUpdateForm"
                                                >

                                                <button
                                                    type="button"
                                                    class="absolute right-2 top-2 z-10 inline-flex h-8 w-8 items-center justify-center rounded-full border border-rose-200 bg-white/95 text-rose-600 transition hover:bg-rose-50"
                                                    data-remove-existing-attachment="kept_attachment_{{ $attachmentIndex }}"
                                                    aria-label="Quitar adjunto"
                                                    title="Quitar adjunto"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6M14 11v6" />
                                                    </svg>
                                                </button>

                                                @if($isPdfAttachment)
                                                    <button
                                                        type="button"
                                                        data-pdf-popup-url="{{ $attachmentUrl }}"
                                                        data-pdf-popup-title="{{ $attachmentName }}"
                                                        class="flex h-32 w-full flex-col justify-between rounded-2xl border border-rose-200 bg-gradient-to-br from-rose-50 via-white to-slate-50 p-3 text-left transition hover:border-rose-300"
                                                    >
                                                        <div>
                                                            <div class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-600">PDF</div>
                                                            <div class="mt-2 break-all pr-10 text-sm font-semibold leading-5 text-slate-900">{{ $attachmentName }}</div>
                                                        </div>
                                                        <div class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700">
                                                            Ver PDF
                                                        </div>
                                                    </button>
                                                @else
                                                    <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener noreferrer" class="block overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:border-slate-300">
                                                        <img src="{{ $attachmentUrl }}" alt="Adjunto del acuerdo" class="h-32 w-full object-cover sm:h-36">
                                                    </a>
                                                @endif
                                            </div>
                                        @empty
                                            <div id="supervisorNoAttachmentsMessage" class="rounded-2xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500 sm:col-span-2">
                                                Este acuerdo no tiene archivos adjuntos.
                                            </div>
                                        @endforelse
                                </div>

                                <input type="file" name="new_agreement_attachments[]" id="supervisorNewAgreementAttachments" accept="image/png,image/jpeg,image/webp,application/pdf" multiple class="hidden" form="agreementUpdateForm">

                                <button type="button" id="openSupervisorAgreementAttachments" class="mt-3 block w-full rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-3 text-center transition hover:border-slate-400 hover:bg-slate-100/80">
                                    <span class="block text-sm font-semibold text-slate-900">Agregar adjuntos</span>
                                    <span class="mt-1 block text-xs text-slate-500">JPG, PNG, WEBP o PDF</span>
                                </button>

                                <div id="supervisorNewAttachmentsSummary" class="mt-3 rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-600">
                                    No has agregado archivos nuevos.
                                </div>

                                @error('new_agreement_attachments')
                                    <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                @error('new_agreement_attachments.*')
                                    <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            @if(!empty($sale->portability_phone_numbers_snapshot) || (!empty($agreementEditData['portability_lines']) || !empty($agreementEditData['portability_promotion_name'])))
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <h2 class="text-lg font-semibold text-slate-900">Números de portabilidad</h2>
                                            <p class="mt-1 text-sm text-slate-500">Los campos se recalculan automáticamente según las líneas de portabilidad que dejes en la oferta.</p>
                                        </div>
                                        <span id="supervisorPortabilityCount" class="inline-flex items-center rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">
                                            {{ count($agreementPortabilityRows ?? []) }} línea{{ count($agreementPortabilityRows ?? []) === 1 ? '' : 's' }}
                                        </span>
                                    </div>

                                    <div id="supervisorPortabilityPhones" class="mt-3 space-y-2.5">
                                        @foreach(($agreementPortabilityRows ?? []) as $portabilityIndex => $portabilityPhone)
                                            <div data-supervisor-portability-phone-row data-supervisor-offer-key="{{ $portabilityPhone['offer_key'] ?? ('portability-'.$portabilityPhone['offer_index']) }}" class="grid gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                                                <div>
                                                    <label class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">
                                                        {{ $portabilityPhone['row_label'] ?? 'Línea' }}
                                                    </label>
                                                    <input
                                                        type="text"
                                                        name="portability_phone_numbers[]"
                                                        value="{{ $portabilityPhone['phone_number'] ?? '' }}"
                                                        class="mt-1 block w-full rounded-xl border-gray-300 text-sm"
                                                        maxlength="9"
                                                        inputmode="numeric"
                                                        pattern="[0-9]{9}"
                                                        form="agreementUpdateForm"
                                                        data-supervisor-portability-phone
                                                    >
                                                </div>
                                                <div>
                                                    <div class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Oferta</div>
                                                    <div class="mt-1 font-semibold text-slate-900">{{ $portabilityPhone['display_offer'] ?? (($portabilityPhone['offer_label'] ?? 'Portabilidad').(($portabilityPhone['promotion_name'] ?? null) ? ' · '.$portabilityPhone['promotion_name'] : '')) }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    @error('portability_phone_numbers')
                                        <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
                                    @enderror

                                    @error('portability_phone_numbers.*')
                                        <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4">
                                <div class="text-sm font-semibold text-amber-900">Liberación a flujo operativo</div>
                                <p class="mt-1.5 text-sm text-amber-800">
                                    Al marcar <span class="font-semibold">Validado</span>, el caso pasará a Postventa y Mesa de Control con estado <span class="font-semibold">En evaluación</span>.
                                </p>

                                <form method="POST" action="{{ route('supervisor.agreements.validate', $sale) }}" class="mt-3" id="agreementValidateForm">
                                    @csrf
                                    <button type="button" id="agreementValidateButton" class="inline-flex w-full items-center justify-center rounded-full bg-black px-5 py-2 text-sm font-medium text-white transition hover:bg-gray-800">
                                        Validado
                                    </button>
                                </form>
                            </div>

                            <div class="rounded-2xl border border-slate-200 p-4">
                                <h2 class="text-lg font-semibold text-slate-900">Historial de cambios</h2>
                                <div class="mt-3 max-h-[420px] space-y-3 overflow-y-auto pr-1">
                                    @forelse($sale->histories as $history)
                                        <div class="rounded-2xl border border-slate-200 px-3 py-3">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <div class="font-semibold text-slate-900">{{ $history->notes ?: ucfirst(str_replace('_', ' ', $history->action)) }}</div>
                                                    <div class="mt-1 text-xs text-slate-500">Usuario: {{ $history->user->name ?? 'Sistema' }}</div>
                                                </div>
                                                <div class="text-xs text-slate-500">{{ optional($history->created_at)->format('d/m/Y H:i') }}</div>
                                            </div>

                                            @if(!empty($history->changed_fields))
                                                <div class="mt-2.5 space-y-2 text-sm text-slate-700">
                                                    @foreach($history->changed_fields as $change)
                                                        @php
                                                            $fieldName = $change['field'] ?? '';
                                                            $oldValue = (string) ($change['old'] ?? '');
                                                            $newValue = (string) ($change['new'] ?? '');
                                                            $isOfferChange = $fieldName === 'products_snapshot';
                                                            $isPortabilityChange = $fieldName === 'portability_phone_numbers_snapshot';
                                                            $isAttachmentChange = $fieldName === 'attachment_paths';
                                                            $oldItems = $isOfferChange
                                                                ? $splitHistoryList($oldValue, ' || ')
                                                                : ($isPortabilityChange
                                                                    ? $splitHistoryList($oldValue, ' | ')
                                                                    : ($isAttachmentChange ? $splitHistoryList($oldValue, ', ') : collect()));
                                                            $newItems = $isOfferChange
                                                                ? $splitHistoryList($newValue, ' || ')
                                                                : ($isPortabilityChange
                                                                    ? $splitHistoryList($newValue, ' | ')
                                                                    : ($isAttachmentChange ? $splitHistoryList($newValue, ', ') : collect()));
                                                            $addedAttachmentItems = $isAttachmentChange ? $newItems->diff($oldItems)->values() : collect();
                                                            $removedAttachmentItems = $isAttachmentChange ? $oldItems->diff($newItems)->values() : collect();
                                                        @endphp

                                                        @if($isOfferChange || $isPortabilityChange || $isAttachmentChange)
                                                            <div class="rounded-xl bg-slate-50 px-3 py-3">
                                                                <div class="font-semibold text-slate-900">{{ $change['label'] ?? $fieldName }}</div>

                                                                @if($isAttachmentChange)
                                                                    <div class="mt-2 grid gap-3 md:grid-cols-2">
                                                                        <div class="rounded-xl border border-rose-200 bg-white px-3 py-2">
                                                                            <div class="text-xs font-semibold uppercase tracking-[0.14em] text-rose-600">Antes</div>
                                                                            <div class="mt-1 text-sm font-medium text-slate-700">{{ $summarizeAttachmentItems($oldItems) }}</div>
                                                                            @if($oldItems->isNotEmpty())
                                                                                <div class="mt-2 flex flex-wrap gap-1.5">
                                                                                    @foreach($oldItems as $item)
                                                                                        <span class="rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-xs text-slate-600">{{ $item }}</span>
                                                                                    @endforeach
                                                                                </div>
                                                                            @endif
                                                                        </div>

                                                                        <div class="rounded-xl border border-emerald-200 bg-white px-3 py-2">
                                                                            <div class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-600">Después</div>
                                                                            <div class="mt-1 text-sm font-medium text-slate-700">{{ $summarizeAttachmentItems($newItems) }}</div>
                                                                            @if($newItems->isNotEmpty())
                                                                                <div class="mt-2 flex flex-wrap gap-1.5">
                                                                                    @foreach($newItems as $item)
                                                                                        <span class="rounded-full border border-slate-200 bg-slate-50 px-2 py-1 text-xs text-slate-600">{{ $item }}</span>
                                                                                    @endforeach
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                    </div>

                                                                    @if($addedAttachmentItems->isNotEmpty() || $removedAttachmentItems->isNotEmpty())
                                                                        <div class="mt-3 grid gap-2 md:grid-cols-2">
                                                                            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2">
                                                                                <div class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">Añadidos</div>
                                                                                <div class="mt-1 text-sm text-emerald-900">
                                                                                    {{ $addedAttachmentItems->isNotEmpty() ? $addedAttachmentItems->implode(', ') : 'Sin archivos añadidos' }}
                                                                                </div>
                                                                            </div>
                                                                            <div class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2">
                                                                                <div class="text-xs font-semibold uppercase tracking-[0.14em] text-rose-700">Quitados</div>
                                                                                <div class="mt-1 text-sm text-rose-900">
                                                                                    {{ $removedAttachmentItems->isNotEmpty() ? $removedAttachmentItems->implode(', ') : 'Sin archivos quitados' }}
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                @else
                                                                    <div class="mt-2 grid gap-3 md:grid-cols-2">
                                                                        <div class="rounded-xl border border-rose-200 bg-white px-3 py-2">
                                                                            <div class="text-xs font-semibold uppercase tracking-[0.14em] text-rose-600">Antes</div>
                                                                            @if($oldItems->isNotEmpty())
                                                                                <div class="mt-2 space-y-1.5">
                                                                                    @foreach($oldItems as $item)
                                                                                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 text-sm text-slate-700">{{ $item }}</div>
                                                                                    @endforeach
                                                                                </div>
                                                                            @else
                                                                                <div class="mt-2 text-sm text-slate-500">Vacío</div>
                                                                            @endif
                                                                        </div>

                                                                        <div class="rounded-xl border border-emerald-200 bg-white px-3 py-2">
                                                                            <div class="text-xs font-semibold uppercase tracking-[0.14em] text-emerald-600">Después</div>
                                                                            @if($newItems->isNotEmpty())
                                                                                <div class="mt-2 space-y-1.5">
                                                                                    @foreach($newItems as $item)
                                                                                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 text-sm text-slate-700">{{ $item }}</div>
                                                                                    @endforeach
                                                                                </div>
                                                                            @else
                                                                                <div class="mt-2 text-sm text-slate-500">Vacío</div>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <div class="rounded-xl bg-slate-50 px-3 py-2">
                                                                <span class="font-semibold">{{ $change['label'] ?? $change['field'] }}:</span>
                                                                <span class="text-rose-600">{{ $change['old'] !== '' ? $change['old'] : 'Vacío' }}</span>
                                                                <span class="mx-1">→</span>
                                                                <span class="text-emerald-700">{{ $change['new'] !== '' ? $change['new'] : 'Vacío' }}</span>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">
                                            Todavía no hay historial registrado para este acuerdo.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="unsavedAgreementChangesModal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/60 px-4 py-6">
        <div class="w-full max-w-md rounded-3xl bg-white shadow-2xl">
            <div class="border-b border-slate-200 px-5 py-4">
                <h3 class="text-lg font-semibold text-slate-900">Hay cambios sin guardar</h3>
                <p class="mt-1 text-sm text-slate-500">Si validas ahora, podemos guardar primero los cambios o dejarte seguir editando.</p>
            </div>
            <div class="space-y-3 px-5 py-5">
                <button type="button" id="continueEditingAgreementButton" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Seguir editando
                </button>
                <button type="button" id="saveAndValidateAgreementButton" class="crm-primary-button inline-flex w-full items-center justify-center rounded-2xl px-4 py-3 text-sm font-semibold transition">
                    Guardar y validar
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let supervisorOfferRowSequence = 0;
            const agreementUpdateForm = document.getElementById('agreementUpdateForm');
            const agreementValidateButton = document.getElementById('agreementValidateButton');
            const validateAfterSaveInput = document.getElementById('validateAfterSaveInput');
            const approvalCodeInput = document.getElementById('approvalCodeInput');
            const approvalCodeFieldWrapper = document.getElementById('approvalCodeFieldWrapper');
            const approvalCodeSummaryCard = document.getElementById('approvalCodeSummaryCard');
            const serviceChannelInput = document.getElementById('serviceChannelInput');
            const unsavedAgreementChangesModal = document.getElementById('unsavedAgreementChangesModal');
            const continueEditingAgreementButton = document.getElementById('continueEditingAgreementButton');
            const saveAndValidateAgreementButton = document.getElementById('saveAndValidateAgreementButton');
            const supervisorPortabilityRows = document.getElementById('supervisorPortabilityRows');
            const supervisorNewRows = document.getElementById('supervisorNewRows');
            const supervisorAddPortabilityRowButton = document.getElementById('supervisorAddPortabilityRow');
            const supervisorAddNewRowButton = document.getElementById('supervisorAddNewRow');
            const supervisorPortabilityPhones = document.getElementById('supervisorPortabilityPhones');
            const supervisorPortabilityCount = document.getElementById('supervisorPortabilityCount');
            const supervisorOfferedLineCount = document.getElementById('supervisorOfferedLineCount');
            const openSupervisorAgreementAttachmentsButton = document.getElementById('openSupervisorAgreementAttachments');
            const supervisorNewAgreementAttachmentsInput = document.getElementById('supervisorNewAgreementAttachments');
            const supervisorNewAttachmentsSummary = document.getElementById('supervisorNewAttachmentsSummary');
            const supervisorExistingAttachments = document.getElementById('supervisorExistingAttachments');
            const supervisorNoAttachmentsMessage = document.getElementById('supervisorNoAttachmentsMessage');
            const supervisorAttachmentsCount = document.getElementById('supervisorAttachmentsCount');
            let selectedSupervisorAttachmentFiles = [];
            let agreementValidationSubmissionInProgress = false;

            function buildFormSnapshot(form) {
                if (!form) return '[]';

                const formData = new FormData(form);
                const entries = [];

                for (const [key, value] of formData.entries()) {
                    if (['_token', '_method', 'validate_after_save'].includes(key)) {
                        continue;
                    }

                    if (value instanceof File) {
                        entries.push([key, `${value.name}|${value.size}|${value.type}|${value.lastModified}`]);
                    } else {
                        entries.push([key, String(value)]);
                    }
                }

                entries.sort((a, b) => {
                    if (a[0] === b[0]) {
                        return a[1].localeCompare(b[1]);
                    }

                    return a[0].localeCompare(b[0]);
                });

                return JSON.stringify(entries);
            }

            let initialAgreementFormSnapshot = buildFormSnapshot(agreementUpdateForm);

            function hasUnsavedAgreementChanges() {
                return buildFormSnapshot(agreementUpdateForm) !== initialAgreementFormSnapshot;
            }

            function openUnsavedAgreementChangesModal() {
                if (!unsavedAgreementChangesModal) return;
                unsavedAgreementChangesModal.classList.remove('hidden');
                unsavedAgreementChangesModal.classList.add('flex');
            }

            function closeUnsavedAgreementChangesModal() {
                if (!unsavedAgreementChangesModal) return;
                unsavedAgreementChangesModal.classList.add('hidden');
                unsavedAgreementChangesModal.classList.remove('flex');
            }

            function ensureApprovalCodeForValidation() {
                if (!approvalCodeInput) {
                    return true;
                }

                if (!shouldShowApprovalCode()) {
                    approvalCodeInput.setCustomValidity('');
                    return true;
                }

                approvalCodeInput.setCustomValidity('');

                if (approvalCodeInput.value.trim() !== '') {
                    return true;
                }

                approvalCodeInput.setCustomValidity('Ingresa el código de aprobación antes de validar el acuerdo.');
                approvalCodeInput.reportValidity();
                approvalCodeInput.focus();

                setTimeout(() => {
                    approvalCodeInput?.setCustomValidity('');
                }, 50);

                return false;
            }

            function shouldShowApprovalCode() {
                if (!serviceChannelInput) {
                    return false;
                }

                return serviceChannelInput.value === 'centralizado';
            }

            function submitAgreementForValidation() {
                if (!agreementUpdateForm || !validateAfterSaveInput || agreementValidationSubmissionInProgress) {
                    return;
                }

                if (!ensureUniqueSupervisorPromotions()) {
                    return;
                }

                agreementValidationSubmissionInProgress = true;
                validateAfterSaveInput.value = '1';
                agreementUpdateForm.submit();
            }

            function syncApprovalCodeVisibility() {
                const visible = shouldShowApprovalCode();

                if (approvalCodeFieldWrapper) {
                    approvalCodeFieldWrapper.classList.toggle('hidden', !visible);
                }

                if (approvalCodeSummaryCard) {
                    approvalCodeSummaryCard.classList.toggle('hidden', !visible);
                }

                if (!visible && approvalCodeInput) {
                    approvalCodeInput.setCustomValidity('');
                }
            }

            if (agreementUpdateForm) {
                agreementUpdateForm.addEventListener('submit', (event) => {
                    if (!ensureUniqueSupervisorPromotions()) {
                        event.preventDefault();
                        agreementValidationSubmissionInProgress = false;
                        return;
                    }

                    if (validateAfterSaveInput) {
                        validateAfterSaveInput.value = validateAfterSaveInput.value || '0';
                    }

                    agreementValidationSubmissionInProgress = true;
                });
            }

            if (serviceChannelInput) {
                serviceChannelInput.addEventListener('change', syncApprovalCodeVisibility);
                syncApprovalCodeVisibility();
            }

            if (agreementValidateButton) {
                agreementValidateButton.addEventListener('click', () => {
                    if (!agreementUpdateForm) {
                        return;
                    }

                    if (!ensureApprovalCodeForValidation()) {
                        return;
                    }

                    if (hasUnsavedAgreementChanges()) {
                        openUnsavedAgreementChangesModal();
                        return;
                    }

                    submitAgreementForValidation();
                });
            }

            if (continueEditingAgreementButton) {
                continueEditingAgreementButton.addEventListener('click', () => {
                    closeUnsavedAgreementChangesModal();
                });
            }

            if (saveAndValidateAgreementButton) {
                saveAndValidateAgreementButton.addEventListener('click', () => {
                    if (!agreementUpdateForm || !validateAfterSaveInput) {
                        return;
                    }

                    if (!ensureApprovalCodeForValidation()) {
                        return;
                    }

                    closeUnsavedAgreementChangesModal();
                    submitAgreementForValidation();
                });
            }

            if (unsavedAgreementChangesModal) {
                unsavedAgreementChangesModal.addEventListener('click', (event) => {
                    if (event.target === unsavedAgreementChangesModal) {
                        closeUnsavedAgreementChangesModal();
                    }
                });
            }

            function nextSupervisorOfferKey(type) {
                supervisorOfferRowSequence += 1;
                return `${type}-new-${supervisorOfferRowSequence}`;
            }

            function createSupervisorOfferRowHtml(type) {
                const lineLabel = type === 'portability' ? 'Cant. líneas' : 'Cant. líneas';
                const lineName = type === 'portability' ? 'portability_lines[]' : 'new_lines[]';
                const promotionName = type === 'portability' ? 'portability_promotion_name[]' : 'new_promotion_name[]';
                const offerKey = nextSupervisorOfferKey(type);

                return `
                    <div data-supervisor-offer-row data-supervisor-offer-key="${offerKey}" class="grid gap-3 md:grid-cols-[110px_minmax(0,1fr)_64px]">
                        <div>
                            <label class="block text-xs font-medium text-slate-700">${lineLabel}</label>
                            <input type="number" name="${lineName}" class="mt-1 block w-full rounded-xl border-gray-300 text-sm" min="1" max="999" step="1" form="agreementUpdateForm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700">Promoción</label>
                            <select name="${promotionName}" class="mt-1 block w-full rounded-xl border-gray-300 text-sm" form="agreementUpdateForm">
                                <option value="">-- Selecciona --</option>
                                @foreach(($promotionNames ?? collect()) as $promotionName)
                                    <option value="{{ $promotionName->name }}">{{ $promotionName->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:pt-6">
                            <button type="button" data-remove-supervisor-offer-row aria-label="Quitar fila" title="Quitar fila" class="inline-flex h-[42px] w-full items-center justify-center rounded-xl border border-red-200 bg-red-50 px-0 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100">
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

            function refreshSupervisorRemoveButtons(container) {
                if (!container) return;

                const rows = [...container.querySelectorAll('[data-supervisor-offer-row]')];
                rows.forEach((row) => {
                    const removeButton = row.querySelector('[data-remove-supervisor-offer-row]');
                    if (!removeButton) return;
                    removeButton.classList.toggle('hidden', rows.length === 1);
                });
            }

            function getSupervisorOfferSectionLabel(container) {
                return container === supervisorPortabilityRows ? 'Portabilidad' : 'Alta nueva';
            }

            function getSupervisorPromotionDuplicateMessage(container) {
                return `No repitas la misma promoción en ${getSupervisorOfferSectionLabel(container)}. Si deseas más líneas para ese plan, aumenta la cantidad en una sola fila.`;
            }

            function getSupervisorPromotionSelects(container) {
                if (!container) {
                    return [];
                }

                return [...container.querySelectorAll('[data-supervisor-offer-row] select')];
            }

            function refreshSupervisorPromotionOptions(container) {
                const selects = getSupervisorPromotionSelects(container);

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

            function ensureUniqueSupervisorPromotionsInContainer(container) {
                const selects = getSupervisorPromotionSelects(container);
                const seenPromotions = new Set();

                for (const select of selects) {
                    const selectedValue = (select.value || '').trim();
                    select.setCustomValidity('');

                    if (!selectedValue) {
                        continue;
                    }

                    const normalizedValue = selectedValue.toLowerCase();

                    if (seenPromotions.has(normalizedValue)) {
                        const message = getSupervisorPromotionDuplicateMessage(container);
                        select.setCustomValidity(message);
                        select.reportValidity();
                        select.focus();
                        return false;
                    }

                    seenPromotions.add(normalizedValue);
                }

                return true;
            }

            function ensureUniqueSupervisorPromotions() {
                refreshSupervisorPromotionOptions(supervisorPortabilityRows);
                refreshSupervisorPromotionOptions(supervisorNewRows);

                return ensureUniqueSupervisorPromotionsInContainer(supervisorPortabilityRows)
                    && ensureUniqueSupervisorPromotionsInContainer(supervisorNewRows);
            }

            function readSupervisorPortabilityPhoneValues() {
                if (!supervisorPortabilityPhones) return new Map();

                const valuesByOfferKey = new Map();

                [...supervisorPortabilityPhones.querySelectorAll('[data-supervisor-portability-phone-row]')].forEach((row) => {
                    const offerKey = row.dataset.supervisorOfferKey || '';
                    const input = row.querySelector('input[name="portability_phone_numbers[]"]');

                    if (!offerKey || !input) {
                        return;
                    }

                    if (!valuesByOfferKey.has(offerKey)) {
                        valuesByOfferKey.set(offerKey, []);
                    }

                    valuesByOfferKey.get(offerKey).push(input.value || '');
                });

                return valuesByOfferKey;
            }

            function rebuildSupervisorPortabilityPhones() {
                if (!supervisorPortabilityPhones) return;

                const previousValues = readSupervisorPortabilityPhoneValues();
                let totalRows = 0;
                let totalLines = 0;

                supervisorPortabilityPhones.innerHTML = '';

                const portabilityRows = supervisorPortabilityRows
                    ? [...supervisorPortabilityRows.querySelectorAll('[data-supervisor-offer-row]')]
                    : [];

                portabilityRows.forEach((row) => {
                    const offerKey = row.dataset.supervisorOfferKey || nextSupervisorOfferKey('portability');
                    row.dataset.supervisorOfferKey = offerKey;
                    const linesValue = Number(row.querySelector('input[name="portability_lines[]"]')?.value || 0);
                    const promotionValue = (row.querySelector('select[name="portability_promotion_name[]"]')?.value || '').trim() || 'Sin promoción';
                    const preservedValues = previousValues.get(offerKey) || [];

                    totalLines += linesValue > 0 ? linesValue : 0;

                    for (let lineIndex = 1; lineIndex <= linesValue; lineIndex++) {
                        const wrapper = document.createElement('div');
                        wrapper.setAttribute('data-supervisor-portability-phone-row', '');
                        wrapper.dataset.supervisorOfferKey = offerKey;
                        wrapper.className = 'grid gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]';
                        wrapper.innerHTML = `
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Línea ${lineIndex}</label>
                                <input
                                    type="text"
                                    name="portability_phone_numbers[]"
                                    value="${preservedValues[lineIndex - 1] || ''}"
                                    class="mt-1 block w-full rounded-xl border-gray-300 text-sm"
                                    maxlength="9"
                                    inputmode="numeric"
                                    pattern="[0-9]{9}"
                                    form="agreementUpdateForm"
                                    data-supervisor-portability-phone
                                >
                            </div>
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Oferta</div>
                                <div class="mt-1 font-semibold text-slate-900">${promotionValue}</div>
                            </div>
                        `;
                        supervisorPortabilityPhones.appendChild(wrapper);
                        totalRows += 1;
                    }
                });

                if (supervisorPortabilityCount) {
                    supervisorPortabilityCount.textContent = `${totalRows} línea${totalRows === 1 ? '' : 's'}`;
                }

                const newRows = supervisorNewRows
                    ? [...supervisorNewRows.querySelectorAll('input[name="new_lines[]"]')]
                    : [];
                totalLines += newRows.reduce((sum, input) => sum + (Number(input.value || 0) || 0), 0);

                if (supervisorOfferedLineCount) {
                    supervisorOfferedLineCount.textContent = String(totalLines);
                }

                supervisorPortabilityPhones.querySelectorAll('[data-supervisor-portability-phone]').forEach((input) => {
                    input.addEventListener('input', (event) => {
                        event.target.value = event.target.value.replace(/\D/g, '').slice(0, 9);
                    });
                });
            }

            function updateSupervisorAttachmentsCount() {
                if (!supervisorAttachmentsCount) return;

                const existingCount = supervisorExistingAttachments
                    ? supervisorExistingAttachments.querySelectorAll('[data-existing-attachment-card]').length
                    : 0;
                const totalCount = existingCount + selectedSupervisorAttachmentFiles.length;

                supervisorAttachmentsCount.textContent = `${totalCount} archivo${totalCount === 1 ? '' : 's'}`;

                if (supervisorNoAttachmentsMessage) {
                    supervisorNoAttachmentsMessage.classList.toggle('hidden', totalCount > 0);
                }
            }

            function syncSupervisorAttachmentsInput() {
                if (!supervisorNewAgreementAttachmentsInput) return;

                const dataTransfer = new DataTransfer();

                selectedSupervisorAttachmentFiles.forEach((file) => {
                    dataTransfer.items.add(file);
                });

                supervisorNewAgreementAttachmentsInput.files = dataTransfer.files;
            }

            function renderSupervisorAttachmentsSummary() {
                if (!supervisorNewAttachmentsSummary) return;

                if (!selectedSupervisorAttachmentFiles.length) {
                    supervisorNewAttachmentsSummary.textContent = 'No has agregado archivos nuevos.';
                    updateSupervisorAttachmentsCount();
                    return;
                }

                supervisorNewAttachmentsSummary.innerHTML = '';

                const countLabel = document.createElement('div');
                countLabel.className = 'font-medium text-slate-900';
                countLabel.textContent = `${selectedSupervisorAttachmentFiles.length} archivo(s) nuevo(s)`;

                const filesList = document.createElement('div');
                filesList.className = 'mt-3 space-y-2';

                selectedSupervisorAttachmentFiles.forEach((file, index) => {
                    const fileRow = document.createElement('div');
                    fileRow.className = 'flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2';

                    const fileName = document.createElement('div');
                    fileName.className = 'min-w-0 flex-1 truncate text-sm text-slate-600';
                    fileName.textContent = file.name;
                    fileName.title = file.name;

                    const removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = 'inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-rose-200 bg-white text-rose-600 transition hover:bg-rose-50';
                    removeButton.dataset.newAttachmentIndex = String(index);
                    removeButton.setAttribute('aria-label', `Quitar ${file.name}`);
                    removeButton.setAttribute('title', 'Quitar archivo');
                    removeButton.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 6V4h8v2" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 6l-1 14H6L5 6" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6M14 11v6" />
                        </svg>
                    `;

                    fileRow.appendChild(fileName);
                    fileRow.appendChild(removeButton);
                    filesList.appendChild(fileRow);
                });

                supervisorNewAttachmentsSummary.appendChild(countLabel);
                supervisorNewAttachmentsSummary.appendChild(filesList);
                updateSupervisorAttachmentsCount();
            }

            openSupervisorAgreementAttachmentsButton?.addEventListener('click', () => {
                supervisorNewAgreementAttachmentsInput?.click();
            });

            supervisorNewAgreementAttachmentsInput?.addEventListener('change', () => {
                const newFiles = Array.from(supervisorNewAgreementAttachmentsInput.files || []);

                if (!newFiles.length) {
                    renderSupervisorAttachmentsSummary();
                    return;
                }

                const fileKeys = new Set(
                    selectedSupervisorAttachmentFiles.map((file) => `${file.name}-${file.size}-${file.lastModified}`)
                );

                newFiles.forEach((file) => {
                    const fileKey = `${file.name}-${file.size}-${file.lastModified}`;

                    if (!fileKeys.has(fileKey)) {
                        selectedSupervisorAttachmentFiles.push(file);
                        fileKeys.add(fileKey);
                    }
                });

                if (selectedSupervisorAttachmentFiles.length > 8) {
                    selectedSupervisorAttachmentFiles = selectedSupervisorAttachmentFiles.slice(0, 8);
                }

                syncSupervisorAttachmentsInput();
                renderSupervisorAttachmentsSummary();
            });

            supervisorNewAttachmentsSummary?.addEventListener('click', (event) => {
                const removeButton = event.target.closest('[data-new-attachment-index]');

                if (!removeButton) {
                    return;
                }

                const attachmentIndex = Number(removeButton.dataset.newAttachmentIndex);

                if (Number.isNaN(attachmentIndex)) {
                    return;
                }

                selectedSupervisorAttachmentFiles.splice(attachmentIndex, 1);
                syncSupervisorAttachmentsInput();
                renderSupervisorAttachmentsSummary();
            });

            supervisorExistingAttachments?.addEventListener('click', (event) => {
                const removeButton = event.target.closest('[data-remove-existing-attachment]');

                if (!removeButton) {
                    return;
                }

                const hiddenInputId = removeButton.dataset.removeExistingAttachment;
                const hiddenInput = hiddenInputId ? document.getElementById(hiddenInputId) : null;
                hiddenInput?.remove();
                removeButton.closest('[data-existing-attachment-card]')?.remove();
                updateSupervisorAttachmentsCount();
            });

            supervisorAddPortabilityRowButton?.addEventListener('click', () => {
                supervisorPortabilityRows?.insertAdjacentHTML('beforeend', createSupervisorOfferRowHtml('portability'));
                refreshSupervisorRemoveButtons(supervisorPortabilityRows);
                refreshSupervisorPromotionOptions(supervisorPortabilityRows);
                rebuildSupervisorPortabilityPhones();
            });

            supervisorAddNewRowButton?.addEventListener('click', () => {
                supervisorNewRows?.insertAdjacentHTML('beforeend', createSupervisorOfferRowHtml('new'));
                refreshSupervisorRemoveButtons(supervisorNewRows);
                refreshSupervisorPromotionOptions(supervisorNewRows);
                rebuildSupervisorPortabilityPhones();
            });

            [supervisorPortabilityRows, supervisorNewRows].forEach((container) => {
                container?.addEventListener('click', (event) => {
                    const removeButton = event.target.closest('[data-remove-supervisor-offer-row]');
                    if (!removeButton) return;

                    const row = removeButton.closest('[data-supervisor-offer-row]');
                    if (!row) return;

                    if (container.querySelectorAll('[data-supervisor-offer-row]').length === 1) {
                        row.querySelectorAll('input, select').forEach((field) => {
                            field.value = '';
                        });
                    } else {
                        row.remove();
                    }

                    refreshSupervisorRemoveButtons(container);
                    refreshSupervisorPromotionOptions(container);
                    rebuildSupervisorPortabilityPhones();
                });

                container?.addEventListener('input', rebuildSupervisorPortabilityPhones);
                container?.addEventListener('change', (event) => {
                    if (event.target.matches('select')) {
                        const currentSelect = event.target;
                        const selectedValue = (currentSelect.value || '').trim();

                        if (selectedValue) {
                            const duplicateSelect = getSupervisorPromotionSelects(container).find((select) => {
                                return select !== currentSelect
                                    && (select.value || '').trim().toLowerCase() === selectedValue.toLowerCase();
                            });

                            if (duplicateSelect) {
                                currentSelect.value = '';
                                currentSelect.setCustomValidity(getSupervisorPromotionDuplicateMessage(container));
                                currentSelect.reportValidity();
                                currentSelect.setCustomValidity('');
                            }
                        }

                        refreshSupervisorPromotionOptions(container);
                    }

                    rebuildSupervisorPortabilityPhones();
                });
            });

            renderSupervisorAttachmentsSummary();
            updateSupervisorAttachmentsCount();
            refreshSupervisorRemoveButtons(supervisorPortabilityRows);
            refreshSupervisorRemoveButtons(supervisorNewRows);
            refreshSupervisorPromotionOptions(supervisorPortabilityRows);
            refreshSupervisorPromotionOptions(supervisorNewRows);
            rebuildSupervisorPortabilityPhones();
        });
    </script>
</x-app-layout>
