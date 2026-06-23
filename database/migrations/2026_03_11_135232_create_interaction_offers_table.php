<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interaction_offers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('interaction_id')->constrained()->cascadeOnDelete();

            $table->string('product_type'); 
            // movil / fijo

            $table->string('mobile_mode')->nullable(); 
            // portabilidad / alta_nueva / porta_alta_nueva

            $table->unsignedInteger('portability_lines')->nullable();
            $table->decimal('portability_monthly', 10, 2)->nullable();

            $table->unsignedInteger('new_lines')->nullable();
            $table->decimal('new_monthly', 10, 2)->nullable();

            $table->string('internet_speed')->nullable();
            $table->decimal('fixed_monthly', 10, 2)->nullable();

            $table->timestamps();

            $table->index(['interaction_id', 'product_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interaction_offers');
    }
};