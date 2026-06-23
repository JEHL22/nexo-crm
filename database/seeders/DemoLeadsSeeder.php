<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Datos de demostración para la campaña CLARO: un segundo ejecutivo bajo el
 * mismo supervisor y una bolsa de leads disponibles para que "A negociar"
 * tenga clientes que mostrar. Idempotente: no duplica si ya hay 20 disponibles.
 */
class DemoLeadsSeeder extends Seeder
{
    private const TARGET_AVAILABLE = 20;

    private const COMPANIES = [
        'Importaciones Andina S.A.C.', 'Distribuidora El Sol E.I.R.L.', 'Constructora Pacífico S.A.C.',
        'Textiles Lima Norte S.A.C.', 'Farmacia Salud Total S.A.C.', 'Transportes Rápido Seguro S.A.C.',
        'Inversiones Costa Verde S.A.C.', 'Comercial San Martín E.I.R.L.', 'Servicios Gráficos Perú S.A.C.',
        'Agroindustrias del Valle S.A.C.', 'Logística Express S.A.C.', 'Ferretería La Económica S.A.C.',
        'Restaurante Sabor Criollo S.A.C.', 'Clínica Vida Sana S.A.C.', 'Estudio Contable Integral S.A.C.',
        'Boutique Elegancia E.I.R.L.', 'Panadería Trigo de Oro S.A.C.', 'Tecnología y Sistemas S.A.C.',
        'Minera Buena Esperanza S.A.C.', 'Editorial Conocimiento S.A.C.',
    ];

    private const REPS = [
        'Carlos Quispe Mamani', 'María Flores Huamán', 'José Rojas Vega', 'Ana Torres Ríos',
        'Luis Castillo Núñez', 'Rosa Mendoza Apaza', 'Jorge Sánchez Díaz', 'Carmen Vargas León',
        'Pedro Ramírez Soto', 'Lucía Chávez Paredes', 'Miguel Ángel Cruz Salas', 'Patricia Gutiérrez Lazo',
        'Fernando Aguilar Ponce', 'Sofía Herrera Campos', 'Raúl Espinoza Bravo', 'Elena Salazar Cárdenas',
        'Víctor Palomino Reyes', 'Diana Cordero Vela', 'Andrés Figueroa Lima', 'Gabriela Núñez Solís',
    ];

    private const ADDRESSES = [
        'Av. Javier Prado Este 1234, San Isidro', 'Jr. de la Unión 567, Cercado de Lima',
        'Av. Arequipa 2890, Lince', 'Calle Las Begonias 450, San Isidro', 'Av. La Marina 1500, San Miguel',
        'Av. Angamos Este 980, Surquillo', 'Jr. Huánuco 320, La Victoria', 'Av. Brasil 2100, Jesús María',
        'Av. Universitaria 4500, Los Olivos', 'Calle Schell 210, Miraflores', 'Av. Benavides 3200, Surco',
        'Av. Túpac Amaru 890, Comas', 'Jr. Cusco 145, Cercado de Lima', 'Av. Colonial 1780, Callao',
        'Av. El Sol 654, San Juan de Lurigancho', 'Calle Berlín 390, Miraflores', 'Av. Petit Thouars 2050, Lince',
        'Av. Salaverry 3100, Magdalena', 'Jr. Lampa 780, Cercado de Lima', 'Av. Faucett 1200, Callao',
    ];

    private const OPERATORS = ['Movistar', 'Entel', 'Bitel'];

    private const SEGMENTS = ['Pyme', 'Mediana empresa', 'Corporativo'];

    private const TECHS = ['HFC', 'FTTH', '4G LTE'];

    private const SPEEDS = ['100 Mbps', '200 Mbps', '300 Mbps', '500 Mbps'];

    private const PACKAGES = ['Internet + Telefonía fija', 'Solo Internet', 'Móvil corporativo', 'Pack Full Negocios'];

