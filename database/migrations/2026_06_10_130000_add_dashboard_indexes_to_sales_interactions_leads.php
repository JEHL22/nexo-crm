<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índices para las queries de dashboards (Gerencia, Supervisor, Ejecutivo):
     * - sales: conteo de acuerdos por supervisor en rangos de accepted_at
     * - interactions: velocímetros diarios/semanales por user_id + created_at
     * - leads: vistas de equipo del supervisor filtradas por estado
     * Idempotente: si el índice ya existe (ej. producción), se omite.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasIndex('sales', 'sales_supervisor_user_id_accepted_at_index')) {
                $table->index(['supervisor_user_id', 'accepted_at'], 'sales_supervisor_user_id_accepted_at_index');
            }
        });

        Schema::table('interactions', function (Blueprint $table) {
            if (! Schema::hasIndex('interactions', 'interactions_user_id_created_at_index')) {
                $table->index(['user_id', 'created_at'], 'interactions_user_id_created_at_index');
            }
        });

        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasIndex('leads', 'leads_supervisor_user_id_status_final_index')) {
                $table->index(['supervisor_user_id', 'status_final'], 'leads_supervisor_user_id_status_final_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasIndex('sales', 'sales_supervisor_user_id_accepted_at_index')) {
                $table->dropIndex('sales_supervisor_user_id_accepted_at_index');
            }
        });

        Schema::table('interactions', function (Blueprint $table) {
            if (Schema::hasIndex('interactions', 'interactions_user_id_created_at_index')) {
                $table->dropIndex('interactions_user_id_created_at_index');
            }
        });

        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasIndex('leads', 'leads_supervisor_user_id_status_final_index')) {
                $table->dropIndex('leads_supervisor_user_id_status_final_index');
            }
        });
    }
};
