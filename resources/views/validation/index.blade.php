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

            <div class="rounded-lg bg-white p-6 shadow">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Validación</h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Revisión de ventas aceptadas y actualización del estado de Mesa de Control.
                    </p>
                </div>

                <form method="GET" action="{{ route('validation.index') }}" class="mb-4">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center">
                        <div class="w-full md:w-56">
                            <select name="sisac_status" class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Estado de M.C</option>
                                @foreach($statusOptions as $key => $label)
                                    <option value="{{ $key }}" @selected(($filters['sisac_status'] ?? '') === $key)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="w-full md:w-56">
                            <input
                                type="text"
                                name="ruc"
                                value="{{ $filters['ruc'] ?? '' }}"
                                placeholder="Buscar : RUC"
                                class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-black px-4 py-2.5 text-sm font-medium text-white transition hover:bg-gray-800"
                            >
                                Filtrar
                            </button>

                            @if(array_filter($filters))
                                <a
                                    href="{{ route('validation.index') }}"
                                    class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50"
                                >
                                    Limpiar
                                </a>
                            @endif
                        </div>
                    </div>
                </form>

                <div class="mb-4 flex justify-end">
                    <p id="validationUpdatedAtLabel" class="text-xs font-medium text-gray-400">
                        Actualizado: {{ now()->setTimezone(config('app.timezone'))->format('d/m/Y H:i:s') }}
                    </p>
                </div>

                <div id="validationList">
                    @include('validation.partials.list', [
                        'sales' => $sales,
                        'statusOptions' => $statusOptions,
                        'statusLabels' => $statusLabels,
                    ])
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const listContainer = document.getElementById('validationList');
            const updatedAtLabel = document.getElementById('validationUpdatedAtLabel');
            const pulseUrl = @json($pulseRoute);

            if (!listContainer || !pulseUrl) {
                return;
            }

            let pulseInFlight = false;

            function hasOpenValidationModal() {
                return Boolean(document.querySelector('[data-validation-modal-open="1"], [data-validation-delivery-modal-open="1"]'));
            }

            async function refreshValidationBoard() {
                if (pulseInFlight || document.hidden || hasOpenValidationModal()) {
                    return;
                }

                pulseInFlight = true;
                const previousScrollY = window.scrollY;
                const previousScrollX = window.scrollX;

                try {
                    const response = await window.fetch(pulseUrl, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    if (!response.ok) {
                        throw new Error('No se pudo refrescar la vista de validación.');
                    }

                    const payload = await response.json();

                    if (payload.list_html && !hasOpenValidationModal()) {
                        listContainer.innerHTML = payload.list_html;
                        window.scrollTo({
                            top: previousScrollY,
                            left: previousScrollX,
                            behavior: 'auto',
                        });
                    }

                    if (updatedAtLabel && payload.updated_at_label) {
                        updatedAtLabel.textContent = `Actualizado: ${payload.updated_at_label}`;
                    }
                } catch (error) {
                    console.error(error);
                } finally {
                    pulseInFlight = false;
                }
            }

            refreshValidationBoard();
            window.setInterval(refreshValidationBoard, 5000);
            window.addEventListener('focus', refreshValidationBoard);
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    refreshValidationBoard();
                }
            });
        });
    </script>
</x-app-layout>
