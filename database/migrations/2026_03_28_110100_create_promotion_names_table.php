<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_names', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 160)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['sort_order', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_names');
    }
};
