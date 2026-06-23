<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Interaction;
use App\Models\Lead;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Pobla el dashboard de Gerencia con gestión realista: leads ya trabajados por
 * los ejecutivos (con historial de llamadas), negociaciones, ventas aceptadas y
 * un volumen de contactos que llena las tarjetas de metas y la tabla por
 * supervisor. Re-ejecutable: limpia la gestión demo anterior y la recrea; el
 * pool de leads "disponible" (DemoLeadsSeeder) se conserva intacto.
 */
class DemoDashboardSeeder extends Seeder
{
    /** [ejecutivo (exec1|exec2), estado del lead, cantidad] */
    private const WORKED = [
        ['exec1', 'acuerdo_aceptado', 3],
        ['exec1', 'negociacion', 3],
        ['exec1', 'reprogramado', 2],
        ['exec1', 'no_desea', 1],
        ['exec2', 'acuerdo_aceptado', 2],
        ['exec2', 'negociacion', 2],
        ['exec2', 'reprogramado', 1],
        ['exec2', 'no_contesta', 1],
        ['exec2', 'telefono_apagado', 1],
    ];

    private const COMPANIES = [
        'Corporación Vega S.A.C.', 'Grupo Empresarial Lima S.A.C.', 'Servicios Integrales Perú E.I.R.L.',
        'Distribuciones del Norte S.A.C.', 'Manufacturas Industriales S.A.C.', 'Comercial Los Andes S.A.C.',
        'Soluciones Digitales S.A.C.', 'Inmobiliaria Horizonte S.A.C.', 'Alimentos Frescos S.A.C.',
        'Maquinarias y Equipos S.A.C.', 'Consultora Estratégica S.A.C.', 'Turismo Aventura E.I.R.L.',
        'Salud y Bienestar S.A.C.', 'Educación Futuro S.A.C.', 'Energía Renovable S.A.C.',
    ];

    private const REPS = [
        'Ricardo Tello Vásquez', 'Mónica Paredes Ríos', 'Hugo Benites Campos', 'Claudia Romero Díaz',
        'Walter Ñahui Quispe', 'Silvia Maldonado León', 'Óscar Bravo Salinas', 'Karina Acosta Vera',
        'Daniel Ríos Montoya', 'Liliana Castro Peña', 'Marco Zúñiga Flores', 'Yolanda Pérez Soto',
        'Iván Carrasco Lume', 'Verónica Soto Aguirre', 'César Delgado Ramos',
    ];

    private const PROMOS = ['Max Negocios S/ 55.90', 'Pospago Empresas S/ 69.90', 'Full Negocios S/ 89.90', 'Plan Pyme S/ 39.90'];

    private int $cursor = 0;

    private int $saleCount = 0;

    public function run(): void
    {
        $campaign = Campaign::firstOrCreate(['name' => 'CLARO'], ['active' => true]);
        $supervisor = User::where('email', 'supervisor@nexo.local')->first();
        $executives = [
            'exec1' => User::where('email', 'ejecutivo@nexo.local')->first(),
            'exec2' => $this->ensureSecondExecutive(),
        ];

        if (! $supervisor || ! $executives['exec1']) {
            $this->command?->warn('Faltan usuarios demo. Corre UserSeeder primero (SEED_DEMO_USERS=true).');

            return;
        }

        $this->linkTeam($campaign, $supervisor, $executives);
        $this->clearPreviousDemo();

        $leads = [];

        foreach (self::WORKED as [$execKey, $status, $count]) {
            for ($i = 0; $i < $count; $i++) {
                $lead = $this->buildWorkedLead($campaign, $supervisor, $executives[$execKey], $status);
                $leads[] = [
                    'id' => $lead->id,
                    'exec' => $executives[$execKey]->id,
                    'campaign' => $campaign->id,
                    'phone' => $lead->last_contact_phone,
                    'rep' => $lead->representative_name,
                ];
            }
        }

        $calls = $this->seedContactVolume($leads);

        $this->command?->info(count($leads)." leads gestionados, {$this->saleCount} ventas y {$calls} llamadas de contacto.");
    }

    /** Borra solo la gestión demo previa; el pool "disponible" se conserva (FKs en cascada). */
    private function clearPreviousDemo(): void
    {
        Lead::query()->where('delivery_status', Lead::DELIVERY_MANAGED)->delete();
    }

