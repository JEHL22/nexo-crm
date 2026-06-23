<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interactions', function (Blueprint $table) {
            if (!Schema::hasColumn('interactions', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('lead_id')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('interactions', 'interaction_type')) {
                $table->string('interaction_type')->default('llamada')->after('user_id');
            }

            if (!Schema::hasColumn('interactions', 'status_general')) {
                $table->string('status_general')->nullable()->after('interaction_type');
            }

            if (!Schema::hasColumn('interactions', 'status_specific')) {
                $table->string('status_specific')->nullable()->after('status_general');
            }

            if (!Schema::hasColumn('interactions', 'product_type_offered')) {
                $table->string('product_type_offered')->nullable()->after('status_specific');
            }

            if (!Schema::hasColumn('interactions', 'offered_line_count')) {
                $table->unsignedInteger('offered_line_count')->nullable()->after('product_type_offered');
            }

            if (!Schema::hasColumn('interactions', 'monthly_payment')) {
                $table->decimal('monthly_payment', 10, 2)->nullable()->after('offered_line_count');
            }

            if (!Schema::hasColumn('interactions', 'call_detail')) {
                $table->text('call_detail')->nullable()->after('monthly_payment');
            }

            if (!Schema::hasColumn('interactions', 'next_contact_at')) {
                $table->timestamp('next_contact_at')->nullable()->after('call_detail');
            }

            if (!Schema::hasColumn('interactions', 'is_agreement')) {
                $table->boolean('is_agreement')->default(false)->after('next_contact_at');
            }

            if (!Schema::hasColumn('interactions', 'agreed_at')) {
                $table->timestamp('agreed_at')->nullable()->after('is_agreement');
            }

            $table->index(['lead_id', 'created_at']);
            $table->index(['user_id', 'interaction_type']);
        });
    }

    public function down(): void
    {
        Schema::table('interactions', function (Blueprint $table) {
            $columns = [
                'user_id',
                'interaction_type',
                'status_general',
                'status_specific',
                'product_type_offered',
                'offered_line_count',
                'monthly_payment',
                'call_detail',
                'next_contact_at',
                'is_agreement',
                'agreed_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('interactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};