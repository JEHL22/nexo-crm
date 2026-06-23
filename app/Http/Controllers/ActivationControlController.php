<?php

namespace App\Http\Controllers;

use App\Models\ActivationControlRecord;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ActivationControlController extends Controller
{
    private const FIELDS = [
        'empresa' => 'Empresa',
        'mes' => 'Mes',
        'fecha_ingreso' => 'F. Ingreso',
        'fecha_activacion' => 'F. Activación',
        'sec' => 'SEC',
        'py' => 'PY',
        'sot' => 'SOT',
        'linea' => 'Linea',
        'large' => 'Large',
        'cliente' => 'Cliente',
        'ruc' => 'RUC',
        'servicio' => 'Servicio',
        'tipo_cliente' => 'Tipo cliente',
        'plan_tarifario' => 'Plan tarifario',
        'porcentaje_dscto' => '% dscto',
        'ajuste' => 'Ajuste',
        'cf' => 'CF',
        'adic' => 'Adic',
        'sva' => 'SVA',
        'cf_sin_igv' => 'CF SIN IGV',
        'q' => 'Q',
        'material' => 'Material',
        'marca' => 'Marca',
        'consultor' => 'Consultor',
        'modalidad' => 'Modalidad',
        'estado' => 'Estado',
        'comentario' => 'Comentario',
        'score' => 'Score',
        'segmento' => 'Segmento',
        'opotunidad' => 'Opotunidad',
        'estado_sf' => 'Estado SF',
        'f_cierre_op' => 'F. Cierre Op',
        'f_liberacion' => 'F. Liberación',
        'validacion' => 'Validación',
    ];

    public function index()
    {
        return view('activation-control.index', [
            'fields' => self::FIELDS,
            'records' => ActivationControlRecord::query()
                ->with(['creator', 'updater'])
                ->latest()
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $payload = $this->normalizePayload($validated);
        $userId = $request->user()?->id;

        if (collect($payload)->filter(fn ($value) => $value !== null && $value !== '')->isEmpty()) {
            return redirect()
                ->back()
                ->withErrors(['record' => 'Completa al menos un campo antes de guardar el registro.'])
                ->withInput();
        }

        ActivationControlRecord::create([
            ...$payload,
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
        ]);

        return redirect()
            ->route('activation-control.index')
            ->with('success', 'Registro de activación guardado correctamente.');
    }

    public function export()
    {
        $rows = ActivationControlRecord::query()
            ->latest()
            ->get()
            ->map(function (ActivationControlRecord $record): array {
                $normalizedRow = [];

                foreach (self::FIELDS as $key => $label) {
                    $value = $record->{$key};
                    $normalizedRow[$label] = $value === null ? '' : trim((string) $value);
                }

                return $normalizedRow;
            });

        if ($rows->isEmpty()) {
            return redirect()
                ->route('activation-control.index')
                ->withErrors(['export' => 'Agrega al menos un registro antes de exportar.']);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Control de Activaciones');

        $headers = array_keys($rows->first());
        $sheet->fromArray($headers, null, 'A1');

        foreach ($rows as $index => $row) {
            $sheet->fromArray(array_values($row), null, 'A'.($index + 2));
        }

        foreach (range(1, count($headers)) as $columnIndex) {
            $sheet->getColumnDimensionByColumn($columnIndex)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $temporaryPath = tempnam(sys_get_temp_dir(), 'activation-control-');
        $xlsxPath = $temporaryPath.'.xlsx';
        rename($temporaryPath, $xlsxPath);
        $writer->save($xlsxPath);

        return response()->download(
            $xlsxPath,
            'control-de-activaciones.xlsx',
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        )->deleteFileAfterSend(true);
    }

    private function rules(): array
    {
        return [
            'empresa' => ['nullable', 'string', 'max:255'],
            'mes' => ['nullable', 'date_format:Y-m'],
            'fecha_ingreso' => ['nullable', 'date'],
            'fecha_activacion' => ['nullable', 'date'],
            'sec' => ['nullable', 'string', 'max:100'],
            'py' => ['nullable', 'string', 'max:100'],
            'sot' => ['nullable', 'string', 'max:100'],
            'linea' => ['nullable', 'string', 'max:100'],
            'large' => ['nullable', 'string', 'max:100'],
            'cliente' => ['nullable', 'string', 'max:255'],
            'ruc' => ['nullable', 'string', 'max:20'],
            'servicio' => ['nullable', 'string', 'max:150'],
            'tipo_cliente' => ['nullable', 'string', 'max:150'],
            'plan_tarifario' => ['nullable', 'string', 'max:255'],
            'porcentaje_dscto' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'ajuste' => ['nullable', 'string', 'max:150'],
            'cf' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'adic' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'sva' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'cf_sin_igv' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'q' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'material' => ['nullable', 'string', 'max:150'],
            'marca' => ['nullable', 'string', 'max:150'],
            'consultor' => ['nullable', 'string', 'max:150'],
            'modalidad' => ['nullable', 'string', 'max:150'],
            'estado' => ['nullable', 'string', 'max:150'],
            'comentario' => ['nullable', 'string', 'max:5000'],
            'score' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'segmento' => ['nullable', 'string', 'max:150'],
            'opotunidad' => ['nullable', 'string', 'max:150'],
            'estado_sf' => ['nullable', 'string', 'max:150'],
            'f_cierre_op' => ['nullable', 'date'],
            'f_liberacion' => ['nullable', 'date'],
            'validacion' => ['nullable', 'string', 'max:150'],
        ];
    }

    private function normalizePayload(array $validated): array
    {
        $payload = [];

        foreach (array_keys(self::FIELDS) as $field) {
            $value = $validated[$field] ?? null;

            if (is_string($value)) {
                $value = trim($value);
                $value = $value === '' ? null : $value;
            }

            $payload[$field] = $value;
        }

        return $payload;
    }
}
