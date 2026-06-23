<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reminder_notifications', function (Blueprint $table) {
            $table->string('reminder_stage', 20)->default('t_minus_5')->after('message');
            $table->dropUnique('reminder_notifications_unique');
            $table->unique(['user_id', 'interaction_id', 'scheduled_for', 'reminder_stage'], 'reminder_notifications_unique');
        });
    }

    public function down(): void
    {
        Schema::table('reminder_notifications', function (Blueprint $table) {
            $table->dropUnique('reminder_notifications_unique');
            $table->dropColumn('reminder_stage');
            $table->unique(['user_id', 'interaction_id', 'scheduled_for'], 'reminder_notifications_unique');
        });
    }
};
