<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('interaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('lead_label', 255);
            $table->string('title', 120);
            $table->text('message');
            $table->timestamp('scheduled_for');
            $table->timestamp('notified_at');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'interaction_id', 'scheduled_for'], 'reminder_notifications_unique');
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_notifications');
    }
};
