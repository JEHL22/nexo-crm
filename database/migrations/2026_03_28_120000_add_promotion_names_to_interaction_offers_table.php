<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interaction_offers', function (Blueprint $table) {
            if (!Schema::hasColumn('interaction_offers', 'portability_promotion_name')) {
                $table->string('portability_promotion_name', 160)->nullable()->after('portability_monthly');
            }

            if (!Schema::hasColumn('interaction_offers', 'new_promotion_name')) {
                $table->string('new_promotion_name', 160)->nullable()->after('new_monthly');
            }
        });
    }

    public function down(): void
    {
        Schema::table('interaction_offers', function (Blueprint $table) {
            if (Schema::hasColumn('interaction_offers', 'portability_promotion_name')) {
                $table->dropColumn('portability_promotion_name');
            }

            if (Schema::hasColumn('interaction_offers', 'new_promotion_name')) {
                $table->dropColumn('new_promotion_name');
            }
        });
    }
};
