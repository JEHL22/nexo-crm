<?php

namespace App\Support;

/**
 * Cálculos sobre el snapshot de productos de un acuerdo (products_snapshot).
 * Compartido por MyWorkController/WorkController y SupervisorAgreementController.
 */
class AgreementProducts
{
    /**
     * 'movil', 'fijo' o 'movil_fijo' si el snapshot mezcla ambos tipos.
     */
    public static function resolveProductType(array $products): ?string
    {
        $types = collect($products)->pluck('type')->unique()->values();

        if ($types->count() === 2) {
            return 'movil_fijo';
        }

        return $types->first();
    }

    /**
     * Totales del acuerdo a partir del snapshot.
     *
     * @return array{0: int|null, 1: float|null} [líneas ofertadas, pago mensual]
     */
    public static function calculateTotals(array $productsSnapshot): array
    {
        $offeredLineCount = collect($productsSnapshot)->sum(fn (array $product) => (int) ($product['line_count'] ?? 0)) ?: null;
        $monthlyPayment = collect($productsSnapshot)->sum(function (array $product) {
            $lineCount = (int) ($product['line_count'] ?? 0);
            $unitPrice = (float) ($product['price'] ?? 0);

            return (float) ($product['line_total'] ?? ($lineCount > 0 ? $unitPrice * $lineCount : $unitPrice));
        }) ?: null;

        return [$offeredLineCount, $monthlyPayment];
    }
}