    private function buildWorkedLead(Campaign $campaign, User $supervisor, User $exec, string $status): Lead
    {
        $lead = $this->createLead($campaign, $supervisor, $exec, $status);
        $this->createPriorCalls($lead, $exec);
        $finalInteraction = $this->createFinalInteraction($lead, $exec, $status);

        if ($status === 'acuerdo_aceptado') {
            $this->createSale($lead, $exec, $supervisor, $finalInteraction);
            $this->saleCount++;
        }

        return $lead;
    }

    private function createLead(Campaign $campaign, User $supervisor, User $exec, string $status): Lead
    {
        $rep = self::REPS[$this->cursor % count(self::REPS)];
        $phone = '9'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $this->cursor++;

        $lead = Lead::create([
            'campaign_id' => $campaign->id,
            'assigned_to_user_id' => $exec->id,
            'supervisor_user_id' => $supervisor->id,
            'source' => Lead::SOURCE_COMPANY,
            'delivery_status' => Lead::DELIVERY_MANAGED,
            'taken_at' => now()->subDays(random_int(1, 18)),
            'no_contact_attempts' => 0,
            'full_name' => $rep,
            'business_name' => self::COMPANIES[$this->cursor % count(self::COMPANIES)],
            'representative_name' => $rep,
            'ruc' => '20'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'dni' => str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'fiscal_address' => 'Av. Empresarial '.random_int(100, 4999).', Lima',
            'current_operator' => ['Movistar', 'Entel', 'Bitel'][random_int(0, 2)],
            'current_line_count' => random_int(4, 30),
            'segment' => ['Pyme', 'Mediana empresa', 'Corporativo'][random_int(0, 2)],
            'last_contact_name' => $rep,
            'last_contact_phone' => $phone,
            'status_general' => $this->generalFor($status),
            'status_specific' => $status,
            'status_final' => $this->finalFor($status),
            'call_summary' => 'Gestión registrada por el ejecutivo.',
        ]);

        $lead->phones()->create(['phone' => $phone, 'type' => 'movil', 'is_primary' => true]);

        return $lead;
    }

    private function createPriorCalls(Lead $lead, User $exec): void
    {
        foreach (range(1, random_int(3, 8)) as $ignored) {
            $priorStatus = ['reprogramado', 'negociacion', 'no_contesta'][random_int(0, 2)];

            $this->makeInteraction([
                'lead_id' => $lead->id,
                'user_id' => $exec->id,
                'campaign_id' => $lead->campaign_id,
                'status' => $priorStatus,
                'interaction_type' => 'a_negociar',
                'status_general' => $priorStatus === 'no_contesta' ? 'no_contactado' : 'contactado',
                'status_specific' => $priorStatus,
                'call_detail' => 'Seguimiento de la gestión comercial.',
                'contact_name' => $lead->representative_name,
                'contact_phone' => $lead->last_contact_phone,
                'is_agreement' => false,
            ], now()->subDays(random_int(1, 20))->setTime(random_int(9, 18), random_int(0, 59)));
        }
    }

    private function createFinalInteraction(Lead $lead, User $exec, string $status): Interaction
    {
        $isAgreement = $status === 'acuerdo_aceptado';

        $interaction = $this->makeInteraction([
            'lead_id' => $lead->id,
            'user_id' => $exec->id,
            'campaign_id' => $lead->campaign_id,
            'status' => $status,
            'interaction_type' => $isAgreement ? 'acuerdo_aceptado' : 'a_negociar',
            'status_general' => $this->generalFor($status),
            'status_specific' => $status,
            'call_detail' => $isAgreement
                ? 'Acuerdo aceptado pendiente de validación por supervisor.'
                : 'Última gestión registrada.',
            'contact_name' => $lead->representative_name,
            'contact_phone' => $lead->last_contact_phone,
            'is_agreement' => $isAgreement,
            'agreed_at' => $isAgreement ? now() : null,
        ], now());

        if (in_array($status, ['negociacion', 'acuerdo_aceptado'], true)) {
            $interaction->offers()->create([
                'product_type' => 'movil',
                'mobile_mode' => 'portabilidad',
                'portability_lines' => random_int(2, 8),
                'portability_monthly' => random_int(30, 90) + 0.90,
                'portability_promotion_name' => self::PROMOS[array_rand(self::PROMOS)],
            ]);
        }

        return $interaction;
    }

