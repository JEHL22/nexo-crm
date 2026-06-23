<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SupervisorStatusNotification;
use App\Models\ValidationUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ValidationController extends Controller
{
    private const STATUS_OPTIONS = [
        'en_evaluacion' => 'En evaluación',
        'activo' => 'Activo',
        'rechazado' => 'Rechazado',
        'entregado' => 'Entregado',
    ];

    private const STATUS_LABELS = [
        'en_evaluacion' => 'En evaluación',
        'pendiente_validacion' => 'En evaluación',
        'observado' => 'En evaluación',
        'activo' => 'Activo',
        'aprobado' => 'Activo',
        'rechazado' => 'Rechazado',
        'entregado' => 'Entregado',
    ];

    public function index(Request $request)
    {
        [$filters, $sales] = $this->buildIndexPayload($request);

        return view('validation.index', [
            'sales' => $sales,
            'filters' => $filters,
            'statusOptions' => self::STATUS_OPTIONS,
            'statusLabels' => self::STATUS_LABELS,
            'pulseRoute' => route('validation.pulse', array_filter([
                ...$filters,
                'page' => $sales->currentPage() > 1 ? $sales->currentPage() : null,
            ], fn ($value) => $value !== null && $value !== '')),
        ]);
    }

    public function pulse(Request $request): JsonResponse
    {
        [$filters, $sales] = $this->buildIndexPayload($request);

        return response()->json([
            'ok' => true,
            'updated_at_label' => now()->setTimezone(config('app.timezone'))->format('d/m/Y H:i:s'),
            'list_html' => view('validation.partials.list', [
                'sales' => $sales,
                'filters' => $filters,
                'statusOptions' => self::STATUS_OPTIONS,
                'statusLabels' => self::STATUS_LABELS,
            ])->render(),
        ]);
    }

    public function update(Request $request, Sale $sale)
    {
        // Mesa de Control solo valida ventas que ya pasaron la validación del
        // supervisor (mismo scope que el index) — evita editar por ID directo
        abort_unless(
            $sale->status === Sale::STATUS_ACCEPTED
                && $sale->supervisor_validation_status === Sale::SUPERVISOR_VALIDATION_VALIDATED,
            403
        );

        $validated = $request->validate([
            'sisac_status' => ['required', 'string', Rule::in(Sale::SISAC_STATUSES)],
            'feedback' => 'required|string|max:5000',
            'sim_details' => 'nullable|array',
            'sim_details.*.serial_number' => 'nullable|string|max:100',
            'sim_details.*.sim_number' => 'nullable|string|max:50',
        ], [
            'sisac_status.required' => 'Selecciona el estado de Mesa de Control.',
            'sisac_status.in' => 'Selecciona un estado válido para Mesa de Control.',
            'feedback.required' => 'Escribe el detalle o comentario de validación.',
            'feedback.max' => 'El comentario de validación no puede superar los 5000 caracteres.',
            'sim_details.array' => 'La información de entrega SIM no tiene un formato válido.',
            'sim_details.*.serial_number.max' => 'La serie SIM no puede superar los 100 caracteres.',
            'sim_details.*.sim_number.max' => 'El número SIM no puede superar los 50 caracteres.',
        ], [
            'sisac_status' => 'estado de Mesa de Control',
            'feedback' => 'comentario de validación',
            'sim_details' => 'datos de entrega SIM',
            'sim_details.*.serial_number' => 'serie SIM',
            'sim_details.*.sim_number' => 'número SIM',
        ]);

        $simDetails = null;
        $requiresSimDelivery = in_array($sale->product_type, ['movil', 'movil_fijo'], true);

        if ($validated['sisac_status'] === Sale::SISAC_DELIVERED && $requiresSimDelivery) {
            $deliveryLines = $this->buildDeliverySimLines($sale);
            $lineCount = count($deliveryLines);

            abort_if($lineCount === 0, 422, 'Esta venta no tiene líneas móviles para registrar entrega SIM.');

            $rawDetails = collect($validated['sim_details'] ?? [])
                ->take($lineCount)
                ->map(function ($detail, $index) use ($deliveryLines) {
                    $line = $deliveryLines[$index] ?? null;

                    return [
                        'line' => $index + 1,
                        'kind' => $line['kind'] ?? null,
                        'serial_number' => trim((string) ($detail['serial_number'] ?? '')),
                        'sim_number' => trim((string) ($detail['sim_number'] ?? ($line['prefilled_sim_number'] ?? ''))),
                    ];
                })
                ->values();

            abort_if($rawDetails->count() !== $lineCount, 422, 'Debes registrar una serie y un número SIM por cada línea ofrecida.');

            foreach ($rawDetails as $detail) {
                abort_if(
                    $detail['serial_number'] === '' || $detail['sim_number'] === '',
                    422,
                    'Debes completar la serie y el número SIM de todas las líneas.'
                );
            }

            $simDetails = $rawDetails->all();
        }

        DB::transaction(function () use ($sale, $validated, $simDetails) {
            $previousSisacStatus = $sale->sisac_status;

            // El estado actual vive en sales para consultas rápidas y el historial queda en validation_updates.
            $sale->update([
                'sisac_status' => $validated['sisac_status'],
            ]);

            $sale->simDetails()->delete();

            if (! empty($simDetails)) {
                $sale->simDetails()->createMany(
                    collect($simDetails)
                        ->map(fn (array $detail) => [
                            'line_number' => (int) ($detail['line'] ?? 1),
                            'serial_number' => $detail['serial_number'],
                            'sim_number' => $detail['sim_number'],
                        ])
                        ->all()
                );
            }

            $validationUpdate = ValidationUpdate::create([
                'sale_id' => $sale->id,
                'user_id' => Auth::id(),
                'sisac_status' => $validated['sisac_status'],
                'feedback' => $validated['feedback'],
            ]);

            if (! empty($simDetails)) {
                $validationUpdate->simDetails()->createMany(
                    collect($simDetails)
                        ->map(fn (array $detail) => [
                            'line_number' => (int) ($detail['line'] ?? 1),
                            'serial_number' => $detail['serial_number'],
                            'sim_number' => $detail['sim_number'],
                        ])
                        ->all()
                );
            }

            if ($sale->supervisor_user_id) {
                $businessLabel = trim((string) ($sale->customer_business_name ?: $sale->lead?->business_name ?: 'Tarjeta sin nombre'));
                $rucLabel = trim((string) ($sale->customer_ruc ?: $sale->lead?->ruc ?: 'Sin RUC'));
                $statusLabel = self::STATUS_LABELS[$validated['sisac_status']] ?? ucfirst(str_replace('_', ' ', $validated['sisac_status']));

                SupervisorStatusNotification::create([
                    'user_id' => $sale->supervisor_user_id,
                    'sale_id' => $sale->id,
                    'validation_update_id' => $validationUpdate->id,
                    'previous_status' => $previousSisacStatus,
                    'current_status' => $validated['sisac_status'],
                    'title' => 'Mesa de Control actualizó un acuerdo',
                    'message' => $businessLabel.' (RUC '.$rucLabel.') ahora está en '.$statusLabel.'.',
                    'notified_at' => now(),
                ]);
            }
        });

        return redirect()
            ->route('validation.index')
            ->with('success', 'Validación actualizada correctamente.');
    }

    private function resolveStatusValues(string $status): array
    {
        return match ($status) {
            'en_evaluacion' => ['en_evaluacion', 'pendiente_validacion', 'observado'],
            'activo' => ['activo', 'aprobado'],
            'rechazado' => ['rechazado'],
            'entregado' => ['entregado'],
            default => [$status],
        };
    }

    private function buildIndexPayload(Request $request): array
    {
        $filters = $request->only(['sisac_status', 'ruc']);

        $query = Sale::query()
            ->with([
                'lead.phones',
                'lead.interactions' => function ($query) {
                    $query->latest('created_at')->with('user');
                },
                'campaign',
                'executive',
                'supervisor',
                'interaction.offers',
                'postSaleUpdates' => function ($query) {
                    $query->latest('created_at')->with('user');
                },
                'validationUpdates' => function ($query) {
                    $query->latest('created_at')->with(['user', 'simDetails']);
                },
                'simDetails',
            ])
            ->where('status', Sale::STATUS_ACCEPTED)
            ->where('supervisor_validation_status', Sale::SUPERVISOR_VALIDATION_VALIDATED);

        if (! empty($filters['sisac_status']) && array_key_exists($filters['sisac_status'], self::STATUS_OPTIONS)) {
            $query->whereIn('sisac_status', $this->resolveStatusValues($filters['sisac_status']));
        }

        if (! empty($filters['ruc'])) {
            $search = trim((string) $filters['ruc']);

            $query->where(function ($saleQuery) use ($search) {
                $saleQuery->where('customer_ruc', 'like', "%{$search}%")
                    ->orWhereHas('lead', function ($leadQuery) use ($search) {
                        $leadQuery->where('ruc', 'like', "%{$search}%");
                    });
            });
        }

        $sales = $query
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        return [$filters, $sales];
    }

    private function buildDeliverySimLines(Sale $sale): array
    {
        $portabilityLines = collect($sale->portability_phone_numbers_snapshot ?? [])
            ->values()
            ->map(function ($row, $index) {
                return [
                    'line' => $index + 1,
                    'kind' => 'portabilidad',
                    'prefilled_sim_number' => trim((string) ($row['phone_number'] ?? '')),
                ];
            });

        $newLines = collect($sale->products_snapshot ?? [])
            ->filter(fn (array $product) => ($product['type'] ?? null) === 'movil' && ($product['detail'] ?? null) === 'Alta nueva')
            ->flatMap(function (array $product) {
                $lineCount = max((int) ($product['line_count'] ?? 0), 0);

                return ($lineCount > 0 ? collect(range(1, $lineCount)) : collect())->map(fn () => [
                    'kind' => 'alta_nueva',
                    'prefilled_sim_number' => '',
                ]);
            })
            ->values();

        return $portabilityLines
            ->concat($newLines)
            ->values()
            ->map(function (array $row, int $index) {
                return [
                    'line' => $index + 1,
                    'kind' => $row['kind'] ?? null,
                    'prefilled_sim_number' => $row['prefilled_sim_number'] ?? '',
                ];
            })
            ->all();
    }
}
