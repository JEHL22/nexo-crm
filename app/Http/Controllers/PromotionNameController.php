<?php

namespace App\Http\Controllers;

use App\Models\PromotionName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PromotionNameController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('administrador de promociones'), 403);

        $promotionNames = PromotionName::query()
            ->with('sender:id,name')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(12);

        return view('promotion-admin.index', [
            'promotionNames' => $promotionNames,
            'totals' => [
                'records' => PromotionName::count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('administrador de promociones'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160', 'unique:promotion_names,name'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $promotionName = PromotionName::create([
                'sender_user_id' => $user->id,
                'name' => trim((string) $validated['name']),
                'monthly_price' => (float) $validated['monthly_price'],
                'sort_order' => 1,
            ]);

            $this->resequencePromotionOrders(
                $promotionName,
                isset($validated['sort_order']) ? (int) $validated['sort_order'] : null
            );
        });

        return redirect()->route('promotion-admin.index')->with('success', 'Nombre de promoción creado correctamente.');
    }

    public function update(Request $request, PromotionName $promotionName): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('administrador de promociones'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:160', 'unique:promotion_names,name,'.$promotionName->id],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($promotionName, $validated) {
            $promotionName->update([
                'name' => trim((string) $validated['name']),
                'monthly_price' => (float) $validated['monthly_price'],
            ]);

            $this->resequencePromotionOrders(
                $promotionName,
                isset($validated['sort_order']) ? (int) $validated['sort_order'] : null
            );
        });

        return redirect()->route('promotion-admin.index')->with('success', 'Nombre de promoción actualizado correctamente.');
    }

    public function destroy(Request $request, PromotionName $promotionName): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('administrador de promociones'), 403);

        $promotionName->update([
            'is_active' => !$promotionName->is_active,
        ]);

        return redirect()->route('promotion-admin.index')->with(
            'success',
            $promotionName->is_active
                ? 'Promoción reactivada correctamente.'
                : 'Promoción deshabilitada correctamente.'
        );
    }

    private function resequencePromotionOrders(PromotionName $promotionName, ?int $desiredPosition = null): void
    {
        $orderedIds = PromotionName::query()
            ->whereKeyNot($promotionName->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->all();

        $targetPosition = $desiredPosition ?? (count($orderedIds) + 1);
        $targetIndex = max(0, min($targetPosition - 1, count($orderedIds)));

        array_splice($orderedIds, $targetIndex, 0, [$promotionName->id]);

        foreach ($orderedIds as $index => $id) {
            PromotionName::query()
                ->whereKey($id)
                ->update(['sort_order' => $index + 1]);
        }
    }

    private function compactPromotionOrders(): void
    {
        $orderedIds = PromotionName::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->all();

        foreach ($orderedIds as $index => $id) {
            PromotionName::query()
                ->whereKey($id)
                ->update(['sort_order' => $index + 1]);
        }
    }
}