    private function createSale(Lead $lead, User $exec, User $supervisor, Interaction $interaction): void
    {
        $validated = $this->saleCount % 2 === 0;

        Sale::create([
            'lead_id' => $lead->id,
            'interaction_id' => $interaction->id,
            'campaign_id' => $lead->campaign_id,
            'executive_user_id' => $exec->id,
            'supervisor_user_id' => $supervisor->id,
            'status' => Sale::STATUS_ACCEPTED,
            'management_status' => Sale::MANAGEMENT_PENDING_SUPERVISION,
            'sisac_status' => Sale::SISAC_PENDING_SUPERVISION,
            'product_type' => 'movil',
            'offered_line_count' => random_int(2, 8),
            'monthly_payment' => random_int(40, 120) + 0.90,
            'customer_ruc' => $lead->ruc,
            'customer_business_name' => $lead->business_name,
            'customer_dni' => $lead->dni,
            'customer_representative_name' => $lead->representative_name,
            'customer_phone' => $lead->last_contact_phone,
            'customer_address' => $lead->fiscal_address,
            'plan_code' => 'PLAN-'.random_int(1000, 9999),
            'operator_name' => $lead->current_operator,
            'tmo_to_agreement_seconds' => random_int(420, 1500),
            'supervisor_validation_status' => $validated ? Sale::SUPERVISOR_VALIDATION_VALIDATED : Sale::SUPERVISOR_VALIDATION_PENDING,
            'supervisor_validated_at' => $validated ? now() : null,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Volumen de llamadas "contactado" repartido en el mes (con peso en la semana)
     * para que las tarjetas de metas no se vean vacías.
     *
     * @param  array<int, array<string, mixed>>  $leads
     */
    private function seedContactVolume(array $leads): int
    {
        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $monthStart = now()->startOfMonth();
        $upper = now()->subMinute();
        $total = 0;

        foreach ($leads as $lead) {
            foreach (range(1, random_int(14, 24)) as $ignored) {
                $when = random_int(0, 100) < 60
                    ? $this->randomMoment($weekStart, $upper)
                    : $this->randomMoment($monthStart, $weekStart);

                $status = random_int(0, 100) < 70 ? 'reprogramado' : 'negociacion';

                $this->makeInteraction([
                    'lead_id' => $lead['id'],
                    'user_id' => $lead['exec'],
                    'campaign_id' => $lead['campaign'],
                    'status' => $status,
                    'interaction_type' => 'a_negociar',
                    'status_general' => 'contactado',
                    'status_specific' => $status,
                    'call_detail' => 'Llamada de seguimiento.',
                    'contact_name' => $lead['rep'],
                    'contact_phone' => $lead['phone'],
                    'is_agreement' => false,
                ], $when);

                $total++;
            }
        }

        return $total;
    }

    private function randomMoment(Carbon $start, Carbon $end): Carbon
    {
        if ($end->lessThanOrEqualTo($start)) {
            return $start->copy();
        }

        return Carbon::createFromTimestamp(random_int($start->getTimestamp(), $end->getTimestamp()));
    }

    private function makeInteraction(array $attributes, Carbon $when): Interaction
    {
        $interaction = new Interaction($attributes);
        $interaction->created_at = $when;
        $interaction->updated_at = $when;
        $interaction->timestamps = false;
        $interaction->save();
        $interaction->timestamps = true;

        return $interaction;
    }

    private function generalFor(string $status): string
    {
        return in_array($status, ['reprogramado', 'negociacion', 'acuerdo_aceptado', 'no_desea'], true)
            ? 'contactado'
            : 'no_contactado';
    }

    private function finalFor(string $status): string
    {
        return match ($status) {
            'acuerdo_aceptado' => Lead::FINAL_AGREEMENT_ACCEPTED,
            'negociacion', 'reprogramado' => Lead::FINAL_IN_FOLLOW_UP,
            'no_desea', 'no_existe' => Lead::FINAL_CLOSED_NO_SALE,
            default => Lead::FINAL_NO_MANAGEMENT,
        };
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

    /** @param  array<string, User>  $executives */
    private function linkTeam(Campaign $campaign, User $supervisor, array $executives): void
    {
        $supervisor->campaigns()->syncWithoutDetaching([$campaign->id]);

        foreach ($executives as $executive) {
            $executive->campaigns()->syncWithoutDetaching([$campaign->id]);

            DB::table('supervisor_executive')->updateOrInsert(
                ['executive_user_id' => $executive->id],
                ['supervisor_user_id' => $supervisor->id, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
