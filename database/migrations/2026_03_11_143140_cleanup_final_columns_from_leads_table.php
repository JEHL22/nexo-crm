<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasIndex('leads', 'leads_campaign_id_status_index')) {
                $table->dropIndex('leads_campaign_id_status_index');
            }

            // Primero eliminar foreign key si existe
            if (Schema::hasColumn('leads', 'reactivated_by_user_id')) {
                $table->dropForeign(['reactivated_by_user_id']);
            }

            $columnsToDrop = [
                'product_type_offered',
                'offered_line_count',
                'monthly_payment',
                'next_contact_at',
                'agreed_at',
                'status',
                'general_status',
                'specific_status',
                'last_contact_at',
                'closed_at',
                'reactivated_at',
                'reactivated_by_user_id',
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('leads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'product_type_offered')) {
                $table->string('product_type_offered')->nullable();
            }
            if (!Schema::hasColumn('leads', 'offered_line_count')) {
                $table->unsignedInteger('offered_line_count')->nullable();
            }
            if (!Schema::hasColumn('leads', 'monthly_payment')) {
                $table->decimal('monthly_payment', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('leads', 'next_contact_at')) {
                $table->timestamp('next_contact_at')->nullable();
            }
            if (!Schema::hasColumn('leads', 'agreed_at')) {
                $table->timestamp('agreed_at')->nullable();
            }
            if (!Schema::hasColumn('leads', 'status')) {
                $table->string('status')->nullable();
            }
            if (!Schema::hasColumn('leads', 'general_status')) {
                $table->string('general_status')->nullable();
            }
            if (!Schema::hasColumn('leads', 'specific_status')) {
                $table->string('specific_status')->nullable();
            }
            if (!Schema::hasColumn('leads', 'last_contact_at')) {
                $table->timestamp('last_contact_at')->nullable();
            }
            if (!Schema::hasColumn('leads', 'closed_at')) {
                $table->timestamp('closed_at')->nullable();
            }
            if (!Schema::hasColumn('leads', 'reactivated_at')) {
                $table->timestamp('reactivated_at')->nullable();
            }
            if (!Schema::hasColumn('leads', 'reactivated_by_user_id')) {
                $table->unsignedBigInteger('reactivated_by_user_id')->nullable();
            }
        });
    }
};
