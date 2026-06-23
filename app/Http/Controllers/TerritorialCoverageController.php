<?php

namespace App\Http\Controllers;

use App\Models\TerritorialProvinceSettingHistory;
use App\Models\TerritorialProvinceSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class TerritorialCoverageController extends Controller
{
    private const CACHE_KEY = 'territorial_coverage_map_payload_v1';
    private const REMOTE_TIMEOUT_SECONDS = 30;
    private const OFFICIAL_PROVINCE_GEOJSON_URL = 'https://geosnirh.ana.gob.pe/server/rest/services/Puntos_Criticos/Limite_Provincial_DU_N_015_2023/FeatureServer/0/query?where=1%3D1&outFields=IDPROV%2CNOMBPROV%2CNOMBDEP%2CCAPITAL&returnGeometry=true&outSR=4326&f=geojson';
    private const OFFICIAL_UBIGEO_CSV_URL = 'https://www.datosabiertos.gob.pe/sites/default/files/Lista_Ubigeos_INEI.csv';

    private const STATUS_OPTIONS = [
        ['value' => 'habilitado', 'label' => 'Habilitado'],
        ['value' => 'deshabilitado', 'label' => 'Deshabilitado'],
    ];

    private const DEFAULT_CONFIG = [
        'status' => 'habilitado',
        'closing_time' => '18:00',
        'visit_time' => '09:00',
        'delivery_time' => '15:00',
    ];

    private const ACTIVE_LOCATIONS = [
        ['province_id' => '0608'],
        ['province_id' => '0601'],
        ['province_id' => '2401'],
        ['province_id' => '2006'],
        ['province_id' => '2001'],
        ['province_id' => '1401'],
        ['province_id' => '1301'],
        ['province_id' => '0218'],
        ['province_id' => '0201'],
        ['province_id' => '1508'],
        ['province_id' => '1505'],
        ['province_id' => '1101'],
        ['province_id' => '0501'],
        ['province_id' => '1201'],
        ['province_id' => '0302'],
        ['province_id' => '0801'],
        ['province_id' => '2111'],
        ['province_id' => '2501'],
        ['province_id' => '0401'],
        ['province_id' => '1801'],
        ['province_id' => '2301'],
    ];

    public function index()
    {
        return view('territorial-coverage.index');
    }

    public function executiveIndex()
    {
        return view('territorial-coverage.executive');
    }

    public function data(): JsonResponse
    {
        $payload = Cache::remember(self::CACHE_KEY, now()->addHours(12), function () {
            return $this->buildPayload();
        });

        return response()->json($payload);
    }

    public function executiveData(): JsonResponse
    {
        $payload = Cache::remember(self::CACHE_KEY, now()->addHours(12), function () {
            return $this->buildPayload();
        });

        return response()->json($this->buildExecutivePayload($payload));
    }

    public function updateProvince(Request $request, string $provinceId): JsonResponse
    {
        if (!Schema::hasTable('territorial_province_settings')) {
            abort(503, 'La tabla de configuración territorial aún no está disponible.');
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:habilitado,deshabilitado'],
            'closing_time' => ['required', 'date_format:H:i'],
            'visit_time' => ['required', 'date_format:H:i'],
            'delivery_time' => ['required', 'date_format:H:i'],
            'selected_district_ids' => ['nullable', 'array'],
            'selected_district_ids.*' => ['string'],
        ]);

        [$provinceRows, $districtRows] = $this->loadUbigeoCatalog();

        $provinceById = [];
        foreach ($provinceRows as $province) {
            $provinceById[(string) $province['id']] = $province;
        }

        $provinceId = (string) $provinceId;
        $province = $provinceById[$provinceId] ?? null;

        if (!$province) {
            abort(404);
        }

        $allowedDistrictIds = [];
        foreach ($districtRows as $district) {
            if ((string) $district['province_id'] === $provinceId) {
                $allowedDistrictIds[] = (string) $district['id'];
            }
        }

        $selectedDistrictIds = collect($validated['selected_district_ids'] ?? [])
            ->map(fn ($districtId) => (string) $districtId)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($validated['status'] === 'deshabilitado') {
            $selectedDistrictIds = [];
            $validated['closing_time'] = self::DEFAULT_CONFIG['closing_time'];
            $validated['visit_time'] = self::DEFAULT_CONFIG['visit_time'];
            $validated['delivery_time'] = self::DEFAULT_CONFIG['delivery_time'];
        }

        $invalidDistrictIds = array_values(array_diff($selectedDistrictIds, $allowedDistrictIds));

        if (!empty($invalidDistrictIds)) {
            throw ValidationException::withMessages([
                'selected_district_ids' => 'La selección de distritos no corresponde a la provincia elegida.',
            ]);
        }

        DB::transaction(function () use ($provinceId, $province, $validated, $selectedDistrictIds, $request) {
            $existingSetting = TerritorialProvinceSetting::query()
                ->where('province_id', $provinceId)
                ->first();

            $beforeSnapshot = $existingSetting
                ? $this->settingSnapshot($existingSetting)
                : null;

            $setting = TerritorialProvinceSetting::query()->updateOrCreate(
                ['province_id' => $provinceId],
                [
                    'province_name' => $this->formatName((string) $province['name']),
                    'status' => $validated['status'],
                    'closing_time' => $validated['closing_time'],
                    'visit_time' => $validated['visit_time'],
                    'delivery_time' => $validated['delivery_time'],
                    'selected_district_ids' => $selectedDistrictIds,
                    'updated_by' => $request->user()?->id,
                ]
            );

            $afterSnapshot = $this->settingSnapshot($setting);
            $changedFields = $this->detectSettingChanges($beforeSnapshot, $afterSnapshot);

            if ($beforeSnapshot === null || !empty($changedFields)) {
                TerritorialProvinceSettingHistory::query()->create([
                    'territorial_province_setting_id' => $setting->id,
                    'province_id' => $provinceId,
                    'province_name' => $setting->province_name,
                    'user_id' => $request->user()?->id,
                    'action' => $beforeSnapshot === null ? 'created' : 'updated',
                    'changed_fields' => $changedFields,
                ]);
            }
        });

        Cache::forget(self::CACHE_KEY);

        return response()->json([
            'ok' => true,
            'message' => 'Configuración provincial actualizada correctamente.',
        ]);
    }

    private function buildPayload(): array
    {
        [$geoJson, $geoSource] = $this->loadProvinceGeoJson();
        [$provinceRows, $districtRows, $ubigeoSource] = $this->loadUbigeoCatalog();
        $savedSettingsByProvinceId = $this->loadSavedSettingsByProvinceId();

        $districtsByProvince = [];
        foreach ($districtRows as $district) {
            $districtsByProvince[$district['province_id']][] = [
                'id' => (string) $district['id'],
                'name' => $this->formatName($district['name']),
            ];
        }

        foreach ($districtsByProvince as &$districts) {
            usort($districts, fn ($left, $right) => strcasecmp($left['name'], $right['name']));
        }
        unset($districts);

        $provinceCatalog = [];
        foreach ($provinceRows as $province) {
            $provinceId = (string) $province['id'];

            $provinceCatalog[$provinceId] = [
                'id' => $provinceId,
                'name' => $this->formatName($province['name']),
                'department_id' => (string) $province['department_id'],
                'districts' => $districtsByProvince[$provinceId] ?? [],
                'coordinates' => null,
            ];
        }

        $provinceFeatures = [];
        $labelCoordinates = [];

        foreach (($geoJson['features'] ?? []) as $feature) {
            $provinceId = (string) (
                data_get($feature, 'properties.IDPROV')
                ?? data_get($feature, 'properties.FIRST_IDPR')
                ?? ''
            );

            if ($provinceId === '' || !isset($provinceCatalog[$provinceId])) {
                continue;
            }

            $catalogProvince = $provinceCatalog[$provinceId];
            $labelCoordinates[$provinceId] = array_merge(
                $labelCoordinates[$provinceId] ?? [],
                $this->flattenCoordinates((array) data_get($feature, 'geometry.coordinates', []))
            );

            $provinceFeatures[] = [
                'type' => 'Feature',
                'properties' => [
                    'province_id' => $provinceId,
                    'province_name' => $catalogProvince['name'],
                    'district_count' => count($catalogProvince['districts']),
                ],
                'geometry' => $feature['geometry'],
            ];
        }

        $labelFeatures = [];
        foreach ($provinceCatalog as $provinceId => $province) {
            if (empty($labelCoordinates[$provinceId])) {
                continue;
            }

            $centerPoint = $this->calculateCenterPoint($labelCoordinates[$provinceId]);
            $provinceCatalog[$provinceId]['coordinates'] = [
                'lng' => $centerPoint[0],
                'lat' => $centerPoint[1],
            ];

            $labelFeatures[] = [
                'type' => 'Feature',
                'properties' => [
                    'province_id' => $provinceId,
                    'province_name' => $province['name'],
                ],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => $centerPoint,
                ],
            ];
        }

        $labelCoordinatesByProvince = [];
        foreach ($labelFeatures as $feature) {
            $labelCoordinatesByProvince[$feature['properties']['province_id']] = $feature['geometry']['coordinates'];
        }

        $activeProvinceIds = $this->resolveActiveProvinceIds($savedSettingsByProvinceId);
        $activeLocationFeatures = [];
        foreach ($activeProvinceIds as $index => $provinceId) {

            if (!isset($provinceCatalog[$provinceId], $labelCoordinatesByProvince[$provinceId])) {
                continue;
            }

            $coordinates = $this->applyLabelOffset(
                $labelCoordinatesByProvince[$provinceId],
                $index
            );

            $activeLocationFeatures[] = [
                'type' => 'Feature',
                'properties' => [
                    'location_label' => $provinceCatalog[$provinceId]['name'],
                    'province_id' => $provinceId,
                    'province_name' => $provinceCatalog[$provinceId]['name'],
                    'coordinates' => [
                        'lng' => $coordinates[0],
                        'lat' => $coordinates[1],
                    ],
                ],
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => $coordinates,
                ],
            ];
        }

        $missingProvinces = [];
        foreach ($provinceCatalog as $provinceId => $province) {
            if (!isset($labelCoordinates[$provinceId])) {
                $missingProvinces[] = $province['name'];
            }
        }

        uasort($provinceCatalog, fn ($left, $right) => strcasecmp($left['name'], $right['name']));
        sort($missingProvinces, SORT_NATURAL | SORT_FLAG_CASE);

        return [
            'generated_at' => now()->toIso8601String(),
            'sources' => [
                'geometry' => $geoSource,
                'ubigeo' => $ubigeoSource,
            ],
            'province_catalog' => array_values($provinceCatalog),
            'province_catalog_by_id' => $provinceCatalog,
            'saved_settings_by_province_id' => $savedSettingsByProvinceId,
            'active_province_ids' => $activeProvinceIds,
            'active_locations' => [
                'type' => 'FeatureCollection',
                'features' => $activeLocationFeatures,
            ],
            'missing_provinces' => $missingProvinces,
            'defaults' => [
                'status_options' => self::STATUS_OPTIONS,
                'config' => self::DEFAULT_CONFIG,
            ],
            'map' => [
                'bounds' => [
                    [-81.4, -18.6],
                    [-68.1, 0.8],
                ],
                'provinces' => [
                    'type' => 'FeatureCollection',
                    'features' => $provinceFeatures,
                ],
                'labels' => [
                    'type' => 'FeatureCollection',
                    'features' => $labelFeatures,
                ],
                'active_labels' => [
                    'type' => 'FeatureCollection',
                    'features' => $activeLocationFeatures,
                ],
            ],
        ];
    }

    private function buildExecutivePayload(array $payload): array
    {
        $activeProvinceIds = collect($payload['active_province_ids'] ?? [])
            ->map(fn ($provinceId) => (string) $provinceId)
            ->values()
            ->all();

        $activeProvinceLookup = array_flip($activeProvinceIds);
        $savedSettingsByProvinceId = $payload['saved_settings_by_province_id'] ?? [];

        $provinceCatalog = collect($payload['province_catalog'] ?? [])
            ->filter(fn (array $province) => isset($activeProvinceLookup[(string) $province['id']]))
            ->map(function (array $province) use ($savedSettingsByProvinceId) {
                $provinceId = (string) $province['id'];
                $savedSetting = $savedSettingsByProvinceId[$provinceId] ?? null;
                $selectedDistrictIds = collect($savedSetting['selected_district_ids'] ?? [])
                    ->map(fn ($districtId) => (string) $districtId)
                    ->filter()
                    ->values()
                    ->all();

                if (!empty($selectedDistrictIds)) {
                    $districtLookup = array_flip($selectedDistrictIds);
                    $province['districts'] = array_values(array_filter(
                        $province['districts'],
                        fn (array $district) => isset($districtLookup[(string) $district['id']])
                    ));
                }

                $province['config'] = [
                    'status' => $savedSetting['status'] ?? self::DEFAULT_CONFIG['status'],
                    'closing_time' => $savedSetting['closing_time'] ?? self::DEFAULT_CONFIG['closing_time'],
                    'visit_time' => $savedSetting['visit_time'] ?? self::DEFAULT_CONFIG['visit_time'],
                    'delivery_time' => $savedSetting['delivery_time'] ?? self::DEFAULT_CONFIG['delivery_time'],
                ];

                return $province;
            })
            ->values()
            ->all();

        $provinceCatalogById = [];
        foreach ($provinceCatalog as $province) {
            $provinceCatalogById[(string) $province['id']] = $province;
        }

        $provinceFeatures = array_values($payload['map']['provinces']['features'] ?? []);

        $activeLocationFeatures = array_values(array_filter(
            $payload['active_locations']['features'] ?? [],
            fn (array $feature) => isset($activeProvinceLookup[(string) ($feature['properties']['province_id'] ?? '')])
        ));

        $missingProvinces = array_values(array_filter(
            $payload['missing_provinces'] ?? [],
            fn (string $provinceName) => collect($provinceCatalog)->contains(fn (array $province) => $province['name'] === $provinceName)
        ));

        return [
            'generated_at' => $payload['generated_at'] ?? now()->toIso8601String(),
            'sources' => $payload['sources'] ?? [],
            'province_catalog' => $provinceCatalog,
            'province_catalog_by_id' => $provinceCatalogById,
            'active_province_ids' => $activeProvinceIds,
            'active_locations' => [
                'type' => 'FeatureCollection',
                'features' => $activeLocationFeatures,
            ],
            'missing_provinces' => $missingProvinces,
            'map' => [
                'bounds' => $payload['map']['bounds'] ?? [
                    [-81.4, -18.6],
                    [-68.1, 0.8],
                ],
                'provinces' => [
                    'type' => 'FeatureCollection',
                    'features' => $provinceFeatures,
                ],
                'active_labels' => [
                    'type' => 'FeatureCollection',
                    'features' => $activeLocationFeatures,
                ],
            ],
            'defaults' => [
                'config' => self::DEFAULT_CONFIG,
            ],
        ];
    }

    private function loadSavedSettingsByProvinceId(): array
    {
        if (!Schema::hasTable('territorial_province_settings')) {
            return [];
        }

        return TerritorialProvinceSetting::query()
            ->get()
            ->mapWithKeys(function (TerritorialProvinceSetting $setting) {
                return [
                    (string) $setting->province_id => [
                        'province_id' => (string) $setting->province_id,
                        'province_name' => $setting->province_name,
                        'status' => (string) $setting->status,
                        'closing_time' => (string) $setting->closing_time,
                        'visit_time' => (string) $setting->visit_time,
                        'delivery_time' => (string) $setting->delivery_time,
                        'selected_district_ids' => collect($setting->selected_district_ids ?? [])
                            ->map(fn ($districtId) => (string) $districtId)
                            ->values()
                            ->all(),
                    ],
                ];
            })
            ->all();
    }

    private function resolveActiveProvinceIds(array $savedSettingsByProvinceId): array
    {
        $defaultActiveProvinceIds = array_map(
            static fn (array $location) => (string) $location['province_id'],
            self::ACTIVE_LOCATIONS
        );

        $activeProvinceIds = [];

        foreach ($defaultActiveProvinceIds as $provinceId) {
            $savedSetting = $savedSettingsByProvinceId[$provinceId] ?? null;
            $isActive = $savedSetting
                ? $savedSetting['status'] === 'habilitado'
                : true;

            if ($isActive) {
                $activeProvinceIds[] = $provinceId;
            }
        }

        $additionalProvinceIds = collect($savedSettingsByProvinceId)
            ->filter(function (array $setting, string $provinceId) use ($defaultActiveProvinceIds) {
                return !in_array($provinceId, $defaultActiveProvinceIds, true)
                    && $setting['status'] === 'habilitado';
            })
            ->sortBy('province_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->keys()
            ->values()
            ->all();

        return array_values(array_merge($activeProvinceIds, $additionalProvinceIds));
    }

    private function settingSnapshot(TerritorialProvinceSetting $setting): array
    {
        return [
            'status' => (string) $setting->status,
            'closing_time' => (string) $setting->closing_time,
            'visit_time' => (string) $setting->visit_time,
            'delivery_time' => (string) $setting->delivery_time,
            'selected_district_ids' => collect($setting->selected_district_ids ?? [])
                ->map(fn ($districtId) => (string) $districtId)
                ->values()
                ->all(),
        ];
    }

    private function detectSettingChanges(?array $beforeSnapshot, array $afterSnapshot): array
    {
        if ($beforeSnapshot === null) {
            return collect($afterSnapshot)
                ->map(fn ($value) => ['old' => null, 'new' => $value])
                ->all();
        }

        $changedFields = [];

        foreach ($afterSnapshot as $field => $newValue) {
            $oldValue = $beforeSnapshot[$field] ?? null;

            if ($oldValue !== $newValue) {
                $changedFields[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changedFields;
    }

    private function loadProvinceGeoJson(): array
    {
        return [
            $this->readJsonFile(resource_path('data/peru/peru_provinces.geojson')),
            [
                'mode' => 'local_snapshot',
                'label' => 'Snapshot provincial completo del proyecto',
                'url' => null,
            ],
        ];
    }

    private function loadUbigeoCatalog(): array
    {
        return [
            $this->readJsonFile(resource_path('data/peru/source_ubigeo_provincias.json')),
            $this->readJsonFile(resource_path('data/peru/source_ubigeo_distritos.json')),
            [
                'mode' => 'local_snapshot',
                'label' => 'Snapshot ubigeo del proyecto',
                'url' => null,
            ],
        ];
    }

    private function readJsonFile(string $path): array
    {
        $contents = file_get_contents($path);

        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }

    private function parseOfficialUbigeoCsv(string $csv): array
    {
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv) ?? $csv;
        $lines = preg_split("/\r\n|\n|\r/", trim($csv)) ?: [];

        if (count($lines) < 2) {
            throw new RuntimeException('El CSV oficial de ubigeos no contiene filas suficientes.');
        }

        $headers = str_getcsv(array_shift($lines));
        $normalizedHeaderMap = [];

        foreach ($headers as $index => $header) {
            $normalizedHeaderMap[$this->normalizeKey($header)] = $index;
        }

        $ubigeoIndex = $this->resolveHeaderIndex($normalizedHeaderMap, ['ubigeo', 'codigo_ubigeo', 'cod_ubigeo', 'idubigeoinei']);
        $departmentIndex = $this->resolveHeaderIndex($normalizedHeaderMap, ['departamento', 'dpto', 'nombre_departamento']);
        $provinceIndex = $this->resolveHeaderIndex($normalizedHeaderMap, ['provincia', 'nombre_provincia']);
        $districtIndex = $this->resolveHeaderIndex($normalizedHeaderMap, ['distrito', 'nombre_distrito']);

        $provinceRows = [];
        $districtRows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $columns = str_getcsv($line);
            $ubigeo = str_pad(trim((string) ($columns[$ubigeoIndex] ?? '')), 6, '0', STR_PAD_LEFT);

            if (strlen($ubigeo) !== 6) {
                continue;
            }

            $departmentId = substr($ubigeo, 0, 2);
            $provinceId = substr($ubigeo, 0, 4);
            $departmentName = $this->formatName((string) ($columns[$departmentIndex] ?? ''));
            $provinceName = $this->formatName((string) ($columns[$provinceIndex] ?? ''));
            $districtName = $this->formatName((string) ($columns[$districtIndex] ?? ''));

            $provinceRows[$provinceId] = [
                'id' => $provinceId,
                'name' => $provinceName,
                'department_id' => $departmentId,
                'department_name' => $departmentName,
            ];

            $districtRows[] = [
                'id' => $ubigeo,
                'name' => $districtName,
                'province_id' => $provinceId,
                'department_id' => $departmentId,
            ];
        }

        uasort($provinceRows, fn ($left, $right) => strcasecmp($left['name'], $right['name']));

        return [
            array_values($provinceRows),
            $districtRows,
        ];
    }

    private function resolveHeaderIndex(array $headerMap, array $candidates): int
    {
        foreach ($candidates as $candidate) {
            $normalizedCandidate = $this->normalizeKey($candidate);

            if (array_key_exists($normalizedCandidate, $headerMap)) {
                return $headerMap[$normalizedCandidate];
            }
        }

        throw new RuntimeException('No se encontraron las columnas esperadas en el CSV oficial.');
    }

    private function normalizeKey(string $value): string
    {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $normalized = strtolower($normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized) ?? $normalized;

        return trim($normalized, '_');
    }

    private function formatName(string $value): string
    {
        return mb_convert_case(mb_strtolower(trim($value)), MB_CASE_TITLE, 'UTF-8');
    }

    private function flattenCoordinates(array $coordinates): array
    {
        if (isset($coordinates[0], $coordinates[1]) && is_numeric($coordinates[0]) && is_numeric($coordinates[1])) {
            return [[(float) $coordinates[0], (float) $coordinates[1]]];
        }

        $points = [];

        foreach ($coordinates as $nestedCoordinates) {
            $points = array_merge($points, $this->flattenCoordinates((array) $nestedCoordinates));
        }

        return $points;
    }

    private function calculateCenterPoint(array $points): array
    {
        $minLng = $maxLng = $points[0][0];
        $minLat = $maxLat = $points[0][1];

        foreach ($points as [$lng, $lat]) {
            $minLng = min($minLng, $lng);
            $maxLng = max($maxLng, $lng);
            $minLat = min($minLat, $lat);
            $maxLat = max($maxLat, $lat);
        }

        return [
            round(($minLng + $maxLng) / 2, 6),
            round(($minLat + $maxLat) / 2, 6),
        ];
    }

    private function applyLabelOffset(array $coordinates, int $index): array
    {
        $offsets = [
            [-0.25, 0.22],
            [0.18, 0.16],
            [-0.18, -0.12],
            [0.24, -0.14],
            [0.00, 0.24],
            [0.00, -0.22],
        ];

        [$lngOffset, $latOffset] = $offsets[$index % count($offsets)];

        return [
            round($coordinates[0] + $lngOffset, 6),
            round($coordinates[1] + $latOffset, 6),
        ];
    }
}
