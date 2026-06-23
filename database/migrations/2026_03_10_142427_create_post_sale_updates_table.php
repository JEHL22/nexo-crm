<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_sale_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('management_status')->default('pendiente_validacion');
            $table->text('feedback')->nullable();

            $table->timestamps();

            $table->index(['sale_id', 'management_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_sale_updates');
    }
};