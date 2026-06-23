<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumns('sales', ['sim_serial_number', 'sim_number'])) {
            $sales = DB::table('sales')
                ->select('id', 'sim_serial_number', 'sim_number')
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
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn(['sim_serial_number', 'sim_number']);
            });
        }

        if (Schema::hasColumns('validation_updates', ['sim_serial_number', 'sim_number'])) {
            $updates = DB::table('validation_updates')
                ->select('id', 'sim_serial_number', 'sim_number')
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
                        'created_at' => now(),
                        'updated_at' => now(),
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
    }
};
