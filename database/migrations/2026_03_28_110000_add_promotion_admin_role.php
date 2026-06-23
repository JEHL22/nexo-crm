<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->insertOrIgnore([
            ['name' => 'administrador de promociones', 'guard_name' => 'web'],
        ]);
    }

    public function down(): void
    {
        DB::table('roles')
            ->where('guard_name', 'web')
            ->where('name', 'administrador de promociones')
            ->delete();
    }
};
