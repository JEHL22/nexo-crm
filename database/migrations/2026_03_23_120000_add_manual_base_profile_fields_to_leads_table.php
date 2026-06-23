<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'fiscal_address')) {
                $table->string('fiscal_address')->nullable()->after('dni');
            }

            if (!Schema::hasColumn('leads', 'segment')) {
                $table->string('segment')->nullable()->after('current_line_count');
            }

            if (!Schema::hasColumn('leads', 'max_speed')) {
                $table->string('max_speed')->nullable()->after('segment');
            }

            if (!Schema::hasColumn('leads', 'package')) {
                $table->string('package')->nullable()->after('max_speed');
            }

            if (!Schema::hasColumn('leads', 'technology')) {
                $table->string('technology')->nullable()->after('package');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $columns = ['fiscal_address', 'segment', 'max_speed', 'package', 'technology'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('leads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
