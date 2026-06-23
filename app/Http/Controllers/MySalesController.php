<?php

namespace App\Http\Controllers;

use App\Models\PromotionName;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class MySalesController extends Controller
{
    private const MANAGEMENT_STATUS_OPTIONS = [
        'pendiente_supervision' => 'Pendiente supervisión',
        'pendiente_validacion' => 'Pendiente validación',
        'aprobado' => 'Aprobado',
        'rechazado' => 'Rechazado',
        'observado' => 'Observado',
    ];

    private const SISAC_FILTER_OPTIONS = [
        'en_evaluacion' => 'En evaluación',
        'activo' => 'Activo',
        'rechazado' => 'Rechazado',
        'entregado' => 'Entregado',
    ];

    private const MANAGEMENT_STATUS_LABELS = [
        'pendiente_supervision' => 'Pendiente supervisión',
        'pendiente_validacion' => 'Pendiente validación',
        'aprobado' => 'Aprobado',
        'rechazado' => 'Rechazado',
        'observado' => 'Observado',
    ];

    private const SISAC_STATUS_LABELS = [
        'pendiente_supervision' => 'Pendiente supervisión',
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
        $user = Auth::user();
        $filters = $request->only(['ruc', 'management_status', 'sisac_status']);

        $query = Sale::query()
            ->with([
                'lead.phones',
                'lead.interactions' => function ($query) {
                    $query->latest('created_at')->with('user');
                },
                'interaction.offers',
                'interaction.user',
                'campaign',
                'executive',
                'supervisor',
                'histories.user',
                'simDetails',
                'postSaleUpdates' => function ($query) {
                    $query->latest('created_at')->with('user');
                },
                'validationUpdates' => function ($query) {
                    $query->latest('created_at')->with(['user', 'simDetails']);
                },
            ])
            ->where('executive_user_id', $user->id)
            ->where('status', Sale::STATUS_ACCEPTED);

        if (! empty($filters['ruc'])) {
            $search = trim((string) $filters['ruc']);

            $query->whereHas('lead', function ($leadQuery) use ($search) {
                $leadQuery->where('ruc', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['management_status']) && array_key_exists($filters['management_status'], self::MANAGEMENT_STATUS_OPTIONS)) {
            $query->where('management_status', $filters['management_status']);
        }

        if (! empty($filters['sisac_status']) && array_key_exists($filters['sisac_status'], self::SISAC_FILTER_OPTIONS)) {
            $query->whereIn('sisac_status', $this->resolveSisacStatuses($filters['sisac_status']));
        }

        $sales = $query
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        $promotionPriceMap = Schema::hasTable('promotion_names')
            ? PromotionName::query()->pluck('monthly_price', 'name')->map(fn ($value) => (float) $value)->all()
            : [];

        return view('my-sales.index', [
            'sales' => $sales,
            'filters' => $filters,
            'managementStatusOptions' => self::MANAGEMENT_STATUS_OPTIONS,
            'sisacFilterOptions' => self::SISAC_FILTER_OPTIONS,
            'managementStatusLabels' => self::MANAGEMENT_STATUS_LABELS,
            'sisacStatusLabels' => self::SISAC_STATUS_LABELS,
            'promotionPriceMap' => $promotionPriceMap,
        ]);
    }

    private function resolveSisacStatuses(string $status): array
    {
        return match ($status) {
            'en_evaluacion' => ['en_evaluacion', 'pendiente_validacion', 'observado'],
            'activo' => ['activo', 'aprobado'],
            'rechazado' => ['rechazado'],
            'entregado' => ['entregado'],
            default => [$status],
        };
    }
}
