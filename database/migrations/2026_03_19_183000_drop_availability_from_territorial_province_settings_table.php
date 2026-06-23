<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('territorial_province_settings', 'availability')) {
            return;
        }

        Schema::table('territorial_province_settings', function (Blueprint $table) {
            $table->dropColumn('availability');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('territorial_province_settings', 'availability')) {
            return;
        }

        Schema::table('territorial_province_settings', function (Blueprint $table) {
            $table->string('availability')->default('disponible')->after('status');
        });
    }
};
