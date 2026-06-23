<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lead_sisac_data')) {
            return;
        }

        Schema::create('lead_sisac_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('semaforo', 20)->nullable();
            $table->string('resultado')->nullable();
            $table->unsignedInteger('cantidad_lineas_ofrecer')->nullable();
            $table->decimal('deposito_garantia', 10, 2)->nullable();
            $table->string('rango_lc_disponible')->nullable();
            $table->timestamps();

            $table->unique('lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_sisac_data');
    }
};
