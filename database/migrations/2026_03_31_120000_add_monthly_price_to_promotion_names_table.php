<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotion_names', function (Blueprint $table) {
            if (!Schema::hasColumn('promotion_names', 'monthly_price')) {
                $table->decimal('monthly_price', 10, 2)->default(0)->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('promotion_names', function (Blueprint $table) {
            if (Schema::hasColumn('promotion_names', 'monthly_price')) {
                $table->dropColumn('monthly_price');
            }
        });
    }
};
