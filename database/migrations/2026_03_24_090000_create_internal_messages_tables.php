<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 120)->nullable();
            $table->text('message');
            $table->timestamps();
        });

        Schema::create('internal_message_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internal_message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('displayed_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['internal_message_id', 'user_id'], 'internal_message_recipients_unique');
            $table->index(['user_id', 'displayed_at']);
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_message_recipients');
        Schema::dropIfExists('internal_messages');
    }
};
