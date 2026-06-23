<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'assigned_to_user_id')) {
                $table->foreignId('assigned_to_user_id')->nullable()->after('campaign_id')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('leads', 'supervisor_user_id')) {
                $table->foreignId('supervisor_user_id')->nullable()->after('assigned_to_user_id')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('leads', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()->after('supervisor_user_id')->constrained('users')->nullOnDelete();
            }

            if (!Schema::hasColumn('leads', 'source')) {
                $table->string('source')->default('importado')->after('created_by_user_id');
            }

            if (!Schema::hasColumn('leads', 'delivery_status')) {
                $table->string('delivery_status')->default('disponible')->after('source');
            }

            if (!Schema::hasColumn('leads', 'taken_at')) {
                $table->timestamp('taken_at')->nullable()->after('delivery_status');
            }

            if (!Schema::hasColumn('leads', 'business_name')) {
                $table->string('business_name')->nullable()->after('ruc');
            }

            if (!Schema::hasColumn('leads', 'representative_name')) {
                $table->string('representative_name')->nullable()->after('business_name');
            }

            if (!Schema::hasColumn('leads', 'dni')) {
                $table->string('dni', 20)->nullable()->after('representative_name');
            }

            if (!Schema::hasColumn('leads', 'current_operator')) {
                $table->string('current_operator')->nullable()->after('dni');
            }

            if (!Schema::hasColumn('leads', 'current_line_count')) {
                $table->unsignedInteger('current_line_count')->nullable()->after('current_operator');
            }

            if (!Schema::hasColumn('leads', 'status_general')) {
                $table->string('status_general')->default('sin_contacto')->after('current_line_count');
            }

            if (!Schema::hasColumn('leads', 'status_specific')) {
                $table->string('status_specific')->default('sin_gestion')->after('status_general');
            }

            if (!Schema::hasColumn('leads', 'status_final')) {
                $table->string('status_final')->default('sin_gestion')->after('status_specific');
            }

            if (!Schema::hasColumn('leads', 'product_type_offered')) {
                $table->string('product_type_offered')->nullable()->after('status_final');
            }

            if (!Schema::hasColumn('leads', 'offered_line_count')) {
                $table->unsignedInteger('offered_line_count')->nullable()->after('product_type_offered');
            }

            if (!Schema::hasColumn('leads', 'monthly_payment')) {
                $table->decimal('monthly_payment', 10, 2)->nullable()->after('offered_line_count');
            }

            if (!Schema::hasColumn('leads', 'call_summary')) {
                $table->text('call_summary')->nullable()->after('monthly_payment');
            }

            if (!Schema::hasColumn('leads', 'next_contact_at')) {
                $table->timestamp('next_contact_at')->nullable()->after('call_summary');
            }

            if (!Schema::hasColumn('leads', 'agreed_at')) {
                $table->timestamp('agreed_at')->nullable()->after('next_contact_at');
            }

            $table->index(['campaign_id', 'delivery_status']);
            $table->index(['assigned_to_user_id', 'status_final']);
            $table->index(['status_general', 'status_specific']);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $columns = [
                'assigned_to_user_id',
                'supervisor_user_id',
                'created_by_user_id',
                'source',
                'delivery_status',
                'taken_at',
                'business_name',
                'representative_name',
                'dni',
                'current_operator',
                'current_line_count',
                'status_general',
                'status_specific',
                'status_final',
                'product_type_offered',
                'offered_line_count',
                'monthly_payment',
                'call_summary',
                'next_contact_at',
                'agreed_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('leads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};