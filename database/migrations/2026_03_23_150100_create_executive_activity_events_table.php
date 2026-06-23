<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('executive_activity_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('executive_activity_sessions')->cascadeOnDelete();
            $table->foreignId('executive_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supervisor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 60);
            $table->string('module_name', 60)->nullable();
            $table->string('route_name', 120)->nullable();
            $table->string('page_url')->nullable();
            $table->string('label', 160)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['session_id', 'occurred_at']);
            $table->index(['executive_user_id', 'occurred_at']);
            $table->index(['supervisor_user_id', 'occurred_at']);
            $table->index(['event_type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('executive_activity_events');
    }
};
