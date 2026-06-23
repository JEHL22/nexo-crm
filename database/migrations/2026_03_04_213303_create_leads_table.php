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
      Schema::create('leads', function (Blueprint $table) {
        $table->id();

        $table->foreignId('campaign_id')->constrained()->cascadeOnUpdate();
        $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();

        // Datos principales
        $table->string('first_name')->nullable();
        $table->string('last_name')->nullable();
        $table->string('full_name')->nullable();

        $table->string('razon_social')->nullable();
        $table->string('ruc')->nullable()->index();
        $table->string('address')->nullable();

        // Estado y control
        $table->string('status')->default('sin_gestion'); // luego lo cambiamos a sin_gestion si quieres
        $table->timestamp('last_contact_at')->nullable();
        $table->timestamp('closed_at')->nullable(); // venta/caida

        // Reactivación (solo sistemas)
        $table->timestamp('reactivated_at')->nullable();
        $table->foreignId('reactivated_by_user_id')->nullable()->constrained('users')->nullOnDelete();

        $table->timestamps();

        $table->index(['campaign_id', 'owner_user_id', 'status']);
        $table->index(['campaign_id', 'status']);

        $table->unique(['campaign_id', 'ruc']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
