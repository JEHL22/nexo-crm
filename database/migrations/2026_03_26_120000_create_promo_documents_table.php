<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('badge', 60)->nullable();
            $table->string('title', 160);
            $table->string('pdf_path', 255);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_documents');
    }
};
