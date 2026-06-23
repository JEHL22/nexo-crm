<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisor_status_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('validation_update_id')->nullable()->constrained()->nullOnDelete();
            $table->string('previous_status', 50)->nullable();
            $table->string('current_status', 50);
            $table->string('title', 120);
            $table->text('message');
            $table->timestamp('notified_at');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['sale_id', 'notified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_status_notifications');
    }
};
