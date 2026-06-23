<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('customer_ruc', 20)->nullable()->after('monthly_payment');
            $table->string('customer_business_name')->nullable()->after('customer_ruc');
            $table->string('customer_dni', 20)->nullable()->after('customer_business_name');
            $table->string('customer_representative_name')->nullable()->after('customer_dni');
            $table->string('customer_phone', 30)->nullable()->after('customer_representative_name');
            $table->string('customer_address')->nullable()->after('customer_phone');
            $table->string('customer_coordinates')->nullable()->after('customer_address');
            $table->string('customer_email')->nullable()->after('customer_coordinates');
            $table->string('service_channel')->nullable()->after('customer_email');
            $table->string('attention_time_slot')->nullable()->after('service_channel');
            $table->string('delivery_type')->nullable()->after('attention_time_slot');
            $table->json('products_snapshot')->nullable()->after('delivery_type');
            $table->string('supervisor_validation_status')->default('pendiente')->after('products_snapshot');
            $table->timestamp('supervisor_validated_at')->nullable()->after('supervisor_validation_status');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'customer_ruc',
                'customer_business_name',
                'customer_dni',
                'customer_representative_name',
                'customer_phone',
                'customer_address',
                'customer_coordinates',
                'customer_email',
                'service_channel',
                'attention_time_slot',
                'delivery_type',
                'products_snapshot',
                'supervisor_validation_status',
                'supervisor_validated_at',
            ]);
        });
    }
};
