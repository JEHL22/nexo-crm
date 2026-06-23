<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_work_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('executive_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('supervisor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('module_name', 30);
            $table->string('route_name', 120)->nullable();
            $table->timestamp('started_at');
            $table->timestamp('last_heartbeat_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['executive_user_id', 'started_at']);
            $table->index(['supervisor_user_id', 'started_at']);
            $table->index(['lead_id', 'started_at']);
            $table->index(['module_name', 'started_at']);
            $table->index(['ended_at', 'last_heartbeat_at']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedInteger('tmo_to_agreement_seconds')->nullable()->after('delivery_type');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('tmo_to_agreement_seconds');
        });

        Schema::dropIfExists('lead_work_sessions');
    }
};
