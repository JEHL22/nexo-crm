<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('territorial_province_setting_histories')) {
            return;
        }

        Schema::create('territorial_province_setting_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('territorial_province_setting_id')->nullable();
            $table->string('province_id', 4);
            $table->string('province_name', 160);
            $table->foreignId('user_id')->nullable();
            $table->string('action', 30)->default('updated');
            $table->json('changed_fields')->nullable();
            $table->timestamps();

            $table->foreign('territorial_province_setting_id', 'tps_hist_setting_fk')
                ->references('id')
                ->on('territorial_province_settings')
                ->nullOnDelete();

            $table->foreign('user_id', 'tps_hist_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['province_id', 'created_at'], 'tps_hist_province_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('territorial_province_setting_histories');
    }
};
