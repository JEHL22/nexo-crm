<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'Administrador',
            'Gerencia',
            'Supervisor',
            'Ejecutivo',
            'Postventa',
            'Mesa de Control',
            'RRHH',
            'MKT',
            'administrador de promociones',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }
    }
}
