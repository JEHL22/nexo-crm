<x-app-layout>
    <div class="py-8">
        <div class="max-w-[1800px] mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Mi cobertura</h1>
                        <p class="mt-1 text-sm text-gray-500">
                            Consulta solo provincias habilitadas y registra una captura operativa de recojo para tu gestión comercial.
                        </p>
                    </div>

                    <div id="executiveCoverageStatusPill" class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700">
                        Cargando cobertura habilitada...
                    </div>
                </div>

                <div id="executiveCoverageError" class="hidden mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                <div
                    id="executiveCoveragePage"
                    data-data-url="{{ route('executive-coverage.data') }}"
                    class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,2fr)_320px]"
                >
                    <div class="rounded-2xl border border-gray-200 overflow-hidden">
                        <div class="flex items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Mapa de provincias habilitadas</div>
                                <div class="text-xs text-gray-500">Solo se muestran provincias activas para la gestión comercial.</div>
                            </div>

                            <button id="executiveCoverageResetViewButton" type="button" class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-white transition">
                                Recentrar mapa
                            </button>
                        </div>

                        <div id="executiveCoverageMap" class="relative h-[62vh] min-h-[560px] bg-slate-100"></div>
                    </div>

                    <div class="space-y-3 xl:flex xl:h-[62vh] xl:min-h-[620px] xl:flex-col">
                        <div class="rounded-2xl border border-gray-200 bg-white p-4 flex flex-col overflow-hidden xl:flex-1">
                            <div class="flex items-center justify-between gap-2.5">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">Provincias habilitadas</h2>
                                </div>
                                <div class="rounded-xl bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-700">Solo lectura</div>
                            </div>
                            <div id="executiveCoverageProvinceList" class="mt-3 grid flex-1 grid-cols-1 gap-1.5 overflow-y-auto pr-1 sm:grid-cols-2"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="executiveCoverageModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-gray-900/60 px-4">
        <div class="w-full max-w-5xl max-h-[92vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between gap-4 border-b border-gray-200 pb-4">
                <div>
                    <h3 id="executiveCoverageModalTitle" class="text-xl font-bold text-gray-900">Gestión operativa</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Visualiza la cobertura habilitada y la configuración vigente definida por Mesa de Control.
                    </p>
                </div>

                <button id="executiveCoverageModalCloseButton" type="button" class="text-3xl font-bold leading-none text-gray-400 hover:text-red-500">&times;</button>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.95fr)]">
                <div class="space-y-5">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Provincia habilitada</label>
                            <input id="executiveCoverageProvinceField" type="text" readonly class="mt-1 block w-full rounded-xl border-gray-300 bg-gray-100 text-gray-700">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Estado operativo</label>
                            <input value="Habilitado" type="text" readonly class="mt-1 block w-full rounded-xl border-gray-300 bg-emerald-50 text-emerald-700 font-semibold">
                        </div>
                    </div>

                    <div>
                        <div>
                            <label for="executiveCoverageDistrictField" class="block text-sm font-medium text-gray-700">Distrito habilitado</label>
                            <select id="executiveCoverageDistrictField" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"></select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label for="executiveCoverageClosingTimeField" class="block text-sm font-medium text-gray-700">Hora de cierre</label>
                            <input id="executiveCoverageClosingTimeField" type="time" readonly class="mt-1 block w-full rounded-xl border-gray-300 bg-gray-100 text-gray-700">
                        </div>

                        <div>
                            <label for="executiveCoverageVisitTimeField" class="block text-sm font-medium text-gray-700">Hora de visita</label>
                            <input id="executiveCoverageVisitTimeField" type="time" readonly class="mt-1 block w-full rounded-xl border-gray-300 bg-gray-100 text-gray-700">
                        </div>

                        <div>
                            <label for="executiveCoverageDeliveryTimeField" class="block text-sm font-medium text-gray-700">Hora de entrega</label>
                            <input id="executiveCoverageDeliveryTimeField" type="time" readonly class="mt-1 block w-full rounded-xl border-gray-300 bg-gray-100 text-gray-700">
                        </div>
                    </div>

                    <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                        Esta información es solo de consulta y siempre refleja la última configuración vigente definida por Mesa de Control para la provincia habilitada.
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 p-4">
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900">Distritos habilitados</h4>
                        <p id="executiveCoverageDistrictHint" class="text-sm text-gray-500 mt-1"></p>
                    </div>

                    <div id="executiveCoverageDistrictList" class="mt-4 flex flex-wrap gap-2"></div>
                </div>

                <div class="xl:col-span-2 flex justify-end gap-3 border-t border-gray-200 pt-5">
                    <button id="executiveCoverageModalCancelButton" type="button" class="rounded-xl border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        #executiveCoverageMap {
            z-index: 0;
        }

        #executiveCoverageMap .leaflet-pane,
        #executiveCoverageMap .leaflet-control,
        #executiveCoverageMap .leaflet-top,
        #executiveCoverageMap .leaflet-bottom {
            z-index: 1 !important;
        }

        #executiveCoverageMap .leaflet-tooltip-pane {
            z-index: 2 !important;
        }

        #executiveCoverageMap .leaflet-popup-pane,
        #executiveCoverageMap .leaflet-marker-pane {
            z-index: 3 !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const page = document.getElementById('executiveCoveragePage');

            if (!page || !window.L) {
                return;
            }

            const mapElement = document.getElementById('executiveCoverageMap');
            const modalElement = document.getElementById('executiveCoverageModal');
            const statusPill = document.getElementById('executiveCoverageStatusPill');
            const errorElement = document.getElementById('executiveCoverageError');
            const provinceList = document.getElementById('executiveCoverageProvinceList');

            const fieldRefs = {
                province: document.getElementById('executiveCoverageProvinceField'),
                district: document.getElementById('executiveCoverageDistrictField'),
                closing_time: document.getElementById('executiveCoverageClosingTimeField'),
                visit_time: document.getElementById('executiveCoverageVisitTimeField'),
                delivery_time: document.getElementById('executiveCoverageDeliveryTimeField'),
            };

            let map;
            let payload;
            let selectedProvinceId = null;
            let allProvinceBounds = null;
            let provinceLayer = null;
            let highlightLayer = null;
            const provinceBoundsById = {};
            const activeMarkers = [];

            const getActiveLocationFeatures = () => payload?.active_locations?.features ?? [];
            const getProvinceFeatures = () => payload?.map?.provinces?.features ?? [];
            const getProvinceById = (provinceId) => payload?.province_catalog_by_id?.[provinceId] ?? null;
            const toLatLng = (coordinates) => [coordinates[1], coordinates[0]];

            const showError = (message) => {
                errorElement.textContent = message;
                errorElement.classList.remove('hidden');
            };

            const hideError = () => {
                errorElement.classList.add('hidden');
            };

            const renderProvinceList = () => {
                provinceList.innerHTML = '';

                (payload?.province_catalog ?? []).forEach((province) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'rounded-xl bg-blue-500 px-3 py-2.5 text-left text-sm font-semibold text-white shadow-sm hover:bg-blue-600 transition';
                    button.textContent = province.name;
                    button.addEventListener('click', () => {
                        selectProvince(province.id, true);
                        openModal();
                    });
                    provinceList.appendChild(button);
                });
            };

            const populateDistrictInput = (provinceId) => {
                const province = getProvinceById(provinceId);

                fieldRefs.district.innerHTML = '';

                province.districts.forEach((district) => {
                    const option = document.createElement('option');
                    option.value = district.id;
                    option.textContent = district.name;
                    fieldRefs.district.appendChild(option);
                });

                fieldRefs.district.value = province.districts[0]?.id ?? '';
            };

            const renderDistrictList = (provinceId) => {
                const province = getProvinceById(provinceId);
                const districtHint = document.getElementById('executiveCoverageDistrictHint');
                const districtList = document.getElementById('executiveCoverageDistrictList');

                districtHint.textContent = `${province.districts.length} distrito(s) habilitado(s) por Mesa de Control.`;
                districtList.innerHTML = '';

                if (!province.districts.length) {
                    const empty = document.createElement('div');
                    empty.className = 'rounded-xl border border-dashed border-gray-300 px-4 py-3 text-sm text-gray-500';
                    empty.textContent = 'No hay distritos habilitados configurados para esta provincia.';
                    districtList.appendChild(empty);
                    return;
                }

                province.districts.forEach((district) => {
                    const chip = document.createElement('span');
                    chip.className = 'inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700';
                    chip.textContent = district.name;
                    districtList.appendChild(chip);
                });
            };

            const fitToProvince = (provinceId) => {
                const bounds = provinceBoundsById[provinceId];

                if (!map || !bounds) {
                    return;
                }

                map.fitBounds(bounds, {
                    padding: [32, 32],
                    animate: true,
                });
            };

            const setHighlightProvince = (provinceId) => {
                if (!map || !highlightLayer) {
                    return;
                }

                highlightLayer.clearLayers();

                if (!provinceId) {
                    return;
                }

                const selectedFeatures = getProvinceFeatures().filter((feature) => String(feature.properties.province_id) === String(provinceId));
                selectedFeatures.forEach((feature) => highlightLayer.addData(feature));
            };

            const selectProvince = (provinceId, shouldFocus = false) => {
                if (!getProvinceById(provinceId)) {
                    return;
                }

                selectedProvinceId = provinceId;
                setHighlightProvince(provinceId);

                if (shouldFocus) {
                    fitToProvince(provinceId);
                }
            };

            const setMapVisibility = (visible) => {
                const container = map?.getContainer?.();

                if (container) {
                    container.style.visibility = visible ? 'visible' : 'hidden';
                }
            };

            const setMarkersVisibility = (visible) => {
                const display = visible ? '' : 'none';

                activeMarkers.forEach((marker) => {
                    const element = marker.getElement();
                    if (element) {
                        element.style.display = display;
                    }
                });
            };

            const openModal = () => {
                if (!selectedProvinceId) {
                    return;
                }

                const province = getProvinceById(selectedProvinceId);
                const config = province?.config ?? payload?.defaults?.config ?? {};

                document.getElementById('executiveCoverageModalTitle').textContent = `Recojo de ${province.name}`;
                fieldRefs.province.value = province.name;
                populateDistrictInput(selectedProvinceId);
                renderDistrictList(selectedProvinceId);
                fieldRefs.closing_time.value = config.closing_time ?? '18:00';
                fieldRefs.visit_time.value = config.visit_time ?? '09:00';
                fieldRefs.delivery_time.value = config.delivery_time ?? '15:00';

                setMarkersVisibility(false);
                setMapVisibility(false);
                modalElement.classList.remove('hidden');
                modalElement.classList.add('flex');
            };

            const closeModal = () => {
                modalElement.classList.add('hidden');
                modalElement.classList.remove('flex');
                setMapVisibility(true);
                setMarkersVisibility(true);
            };

            const createTextMarker = (feature, clickHandler) => {
                const marker = window.L.marker(
                    toLatLng(feature.geometry.coordinates),
                    {
                        icon: window.L.divIcon({
                            className: '',
                            html: `<button type="button" class="coverage-active-marker">${feature.properties.location_label ?? feature.properties.province_name}</button>`,
                            iconSize: null,
                        }),
                        keyboard: false,
                    }
                );

                marker.on('click', clickHandler);

                return marker;
            };

            const initializeMap = () => {
                map = window.L.map(mapElement, {
                    zoomControl: false,
                    attributionControl: false,
                    minZoom: 4.2,
                    maxZoom: 11,
                });

                window.L.control.zoom({ position: 'topright' }).addTo(map);

                provinceLayer = window.L.geoJSON(payload.map.provinces, {
                    style: (feature) => {
                        const provinceId = String(feature.properties.province_id);
                        const isActive = (payload?.active_province_ids ?? []).includes(provinceId);

                        return {
                            color: isActive ? '#64748b' : '#94a3b8',
                            weight: isActive ? 1.1 : 0.9,
                            fillColor: isActive ? '#5b9ae6' : '#ffffff',
                            fillOpacity: isActive ? 0.8 : 0.12,
                        };
                    },
                    onEachFeature: (feature, layer) => {
                        const provinceId = String(feature.properties.province_id);
                        const bounds = layer.getBounds();

                        provinceBoundsById[provinceId] = provinceBoundsById[provinceId]
                            ? provinceBoundsById[provinceId].extend(bounds)
                            : bounds;

                        layer.on({
                            mouseover: () => {
                                mapElement.style.cursor = 'pointer';
                            },
                            mouseout: () => {
                                mapElement.style.cursor = '';
                            },
                            click: () => {
                                selectProvince(provinceId, false);
                                openModal();
                            },
                        });
                    },
                }).addTo(map);

                allProvinceBounds = provinceLayer.getBounds();

                highlightLayer = window.L.geoJSON([], {
                    interactive: false,
                    style: {
                        color: '#111827',
                        weight: 3,
                        fillOpacity: 0,
                    },
                }).addTo(map);

                getActiveLocationFeatures().forEach((feature) => {
                    const marker = createTextMarker(feature, () => {
                        selectProvince(feature.properties.province_id, false);
                        openModal();
                    });

                    marker.addTo(map);
                    activeMarkers.push(marker);
                });

                if (allProvinceBounds.isValid()) {
                    map.fitBounds(allProvinceBounds, {
                        padding: [18, 18],
                    });
                }
            };

            const initializePage = async () => {
                hideError();

                try {
                    const response = await window.fetch(page.dataset.dataUrl, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('No se pudo cargar la cobertura del ejecutivo.');
                    }

                    payload = await response.json();
                    renderProvinceList();
                    initializeMap();

                    statusPill.textContent = `Cobertura lista: ${payload.province_catalog.length} provincias habilitadas`;
                    statusPill.className = 'inline-flex items-center rounded-full border border-green-200 bg-green-50 px-4 py-2 text-sm font-medium text-green-700';
                } catch (error) {
                    statusPill.textContent = 'No se pudo cargar la cobertura';
                    statusPill.className = 'inline-flex items-center rounded-full border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-700';
                    showError(error.message);
                }
            };

            document.getElementById('executiveCoverageResetViewButton').addEventListener('click', () => {
                if (!map || !allProvinceBounds || !allProvinceBounds.isValid()) {
                    return;
                }

                map.fitBounds(allProvinceBounds, {
                    padding: [18, 18],
                    animate: true,
                });
            });

            document.getElementById('executiveCoverageModalCloseButton').addEventListener('click', closeModal);
            document.getElementById('executiveCoverageModalCancelButton').addEventListener('click', closeModal);

            modalElement.addEventListener('click', (event) => {
                if (event.target === modalElement) {
                    closeModal();
                }
            });

            initializePage();
        });
    </script>
</x-app-layout>
