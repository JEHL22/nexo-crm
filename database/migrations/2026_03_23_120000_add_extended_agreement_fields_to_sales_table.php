<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('plan_code')->nullable()->after('customer_coordinates');
            $table->date('attention_date')->nullable()->after('attention_time_slot');
            $table->string('operator_name', 30)->nullable()->after('attention_date');
            $table->json('attachment_paths')->nullable()->after('products_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'plan_code',
                'attention_date',
                'operator_name',
                'attachment_paths',
            ]);
        });
    }
};
