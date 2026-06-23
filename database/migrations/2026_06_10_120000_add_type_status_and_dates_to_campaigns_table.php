<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El modelo Campaign declara type, status, starts_at y ends_at en fillable
     * pero ninguna migración creó esas columnas. Idempotente: si el entorno
     * (ej. producción) ya las tiene, se omiten.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            if (! Schema::hasColumn('campaigns', 'type')) {
                $table->string('type')->nullable()->after('name');
            }

            if (! Schema::hasColumn('campaigns', 'status')) {
                $table->string('status')->nullable()->after('type');
            }

            if (! Schema::hasColumn('campaigns', 'starts_at')) {
                $table->timestamp('starts_at')->nullable()->after('active');
            }

            if (! Schema::hasColumn('campaigns', 'ends_at')) {
                $table->timestamp('ends_at')->nullable()->after('starts_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            foreach (['ends_at', 'starts_at', 'status', 'type'] as $column) {
                if (Schema::hasColumn('campaigns', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
