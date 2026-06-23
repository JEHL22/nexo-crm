<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white shadow rounded-2xl p-6">
                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Importar leads</h1>
                        <p class="mt-1 text-sm text-gray-500">
                            Carga leads para el modulo A negociar usando un archivo CSV o Excel, incluyendo los datos visibles en Nuevo Cliente.
                        </p>
                    </div>

                    <a href="{{ route('admin.leads.template') }}"
                       class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-gray-900 text-gray-900 font-medium hover:bg-gray-50 transition">
                        Descargar plantilla {{ $excelAvailable ? 'Excel' : 'CSV' }}
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php($summary = session('import_summary'))
            @if ($summary)
                <div class="bg-white shadow rounded-2xl p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Resultado de la importacion</h2>

                    <div class="grid gap-4 md:grid-cols-4">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-sm text-slate-500">Filas procesadas</div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">{{ $summary['processed'] }}</div>
                        </div>

                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                            <div class="text-sm text-emerald-700">Creados</div>
                            <div class="mt-2 text-2xl font-semibold text-emerald-900">{{ $summary['created'] }}</div>
                        </div>

                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <div class="text-sm text-amber-700">Actualizados</div>
                            <div class="mt-2 text-2xl font-semibold text-amber-900">{{ $summary['updated'] }}</div>
                        </div>

                        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                            <div class="text-sm text-rose-700">Omitidos</div>
                            <div class="mt-2 text-2xl font-semibold text-rose-900">{{ $summary['skipped'] }}</div>
                        </div>
                    </div>

                    @if (!empty($summary['errors']))
                        <div class="rounded-xl border border-slate-200 p-4">
                            <h3 class="text-sm font-semibold text-gray-900">Observaciones</h3>
                            <ul class="mt-3 space-y-2 text-sm text-gray-600">
                                @foreach (collect($summary['errors'])->take(20) as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>

                            @if (count($summary['errors']) > 20)
                                <p class="mt-3 text-xs text-gray-500">
                                    Se muestran solo las primeras 20 observaciones.
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(320px,0.8fr)]">
                <div class="bg-white shadow rounded-2xl p-6">
                    <form method="POST" action="{{ route('admin.leads.store') }}" enctype="multipart/form-data" class="space-y-5">
                        @csrf

                        <div>
                            <label for="file" class="block text-sm font-medium text-gray-700">Archivo</label>
                            <input
                                id="file"
                                name="file"
                                type="file"
                                accept=".csv,.txt,.xlsx,.xls"
                                class="mt-2 block w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                required
                            >
                            <p class="mt-2 text-xs text-gray-500">
                                {{ $excelAvailable
                                    ? 'Soporta CSV y Excel (.xlsx, .xls).'
                                    : 'Soporta CSV de inmediato. Excel (.xlsx, .xls) requiere la libreria phpoffice/phpspreadsheet.' }}
                            </p>
                        </div>

                        <div id="previewState" class="hidden rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                            Preparando vista previa...
                        </div>

                        <div id="previewPanel" class="hidden rounded-xl border border-slate-200 p-4 space-y-4">
                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <h2 class="text-sm font-semibold text-slate-900">Vista previa del archivo</h2>
                                    <p id="previewMeta" class="mt-1 text-xs text-slate-500"></p>
                                </div>
                            </div>

                            <div id="previewWarnings" class="hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"></div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3 text-sm">
                                    <div>
                                        <div class="font-semibold text-slate-900">Bloque Lead</div>
                                        <p class="mt-1 text-slate-600">
                                            Campaña, RUC, razón social, representante, DNI, dirección fiscal, operador actual, líneas actuales, segmento, datos fija y teléfonos.
                                        </p>
                                    </div>

                                    @if ($hasSisacTable)
                                        <div>
                                            <div class="font-semibold text-slate-900">Bloque SISAC</div>
                                            <p class="mt-1 text-slate-600">
                                                Semáforo, resultado, líneas a ofrecer, depósito de garantía y rango LC disponible.
                                            </p>
                                        </div>
                                    @endif

                                    <div>
                                        <div class="font-semibold text-slate-900">Regla de carga</div>
                                        <p class="mt-1 text-slate-600">
                                            Si el lead no tiene gestión previa, se actualiza; si ya tiene historial, se omite.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="overflow-x-auto rounded-xl border border-slate-200">
                                <table class="min-w-full divide-y divide-slate-200 text-sm">
                                    <thead class="bg-slate-50 text-slate-700">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-medium">Fila</th>
                                            <th class="px-3 py-2 text-left font-medium">Campaña</th>
                                            <th class="px-3 py-2 text-left font-medium">RUC</th>
                                            <th class="px-3 py-2 text-left font-medium">Razón social</th>
                                            <th class="px-3 py-2 text-left font-medium">Representante</th>
                                            <th class="px-3 py-2 text-left font-medium">Dirección fiscal</th>
                                            <th class="px-3 py-2 text-left font-medium">Operador</th>
                                            <th class="px-3 py-2 text-left font-medium"># Líneas</th>
                                            <th class="px-3 py-2 text-left font-medium">Segmento</th>
                                            <th class="px-3 py-2 text-left font-medium">Velocidad max</th>
                                            <th class="px-3 py-2 text-left font-medium">Paquete</th>
                                            <th class="px-3 py-2 text-left font-medium">Tecnología</th>
                                            <th class="px-3 py-2 text-left font-medium">Teléfonos</th>
                                            @if ($hasSisacTable)
                                                <th class="px-3 py-2 text-left font-medium">Semáforo</th>
                                                <th class="px-3 py-2 text-left font-medium">Resultado</th>
                                                <th class="px-3 py-2 text-left font-medium">Líneas a ofrecer</th>
                                                <th class="px-3 py-2 text-left font-medium">Depósito garantía</th>
                                                <th class="px-3 py-2 text-left font-medium">Rango LC</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody id="previewBody" class="divide-y divide-slate-100 bg-white"></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                            <div class="font-semibold text-slate-900">Comportamiento de la importacion</div>
                            <ul class="mt-3 space-y-2">
                                <li>Los leads se cargan con estado disponible para que aparezcan en A negociar.</li>
                                <li>Si el RUC ya existe en la misma campaña y no tiene gestion, se actualiza.</li>
                                <li>Si el lead ya tiene historial de gestion, se omite para no perder trazabilidad.</li>
                                <li>Los campos visibles para el ejecutivo tambien se pueden cargar desde el archivo: dirección fiscal, segmento, velocidad max, paquete y tecnología.</li>
                                @if ($hasSisacTable)
                                    <li>Los datos SISAC se guardan en su tabla vinculada para mostrarse en el bloque Nuevo Cliente del ejecutivo.</li>
                                @endif
                            </ul>
                        </div>

                        <button type="submit"
                                class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-black text-white font-medium hover:bg-gray-800 transition">
                            Importar archivo
                        </button>
                    </form>
                </div>

                <div class="space-y-6">
                    <div class="bg-white shadow rounded-2xl p-6">
                        <h2 class="text-lg font-semibold text-gray-900">Estructura esperada</h2>
                        <div class="mt-4 space-y-4">
                            <div class="rounded-xl border border-slate-200 p-4">
                                <div class="text-sm font-semibold text-slate-900">Datos base del lead</div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($expectedLeadColumns as $column)
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                            {{ $column }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            @if ($hasSisacTable)
                                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                                    <div class="text-sm font-semibold text-amber-900">Datos SISAC del lead</div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($expectedSisacColumns as $column)
                                            <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-medium text-amber-800 ring-1 ring-amber-200">
                                                {{ $column }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <p class="mt-4 text-sm text-gray-500">
                            Tambien puedes usar <code>campaign_id</code> en lugar de <code>campaign</code>. El importador reconoce alias comunes como <code>razon_social</code>, <code>direccion_fiscal</code>, <code>segmento</code>, <code>velocidad_max</code>, <code>paquete_plan</code> y <code>tecnologia_servicio</code>.
                        </p>
                    </div>

                    <div class="bg-white shadow rounded-2xl p-6">
                        <h2 class="text-lg font-semibold text-gray-900">Campanas disponibles</h2>

                        @if ($campaigns->isEmpty())
                            <p class="mt-3 text-sm text-gray-500">
                                Aun no hay campanas creadas. Crea una primero para poder importar leads.
                            </p>
                        @else
                            <ul class="mt-4 space-y-2 text-sm text-gray-700">
                                @foreach ($campaigns as $campaign)
                                    <li>{{ $campaign->name }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="bg-white shadow rounded-2xl p-6">
                        <h2 class="text-lg font-semibold text-gray-900">Estado de Excel</h2>
                        <p class="mt-3 text-sm {{ $excelAvailable ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $excelAvailable ? 'La importacion Excel esta habilitada.' : 'La importacion Excel quedara habilitada cuando instales phpoffice/phpspreadsheet.' }}
                        </p>
                        @unless ($excelAvailable)
                            <pre class="mt-4 rounded-xl bg-slate-950 px-4 py-3 text-xs text-slate-100 overflow-x-auto">composer require phpoffice/phpspreadsheet</pre>
                        @endunless
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('file');
            const previewState = document.getElementById('previewState');
            const previewPanel = document.getElementById('previewPanel');
            const previewMeta = document.getElementById('previewMeta');
            const previewBody = document.getElementById('previewBody');
            const previewWarnings = document.getElementById('previewWarnings');

            if (!fileInput) {
                return;
            }

            fileInput.addEventListener('change', async () => {
                const file = fileInput.files?.[0];

                previewPanel.classList.add('hidden');
                previewWarnings.classList.add('hidden');
                previewWarnings.textContent = '';
                previewBody.innerHTML = '';

                if (!file) {
                    previewState.classList.add('hidden');
                    previewState.textContent = 'Preparando vista previa...';
                    return;
                }

                previewState.classList.remove('hidden');
                previewState.textContent = 'Preparando vista previa...';

                const formData = new FormData();
                formData.append('file', file);

                try {
                    const response = await fetch('{{ route('admin.leads.preview') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        const message = data?.message || 'No se pudo generar la vista previa.';
                        throw new Error(message);
                    }

                    previewState.classList.add('hidden');
                    previewPanel.classList.remove('hidden');
                    previewMeta.textContent = `Se detectaron ${data.total_rows} filas con datos. Se muestran las primeras ${data.rows.length}.`;

                    previewBody.innerHTML = data.rows.map((row) => `
                        <tr>
                            <td class="px-3 py-2 text-slate-600">${row.row_number}</td>
                            <td class="px-3 py-2 text-slate-900">${row.campaign || '-'}</td>
                            <td class="px-3 py-2 text-slate-900">${row.ruc || '-'}</td>
                            <td class="px-3 py-2 text-slate-900">${row.business_name || '-'}</td>
                            <td class="px-3 py-2 text-slate-900">${row.representative_name || '-'}</td>
                            <td class="px-3 py-2 text-slate-900">${row.fiscal_address || '-'}</td>
                            <td class="px-3 py-2 text-slate-900">${row.current_operator || '-'}</td>
                            <td class="px-3 py-2 text-slate-900">${row.current_line_count || '-'}</td>
                            <td class="px-3 py-2 text-slate-900">${row.segment || '-'}</td>
                            <td class="px-3 py-2 text-slate-900">${row.max_speed || '-'}</td>
                            <td class="px-3 py-2 text-slate-900">${row.package || '-'}</td>
                            <td class="px-3 py-2 text-slate-900">${row.technology || '-'}</td>
                            <td class="px-3 py-2 text-slate-900">${row.phones || '-'}</td>
                            ${data.has_sisac_table ? `
                                <td class="px-3 py-2 text-slate-900">${row.semaforo || '-'}</td>
                                <td class="px-3 py-2 text-slate-900">${row.resultado || '-'}</td>
                                <td class="px-3 py-2 text-slate-900">${row.cantidad_lineas_ofrecer || '-'}</td>
                                <td class="px-3 py-2 text-slate-900">${row.deposito_garantia || '-'}</td>
                                <td class="px-3 py-2 text-slate-900">${row.rango_lc_disponible || '-'}</td>
                            ` : ''}
                        </tr>
                    `).join('');

                    if (data.warnings.length > 0) {
                        previewWarnings.classList.remove('hidden');
                        previewWarnings.innerHTML = `
                            <div class="font-medium">Observaciones detectadas en la vista previa</div>
                            <ul class="mt-2 space-y-1">
                                ${data.warnings.map((warning) => `<li>${warning}</li>`).join('')}
                            </ul>
                        `;
                    }
                } catch (error) {
                    previewState.textContent = error.message || 'No se pudo generar la vista previa.';
                }
            });
        });
    </script>
</x-app-layout>
