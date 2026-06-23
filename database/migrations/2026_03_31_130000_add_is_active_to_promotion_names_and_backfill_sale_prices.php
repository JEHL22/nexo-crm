<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotion_names', function (Blueprint $table) {
            if (!Schema::hasColumn('promotion_names', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('monthly_price');
            }
        });

        $promotionPriceMap = DB::table('promotion_names')
            ->pluck('monthly_price', 'name')
            ->map(fn ($value) => (float) $value)
            ->all();

        DB::table('sales')
            ->select(['id', 'products_snapshot'])
            ->orderBy('id')
            ->chunkById(100, function ($sales) use ($promotionPriceMap) {
                foreach ($sales as $sale) {
                    $products = json_decode($sale->products_snapshot ?? '[]', true);

                    if (!is_array($products) || empty($products)) {
                        continue;
                    }

                    $updatedProducts = collect($products)->map(function (array $product) use ($promotionPriceMap) {
                        $lineCount = (int) ($product['line_count'] ?? 0);
                        $isMobile = ($product['type'] ?? null) === 'movil';
                        $summaryValue = trim((string) ($product['summary_value'] ?? ''));
                        $resolvedPrice = $isMobile && $summaryValue !== ''
                            ? (float) ($promotionPriceMap[$summaryValue] ?? ($product['price'] ?? 0))
                            : (float) ($product['price'] ?? 0);

                        $product['price'] = $resolvedPrice;
                        $product['line_total'] = $lineCount > 0 ? $resolvedPrice * $lineCount : $resolvedPrice;

                        return $product;
                    })->values();

                    $monthlyPayment = $updatedProducts->sum(fn (array $product) => (float) ($product['line_total'] ?? 0));

                    DB::table('sales')
                        ->where('id', $sale->id)
                        ->update([
                            'products_snapshot' => json_encode($updatedProducts->all(), JSON_UNESCAPED_UNICODE),
                            'monthly_payment' => $monthlyPayment > 0 ? $monthlyPayment : null,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('promotion_names', function (Blueprint $table) {
            if (Schema::hasColumn('promotion_names', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
