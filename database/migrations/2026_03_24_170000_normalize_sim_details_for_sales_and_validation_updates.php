<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sale_sim_details')) {
            Schema::create('sale_sim_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('line_number')->default(1);
                $table->string('serial_number', 100);
                $table->string('sim_number', 50);
                $table->timestamps();

                $table->unique(['sale_id', 'line_number'], 'sale_sim_line_unique');
                $table->index(['sale_id', 'sim_number'], 'sale_sim_number_idx');
            });
        }

        if (!Schema::hasTable('validation_update_sim_details')) {
            Schema::create('validation_update_sim_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('validation_update_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('line_number')->default(1);
                $table->string('serial_number', 100);
                $table->string('sim_number', 50);
                $table->timestamps();

                $table->unique(['validation_update_id', 'line_number'], 'validation_sim_line_unique');
            });
        }

        if (Schema::hasColumns('sales', ['sim_serial_number', 'sim_number'])) {
            $sales = DB::table('sales')
                ->select('id', 'sim_serial_number', 'sim_number', 'created_at', 'updated_at')
                ->whereNotNull('sim_serial_number')
                ->whereNotNull('sim_number')
                ->get();

            foreach ($sales as $sale) {
                $serialNumber = trim((string) $sale->sim_serial_number);
                $simNumber = trim((string) $sale->sim_number);

                if ($serialNumber === '' || $simNumber === '') {
                    continue;
                }

                $exists = DB::table('sale_sim_details')
                    ->where('sale_id', $sale->id)
                    ->where('line_number', 1)
                    ->exists();

                if (!$exists) {
                    DB::table('sale_sim_details')->insert([
                        'sale_id' => $sale->id,
                        'line_number' => 1,
                        'serial_number' => $serialNumber,
                        'sim_number' => $simNumber,
                        'created_at' => $sale->created_at,
                        'updated_at' => $sale->updated_at,
                    ]);
                }
            }

            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn(['sim_serial_number', 'sim_number']);
            });
        }

        if (Schema::hasColumns('validation_updates', ['sim_serial_number', 'sim_number'])) {
            $updates = DB::table('validation_updates')
                ->select('id', 'sim_serial_number', 'sim_number', 'created_at', 'updated_at')
                ->whereNotNull('sim_serial_number')
                ->whereNotNull('sim_number')
                ->get();

            foreach ($updates as $update) {
                $serialNumber = trim((string) $update->sim_serial_number);
                $simNumber = trim((string) $update->sim_number);

                if ($serialNumber === '' || $simNumber === '') {
                    continue;
                }

                $exists = DB::table('validation_update_sim_details')
                    ->where('validation_update_id', $update->id)
                    ->where('line_number', 1)
                    ->exists();

                if (!$exists) {
                    DB::table('validation_update_sim_details')->insert([
                        'validation_update_id' => $update->id,
                        'line_number' => 1,
                        'serial_number' => $serialNumber,
                        'sim_number' => $simNumber,
                        'created_at' => $update->created_at,
                        'updated_at' => $update->updated_at,
                    ]);
                }
            }

            Schema::table('validation_updates', function (Blueprint $table) {
                $table->dropColumn(['sim_serial_number', 'sim_number']);
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumns('sales', ['sim_serial_number', 'sim_number'])) {
            Schema::table('sales', function (Blueprint $table) {
                $table->string('sim_serial_number', 100)->nullable()->after('delivery_type');
                $table->string('sim_number', 50)->nullable()->after('sim_serial_number');
            });
        }

        if (!Schema::hasColumns('validation_updates', ['sim_serial_number', 'sim_number'])) {
            Schema::table('validation_updates', function (Blueprint $table) {
                $table->string('sim_serial_number', 100)->nullable()->after('feedback');
                $table->string('sim_number', 50)->nullable()->after('sim_serial_number');
            });
        }

        $firstSaleSimBySale = DB::table('sale_sim_details')
            ->select('sale_id', 'serial_number', 'sim_number')
            ->orderBy('line_number')
            ->get()
            ->unique('sale_id');

        foreach ($firstSaleSimBySale as $detail) {
            DB::table('sales')
                ->where('id', $detail->sale_id)
                ->update([
                    'sim_serial_number' => $detail->serial_number,
                    'sim_number' => $detail->sim_number,
                ]);
        }

        $firstValidationSimByUpdate = DB::table('validation_update_sim_details')
            ->select('validation_update_id', 'serial_number', 'sim_number')
            ->orderBy('line_number')
            ->get()
            ->unique('validation_update_id');

        foreach ($firstValidationSimByUpdate as $detail) {
            DB::table('validation_updates')
                ->where('id', $detail->validation_update_id)
                ->update([
                    'sim_serial_number' => $detail->serial_number,
                    'sim_number' => $detail->sim_number,
                ]);
        }

        Schema::dropIfExists('validation_update_sim_details');
        Schema::dropIfExists('sale_sim_details');
    }
};
