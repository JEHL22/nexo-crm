<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Normalización y validación de filas de promoción (líneas + plan) del
 * formulario de acuerdos. Compartido por WorkController, MyWorkController
 * y SupervisorAgreementController — antes estaba duplicado en los tres.
 */
class PromotionRows
{
    /**
     * Combina los arrays paralelos de líneas y nombres de promoción en filas
     * normalizadas, descartando las filas completamente vacías.
     */
    public static function normalize(array $lines, array $promotionNames): array
    {
        $rows = [];
        $totalRows = max(count($lines), count($promotionNames));

        for ($index = 0; $index < $totalRows; $index++) {
            $rawLines = $lines[$index] ?? null;
            $rawPromotionName = $promotionNames[$index] ?? null;

            $lineValue = filled($rawLines) ? (int) $rawLines : null;
            $promotionValue = filled($rawPromotionName) ? trim((string) $rawPromotionName) : null;

            if ($lineValue === null && $promotionValue === null) {
                continue;
            }

            $rows[] = [
                'index' => $index + 1,
                'lines' => $lineValue,
                'promotion_name' => $promotionValue,
            ];
        }

        return $rows;
    }

    /**
     * Valida filas normalizadas. $requireRows = false para flujos donde el
     * conjunto puede quedar vacío (edición del supervisor).
     *
     * @return array<string, string> errores por clave de campo
     */
    public static function validate(array $rows, string $prefix, string $label, bool $requireRows = true): array
    {
        if ($requireRows && empty($rows)) {
            return [
                $prefix.'_rows' => 'Agrega al menos una fila de '.$label.'.',
            ];
        }

        $errors = [];
        $selectedPromotions = [];

        foreach ($rows as $row) {
            if (! $row['lines']) {
                $errors[$prefix.'_lines_'.$row['index']] = 'Completa la cantidad de líneas en '.$label.' fila '.$row['index'].'.';
            }

            if (! $row['promotion_name']) {
                $errors[$prefix.'_promotion_'.$row['index']] = 'Selecciona una promoción en '.$label.' fila '.$row['index'].'.';

                continue;
            }

            $normalizedPromotionName = Str::lower(trim((string) $row['promotion_name']));

            if (isset($selectedPromotions[$normalizedPromotionName])) {
                $errors[$prefix.'_rows'] = 'No repitas la misma promoción en '.$label.'. Si deseas más líneas para ese plan, aumenta la cantidad en una sola fila.';

                continue;
            }

            $selectedPromotions[$normalizedPromotionName] = true;
        }

        return $errors;
    }
}
