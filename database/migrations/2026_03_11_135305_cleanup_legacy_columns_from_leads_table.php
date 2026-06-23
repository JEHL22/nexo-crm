<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('leads')) {
            if (Schema::hasColumn('leads', 'razon_social') && Schema::hasColumn('leads', 'business_name')) {
                DB::statement("
                    UPDATE leads
                    SET business_name = COALESCE(NULLIF(business_name, ''), razon_social)
                ");
            }

            if (Schema::hasColumn('leads', 'representante_nombre') && Schema::hasColumn('leads', 'representative_name')) {
                DB::statement("
                    UPDATE leads
                    SET representative_name = COALESCE(NULLIF(representative_name, ''), representante_nombre)
                ");
            }

            if (Schema::hasColumn('leads', 'dni_representante') && Schema::hasColumn('leads', 'dni')) {
                DB::statement("
                    UPDATE leads
                    SET dni = COALESCE(NULLIF(dni, ''), dni_representante)
                ");
            }

            if (Schema::hasColumn('leads', 'operator_current') && Schema::hasColumn('leads', 'current_operator')) {
                DB::statement("
                    UPDATE leads
                    SET current_operator = COALESCE(NULLIF(current_operator, ''), operator_current)
                ");
            }

            if (Schema::hasColumn('leads', 'lines_count') && Schema::hasColumn('leads', 'current_line_count')) {
                DB::statement("
                    UPDATE leads
                    SET current_line_count = COALESCE(current_line_count, lines_count)
                ");
            }
        }

        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'owner_user_id')) {
                foreach ([
                    'leads_campaign_id_owner_user_id_status_index',
                    'leads_campaign_id_owner_user_id_closed_at_index',
                ] as $indexName) {
                    if (Schema::hasIndex('leads', $indexName)) {
                        $table->dropIndex($indexName);
                    }
                }

                $table->dropForeign(['owner_user_id']);
                $table->dropColumn('owner_user_id');
            }

            $columnsToDrop = [
                'first_name',
                'last_name',
                'razon_social',
                'address',
                'dni_representante',
                'representante_nombre',
                'operator_current',
                'lines_count',
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('leads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'owner_user_id')) {
                $table->unsignedBigInteger('owner_user_id')->nullable();
            }
            if (!Schema::hasColumn('leads', 'first_name')) {
                $table->string('first_name')->nullable();
            }
            if (!Schema::hasColumn('leads', 'last_name')) {
                $table->string('last_name')->nullable();
            }
            if (!Schema::hasColumn('leads', 'razon_social')) {
                $table->string('razon_social')->nullable();
            }
            if (!Schema::hasColumn('leads', 'address')) {
                $table->string('address')->nullable();
            }
            if (!Schema::hasColumn('leads', 'dni_representante')) {
                $table->string('dni_representante')->nullable();
            }
            if (!Schema::hasColumn('leads', 'representante_nombre')) {
                $table->string('representante_nombre')->nullable();
            }
            if (!Schema::hasColumn('leads', 'operator_current')) {
                $table->string('operator_current')->nullable();
            }
            if (!Schema::hasColumn('leads', 'lines_count')) {
                $table->unsignedSmallInteger('lines_count')->nullable();
            }
        });
    }
};
