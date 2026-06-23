<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('executive_activity_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('executive_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supervisor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->timestamp('login_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('logout_at')->nullable();
            $table->string('current_module_name', 60)->nullable();
            $table->string('current_route_name', 120)->nullable();
            $table->string('current_page_url')->nullable();
            $table->timestamp('current_page_entered_at')->nullable();
            $table->boolean('is_crm_focused')->default(true);
            $table->timestamp('last_focus_change_at')->nullable();
            $table->unsignedInteger('total_blurred_seconds')->default(0);
            $table->timestamps();

            $table->index(['executive_user_id', 'logout_at']);
            $table->index(['supervisor_user_id', 'logout_at']);
            $table->index(['login_at', 'logout_at']);
            $table->index(['current_module_name', 'logout_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('executive_activity_sessions');
    }
};
