<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 120)->nullable();
            $table->text('prompt');
            $table->string('response_type', 40);
            $table->json('options_json')->nullable();
            $table->string('detail_placeholder', 180)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hr_survey_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hr_survey_id')->constrained('hr_surveys')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('displayed_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->string('selected_option', 120)->nullable();
            $table->text('answer_detail')->nullable();
            $table->timestamps();

            $table->unique(['hr_survey_id', 'user_id']);
            $table->index(['user_id', 'answered_at']);
        });

        Schema::create('marketing_phrases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 120)->nullable();
            $table->string('phrase', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_phrases');
        Schema::dropIfExists('hr_survey_recipients');
        Schema::dropIfExists('hr_surveys');
    }
};
