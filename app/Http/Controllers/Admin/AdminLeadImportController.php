<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminLeadImportController extends Controller
{
    private const REQUIRED_COLUMNS = [
        'ruc',
        'business_name',
        'phone',
    ];

    private const LEAD_TEMPLATE_HEADERS = [
        'campaign',
        'ruc',
        'business_name',
        'representative_name',
        'dni',
        'fiscal_address',
        'current_operator',
        'current_line_count',
        'segment',
        'max_speed',
        'package',
        'technology',
        'phone',
        'phone_2',
        'phone_3',
    ];

    private const SISAC_TEMPLATE_HEADERS = [
        'semaforo',
        'resultado',
        'cantidad_lineas_ofrecer',
        'deposito_garantia',
        'rango_lc_disponible',
    ];

    public function index()
    {
        $hasSisacTable = $this->hasSisacTable();

        return view('admin.leads.import', [
            'campaigns' => Campaign::orderBy('name')->get(),
            'excelAvailable' => $this->excelSupportAvailable(),
            'hasSisacTable' => $hasSisacTable,
            'expectedLeadColumns' => self::LEAD_TEMPLATE_HEADERS,
            'expectedSisacColumns' => $hasSisacTable ? self::SISAC_TEMPLATE_HEADERS : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ], [
            'file.mimes' => 'El archivo debe ser CSV o Excel (.xlsx, .xls).',
            'file.max' => 'El archivo no puede superar los 10 MB.',
        ]);

        [$headers, $rows] = $this->parseUploadedFile($validated['file']);

        $campaignsById = Campaign::query()->get()->keyBy('id');
        $campaignsByName = Campaign::query()
            ->get()
            ->keyBy(fn (Campaign $campaign) => $this->normalizeCampaignName($campaign->name));

        $summary = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];
        $hasSisacTable = $this->hasSisacTable();

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = $this->mapRowToData($headers, $row);

            if ($this->isEmptyRow($data)) {
                continue;
            }

            $summary['processed']++;

            try {
                $campaign = $this->resolveCampaign($data, $campaignsById, $campaignsByName);
                $phones = $this->extractPhones($data);

                if ($phones->isEmpty()) {
                    throw ValidationException::withMessages([
                        'file' => "Fila {$rowNumber}: debes indicar al menos un teléfono.",
                    ]);
                }

                $operator = $this->normalizeOperator($data['current_operator'] ?? null, $rowNumber);
                $lineCount = $this->normalizeLineCount($data['current_line_count'] ?? null, $rowNumber);
                $payload = $this->buildLeadPayload($data, $campaign->id, $operator, $lineCount, (int) $request->user()->id, $rowNumber);
                $sisacPayload = $hasSisacTable ? $this->buildSisacPayload($data, $rowNumber) : null;

                DB::transaction(function () use ($campaign, $payload, $phones, $sisacPayload, $hasSisacTable, &$summary, $rowNumber) {
                    $existingLead = Lead::query()
                        ->where('campaign_id', $campaign->id)
                        ->where('ruc', $payload['ruc'])
                        ->first();

                    if ($existingLead && ($existingLead->interactions()->exists() || $existingLead->status_final !== 'sin_gestion')) {
                        $summary['skipped']++;
                        $summary['errors'][] = "Fila {$rowNumber}: el lead con RUC {$payload['ruc']} ya tiene gestión y se omitió para no sobrescribir historial.";
                        return;
                    }

                    if ($existingLead) {
                        $existingLead->update($payload);
                        $existingLead->phones()->delete();
                        $this->syncSisacData($existingLead, $sisacPayload, $hasSisacTable);

                        foreach ($phones as $position => $phone) {
                            $existingLead->phones()->create([
                                'phone' => $phone,
                                'type' => 'movil',
                                'is_primary' => $position === 0,
                            ]);
                        }

                        $summary['updated']++;
                        return;
                    }

                    $lead = Lead::create($payload);
                    $this->syncSisacData($lead, $sisacPayload, $hasSisacTable);

                    foreach ($phones as $position => $phone) {
                        $lead->phones()->create([
                            'phone' => $phone,
                            'type' => 'movil',
                            'is_primary' => $position === 0,
                        ]);
                    }

                    $summary['created']++;
                });
            } catch (ValidationException $exception) {
                foreach ($exception->errors() as $messages) {
                    foreach ($messages as $message) {
                        $summary['errors'][] = $message;
                    }
                }

                $summary['skipped']++;
            } catch (\Throwable $exception) {
                $summary['errors'][] = "Fila {$rowNumber}: {$exception->getMessage()}";
                $summary['skipped']++;
            }
        }

        return back()->with([
            'success' => 'Importacion procesada correctamente.',
            'import_summary' => $summary,
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ], [
            'file.mimes' => 'El archivo debe ser CSV o Excel (.xlsx, .xls).',
            'file.max' => 'El archivo no puede superar los 10 MB.',
        ]);

        [$headers, $rows] = $this->parseUploadedFile($validated['file']);

        $campaignsById = Campaign::query()->get()->keyBy('id');
        $campaignsByName = Campaign::query()
            ->get()
            ->keyBy(fn (Campaign $campaign) => $this->normalizeCampaignName($campaign->name));

        $previewRows = [];
        $warnings = [];
        $processedRows = 0;
        $hasSisacTable = $this->hasSisacTable();

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $data = $this->mapRowToData($headers, $row);

            if ($this->isEmptyRow($data)) {
                continue;
            }

            $processedRows++;

            $previewRows[] = [
                'row_number' => $rowNumber,
                'campaign' => $data['campaign'] ?? $data['campaign_id'] ?? '',
                'ruc' => $data['ruc'] ?? '',
                'business_name' => $data['business_name'] ?? '',
                'representative_name' => $data['representative_name'] ?? '',
                'fiscal_address' => $data['fiscal_address'] ?? '',
                'current_operator' => $data['current_operator'] ?? '',
                'current_line_count' => $data['current_line_count'] ?? '',
                'segment' => $data['segment'] ?? '',
                'max_speed' => $data['max_speed'] ?? '',
                'package' => $data['package'] ?? '',
                'technology' => $data['technology'] ?? '',
                'phones' => $this->extractPhones($data)->implode(', '),
                'semaforo' => $hasSisacTable ? ($data['semaforo'] ?? '') : '',
                'resultado' => $hasSisacTable ? ($data['resultado'] ?? '') : '',
                'cantidad_lineas_ofrecer' => $hasSisacTable ? ($data['cantidad_lineas_ofrecer'] ?? '') : '',
                'deposito_garantia' => $hasSisacTable ? ($data['deposito_garantia'] ?? '') : '',
                'rango_lc_disponible' => $hasSisacTable ? ($data['rango_lc_disponible'] ?? '') : '',
            ];

            try {
                $this->resolveCampaign($data, $campaignsById, $campaignsByName);
                $this->extractPhones($data)->isNotEmpty() ?: throw ValidationException::withMessages([
                    'file' => "Fila {$rowNumber}: debes indicar al menos un teléfono válido de 9 dígitos.",
                ]);
                $rucPreview = trim((string) ($data['ruc'] ?? ''));
                if ($rucPreview !== '' && (!ctype_digit($rucPreview) || strlen($rucPreview) !== 11)) {
                    throw ValidationException::withMessages([
                        'file' => "Fila {$rowNumber}: el RUC debe contener exactamente 11 dígitos numéricos.",
                    ]);
                }
                $lineCountPreview = $data['current_line_count'] ?? null;
                if (filled($lineCountPreview) && (!is_numeric($lineCountPreview) || (int) $lineCountPreview < 1)) {
                    throw ValidationException::withMessages([
                        'file' => "Fila {$rowNumber}: current_line_count debe ser un numero entero mayor o igual a 1.",
                    ]);
                }
                if ($hasSisacTable) {
                    $this->buildSisacPayload($data, $rowNumber);
                }
            } catch (ValidationException $exception) {
                foreach ($exception->errors() as $messages) {
                    foreach ($messages as $message) {
                        $warnings[] = $message;
                    }
                }
            }
        }

        return response()->json([
            'headers' => array_values(array_filter($headers)),
            'has_sisac_table' => $hasSisacTable,
            'rows' => array_slice($previewRows, 0, 10),
            'total_rows' => $processedRows,
            'warnings' => array_slice($warnings, 0, 10),
        ]);
    }

    public function template(): StreamedResponse|BinaryFileResponse
    {
        $sampleRow = [
            'Campana Corporativa',
            '20123456789',
            'Empresa Demo SAC',
            'Juan Perez',
            '12345678',
            'Av. Javier Prado Este 123, San Isidro',
            'Claro',
            '5',
            'Corporativo',
            '400 Mbps',
            'Internet Negocios Pro',
            'Fibra',
            '987654321',
            '912345678',
            '',
        ];
        $headers = $this->templateHeaders();

        if ($this->hasSisacTable()) {
            $sampleRow = array_merge($sampleRow, [
                'verde',
                'Aprobado',
                '3',
                '0.00',
                '3000 - 5000',
            ]);
        }

        if ($this->excelSupportAvailable()) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Leads');

            foreach ($headers as $index => $header) {
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                $sheet->setCellValue($column . '1', $header);
            }

            foreach ($sampleRow as $index => $value) {
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                $sheet->setCellValue($column . '2', $value);
            }

            foreach (range(1, count($headers)) as $columnIndex) {
                $sheet->getColumnDimensionByColumn($columnIndex)->setAutoSize(true);
            }

            $temporaryPath = tempnam(sys_get_temp_dir(), 'lead-template-');

            if ($temporaryPath === false) {
                throw ValidationException::withMessages([
                    'file' => 'No se pudo generar la plantilla Excel.',
                ]);
            }

            $xlsxPath = $temporaryPath . '.xlsx';
            rename($temporaryPath, $xlsxPath);

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($xlsxPath);

            return response()->download(
                $xlsxPath,
                'plantilla-importacion-leads.xlsx',
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )->deleteFileAfterSend(true);
        }

        $filename = 'plantilla-importacion-leads.csv';

        return response()->streamDownload(function () use ($sampleRow, $headers) {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, $headers);
            fputcsv($handle, $sampleRow);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function readCsvRows(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false || $lines === []) {
            throw ValidationException::withMessages([
                'file' => 'No se pudo leer el archivo CSV.',
            ]);
        }

        $sampleLines = array_slice($lines, 0, 5);
        $delimiter = $this->detectDelimiter($sampleLines);
        $rows = [];
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'No se pudo abrir el archivo CSV.',
            ]);
        }

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($row === [null]) {
                continue;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function readSpreadsheetRows(string $path): array
    {
        if (!$this->excelSupportAvailable()) {
            throw ValidationException::withMessages([
                'file' => 'Para importar archivos Excel instala la dependencia: composer require phpoffice/phpspreadsheet',
            ]);
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);

        return $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
    }

    private function parseUploadedFile($file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        $rows = match ($extension) {
            'csv', 'txt' => $this->readCsvRows($file->getRealPath()),
            'xlsx', 'xls' => $this->readSpreadsheetRows($file->getRealPath()),
            default => throw ValidationException::withMessages([
                'file' => 'Formato de archivo no soportado.',
            ]),
        };

        if (count($rows) < 2) {
            throw ValidationException::withMessages([
                'file' => 'El archivo no contiene filas para importar.',
            ]);
        }

        $headers = $this->normalizeHeaders(array_shift($rows));
        $this->ensureRequiredHeaders($headers);

        return [$headers, $rows];
    }

    private function detectDelimiter(array $sampleLines): string
    {
        $delimiters = [',', ';', "\t"];
        $scores = [];

        foreach ($delimiters as $delimiter) {
            $columnCounts = array_map(
                static fn (string $line): int => count(str_getcsv($line, $delimiter)),
                $sampleLines
            );

            $scores[$delimiter] = [
                'max' => max($columnCounts),
                'avg' => array_sum($columnCounts) / max(count($columnCounts), 1),
            ];
        }

        uasort($scores, static function (array $left, array $right): int {
            if ($left['max'] === $right['max']) {
                return $right['avg'] <=> $left['avg'];
            }

            return $right['max'] <=> $left['max'];
        });

        $selected = array_key_first($scores);

        return is_string($selected) ? $selected : ',';
    }

    private function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            $normalized = $this->normalizeHeader((string) $header);

            return match ($normalized) {
                'campaign_name', 'campana', 'campaña' => 'campaign',
                'razon_social' => 'business_name',
                'representante', 'representante_nombre' => 'representative_name',
                'direccion_fiscal', 'direccion' => 'fiscal_address',
                'operador_actual' => 'current_operator',
                'lineas_actuales', 'cantidad_lineas' => 'current_line_count',
                'segmento' => 'segment',
                'velocidad_max', 'velocidad_maxima' => 'max_speed',
                'paquete_plan', 'plan' => 'package',
                'tecnologia_servicio' => 'technology',
                'telefono', 'telefono_1', 'celular' => 'phone',
                'telefono_2', 'celular_2' => 'phone_2',
                'telefono_3', 'celular_3' => 'phone_3',
                'cantidad_lineas_a_ofrecer', 'lineas_ofrecer' => 'cantidad_lineas_ofrecer',
                'deposito_de_garantia' => 'deposito_garantia',
                'rango_lc' => 'rango_lc_disponible',
                default => $normalized,
            };
        }, $headers);
    }

    private function normalizeHeader(string $header): string
    {
        $header = trim(str_replace("\xEF\xBB\xBF", '', $header));

        return Str::of($header)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->value();
    }

    private function ensureRequiredHeaders(array $headers): void
    {
        $missingColumns = collect(self::REQUIRED_COLUMNS)
            ->reject(fn (string $column) => in_array($column, $headers, true))
            ->values();

        if (!in_array('campaign', $headers, true) && !in_array('campaign_id', $headers, true)) {
            $missingColumns->push('campaign o campaign_id');
        }

        if ($missingColumns->isNotEmpty()) {
            throw ValidationException::withMessages([
                'file' => 'Faltan columnas requeridas: ' . $missingColumns->implode(', '),
            ]);
        }
    }

    private function mapRowToData(array $headers, array $row): array
    {
        $data = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $data[$header] = isset($row[$index]) ? trim((string) $row[$index]) : null;
        }

        return $data;
    }

    private function isEmptyRow(array $data): bool
    {
        return collect($data)
            ->filter(fn ($value) => filled($value))
            ->isEmpty();
    }

    private function resolveCampaign(array $data, Collection $campaignsById, Collection $campaignsByName): Campaign
    {
        if (filled($data['campaign_id'] ?? null)) {
            $campaign = $campaignsById->get((int) $data['campaign_id']);

            if (!$campaign) {
                throw ValidationException::withMessages([
                    'file' => "No existe la campana con ID {$data['campaign_id']}.",
                ]);
            }

            return $campaign;
        }

        $campaignName = trim((string) ($data['campaign'] ?? ''));

        if ($campaignName === '') {
            throw ValidationException::withMessages([
                'file' => 'Debes indicar la campana en la columna campaign.',
            ]);
        }

        $campaign = $campaignsByName->get($this->normalizeCampaignName($campaignName));

        if (!$campaign) {
            throw ValidationException::withMessages([
                'file' => "No existe la campana {$campaignName}.",
            ]);
        }

        return $campaign;
    }

    private function normalizeCampaignName(string $campaignName): string
    {
        return Str::of($campaignName)
            ->lower()
            ->ascii()
            ->squish()
            ->value();
    }

    private function extractPhones(array $data): Collection
    {
        return collect([
            $data['phone'] ?? null,
            $data['phone_2'] ?? null,
            $data['phone_3'] ?? null,
            $data['phones'] ?? null,
        ])->filter()
            ->flatMap(function (string $value) {
                return preg_split('/[;,|]+/', $value) ?: [];
            })
            ->map(fn (string $phone) => preg_replace('/\D+/', '', $phone))
            ->filter(fn (string $phone) => strlen($phone) === 9 && ctype_digit($phone))
            ->unique()
            ->values();
    }

    private function normalizeOperator(?string $operator, int $rowNumber): ?string
    {
        if (!filled($operator)) {
            return null;
        }

        $normalized = Str::of($operator)->lower()->ascii()->trim()->value();

        $mapped = match ($normalized) {
            'claro' => 'Claro',
            'movistar' => 'Movistar',
            'entel' => 'Entel',
            'bitel' => 'Bitel',
            default => null,
        };

        if (!$mapped) {
            throw ValidationException::withMessages([
                'file' => "Fila {$rowNumber}: current_operator debe ser Claro, Movistar, Entel o Bitel.",
            ]);
        }

        return $mapped;
    }

    private function normalizeLineCount(?string $lineCount, int $rowNumber): ?int
    {
        if (!filled($lineCount)) {
            return null;
        }

        if (!is_numeric($lineCount) || (int) $lineCount < 1) {
            throw ValidationException::withMessages([
                'file' => "Fila {$rowNumber}: current_line_count debe ser un numero entero mayor o igual a 1.",
            ]);
        }

        return (int) $lineCount;
    }

    private function buildLeadPayload(array $data, int $campaignId, ?string $operator, ?int $lineCount, int $userId, int $rowNumber = 0): array
    {
        $ruc = trim((string) ($data['ruc'] ?? ''));
        $businessName = trim((string) ($data['business_name'] ?? ''));

        if ($ruc === '') {
            throw ValidationException::withMessages([
                'file' => 'La columna ruc es obligatoria.',
            ]);
        }

        if (!ctype_digit($ruc) || strlen($ruc) !== 11) {
            throw ValidationException::withMessages([
                'file' => "Fila {$rowNumber}: el RUC debe contener exactamente 11 dígitos numéricos.",
            ]);
        }

        if ($businessName === '') {
            throw ValidationException::withMessages([
                'file' => 'La columna business_name es obligatoria.',
            ]);
        }

        return [
            'campaign_id' => $campaignId,
            'assigned_to_user_id' => null,
            'supervisor_user_id' => null,
            'created_by_user_id' => $userId,
            'source' => 'carga_masiva',
            'delivery_status' => 'disponible',
            'taken_at' => null,
            'released_at' => null,
            'disabled_at' => null,
            'disabled_reason' => null,
            'no_contact_attempts' => 0,
            'full_name' => trim((string) ($data['representative_name'] ?? '')) ?: null,
            'ruc' => $ruc,
            'business_name' => $businessName,
            'representative_name' => trim((string) ($data['representative_name'] ?? '')) ?: null,
            'dni' => trim((string) ($data['dni'] ?? '')) ?: null,
            'fiscal_address' => trim((string) ($data['fiscal_address'] ?? '')) ?: null,
            'current_operator' => $operator,
            'current_line_count' => $lineCount,
            'segment' => trim((string) ($data['segment'] ?? '')) ?: null,
            'max_speed' => trim((string) ($data['max_speed'] ?? '')) ?: null,
            'package' => trim((string) ($data['package'] ?? '')) ?: null,
            'technology' => trim((string) ($data['technology'] ?? '')) ?: null,
            'last_contact_name' => null,
            'last_contact_phone' => null,
            'status_general' => 'sin_contacto',
            'status_specific' => 'sin_gestion',
            'status_final' => 'sin_gestion',
            'call_summary' => null,
        ];
    }

    private function buildSisacPayload(array $data, int $rowNumber): ?array
    {
        $payload = [
            'semaforo' => trim((string) ($data['semaforo'] ?? '')) ?: null,
            'resultado' => trim((string) ($data['resultado'] ?? '')) ?: null,
            'cantidad_lineas_ofrecer' => $this->normalizeSisacInteger($data['cantidad_lineas_ofrecer'] ?? null, 'cantidad_lineas_ofrecer', $rowNumber),
            'deposito_garantia' => $this->normalizeSisacDecimal($data['deposito_garantia'] ?? null, 'deposito_garantia', $rowNumber),
            'rango_lc_disponible' => trim((string) ($data['rango_lc_disponible'] ?? '')) ?: null,
        ];

        return collect($payload)->filter(fn ($value) => $value !== null && $value !== '')->isEmpty()
            ? null
            : $payload;
    }

    private function normalizeSisacInteger(?string $value, string $column, int $rowNumber): ?int
    {
        if (!filled($value)) {
            return null;
        }

        if (!is_numeric($value) || (int) $value < 0) {
            throw ValidationException::withMessages([
                'file' => "Fila {$rowNumber}: {$column} debe ser un numero entero mayor o igual a 0.",
            ]);
        }

        return (int) $value;
    }

    private function normalizeSisacDecimal(?string $value, string $column, int $rowNumber): ?string
    {
        if (!filled($value)) {
            return null;
        }

        $normalized = str_replace(',', '.', trim((string) $value));

        if (!is_numeric($normalized) || (float) $normalized < 0) {
            throw ValidationException::withMessages([
                'file' => "Fila {$rowNumber}: {$column} debe ser un numero mayor o igual a 0.",
            ]);
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    private function syncSisacData(Lead $lead, ?array $sisacPayload, bool $hasSisacTable): void
    {
        if (!$hasSisacTable || $sisacPayload === null) {
            return;
        }

        $lead->sisacData()->updateOrCreate(
            ['lead_id' => $lead->id],
            $sisacPayload
        );
    }

    private function templateHeaders(): array
    {
        return $this->hasSisacTable()
            ? array_merge(self::LEAD_TEMPLATE_HEADERS, self::SISAC_TEMPLATE_HEADERS)
            : self::LEAD_TEMPLATE_HEADERS;
    }

    private function hasSisacTable(): bool
    {
        return Schema::hasTable('lead_sisac_data');
    }

    private function excelSupportAvailable(): bool
    {
        return class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class);
    }
}
