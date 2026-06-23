<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('executive_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('supervisor_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status')->default('acuerdo_aceptado');
            $table->string('management_status')->default('pendiente_validacion');
            $table->string('sisac_status')->default('pendiente_validacion');

            $table->string('product_type')->nullable();
            $table->unsignedInteger('offered_line_count')->nullable();
            $table->decimal('monthly_payment', 10, 2)->nullable();

            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->unique('lead_id');
            $table->index(['executive_user_id', 'status']);
            $table->index(['supervisor_user_id', 'status']);
            $table->index(['management_status', 'sisac_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};