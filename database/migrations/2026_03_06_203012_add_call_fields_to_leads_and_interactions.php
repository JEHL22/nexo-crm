<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // -------------------------
        // LEADS
        // -------------------------
        Schema::table('leads', function (Blueprint $table) {
            // Estados de gestión
            $table->string('general_status')->nullable()->after('status');   // contactado | no_contactado
            $table->string('specific_status')->nullable()->after('general_status'); // depende del general_status

            // Conteo para desactivar lead (solo para: no_contesta / telefono_apagado)
            $table->unsignedTinyInteger('no_contact_attempts')->default(0)->after('specific_status');

            // Control de disponibilidad
            $table->timestamp('released_at')->nullable()->after('no_contact_attempts'); // vuelve a cola después de 1h
            $table->timestamp('disabled_at')->nullable()->after('released_at');
            $table->string('disabled_reason')->nullable()->after('disabled_at'); // no_contact_2, etc.

            // Datos adicionales del lead para mostrar en "Nuevo Cliente"
            $table->string('dni_representante')->nullable()->after('address');
            $table->string('representante_nombre')->nullable()->after('dni_representante');
            $table->string('operator_current')->nullable()->after('representante_nombre');

            // Si quieres almacenar aquí también:
            $table->unsignedSmallInteger('lines_count')->nullable()->after('operator_current');
            $table->decimal('monthly_payment', 10, 2)->nullable()->after('lines_count');

            // Índices para performance en la cola
            $table->index(['campaign_id', 'owner_user_id', 'closed_at']);
            $table->index(['campaign_id', 'released_at', 'disabled_at']);
        });

        // -------------------------
        // INTERACTIONS (historial)
        // -------------------------
        Schema::table('interactions', function (Blueprint $table) {
            $table->string('general_status')->nullable()->after('status');
            $table->string('specific_status')->nullable()->after('general_status');

            // canales seleccionados: movil / fijo / movil_fijo (puede ser JSON)
            $table->json('channels')->nullable()->after('specific_status');

            $table->unsignedSmallInteger('lines_count')->nullable()->after('channels');
            $table->decimal('monthly_payment', 10, 2)->nullable()->after('lines_count');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['campaign_id', 'owner_user_id', 'closed_at']);
            $table->dropIndex(['campaign_id', 'released_at', 'disabled_at']);

            $table->dropColumn([
                'general_status',
                'specific_status',
                'no_contact_attempts',
                'released_at',
                'disabled_at',
                'disabled_reason',
                'dni_representante',
                'representante_nombre',
                'operator_current',
                'lines_count',
                'monthly_payment',
            ]);
        });

        Schema::table('interactions', function (Blueprint $table) {
            $table->dropColumn([
                'general_status',
                'specific_status',
                'channels',
                'lines_count',
                'monthly_payment',
            ]);
        });
    }
};