    public function run(): void
    {
        $campaign = Campaign::firstOrCreate(['name' => 'CLARO'], ['active' => true]);
        $supervisor = User::where('email', 'supervisor@nexo.local')->first();
        $executive = User::where('email', 'ejecutivo@nexo.local')->first();

        if (! $supervisor || ! $executive) {
            $this->command?->warn('Faltan los usuarios demo. Corre UserSeeder primero (SEED_DEMO_USERS=true).');

            return;
        }

        $secondExecutive = $this->ensureSecondExecutive();

        $this->linkTeam($campaign, $supervisor, [$executive, $secondExecutive]);
        $supervisor->campaigns()->syncWithoutDetaching([$campaign->id]);

        $this->seedAvailableLeads($campaign, $supervisor);
    }

    private function ensureSecondExecutive(): User
    {
        $user = User::firstOrCreate(
            ['email' => 'ejecutivo2@nexo.local'],
            ['name' => 'Ejecutivo Nexo 2', 'password' => Hash::make('Nexo')]
        );

        $user->syncRoles(['Ejecutivo']);

        return $user;
    }

    /** @param  array<int, User>  $executives */
    private function linkTeam(Campaign $campaign, User $supervisor, array $executives): void
    {
        foreach ($executives as $executive) {
            $executive->campaigns()->syncWithoutDetaching([$campaign->id]);

            DB::table('supervisor_executive')->updateOrInsert(
                ['executive_user_id' => $executive->id],
                ['supervisor_user_id' => $supervisor->id, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    private function seedAvailableLeads(Campaign $campaign, User $supervisor): void
    {
        $current = Lead::query()
            ->where('campaign_id', $campaign->id)
            ->whereNull('assigned_to_user_id')
            ->whereNull('disabled_at')
            ->where('delivery_status', Lead::DELIVERY_AVAILABLE)
            ->where('status_final', Lead::FINAL_NO_MANAGEMENT)
            ->count();

        $toCreate = max(0, self::TARGET_AVAILABLE - $current);

        if ($toCreate === 0) {
            $this->command?->info("CLARO ya tiene {$current} leads disponibles; no se crea nada.");

            return;
        }

        for ($index = 0; $index < $toCreate; $index++) {
            $this->createDemoLead($campaign, $supervisor, $index);
        }

        $this->command?->info("Se crearon {$toCreate} leads disponibles en CLARO.");
    }

    private function createDemoLead(Campaign $campaign, User $supervisor, int $index): void
    {
        $representative = self::REPS[$index % count(self::REPS)];

        $lead = Lead::create([
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => null,
            'supervisor_user_id' => $supervisor->id,
            'created_by_user_id' => null,
            'source' => Lead::SOURCE_COMPANY,
            'delivery_status' => Lead::DELIVERY_AVAILABLE,
            'no_contact_attempts' => 0,
            'full_name' => $representative,
            'business_name' => self::COMPANIES[$index % count(self::COMPANIES)],
            'representative_name' => $representative,
            'ruc' => '20'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'dni' => str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'fiscal_address' => self::ADDRESSES[$index % count(self::ADDRESSES)],
            'current_operator' => self::OPERATORS[$index % count(self::OPERATORS)],
            'current_line_count' => random_int(3, 25),
            'segment' => self::SEGMENTS[$index % count(self::SEGMENTS)],
            'max_speed' => self::SPEEDS[$index % count(self::SPEEDS)],
            'package' => self::PACKAGES[$index % count(self::PACKAGES)],
            'technology' => self::TECHS[$index % count(self::TECHS)],
            'status_general' => 'sin_contacto',
            'status_specific' => Lead::FINAL_NO_MANAGEMENT,
            'status_final' => Lead::FINAL_NO_MANAGEMENT,
        ]);

        $lead->phones()->create([
            'phone' => '9'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'type' => 'movil',
            'is_primary' => true,
        ]);

        if ($index % 3 === 0) {
            $lead->phones()->create([
                'phone' => '01'.str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT),
                'type' => 'fijo',
                'is_primary' => false,
            ]);
        }
    }
}
