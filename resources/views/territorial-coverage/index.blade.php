<x-app-layout>
    <div class="py-8">
        <div class="max-w-[1800px] mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Cobertura territorial</h1>
                    </div>

                    <div id="coverageStatusPill" class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700">
                        Cargando mapa y cat&aacute;logos...
                    </div>
                </div>

                <div id="coverageMissingAlert" class="hidden mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"></div>
                <div id="coverageSourceAlert" class="hidden mb-6 rounded-xl border px-4 py-3 text-sm"></div>
                <div id="coverageToast" class="hidden mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"></div>
                <div id="coverageError" class="hidden mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                <div
                    id="territorialCoveragePage"
                    data-data-url="{{ route('territorial-coverage.data') }}"
                    data-save-url-template="{{ route('territorial-coverage.provinces.update', ['provinceId' => '__PROVINCE__']) }}"
                    class="grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,2fr)_320px]"
                >
                    <div class="rounded-2xl border border-gray-200 overflow-hidden">
                        <div class="flex items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3">
                            <div>
                                <div class="text-sm font-semibold text-gray-900">Mapa provincial</div>
                                <div class="text-xs text-gray-500">El mapa prioriza provincias activas. Haz clic sobre una provincia o etiqueta activa para abrir el modal.</div>
                            </div>

                            <button id="coverageResetViewButton" type="button" class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-white transition">
                                Recentrar mapa
                            </button>
                        </div>

                        <div id="coverageMap" class="relative h-[62vh] min-h-[560px] bg-slate-100"></div>
                    </div>

                    <div class="space-y-3 xl:flex xl:h-[62vh] xl:min-h-[620px] xl:flex-col">
                        <div class="rounded-2xl border border-gray-200 bg-white p-4">
                            <label for="coverageProvinceSearch" class="block text-sm font-medium text-gray-700 mb-1.5">Buscar provincia o activo</label>
                            <input id="coverageProvinceSearch" type="text" placeholder="Ej. Piura, Juliaca, Cañete..." class="w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <div id="coverageSearchResults" class="mt-2.5 max-h-56 overflow-y-auto space-y-1.5"></div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white p-4 flex flex-col overflow-hidden xl:flex-1">
                            <div class="flex items-center justify-between gap-2.5">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">Activos priorizados</h2>
                                </div>
                                <div class="rounded-xl bg-blue-50 px-2.5 py-1.5 text-xs font-semibold text-blue-700">Express Chip</div>
                            </div>
                            <div id="coverageActiveLocationList" class="mt-3 grid flex-1 grid-cols-1 gap-1.5 overflow-y-auto pr-1 sm:grid-cols-2"></div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="coverageModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-gray-900/60 px-4">
        <div class="w-full max-w-5xl max-h-[92vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-xl">
            <div class="flex items-center justify-between gap-4 border-b border-gray-200 pb-4">
                <div>
                    <h3 id="coverageModalTitle" class="text-xl font-bold text-gray-900">Configuraci&oacute;n provincial</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Completa la configuraci&oacute;n de la provincia seleccionada. Los cambios se guardan al momento desde Mesa de Control.
                    </p>
                </div>

                <button id="coverageModalCloseButton" type="button" class="text-3xl font-bold leading-none text-gray-400 hover:text-red-500">&times;</button>
            </div>

            <form id="coverageModalForm" class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.95fr)]">
                <div class="space-y-5">
                    <div>
                        <div>
                            <label for="coverageStatusField" class="block text-sm font-medium text-gray-700">Estado de la provincia</label>
                            <select id="coverageStatusField" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"></select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label for="coverageClosingTimeField" class="block text-sm font-medium text-gray-700">Hora de cierre</label>
                            <input id="coverageClosingTimeField" type="time" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="coverageVisitTimeField" class="block text-sm font-medium text-gray-700">Hora de visita</label>
                            <input id="coverageVisitTimeField" type="time" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label for="coverageDeliveryTimeField" class="block text-sm font-medium text-gray-700">Hora de entrega</label>
                            <input id="coverageDeliveryTimeField" type="time" class="mt-1 block w-full rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                        Los cambios guardados aqu&iacute; actualizan el estado operativo de la provincia y se reflejan en el mapa de cobertura.
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h4 class="text-lg font-semibold text-gray-900">Distritos de la provincia</h4>
                            <p id="coverageDistrictHint" class="text-sm text-gray-500 mt-1"></p>
                        </div>

                        <div class="flex gap-2">
                            <button id="coverageSelectAllDistrictsButton" type="button" class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                Marcar todos
                            </button>

                            <button id="coverageClearDistrictsButton" type="button" class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                Limpiar
                            </button>
                        </div>
                    </div>

                    <div id="coverageDistrictChecklist" class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2 max-h-[420px] overflow-y-auto pr-1"></div>
                </div>

                <div class="xl:col-span-2 flex justify-end gap-3 border-t border-gray-200 pt-5">
                    <button id="coverageModalCancelButton" type="button" class="rounded-xl border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Cerrar
                    </button>

                    <button id="coverageModalSaveButton" type="submit" class="rounded-xl bg-black px-5 py-2.5 text-sm font-medium text-white hover:bg-gray-800 transition disabled:cursor-not-allowed disabled:bg-gray-400">
                        Guardar configuraci&oacute;n
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
        #coverageMap {
            z-index: 0;
        }

        #coverageMap .leaflet-pane,
        #coverageMap .leaflet-control,
        #coverageMap .leaflet-top,
        #coverageMap .leaflet-bottom {
            z-index: 1 !important;
        }

        #coverageMap .leaflet-tooltip-pane {
            z-index: 2 !important;
        }

        #coverageMap .leaflet-popup-pane,
        #coverageMap .leaflet-marker-pane {
            z-index: 3 !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const page = document.getElementById('territorialCoveragePage');

            if (!page || !window.L) {
                return;
            }

            const mapElement = document.getElementById('coverageMap');
            const modalElement = document.getElementById('coverageModal');
            const statusPill = document.getElementById('coverageStatusPill');
            const toastElement = document.getElementById('coverageToast');
            const errorElement = document.getElementById('coverageError');
            const missingAlertElement = document.getElementById('coverageMissingAlert');
            const sourceAlertElement = document.getElementById('coverageSourceAlert');
            const provinceSearchField = document.getElementById('coverageProvinceSearch');
            const searchResults = document.getElementById('coverageSearchResults');
            const activeLocationList = document.getElementById('coverageActiveLocationList');
            const saveButton = document.getElementById('coverageModalSaveButton');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const selectAllDistrictsButton = document.getElementById('coverageSelectAllDistrictsButton');
            const clearDistrictsButton = document.getElementById('coverageClearDistrictsButton');

            const fieldRefs = {
                status: document.getElementById('coverageStatusField'),
                closing_time: document.getElementById('coverageClosingTimeField'),
                visit_time: document.getElementById('coverageVisitTimeField'),
                delivery_time: document.getElementById('coverageDeliveryTimeField'),
            };

            let map;
            let payload;
            let selectedProvinceId = null;
            let allProvinceBounds = null;
            let provinceLayer = null;
            let highlightLayer = null;
            const provinceDrafts = {};
            const provinceBoundsById = {};
            const activeMarkers = [];

            const getActiveLocationFeatures = () => payload?.active_locations?.features ?? [];
            const getProvinceFeatures = () => payload?.map?.provinces?.features ?? [];
            const getSavedSettingByProvinceId = (provinceId) => payload?.saved_settings_by_province_id?.[provinceId] ?? null;

            const formatLabel = (value) => value
                .replaceAll('_', ' ')
                .replace(/\b\w/g, (letter) => letter.toUpperCase());

            const showToast = (message) => {
                toastElement.textContent = message;
                toastElement.classList.remove('hidden');

                window.clearTimeout(showToast.timer);
                showToast.timer = window.setTimeout(() => {
                    toastElement.classList.add('hidden');
                }, 3200);
            };

            const showError = (message) => {
                errorElement.textContent = message;
                errorElement.classList.remove('hidden');
            };

            const hideError = () => {
                errorElement.classList.add('hidden');
            };

            const toLatLng = (coordinates) => [coordinates[1], coordinates[0]];

            const getProvinceById = (provinceId) => payload?.province_catalog_by_id?.[provinceId] ?? null;

            const setMarkersVisibility = (visible) => {
                const display = visible ? '' : 'none';

                activeMarkers.forEach((marker) => {
                    const element = marker.getElement();
                    if (element) {
                        element.style.display = display;
                    }
                });

            };

            const setMapVisibility = (visible) => {
                const container = map?.getContainer?.();

                if (container) {
                    container.style.visibility = visible ? 'visible' : 'hidden';
                }
            };

            const getDefaultDraft = (provinceId) => {
                const province = getProvinceById(provinceId);
                const districtIds = (province?.districts ?? []).map((district) => district.id);
                const isActiveProvince = payload?.active_province_ids?.includes(provinceId);
                const savedSetting = getSavedSettingByProvinceId(provinceId);

                if (savedSetting) {
                    return {
                        ...payload.defaults.config,
                        ...savedSetting,
                        selected_district_ids: Array.isArray(savedSetting.selected_district_ids)
                            ? savedSetting.selected_district_ids
                            : districtIds,
                    };
                }

                return {
                    ...payload.defaults.config,
                    status: isActiveProvince ? 'habilitado' : 'deshabilitado',
                    selected_district_ids: districtIds,
                };
            };

            const getProvinceSaveUrl = (provinceId) => {
                return page.dataset.saveUrlTemplate.replace('__PROVINCE__', encodeURIComponent(provinceId));
            };

            const applyStatusRules = () => {
                const isDisabledProvince = fieldRefs.status.value === 'deshabilitado';
                const districtCheckboxes = document.querySelectorAll('#coverageDistrictChecklist input[type="checkbox"]');

                [fieldRefs.closing_time, fieldRefs.visit_time, fieldRefs.delivery_time].forEach((field) => {
                    field.disabled = isDisabledProvince;
                    field.classList.toggle('bg-gray-100', isDisabledProvince);
                    field.classList.toggle('text-gray-500', isDisabledProvince);
                    field.classList.toggle('cursor-not-allowed', isDisabledProvince);
                });

                [selectAllDistrictsButton, clearDistrictsButton].forEach((button) => {
                    button.disabled = isDisabledProvince;
                    button.classList.toggle('opacity-50', isDisabledProvince);
                    button.classList.toggle('cursor-not-allowed', isDisabledProvince);
                });

                districtCheckboxes.forEach((checkbox) => {
                    checkbox.disabled = isDisabledProvince;

                    if (isDisabledProvince) {
                        checkbox.checked = false;
                    }
                });

                if (isDisabledProvince) {
                    fieldRefs.closing_time.value = payload.defaults.config.closing_time;
                    fieldRefs.visit_time.value = payload.defaults.config.visit_time;
                    fieldRefs.delivery_time.value = payload.defaults.config.delivery_time;
                }
            };

            const resetMapState = () => {
                if (map) {
                    map.off();
                    map.remove();
                    map = null;
                }

                mapElement.innerHTML = '';
                allProvinceBounds = null;
                provinceLayer = null;
                highlightLayer = null;
                activeMarkers.splice(0, activeMarkers.length);

                Object.keys(provinceBoundsById).forEach((provinceId) => {
                    delete provinceBoundsById[provinceId];
                });
            };

            const getProvinceDraft = (provinceId) => {
                if (!provinceDrafts[provinceId]) {
                    provinceDrafts[provinceId] = getDefaultDraft(provinceId);
                }

                return provinceDrafts[provinceId];
            };

            const updateSelectionPanel = (provinceId) => {
                if (!getProvinceById(provinceId)) {
                    return;
                }

                getProvinceDraft(provinceId);
            };

            const buildSearchIndex = () => {
                const items = [];

                payload.province_catalog.forEach((province) => {
                    items.push({
                        key: `province:${province.id}`,
                        province_id: province.id,
                        title: province.name,
                        subtitle: `${province.districts.length} distritos`,
                    });
                });

                return items;
            };

            const renderSearchResults = (term = '') => {
                const normalizedTerm = term.trim().toLowerCase();
                const searchIndex = buildSearchIndex();
                const filtered = normalizedTerm === ''
                    ? searchIndex.slice(0, 10)
                    : searchIndex.filter((item) =>
                        item.title.toLowerCase().includes(normalizedTerm) ||
                        item.subtitle.toLowerCase().includes(normalizedTerm)
                    ).slice(0, 12);

                searchResults.innerHTML = '';

                if (!filtered.length) {
                    const empty = document.createElement('div');
                    empty.className = 'rounded-xl border border-dashed border-gray-300 px-4 py-3 text-sm text-gray-500';
                    empty.textContent = 'No se encontraron coincidencias.';
                    searchResults.appendChild(empty);
                    return;
                }

                filtered.forEach((item) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'w-full rounded-xl border border-gray-200 px-3 py-2.5 text-left hover:bg-gray-50 transition';
                    button.innerHTML = `
                        <div class="text-sm font-semibold text-gray-900">${item.title}</div>
                        <div class="mt-1 text-xs text-gray-500">${item.subtitle}</div>
                    `;
                    button.addEventListener('click', () => {
                        selectProvince(item.province_id, true);
                        openModal();
                    });
                    searchResults.appendChild(button);
                });
            };

            const renderActiveLocationList = () => {
                activeLocationList.innerHTML = '';

                getActiveLocationFeatures().forEach((feature) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'rounded-xl bg-blue-500 px-3 py-2.5 text-left text-sm font-semibold text-white shadow-sm hover:bg-blue-600 transition';
                    button.textContent = feature.properties.location_label;
                    button.addEventListener('click', () => {
                        selectProvince(feature.properties.province_id, true);
                        openModal();
                    });
                    activeLocationList.appendChild(button);
                });
            };

            const populateSelectOptions = (select, options) => {
                select.innerHTML = '';

                options.forEach((option) => {
                    const node = document.createElement('option');
                    node.value = option.value;
                    node.textContent = option.label;
                    select.appendChild(node);
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
                updateSelectionPanel(provinceId);

                if (shouldFocus) {
                    fitToProvince(provinceId);
                }
            };

            const renderDistrictChecklist = (provinceId) => {
                const province = getProvinceById(provinceId);
                const draft = getProvinceDraft(provinceId);
                const container = document.getElementById('coverageDistrictChecklist');
                const hint = document.getElementById('coverageDistrictHint');

                container.innerHTML = '';
                hint.textContent = `${province.districts.length} distritos reales cargados desde el catálogo local.`;

                province.districts.forEach((district) => {
                    const label = document.createElement('label');
                    label.className = 'flex items-start gap-3 rounded-xl border border-gray-200 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50';

                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.value = district.id;
                    checkbox.className = 'mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500';
                    checkbox.checked = draft.selected_district_ids.includes(district.id);

                    const text = document.createElement('span');
                    text.textContent = district.name;

                    label.appendChild(checkbox);
                    label.appendChild(text);
                    container.appendChild(label);
                });
            };

            const openModal = () => {
                if (!selectedProvinceId) {
                    return;
                }

                const province = getProvinceById(selectedProvinceId);
                const draft = getProvinceDraft(selectedProvinceId);

                document.getElementById('coverageModalTitle').textContent = `Configuración de ${province.name}`;
                fieldRefs.status.value = draft.status;
                fieldRefs.closing_time.value = draft.closing_time;
                fieldRefs.visit_time.value = draft.visit_time;
                fieldRefs.delivery_time.value = draft.delivery_time;

                renderDistrictChecklist(selectedProvinceId);
                applyStatusRules();

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

            const createTextMarker = (feature, type, clickHandler) => {
                const marker = window.L.marker(
                    toLatLng(feature.geometry.coordinates),
                    {
                        icon: window.L.divIcon({
                            className: '',
                            html: `<button type="button" class="${type === 'active' ? 'coverage-active-marker' : 'coverage-inactive-marker'}">${feature.properties.location_label ?? feature.properties.province_name}</button>`,
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

                const activeProvinceIds = new Set((payload.active_province_ids ?? []).map((value) => String(value)));

                provinceLayer = window.L.geoJSON(payload.map.provinces, {
                    style: (feature) => ({
                        color: '#64748b',
                        weight: 1,
                        fillColor: activeProvinceIds.has(String(feature.properties.province_id)) ? '#5b9ae6' : '#ffffff',
                        fillOpacity: activeProvinceIds.has(String(feature.properties.province_id)) ? 0.9 : 0.95,
                    }),
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
                    const marker = createTextMarker(feature, 'active', () => {
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

            const populateTotals = () => {
                const missingCount = payload.missing_provinces.length;

                if (missingCount > 0) {
                    const preview = payload.missing_provinces.slice(0, 4).join(', ');
                    const suffix = missingCount > 4 ? ` y ${missingCount - 4} más` : '';
                    missingAlertElement.textContent = `La geometría base no incluye ${missingCount} provincia(s): ${preview}${suffix}.`;
                    missingAlertElement.classList.remove('hidden');
                } else {
                    missingAlertElement.classList.add('hidden');
                }

                const geometrySource = payload.sources?.geometry;
                const ubigeoSource = payload.sources?.ubigeo;

                if (geometrySource?.mode === 'local_snapshot' && ubigeoSource?.mode === 'local_snapshot') {
                    sourceAlertElement.classList.add('hidden');
                } else {
                    const isOfficial = geometrySource?.mode === 'official_remote' && ubigeoSource?.mode === 'official_remote';
                    sourceAlertElement.className = `mb-6 rounded-xl border px-4 py-3 text-sm ${isOfficial ? 'border-green-200 bg-green-50 text-green-700' : 'border-amber-200 bg-amber-50 text-amber-800'}`;
                    sourceAlertElement.textContent = isOfficial
                        ? 'Mapa y ubigeos cargados desde fuentes oficiales remotas del Estado peruano.'
                        : 'Se activó el respaldo local para una o más fuentes.';
                    sourceAlertElement.classList.remove('hidden');
                }
            };

            const loadCoverageData = async ({ preserveSelection = true } = {}) => {
                hideError();

                const response = await window.fetch(page.dataset.dataUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('No se pudieron cargar los datos del mapa.');
                }

                payload = await response.json();

                Object.keys(provinceDrafts).forEach((provinceId) => {
                    delete provinceDrafts[provinceId];
                });

                populateSelectOptions(fieldRefs.status, payload.defaults.status_options);
                renderSearchResults(provinceSearchField.value);
                renderActiveLocationList();
                populateTotals();
                resetMapState();
                initializeMap();

                if (preserveSelection && selectedProvinceId && getProvinceById(selectedProvinceId)) {
                    setHighlightProvince(selectedProvinceId);
                }

                statusPill.textContent = `Catálogo listo: ${payload.province_catalog.length} provincias cargadas`;
                statusPill.className = 'inline-flex items-center rounded-full border border-green-200 bg-green-50 px-4 py-2 text-sm font-medium text-green-700';
            };

            const initializePage = async () => {
                try {
                    await loadCoverageData();
                } catch (error) {
                    statusPill.textContent = 'No se pudo cargar la cobertura';
                    statusPill.className = 'inline-flex items-center rounded-full border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-700';
                    showError(error.message);
                }
            };

            provinceSearchField.addEventListener('input', (event) => {
                renderSearchResults(event.target.value);
            });

            document.getElementById('coverageResetViewButton').addEventListener('click', () => {
                if (!map || !allProvinceBounds || !allProvinceBounds.isValid()) {
                    return;
                }

                map.fitBounds(allProvinceBounds, {
                    padding: [18, 18],
                    animate: true,
                });
            });

            document.getElementById('coverageModalCloseButton').addEventListener('click', closeModal);
            document.getElementById('coverageModalCancelButton').addEventListener('click', closeModal);

            modalElement.addEventListener('click', (event) => {
                if (event.target === modalElement) {
                    closeModal();
                }
            });

            fieldRefs.status.addEventListener('change', applyStatusRules);

            selectAllDistrictsButton.addEventListener('click', () => {
                if (!selectedProvinceId) {
                    return;
                }

                document.querySelectorAll('#coverageDistrictChecklist input[type="checkbox"]').forEach((checkbox) => {
                    checkbox.checked = true;
                });
            });

            clearDistrictsButton.addEventListener('click', () => {
                document.querySelectorAll('#coverageDistrictChecklist input[type="checkbox"]').forEach((checkbox) => {
                    checkbox.checked = false;
                });
            });

            document.getElementById('coverageModalForm').addEventListener('submit', async (event) => {
                event.preventDefault();

                if (!selectedProvinceId) {
                    return;
                }

                const selectedDistrictIds = Array.from(
                    document.querySelectorAll('#coverageDistrictChecklist input[type="checkbox"]:checked')
                ).map((checkbox) => checkbox.value);

                const requestPayload = {
                    status: fieldRefs.status.value,
                    closing_time: fieldRefs.closing_time.value,
                    visit_time: fieldRefs.visit_time.value,
                    delivery_time: fieldRefs.delivery_time.value,
                    selected_district_ids: selectedDistrictIds,
                };

                hideError();
                saveButton.disabled = true;

                try {
                    const response = await window.fetch(getProvinceSaveUrl(selectedProvinceId), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(requestPayload),
                    });

                    const result = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        const validationMessage = result?.errors
                            ? Object.values(result.errors).flat().join(' ')
                            : null;

                        throw new Error(validationMessage || result?.message || 'No se pudo guardar la configuración de la provincia.');
                    }

                    closeModal();
                    await loadCoverageData({ preserveSelection: true });
                    showToast(result.message || 'Configuración provincial actualizada correctamente.');
                } catch (error) {
                    showError(error.message);
                } finally {
                    saveButton.disabled = false;
                }
            });

            initializePage();
        });
    </script>
</x-app-layout>
