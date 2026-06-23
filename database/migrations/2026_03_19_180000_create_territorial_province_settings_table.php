<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('territorial_province_settings', function (Blueprint $table) {
            $table->id();
            $table->string('province_id', 4)->unique();
            $table->string('province_name', 160);
            $table->string('status')->default('habilitado');
            $table->string('availability')->default('disponible');
            $table->string('closing_time', 5)->default('18:00');
            $table->string('visit_time', 5)->default('09:00');
            $table->string('delivery_time', 5)->default('15:00');
            $table->json('selected_district_ids')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'province_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('territorial_province_settings');
    }
};
