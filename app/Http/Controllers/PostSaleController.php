<?php

namespace App\Http\Controllers;

use App\Models\PostSaleUpdate;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PostSaleController extends Controller
{
    private const STATUS_OPTIONS = [
        'pendiente_validacion' => 'Pendiente validación',
        'aprobado' => 'Aprobado',
        'rechazado' => 'Rechazado',
        'observado' => 'Observado',
    ];

    public function index(Request $request)
    {
        $filters = $request->only(['management_status', 'ruc']);

        $query = Sale::query()
            ->with([
                'lead.phones',
                'campaign',
                'executive',
                'supervisor',
                'interaction.offers',
                'postSaleUpdates' => function ($query) {
                    $query->latest('created_at')->with('user');
                },
                'validationUpdates' => function ($query) {
                    $query->latest('created_at')->with('user');
                },
            ])
            ->where('status', Sale::STATUS_ACCEPTED)
            ->where('supervisor_validation_status', Sale::SUPERVISOR_VALIDATION_VALIDATED);

        if (! empty($filters['management_status']) && array_key_exists($filters['management_status'], self::STATUS_OPTIONS)) {
            $query->where('management_status', $filters['management_status']);
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

        return view('post-sale.index', [
            'sales' => $sales,
            'filters' => $filters,
            'statusOptions' => self::STATUS_OPTIONS,
        ]);
    }

    public function update(Request $request, Sale $sale)
    {
        // Postventa solo gestiona ventas que ya pasaron la validación del
        // supervisor (mismo scope que el index) — evita editar por ID directo
        abort_unless(
            $sale->status === Sale::STATUS_ACCEPTED
                && $sale->supervisor_validation_status === Sale::SUPERVISOR_VALIDATION_VALIDATED,
            403
        );

        $validated = $request->validate([
            'management_status' => ['required', 'string', Rule::in(Sale::MANAGEMENT_STATUSES)],
            'feedback' => 'required|string|max:5000',
        ]);

        DB::transaction(function () use ($sale, $validated) {
            // Guardamos el estado vigente en sales y el historial completo en post_sale_updates.
            $sale->update([
                'management_status' => $validated['management_status'],
            ]);

            PostSaleUpdate::create([
                'sale_id' => $sale->id,
                'user_id' => Auth::id(),
                'management_status' => $validated['management_status'],
                'feedback' => $validated['feedback'],
            ]);
        });

        return redirect()
            ->route('post-sale.index')
            ->with('success', 'Gestión de postventa actualizada correctamente.');
    }
}
