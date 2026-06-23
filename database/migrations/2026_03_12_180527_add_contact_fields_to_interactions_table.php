<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interactions', function (Blueprint $table) {
            $table->string('contact_name')->nullable()->after('call_detail');
            $table->string('contact_phone', 30)->nullable()->after('contact_name');
        });
    }

    public function down(): void
    {
        Schema::table('interactions', function (Blueprint $table) {
            $table->dropColumn(['contact_name', 'contact_phone']);
        });
    }
};