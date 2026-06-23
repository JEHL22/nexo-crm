<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('profile_photo_path')->nullable()->after('remember_token');
            $table->string('crm_primary_color', 7)->nullable()->after('profile_photo_path');
            $table->string('crm_secondary_color', 7)->nullable()->after('crm_primary_color');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profile_photo_path',
                'crm_primary_color',
                'crm_secondary_color',
            ]);
        });
    }
};
