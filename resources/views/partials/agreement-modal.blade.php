@php
    $agreementProducts = $agreementProducts ?? [];
    $agreementPortabilityRows = collect($agreementPortabilityRows ?? [])->values();
    $agreementDraft = $agreementDraft ?? [];
    $agreementErrorFields = [
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
    ];
    $agreementErrorMessage = collect($agreementErrorFields)
        ->flatMap(fn ($field) => collect($errors->get($field))->flatten())
        ->filter()
        ->first();
    $serviceChannels = $serviceChannels ?? [
        'pdv' => 'PDV',
        'centralizado' => 'Centralizado',
    ];
    $attentionSlots = $attentionSlots ?? ['9 am - 11 am', '11 am - 1 pm', '2 pm - 4 pm', '4 pm - 6 pm'];
    $deliveryTypes = $deliveryTypes ?? [
        'regular' => 'Regular',
        'express' => 'Express',
        'almacen_propio' => 'Almacen Propio',
    ];
@endphp

<div id="agreementModal" class="fixed inset-0 z-50 hidden">
    <div id="agreementModalBackdrop" class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"></div>

    <div class="relative flex min-h-full items-center justify-center px-3 py-4">
        <div class="relative max-h-[92vh] w-full max-w-5xl overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-2xl">
            <div class="crm-panel-hero border-b border-slate-200 px-5 py-5 text-white sm:px-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="max-w-3xl">
                        <p class="text-xs font-medium uppercase tracking-[0.24em] text-white/75">Cierre comercial</p>
                        <h3 class="mt-2 text-2xl font-semibold tracking-tight">Formulario de acuerdo aceptado</h3>
                    </div>

                    <button
                        type="button"
                        id="closeAgreementModal"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/20 text-white transition hover:bg-white/10"
                    >
                        <span class="sr-only">Cerrar modal</span>
                        <span class="text-xl leading-none">&times;</span>
                    </button>
                </div>
            </div>

            <div class="max-h-[calc(92vh-112px)] overflow-y-auto px-4 py-4 sm:px-5">
                <form method="POST" action="{{ $agreementSubmitUrl }}" id="agreementForm" class="flex min-h-full flex-col" enctype="multipart/form-data">
                    @csrf

                    @if($agreementErrorMessage)
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-sm text-red-700">
                            {{ $agreementErrorMessage }}
                        </div>
                    @endif

                    <div class="flex-1 space-y-4 pb-24">
                        <div class="grid gap-4 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]">
                            <div class="space-y-4">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="mb-3">
                                        <h4 class="text-lg font-semibold text-slate-900">Datos del cliente</h4>
                                    </div>

                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700">RUC</label>
                                            <input type="text" name="customer_ruc" value="{{ $agreementDraft['customer_ruc'] ?? '' }}" class="agreement-required mt-1 block w-full rounded-xl border-gray-300" maxlength="20" autocomplete="off">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-slate-700">Razón social</label>
                                            <input type="text" name="customer_business_name" value="{{ $agreementDraft['customer_business_name'] ?? '' }}" class="agreement-required mt-1 block w-full rounded-xl border-gray-300" maxlength="255" autocomplete="off">
                                        </div>

                                        <div>
                                            <label class="block text-sm font-medium text-slate-700">DNI del representante</label>
                                            <input type="text" name="customer_dni" value="{{ $agreementDraft['customer_dni'] ?? '' }}" class="agreement-required mt-1 block w-full rounded-xl border-gray-300" maxlength="20" autocomplete="off">
                                        </div>

                                        <div class="md:col-span-2 rounded-2xl border-2 border-amber-300 bg-amber-50/80 p-3">
                                            <div class="mb-2">
                                                <div class="text-sm font-semibold text-amber-900">Datos obligatorios del representante</div>
                                                <p class="mt-1 text-xs text-amber-800">Verifica con cuidado el nombre, celular y correo del representante antes de aceptar el acuerdo.</p>
                                            </div>

                                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                                <div class="md:col-span-2">
                                                    <label class="block text-sm font-semibold text-amber-900">Nombre completo del representante autorizado</label>
                                                    <input type="text" name="customer_representative_name" value="{{ $agreementDraft['customer_representative_name'] ?? '' }}" class="agreement-required mt-1 block w-full rounded-xl border-amber-300 bg-white" maxlength="255" autocomplete="off" placeholder="Ingresa exactamente el nombre del representante">
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-semibold text-amber-900">Celular del representante autorizado</label>
                                                    <input type="text" name="customer_phone" value="{{ $agreementDraft['customer_phone'] ?? '' }}" class="agreement-required mt-1 block w-full rounded-xl border-amber-300 bg-white" maxlength="9" inputmode="numeric" pattern="[0-9]{9}" autocomplete="off" data-digits-only="9" placeholder="9 dígitos del representante">
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-semibold text-amber-900">Correo electrónico del representante autorizado</label>
                                                    <input type="email" name="customer_email" value="{{ $agreementDraft['customer_email'] ?? '' }}" class="agreement-required mt-1 block w-full rounded-xl border-amber-300 bg-white" maxlength="255" autocomplete="off" placeholder="correo del representante">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-slate-700">Dirección</label>
                                            <input type="text" name="customer_address" value="{{ $agreementDraft['customer_address'] ?? '' }}" class="agreement-required mt-1 block w-full rounded-xl border-gray-300" maxlength="255" autocomplete="off">
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-slate-700">Coordenadas</label>
                                            <input type="text" name="customer_coordinates" value="{{ $agreementDraft['customer_coordinates'] ?? '' }}" class="agreement-required mt-1 block w-full rounded-xl border-gray-300" maxlength="255" placeholder="-12.0464,-77.0428" autocomplete="off">
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="block text-sm font-medium text-slate-700">Plano (Portal factibilidad)</label>
                                            <input type="text" name="plan_code" value="{{ $agreementDraft['plan_code'] ?? '' }}" class="agreement-required mt-1 block w-full rounded-xl border-gray-300" maxlength="120" autocomplete="off" placeholder="Código copiado de la otra plataforma">
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <h4 class="text-lg font-semibold text-slate-900">Condiciones operativas</h4>
                                    <div class="mt-4 space-y-4">
                                        @if(!$isFixedOnlyAgreement)
                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3.5">
                                                <div class="text-sm font-medium text-slate-700">Canal de atención</div>
                                                <div class="mt-3 grid grid-cols-1 gap-3 xl:grid-cols-2">
                                                    @foreach($serviceChannels as $value => $label)
                                                        <label class="agreement-option flex min-h-[76px] cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50/70" data-exclusive-group="service_channel">
                                                            <input type="checkbox" name="service_channel_option" value="{{ $value }}" class="agreement-exclusive mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" data-target-input="service_channel" {{ ($agreementDraft['service_channel'] ?? '') === $value ? 'checked' : '' }}>
                                                            <span>
                                                                <span class="block font-semibold text-slate-900">{{ $label }}</span>
                                                                <span class="mt-1 block text-xs leading-5 text-slate-500">Selecciona una sola forma de atención.</span>
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                <input type="hidden" name="service_channel" id="service_channel" value="{{ $agreementDraft['service_channel'] ?? '' }}" class="agreement-hidden-required">
                                            </div>

                                            <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
                                                <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                                    <label class="block text-sm font-medium text-slate-700">Franja de atención</label>
                                                    <select name="attention_time_slot" id="attention_time_slot" class="agreement-required mt-2 block w-full rounded-xl border-gray-300">
                                                        <option value="">Selecciona una franja</option>
                                                        @foreach($attentionSlots as $slot)
                                                            <option value="{{ $slot }}" {{ ($agreementDraft['attention_time_slot'] ?? '') === $slot ? 'selected' : '' }}>{{ $slot }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                                    <label class="block text-sm font-medium text-slate-700">Fecha</label>
                                                    <input type="date" name="attention_date" value="{{ $agreementDraft['attention_date'] ?? '' }}" class="agreement-required mt-2 block w-full rounded-xl border-gray-300">
                                                </div>

                                                <div class="rounded-2xl border border-slate-200 bg-white p-3">
                                                    <label class="block text-sm font-medium text-slate-700">Operador</label>
                                                    <select name="operator_name" class="agreement-required mt-2 block w-full rounded-xl border-gray-300">
                                                        <option value="">Selecciona un operador</option>
                                                        @foreach(['Entel', 'Bitel', 'Claro', 'Movistar'] as $operator)
                                                            <option value="{{ $operator }}" {{ ($agreementDraft['operator_name'] ?? '') === $operator ? 'selected' : '' }}>{{ $operator }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        @endif

                                        @if($requiresFixedAgreementSupport)
                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3.5">
                                                <div class="mb-3 text-sm font-medium text-slate-700">Soporte del acuerdo fija</div>
                                                <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                                                    @foreach(($fixedAgreementSupportOptions ?? []) as $value => $label)
                                                        <label class="agreement-option flex min-h-[50px] cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50/60" data-exclusive-group="fixed_agreement_supports">
                                                            <input type="checkbox" name="fixed_agreement_supports[]" value="{{ $value }}" class="agreement-exclusive mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" data-target-input="fixed_agreement_supports" {{ in_array($value, (array) ($agreementDraft['fixed_agreement_supports'] ?? []), true) ? 'checked' : '' }}>
                                                            <span>
                                                                <span class="block font-semibold text-slate-900">{{ $label }}</span>
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                @error('fixed_agreement_supports')
                                                    <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <h4 class="text-lg font-semibold text-slate-900">Productos ofrecidos</h4>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">
                                            {{ count($agreementProducts) }} registro{{ count($agreementProducts) === 1 ? '' : 's' }}
                                        </span>
                                    </div>

                                    <div class="mt-3 space-y-2.5">
                                        @forelse($agreementProducts as $product)
                                            <div class="rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                                                <div class="flex items-start justify-between gap-3">
                                                    <div>
                                                        <div class="font-semibold text-slate-900">{{ $product['label'] ?? '-' }}</div>
                                                        <div class="mt-1 text-slate-600">{{ $product['detail'] ?? '-' }}</div>
                                                    </div>
                                                    <div class="text-right text-xs text-slate-500">
                                                        <div>Líneas: {{ $product['line_count'] ?? 0 }}</div>
                                                        <div class="mt-1 font-semibold text-slate-900">
                                                            {{ ($product['summary_value'] ?? null) ?: (isset($product['price']) && $product['price'] !== null ? 'S/ '.number_format((float) $product['price'], 2) : '-') }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="rounded-2xl border border-dashed border-slate-300 px-3 py-4 text-sm text-slate-500">
                                                Aún no hay oferta comercial registrada. Primero guarda una gestión con productos y luego podrás cerrar el acuerdo.
                                            </div>
                                        @endforelse
                                    </div>

                                    <div class="mt-3 rounded-2xl border border-dashed border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700">
                                        <span class="font-semibold">Cantidad de líneas total:</span>
                                        <span>{{ collect($agreementProducts)->sum(fn ($product) => (int) ($product['line_count'] ?? 0)) }}</span>
                                    </div>
                                </div>

                                @if($agreementPortabilityRows->isNotEmpty())
                                    <div class="rounded-2xl border border-slate-200 p-4">
                                        <div class="mb-3">
                                            <h4 class="text-lg font-semibold text-slate-900">Números para portabilidad</h4>
                                            <p class="mt-1 text-sm text-slate-500">Completa un número por cada línea solicitada en portabilidad. La oferta queda precargada para evitar errores.</p>
                                        </div>

                                        <div class="max-h-72 space-y-2.5 overflow-y-auto pr-1.5">
                                            @foreach($agreementPortabilityRows as $index => $portabilityRow)
                                                <div class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                                                    <div>
                                                        <label class="block text-sm font-medium text-slate-700">
                                                            Número de teléfono
                                                            <span class="ml-1 text-xs font-normal text-slate-500">{{ $portabilityRow['row_label'] }}</span>
                                                        </label>
                                                        <input
                                                            type="text"
                                                            name="portability_phone_numbers[]"
                                                            value="{{ $agreementDraft['portability_phone_numbers'][$index] ?? '' }}"
                                                            class="agreement-required mt-1 block w-full rounded-xl border-gray-300"
                                                            maxlength="9"
                                                            inputmode="numeric"
                                                            pattern="[0-9]{9}"
                                                            autocomplete="off"
                                                            data-digits-only="9"
                                                        >
                                                    </div>

                                                    <div>
                                                        <label class="block text-sm font-medium text-slate-700">Oferta seleccionada</label>
                                                        <div class="mt-1 flex min-h-[46px] items-center rounded-xl border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700">
                                                            {{ $portabilityRow['display_offer'] }}
                                                        </div>
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

                                <div class="rounded-2xl border border-slate-200 p-4">
                                    <div class="mb-3">
                                        <h4 class="text-lg font-semibold text-slate-900">Adjuntos del acuerdo</h4>
                                    </div>

                                    <input type="file" name="agreement_attachments[]" id="agreement_attachments" accept="image/png,image/jpeg,image/webp,application/pdf" multiple class="hidden">

                                    <button type="button" id="openAgreementAttachments" class="block w-full rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3.5 text-center transition hover:border-slate-400 hover:bg-slate-100/80">
                                        <span class="block text-sm font-semibold text-slate-900">Seleccionar archivos</span>
                                        <span class="mt-1 block text-xs text-slate-500">JPG, PNG, WEBP o PDF</span>
                                    </button>

                                    <div id="agreementAttachmentsSummary" class="mt-3 rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-600">
                                        Ningún archivo seleccionado todavía.
                                    </div>

                                    <p class="mt-2 text-xs text-slate-500">Máximo 8 archivos de 20 MB.</p>
                                </div>

                                @if(!$isFixedOnlyAgreement)
                                    <div class="rounded-2xl border border-slate-200 p-4">
                                        <div class="mb-3">
                                            <h4 class="text-lg font-semibold text-slate-900">Tipo de entrega</h4>
                                        </div>

                                        <div class="space-y-2.5">
                                            @foreach($deliveryTypes as $value => $label)
                                                <label class="agreement-option flex min-h-[72px] cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 transition hover:border-emerald-300 hover:bg-emerald-50/70" data-exclusive-group="delivery_type">
                                                    <input type="checkbox" name="delivery_type_option" value="{{ $value }}" class="agreement-exclusive mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" data-target-input="delivery_type" {{ ($agreementDraft['delivery_type'] ?? '') === $value ? 'checked' : '' }}>
                                                    <span>
                                                        <span class="block font-semibold text-slate-900">{{ $label }}</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>

                                        <input type="hidden" name="delivery_type" id="delivery_type" value="{{ $agreementDraft['delivery_type'] ?? '' }}" class="agreement-hidden-required">
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="sticky bottom-0 mt-4 border-t border-slate-200 bg-white px-1 pb-1.5 pt-4 shadow-[0_-8px_18px_rgba(15,23,42,0.08)] sm:px-0">
                        <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-sm text-slate-500">
                                El supervisor podrá corregir la ficha antes de liberarla a Postventa y Mesa de Control.
                            </div>

                            <div class="flex gap-3">
                                <button type="button" id="cancelAgreementModal" class="inline-flex items-center justify-center rounded-full border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                                    Cancelar
                                </button>
                                <button type="submit" id="agreementSubmitButton" class="inline-flex items-center justify-center rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-slate-300">
                                    Aceptar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const agreementForm = document.getElementById('agreementForm');
        const openAgreementAttachmentsButton = document.getElementById('openAgreementAttachments');
        const agreementAttachmentsInput = document.getElementById('agreement_attachments');
        const agreementAttachmentsSummary = document.getElementById('agreementAttachmentsSummary');

        if (!agreementForm || !openAgreementAttachmentsButton || !agreementAttachmentsInput || !agreementAttachmentsSummary) {
            return;
        }

        if (agreementForm.dataset.attachmentsInitialized === 'true') {
            return;
        }

        agreementForm.dataset.attachmentsInitialized = 'true';

        let selectedAgreementFiles = [];

        function buildAgreementAttachmentKey(file) {
            return `${file.name}-${file.size}-${file.lastModified}`;
        }

        function syncAgreementAttachmentsInput() {
            if (typeof DataTransfer === 'undefined') {
                return;
            }

            const dataTransfer = new DataTransfer();

            selectedAgreementFiles.forEach((file) => {
                dataTransfer.items.add(file);
            });

            agreementAttachmentsInput.files = dataTransfer.files;
        }

        function renderAgreementAttachmentsSummary() {
            if (!selectedAgreementFiles.length) {
                agreementAttachmentsSummary.textContent = 'Ningún archivo seleccionado todavía.';
                return;
            }

            agreementAttachmentsSummary.innerHTML = '';

            const countLabel = document.createElement('div');
            countLabel.className = 'font-medium text-slate-900';
            countLabel.textContent = `${selectedAgreementFiles.length} archivo(s) seleccionado(s)`;

            const filesList = document.createElement('div');
            filesList.className = 'mt-3 space-y-2';

            selectedAgreementFiles.forEach((file, index) => {
                const fileRow = document.createElement('div');
                fileRow.className = 'flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2';

                const fileName = document.createElement('div');
                fileName.className = 'min-w-0 flex-1 truncate text-sm text-slate-600';
                fileName.textContent = file.name;
                fileName.title = file.name;

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-rose-200 bg-white text-rose-600 transition hover:bg-rose-50';
                removeButton.dataset.attachmentIndex = String(index);
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

            agreementAttachmentsSummary.appendChild(countLabel);
            agreementAttachmentsSummary.appendChild(filesList);
        }

        openAgreementAttachmentsButton.addEventListener('click', () => {
            agreementAttachmentsInput.click();
        });

        agreementAttachmentsInput.addEventListener('change', () => {
            const newFiles = Array.from(agreementAttachmentsInput.files || []);

            if (!newFiles.length) {
                renderAgreementAttachmentsSummary();
                return;
            }

            const fileKeys = new Set(selectedAgreementFiles.map(buildAgreementAttachmentKey));

            newFiles.forEach((file) => {
                const fileKey = buildAgreementAttachmentKey(file);

                if (!fileKeys.has(fileKey)) {
                    selectedAgreementFiles.push(file);
                    fileKeys.add(fileKey);
                }
            });

            if (selectedAgreementFiles.length > 8) {
                selectedAgreementFiles = selectedAgreementFiles.slice(0, 8);
            }

            syncAgreementAttachmentsInput();
            renderAgreementAttachmentsSummary();
        });

        agreementAttachmentsSummary.addEventListener('click', (event) => {
            const removeButton = event.target.closest('[data-attachment-index]');

            if (!removeButton) {
                return;
            }

            const attachmentIndex = Number(removeButton.dataset.attachmentIndex);

            if (Number.isNaN(attachmentIndex)) {
                return;
            }

            selectedAgreementFiles.splice(attachmentIndex, 1);
            syncAgreementAttachmentsInput();
            renderAgreementAttachmentsSummary();
        });

        renderAgreementAttachmentsSummary();
    });
</script>
