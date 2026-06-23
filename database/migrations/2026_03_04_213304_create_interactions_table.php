<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
      Schema::create('interactions', function (Blueprint $table) {
        $table->id();

        $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();

        $table->string('status');     // interesado, negociando, proyeccion_cierre, no_contesto, caida, venta
        $table->text('notes')->nullable();

        $table->timestamps();

        $table->index(['campaign_id', 'user_id', 'status']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};
