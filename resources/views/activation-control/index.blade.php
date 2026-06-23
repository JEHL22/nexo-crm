<x-app-layout>
    <div class="py-8">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ implode(' | ', $errors->all()) }}
                </div>
            @endif

            @php
                $fieldTypes = [
                    'mes' => 'month',
                    'fecha_ingreso' => 'date',
                    'fecha_activacion' => 'date',
                    'porcentaje_dscto' => 'number',
                    'cf' => 'number',
                    'adic' => 'number',
                    'sva' => 'number',
                    'cf_sin_igv' => 'number',
                    'q' => 'number',
                    'score' => 'number',
                    'comentario' => 'textarea',
                    'f_cierre_op' => 'date',
                    'f_liberacion' => 'date',
                ];

                $fieldGroups = [
                    'Datos base' => [
                        'empresa',
                        'mes',
                        'fecha_ingreso',
                        'fecha_activacion',
                    ],
                    'Datos operativos' => [
                        'sec',
                        'py',
                        'sot',
                        'linea',
                        'large',
                    ],
                    'Datos del cliente' => [
                        'cliente',
                        'ruc',
                        'servicio',
                        'tipo_cliente',
                    ],
                    'Datos comerciales' => [
                        'plan_tarifario',
                        'porcentaje_dscto',
                        'ajuste',
                        'cf',
                        'adic',
                        'sva',
                        'cf_sin_igv',
                        'q',
                        'material',
                        'marca',
                        'consultor',
                        'modalidad',
                    ],
                    'Seguimiento final' => [
                        'estado',
                        'comentario',
                        'score',
                        'segmento',
                        'opotunidad',
                        'estado_sf',
                        'f_cierre_op',
                        'f_liberacion',
                        'validacion',
                    ],
                ];

                $numberSteps = [
                    'porcentaje_dscto' => '0.01',
                    'cf' => '0.01',
                    'adic' => '0.01',
                    'sva' => '0.01',
                    'cf_sin_igv' => '0.01',
                    'q' => '1',
                    'score' => '1',
                ];
            @endphp

            <div class="space-y-4" x-data="{ openPanel: true, openGroup: 'Datos base' }">
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <button
                        type="button"
                        class="flex w-full items-start justify-between gap-4 px-6 py-5 text-left"
                        @click="openPanel = !openPanel"
                        :aria-expanded="openPanel"
                    >
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Control de Activaciones</h1>
                            <p class="mt-1 text-sm text-gray-500">
                                Registra los campos reales del Excel de activaciones. Cada fila se guarda en base de datos y queda asociada al usuario que la registró.
                            </p>
                        </div>

                        <span class="mt-1 text-xl leading-none text-gray-400" x-text="openPanel ? '−' : '+'"></span>
                    </button>

                    <div x-show="openPanel" x-collapse class="border-t border-gray-100 px-6 pb-6 pt-5">
                        <form method="POST" action="{{ route('activation-control.store') }}" class="space-y-5">
                            @csrf

                            <div class="space-y-3">
                                @foreach($fieldGroups as $groupLabel => $groupFields)
                                    <div class="rounded-xl border border-gray-200">
                                        <button
                                            type="button"
                                            class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left"
                                            @click="openGroup = openGroup === '{{ $groupLabel }}' ? '' : '{{ $groupLabel }}'"
                                            :aria-expanded="openGroup === '{{ $groupLabel }}'"
                                        >
                                            <span class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-600">{{ $groupLabel }}</span>
                                            <span class="text-lg leading-none text-gray-400" x-text="openGroup === '{{ $groupLabel }}' ? '−' : '+'"></span>
                                        </button>

                                        <div
                                            x-show="openGroup === '{{ $groupLabel }}'"
                                            x-collapse
                                            class="border-t border-gray-100 px-4 pb-4 pt-3"
                                        >
                                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                                                @foreach($groupFields as $key)
                                                    @php
                                                        $label = $fields[$key] ?? $key;
                                                        $fieldType = $fieldTypes[$key] ?? 'text';
                                                        $isTextarea = $fieldType === 'textarea';
                                                        $oldValue = old($key);
                                                    @endphp

                                                    <div class="{{ $isTextarea ? 'md:col-span-2 xl:col-span-4' : '' }}">
                                                        <label for="field_{{ $key }}" class="mb-1 block text-sm font-medium text-gray-700">{{ $label }}</label>

                                                        @if($isTextarea)
                                                            <textarea
                                                                id="field_{{ $key }}"
                                                                name="{{ $key }}"
                                                                rows="2"
                                                                class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                                                placeholder="Ingresa {{ \Illuminate\Support\Str::lower($label) }}"
                                                            >{{ $oldValue }}</textarea>
                                                        @else
                                                            <input
                                                                id="field_{{ $key }}"
                                                                name="{{ $key }}"
                                                                type="{{ $fieldType }}"
                                                                value="{{ $oldValue }}"
                                                                @if(isset($numberSteps[$key])) step="{{ $numberSteps[$key] }}" @endif
                                                                class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                                                placeholder="Ingresa {{ \Illuminate\Support\Str::lower($label) }}"
                                                            >
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row">
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-xl bg-black px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800"
                                >
                                    Guardar registro
                                </button>

                                <button
                                    type="reset"
                                    class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                >
                                    Limpiar campos
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow">
                    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Registros guardados</h2>
                            <p class="mt-1 text-sm text-gray-500">
                                La tabla muestra los registros persistidos en base de datos con trazabilidad del usuario que los cargó.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('activation-control.export') }}">
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-black px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:bg-gray-300"
                                @disabled($records->isEmpty())
                            >
                                Exportar a Excel
                            </button>
                        </form>
                    </div>

                    @if($records->isEmpty())
                        <div class="rounded-xl border border-dashed border-gray-300 px-6 py-10 text-center text-sm text-gray-500">
                            Aún no hay registros guardados. Completa el formulario y usa "Guardar registro".
                        </div>
                    @else
                        <div class="overflow-x-auto rounded-2xl border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">#</th>
                                        @foreach($fields as $label)
                                            <th class="whitespace-nowrap px-4 py-3 text-left font-semibold text-gray-700">{{ $label }}</th>
                                        @endforeach
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Registro</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach($records as $record)
                                        @php
                                            $createdAt = $record->created_at?->setTimezone(config('app.timezone'));
                                            $updatedAt = $record->updated_at?->setTimezone(config('app.timezone'));
                                            $showUpdatedBy = $record->updated_by_user_id
                                                && ($record->updated_by_user_id !== $record->created_by_user_id || $record->updated_at?->ne($record->created_at));
                                        @endphp
                                        <tr class="align-top">
                                            <td class="px-4 py-3 text-gray-500">{{ $records->firstItem() + $loop->index }}</td>
                                            @foreach($fields as $key => $label)
                                                @php
                                                    $value = $record->{$key};
                                                    $displayValue = $value instanceof \Carbon\CarbonInterface
                                                        ? $value->format('Y-m-d')
                                                        : $value;
                                                @endphp
                                                <td class="{{ $key === 'comentario' ? 'min-w-[16rem] whitespace-normal px-4 py-3 text-gray-700' : 'whitespace-nowrap px-4 py-3 text-gray-700' }}">
                                                    {{ $displayValue !== null && $displayValue !== '' ? $displayValue : '-' }}
                                                </td>
                                            @endforeach
                                            <td class="min-w-[13rem] px-4 py-3 text-gray-700">
                                                <div class="font-medium text-gray-900">{{ $record->creator?->name ?? 'Usuario no disponible' }}</div>
                                                <div class="mt-1 text-xs text-gray-500">
                                                    {{ $createdAt?->format('d/m/Y H:i') ?? '-' }}
                                                </div>
                                                @if($showUpdatedBy)
                                                    <div class="mt-2 text-xs text-gray-500">
                                                        Actualizado por {{ $record->updater?->name ?? 'Usuario no disponible' }}
                                                        @if($updatedAt)
                                                            el {{ $updatedAt->format('d/m/Y H:i') }}
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $records->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
