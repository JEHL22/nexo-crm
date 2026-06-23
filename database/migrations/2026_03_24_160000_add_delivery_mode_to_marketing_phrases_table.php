<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_phrases', function (Blueprint $table) {
            $table->string('delivery_mode', 40)->default('immediate')->after('phrase');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_phrases', function (Blueprint $table) {
            $table->dropColumn('delivery_mode');
        });
    }
};
