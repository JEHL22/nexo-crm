<x-app-layout>
    <div class="py-8">
        <div class="max-w-[1800px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

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

            <div
                x-data="{ openModal: {{ $errors->any() ? 'true' : 'false' }} }"
                class="space-y-6"
            >
                <div class="overflow-hidden rounded-[28px] border border-amber-200 bg-white shadow-sm">
                    <div class="crm-panel-hero border-b border-amber-200 px-6 py-8 text-white sm:px-8">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                            <div class="max-w-2xl">
                                <p class="text-sm font-medium uppercase tracking-[0.22em] text-white/75">Prospección propia</p>
                                <h1 class="mt-3 text-3xl font-semibold tracking-tight">Mi base</h1>
                                <p class="mt-2 text-sm text-white/80 sm:text-base">
                                    Registra, organiza y da seguimiento a los leads que tú mismo incorporas al pipeline.
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                    <div class="text-xs uppercase tracking-[0.18em] text-white/70">Registros</div>
                                    <div class="mt-2 text-2xl font-semibold">{{ $leads->total() }}</div>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur">
                                    <div class="text-xs uppercase tracking-[0.18em] text-white/70">Página</div>
                                    <div class="mt-2 text-2xl font-semibold">{{ $leads->currentPage() }}</div>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 backdrop-blur sm:col-span-1 col-span-2">
                                    <div class="text-xs uppercase tracking-[0.18em] text-white/70">Búsqueda</div>
                                    <div class="mt-2 text-sm font-semibold">{{ $search !== '' ? $search : 'Sin filtro' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div></div>

                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="openModal = true"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-black text-white text-sm font-medium hover:bg-gray-800 transition"
                                >
                                    + Agregar lead
                                </button>

                                <a href="{{ route('my-work.index') }}"
                                   class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                    Volver a Mi chamba
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    x-show="openModal"
                    x-transition
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    style="display: none;"
                >
                    <div class="absolute inset-0 bg-black/40" @click="openModal = false"></div>

                    <div class="relative flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">Registrar lead</h2>
                                <p class="mt-1 text-sm text-gray-500">Agrega un nuevo lead manualmente a tu base personal.</p>
                            </div>

                            <button
                                type="button"
                                @click="openModal = false"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-300 text-gray-600 hover:bg-gray-50"
                            >
                                ✕
                            </button>
                        </div>

                        <form method="POST" action="{{ route('my-work.base.store') }}" class="flex min-h-0 flex-1 flex-col">
                            @csrf

                            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5">
                                <div class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(0,1fr)]">
                                    <div class="space-y-6">
                                        <div class="rounded-2xl border border-gray-200 bg-white p-5">
                                        <h3 class="text-base font-semibold text-gray-900">Nuevo cliente</h3>

                                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">RUC</label>
                                                <input
                                                    type="text"
                                                    name="ruc"
                                                    value="{{ old('ruc') }}"
                                                    class="mt-1 block w-full rounded border-gray-300"
                                                    inputmode="numeric"
                                                    pattern="[0-9]{11}"
                                                    maxlength="11"
                                                    autocomplete="off"
                                                    required
                                                >
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Razón Social</label>
                                                <input
                                                    type="text"
                                                    name="business_name"
                                                    value="{{ old('business_name') }}"
                                                    class="mt-1 block w-full rounded border-gray-300"
                                                    required
                                                >
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Nombre (Representante)</label>
                                                <input
                                                    type="text"
                                                    name="representative_name"
                                                    value="{{ old('representative_name') }}"
                                                    class="mt-1 block w-full rounded border-gray-300"
                                                    maxlength="255"
                                                    minlength="2"
                                                    autocomplete="off"
                                                    title="Solo letras, espacios, apóstrofes, puntos y guiones."
                                                >
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">DNI</label>
                                                <input
                                                    type="text"
                                                    name="dni"
                                                    value="{{ old('dni') }}"
                                                    class="mt-1 block w-full rounded border-gray-300"
                                                    inputmode="numeric"
                                                    pattern="[0-9]{8}"
                                                    maxlength="8"
                                                    autocomplete="off"
                                                >
                                            </div>

                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700">Dirección fiscal</label>
                                                <input
                                                    type="text"
                                                    name="fiscal_address"
                                                    value="{{ old('fiscal_address') }}"
                                                    class="mt-1 block w-full rounded border-gray-300"
                                                >
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">N° Teléfono principal</label>
                                                <input
                                                    type="text"
                                                    name="primary_phone"
                                                    value="{{ old('primary_phone') }}"
                                                    class="mt-1 block w-full rounded border-gray-300"
                                                    inputmode="numeric"
                                                    pattern="[0-9]{9}"
                                                    maxlength="9"
                                                    autocomplete="off"
                                                    required
                                                >
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700"># Líneas</label>
                                                <input
                                                    type="number"
                                                    name="current_line_count"
                                                    value="{{ old('current_line_count') }}"
                                                    class="mt-1 block w-full rounded border-gray-300"
                                                    min="0"
                                                >
                                            </div>

                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-medium text-gray-700">Celulares del lead</label>
                                                <div class="mt-1 grid grid-cols-1 gap-3 md:grid-cols-3">
                                                    @for($i = 0; $i < 3; $i++)
                                                        <input
                                                            type="text"
                                                            name="cellphones[]"
                                                            value="{{ old('cellphones.' . $i) }}"
                                                            class="block w-full rounded border-gray-300"
                                                            inputmode="numeric"
                                                            pattern="[0-9]{9}"
                                                            maxlength="9"
                                                            autocomplete="off"
                                                            placeholder="Celular adicional {{ $i + 1 }}"
                                                        >
                                                    @endfor
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Operador actual</label>
                                                <select
                                                    name="current_operator"
                                                    class="mt-1 block w-full rounded border-gray-300"
                                                >
                                                    <option value="">-- Selecciona --</option>
                                                    <option value="Claro" {{ old('current_operator') === 'Claro' ? 'selected' : '' }}>Claro</option>
                                                    <option value="Movistar" {{ old('current_operator') === 'Movistar' ? 'selected' : '' }}>Movistar</option>
                                                    <option value="Entel" {{ old('current_operator') === 'Entel' ? 'selected' : '' }}>Entel</option>
                                                    <option value="Bitel" {{ old('current_operator') === 'Bitel' ? 'selected' : '' }}>Bitel</option>
                                                </select>
                                            </div>
                                        </div>
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <div class="rounded-2xl border border-indigo-100 bg-indigo-50/60 p-5">
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="text-sm font-semibold text-indigo-900">Datos SISAC</div>
                                                <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-indigo-700">
                                                    Opcional
                                                </span>
                                            </div>

                                            <div class="mt-4 space-y-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Segmento</label>
                                                    <input
                                                        type="text"
                                                        name="segment"
                                                        value="{{ old('segment') }}"
                                                        class="mt-1 block w-full rounded border-gray-300 bg-white"
                                                    >
                                                </div>

                                                <div class="rounded-xl border border-slate-200 bg-white px-4 py-4">
                                                    <div class="text-sm font-semibold text-slate-900">Datos fija</div>

                                                    <div class="mt-3 space-y-3">
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700">Velocidad max</label>
                                                            <input
                                                                type="text"
                                                                name="max_speed"
                                                                value="{{ old('max_speed') }}"
                                                                class="mt-1 block w-full rounded border-gray-300"
                                                            >
                                                        </div>

                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700">Paquete</label>
                                                            <input
                                                                type="text"
                                                                name="package"
                                                                value="{{ old('package') }}"
                                                                class="mt-1 block w-full rounded border-gray-300"
                                                            >
                                                        </div>

                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700">Tecnología</label>
                                                            <input
                                                                type="text"
                                                                name="technology"
                                                                value="{{ old('technology') }}"
                                                                class="mt-1 block w-full rounded border-gray-300"
                                                            >
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 border-t border-gray-200 px-6 py-4">
                                <button
                                    type="button"
                                    @click="openModal = false"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                                >
                                    Cancelar
                                </button>

                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-black text-white text-sm font-medium hover:bg-gray-800 transition"
                                >
                                    Registrar lead
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="bg-white shadow rounded-2xl p-6">
                    <form method="GET" action="{{ route('my-work.base') }}" class="mb-6">
                        <div class="flex flex-col md:flex-row gap-3 md:items-center">
                            <div class="w-full md:w-72">
                                <input
                                    type="text"
                                    name="search"
                                    value="{{ $search }}"
                                    placeholder="Buscar por RUC o Razón Social"
                                    class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                >
                            </div>

                            <div class="flex gap-2">
                                <button type="submit"
                                    class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-black text-white text-sm font-medium hover:bg-gray-800 transition">
                                    Filtrar
                                </button>

                                <a href="{{ route('my-work.base') }}"
                                   class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                    Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    @if($leads->isEmpty())
                        <div class="border border-dashed border-gray-300 rounded-xl p-8 text-center text-gray-500">
                            No has registrado leads en Mi base todavía.
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach($leads as $lead)
                                @php
                                    $phones = $lead->phones->sortByDesc('is_primary')->values();
                                    $phone = optional($phones->first())->phone;
                                @endphp

                                <div class="rounded-2xl border border-gray-300 bg-white px-5 py-4">
                                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                        <div class="flex-1">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 text-sm text-gray-900">
                                                <div class="space-y-2">
                                                    <div><span class="font-semibold">RUC:</span> {{ $lead->ruc ?? '-' }}</div>
                                                    <div><span class="font-semibold">Razón Social:</span> {{ $lead->business_name ?? '-' }}</div>
                                                    <div><span class="font-semibold">N° principal:</span> {{ $phone ?? '-' }}</div>
                                                </div>

                                                <div class="space-y-2">
                                                    <div><span class="font-semibold">Operador actual:</span> {{ $lead->current_operator ?? '-' }}</div>
                                                    <div><span class="font-semibold"># Líneas:</span> {{ $lead->current_line_count ?? '-' }}</div>
                                                    <div><span class="font-semibold">Estado:</span> {{ $lead->status_specific ?? 'sin_gestion' }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="lg:self-center">
                                            <a href="{{ route('my-work.show', $lead->id) }}"
                                               class="inline-flex items-center justify-center px-4 py-2 rounded-xl border-2 border-gray-900 text-gray-900 font-semibold hover:bg-gray-50 transition">
                                                Detalle
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $leads->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
