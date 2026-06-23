<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('admin.bootstrap_admins', []) as $bootstrapAdmin) {
            $admin = User::updateOrCreate(
                ['email' => $bootstrapAdmin['email']],
                [
                    'name' => $bootstrapAdmin['name'],
                    'password' => Hash::make($bootstrapAdmin['password']),
                ]
            );

            $admin->syncRoles(['Administrador']);
        }

        if (! config('admin.seed_demo_users', false)) {
            return;
        }

        $gerencia = User::updateOrCreate(
            ['email' => 'gerencia@nexo.local'],
            [
                'name' => 'Gerencia Nexo',
                'password' => Hash::make('Nexo'),
            ]
        );
        $gerencia->syncRoles(['Gerencia']);

        $supervisor = User::updateOrCreate(
            ['email' => 'supervisor@nexo.local'],
            [
                'name' => 'Supervisor Nexo',
                'password' => Hash::make('Nexo'),
            ]
        );
        $supervisor->syncRoles(['Supervisor']);

        $ejecutivo = User::updateOrCreate(
            ['email' => 'ejecutivo@nexo.local'],
            [
                'name' => 'Ejecutivo Nexo',
                'password' => Hash::make('Nexo'),
            ]
        );
        $ejecutivo->syncRoles(['Ejecutivo']);

        $postventa = User::updateOrCreate(
            ['email' => 'postventa@nexo.local'],
            [
                'name' => 'Post Venta Nexo',
                'password' => Hash::make('Nexo'),
            ]
        );
        $postventa->syncRoles(['Postventa']);

        $mesa = User::updateOrCreate(
            ['email' => 'mesa@nexo.local'],
            [
                'name' => 'Mesa de Control',
                'password' => Hash::make('Nexo'),
            ]
        );
        $mesa->syncRoles(['Mesa de Control']);
    }
}
