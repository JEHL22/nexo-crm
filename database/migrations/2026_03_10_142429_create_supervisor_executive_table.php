<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisor_executive', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('executive_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['supervisor_user_id', 'executive_user_id', 'campaign_id'], 'sup_exec_campaign_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_executive');
    }
};