<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activation_control_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('empresa')->nullable();
            $table->string('mes', 7)->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_activacion')->nullable();
            $table->string('sec', 100)->nullable();
            $table->string('py', 100)->nullable();
            $table->string('sot', 100)->nullable();
            $table->string('linea', 100)->nullable();
            $table->string('large', 100)->nullable();
            $table->string('cliente')->nullable();
            $table->string('ruc', 20)->nullable();
            $table->string('servicio', 150)->nullable();
            $table->string('tipo_cliente', 150)->nullable();
            $table->string('plan_tarifario')->nullable();
            $table->decimal('porcentaje_dscto', 8, 2)->nullable();
            $table->string('ajuste', 150)->nullable();
            $table->decimal('cf', 12, 2)->nullable();
            $table->decimal('adic', 12, 2)->nullable();
            $table->decimal('sva', 12, 2)->nullable();
            $table->decimal('cf_sin_igv', 12, 2)->nullable();
            $table->unsignedInteger('q')->nullable();
            $table->string('material', 150)->nullable();
            $table->string('marca', 150)->nullable();
            $table->string('consultor', 150)->nullable();
            $table->string('modalidad', 150)->nullable();
            $table->string('estado', 150)->nullable();
            $table->text('comentario')->nullable();
            $table->unsignedInteger('score')->nullable();
            $table->string('segmento', 150)->nullable();
            $table->string('opotunidad', 150)->nullable();
            $table->string('estado_sf', 150)->nullable();
            $table->date('f_cierre_op')->nullable();
            $table->date('f_liberacion')->nullable();
            $table->string('validacion', 150)->nullable();
            $table->timestamps();

            $table->index('mes');
            $table->index('ruc');
            $table->index('fecha_activacion');
            $table->index('created_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activation_control_records');
    }
};
