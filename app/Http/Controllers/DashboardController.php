<?php

namespace App\Http\Controllers;

use App\Models\Interaction;
use App\Models\Lead;
use App\Models\Sale;
use App\Models\SupervisorExecutive;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const STATUS_OPTIONS = [
        'reprogramado' => 'Reprogramado',
        'negociacion' => 'Negociación',
        'acuerdo_aceptado' => 'Acuerdo aceptado',
        'no_desea' => 'No desea',
        'no_contesta' => 'No contesta',
        'telefono_apagado' => 'Teléfono apagado',
        'no_existe' => 'No existe',
    ];

    private const POSITIVE_STATUSES = [
        'negociacion',
        'acuerdo_aceptado',
    ];

    private const CONTACTED_STATUSES = [
        'reprogramado',
        'negociacion',
        'acuerdo_aceptado',
    ];

    private const NEGATIVE_STATUSES = [
        'no_desea',
        'no_contesta',
        'telefono_apagado',
        'no_existe',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Gerencia')) {
            return view('dashboard');
        }

        $today = now()->format('Y-m-d');
        $from = trim((string) $request->input('from', $today));
        $to = trim((string) $request->input('to', $today));
        [$startDate, $endDate] = $this->resolveDashboardDateRange($from, $to);

        $supervisors = User::role('Supervisor')
            ->orderBy('name')
            ->get(['id', 'name']);
        $supervisorIds = $supervisors->pluck('id')->all();

        $mappedExecutiveIds = SupervisorExecutive::query()
            ->whereIn('supervisor_user_id', $supervisorIds)
            ->distinct()
            ->pluck('executive_user_id');

        $goalMetricCards = $this->buildGoalMetricCards($mappedExecutiveIds->unique()->count(), $supervisorIds);

        $rows = $supervisors
            ->map(fn (User $supervisor) => $this->buildManagementSupervisorRow($supervisor, $startDate, $endDate))
            ->sortBy([
                ['gestion_total', 'desc'],
                ['acuerdo_aceptado', 'desc'],
                ['negociacion', 'desc'],
                ['supervisor_name', 'asc'],
            ])
            ->values();

        $managementTotals = [];

        foreach (self::STATUS_OPTIONS as $status => $label) {
            $managementTotals[$status] = (int) $rows->sum($status);
        }

        $managementTotals['contactado'] = (int) $rows->sum('contactado');
        $managementTotals['gestion_total'] = (int) $rows->sum('gestion_total');
        $managementTotals['total'] = (int) $rows->sum('total');
        $managementTotals['supervisors'] = (int) $rows->count();
        $managementTotals['supervisors_with_portfolio'] = (int) $rows->where('gestion_total', '>', 0)->count();
        $managementTotals['mapped_executives'] = (int) $mappedExecutiveIds->unique()->count();
        $managementTotals['executives_with_portfolio'] = (int) $rows
            ->flatMap(fn (array $row) => collect($row['executives'] ?? []))
            ->where('gestion_total', '>', 0)
            ->pluck('executive_user_id')
            ->filter()
            ->unique()
            ->count();

        $initialSupervisorId = trim((string) $request->input('focus_supervisor_user_id', ''));
        $initialSupervisorId = $supervisors->contains('id', (int) $initialSupervisorId)
            ? (string) (int) $initialSupervisorId
            : 'all';

        return view('management.dashboard', [
            'filters' => [
                'from' => $from,
                'to' => $to,
                'focus_supervisor_user_id' => $initialSupervisorId,
            ],
            'supervisors' => $supervisors,
            'goalMetricCards' => $goalMetricCards,
            'positiveStatuses' => self::POSITIVE_STATUSES,
            'negativeStatuses' => self::NEGATIVE_STATUSES,
            'dashboardPayload' => [
                'initial_supervisor_id' => $initialSupervisorId,
                'statuses' => collect(self::STATUS_OPTIONS)->map(function (string $label, string $status) {
                    return [
                        'key' => $status,
                        'label' => $label,
                        'tone' => in_array($status, self::POSITIVE_STATUSES, true)
                            ? 'positive'
                            : (in_array($status, self::NEGATIVE_STATUSES, true) ? 'negative' : 'neutral'),
                    ];
                })->values(),
                'supervisors' => $rows->values(),
                'management_totals' => $managementTotals,
            ],
        ]);
    }

    private function buildGoalMetricCards(int $executiveCount, array $supervisorIds): array
    {
        $weekStart = now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = now()->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
        $monthStart = now()->startOfMonth()->startOfDay();
        $monthEnd = now()->copy()->endOfMonth()->endOfDay();

        $monthlySalesActual = $this->countMappedAcceptedAgreements($supervisorIds, $monthStart, $monthEnd);
        $weeklySalesActual = $this->countMappedAcceptedAgreements($supervisorIds, $weekStart, $weekEnd);
        $monthlyNegotiationActual = $this->countMappedInteractionsBySpecificStatus($supervisorIds, 'negociacion', $monthStart, $monthEnd);
        $weeklyNegotiationActual = $this->countMappedInteractionsBySpecificStatus($supervisorIds, 'negociacion', $weekStart, $weekEnd);
        $monthlyContactedActual = $this->countMappedContactedInteractions($supervisorIds, $monthStart, $monthEnd);
        $weeklyContactedActual = $this->countMappedContactedInteractions($supervisorIds, $weekStart, $weekEnd);

        return [
            $this->buildGoalMetricCard(
                'Contactados requeridos al mes',
                $monthlyContactedActual,
                $executiveCount * $this->dashboardGoalMonthlyPerExecutive('contactado'),
                $executiveCount,
                $this->dashboardGoalMonthlyPerExecutive('contactado'),
                'emerald'
            ),
            $this->buildGoalMetricCard(
                'Contactados requeridos semanal',
                $weeklyContactedActual,
                $executiveCount * $this->dashboardGoalWeeklyPerExecutive('contactado'),
                $executiveCount,
                $this->dashboardGoalWeeklyPerExecutive('contactado'),
                'emerald'
            ),
            $this->buildGoalMetricCard(
                'Negociaciones requeridas al mes',
                $monthlyNegotiationActual,
                $executiveCount * $this->dashboardGoalMonthlyPerExecutive('negociacion'),
                $executiveCount,
                $this->dashboardGoalMonthlyPerExecutive('negociacion'),
                'amber'
            ),
            $this->buildGoalMetricCard(
                'Negociaciones requeridas semanal',
                $weeklyNegotiationActual,
                $executiveCount * $this->dashboardGoalWeeklyPerExecutive('negociacion'),
                $executiveCount,
                $this->dashboardGoalWeeklyPerExecutive('negociacion'),
                'amber'
            ),
            $this->buildGoalMetricCard(
                'Cierres requeridos al mes',
                $monthlySalesActual,
                $executiveCount * $this->dashboardGoalMonthlyPerExecutive('acuerdo_aceptado'),
                $executiveCount,
                $this->dashboardGoalMonthlyPerExecutive('acuerdo_aceptado'),
                'sky'
            ),
            $this->buildGoalMetricCard(
                'Cierres requeridos semanal',
                $weeklySalesActual,
                $executiveCount * $this->dashboardGoalWeeklyPerExecutive('acuerdo_aceptado'),
                $executiveCount,
                $this->dashboardGoalWeeklyPerExecutive('acuerdo_aceptado'),
                'sky'
            ),
        ];
    }

    private function dashboardGoalWeeklyPerExecutive(string $metric): int
    {
        $weeklyGoals = config('dashboard_goals.weekly_per_executive', []);

        if (! array_key_exists($metric, $weeklyGoals)) {
            throw new \RuntimeException('No se encontró la meta semanal configurada para el dashboard: '.$metric);
        }

        return max(0, (int) $weeklyGoals[$metric]);
    }

    private function dashboardGoalMonthlyPerExecutive(string $metric): int
    {
        return $this->dashboardGoalWeeklyPerExecutive($metric) * 4;
    }

    private function buildGoalMetricCard(
        string $label,
        int $actual,
        int $goal,
        int $executiveCount,
        int $goalPerExecutive,
        string $tone
    ): array {
        return [
            'label' => $label,
            'progress' => number_format($actual).'/'.number_format($goal),
            'actual' => $actual,
            'goal' => $goal,
            'meta_label' => $executiveCount > 0
                ? number_format($executiveCount).' ejecutivos x '.number_format($goalPerExecutive)
                : 'Sin ejecutivos asignados',
            'tone' => $tone,
        ];
    }

    private function countMappedAcceptedAgreements(array $supervisorIds, Carbon $startDate, Carbon $endDate): int
    {
        if (empty($supervisorIds)) {
            return 0;
        }

        return (int) Sale::query()
            ->where('status', Sale::STATUS_ACCEPTED)
            ->whereBetween('accepted_at', [$startDate, $endDate])
            ->whereExists(function ($subQuery) use ($supervisorIds) {
                $subQuery->selectRaw('1')
                    ->from('supervisor_executive as se')
                    ->whereIn('se.supervisor_user_id', $supervisorIds)
                    ->where(function ($campaignQuery) {
                        $campaignQuery->whereNull('se.campaign_id')
                            ->orWhereColumn('se.campaign_id', 'sales.campaign_id');
                    })
                    ->whereColumn('se.executive_user_id', 'sales.executive_user_id');
            })
            ->count();
    }

    private function countMappedInteractionsBySpecificStatus(
        array $supervisorIds,
        string $specificStatus,
        Carbon $startDate,
        Carbon $endDate
    ): int {
        if (empty($supervisorIds)) {
            return 0;
        }

        return (int) Interaction::query()
            ->where('status_specific', $specificStatus)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereExists(function ($subQuery) use ($supervisorIds) {
                $subQuery->selectRaw('1')
                    ->from('supervisor_executive as se')
                    ->whereIn('se.supervisor_user_id', $supervisorIds)
                    ->where(function ($campaignQuery) {
                        $campaignQuery->whereNull('se.campaign_id')
                            ->orWhereColumn('se.campaign_id', 'interactions.campaign_id');
                    })
                    ->whereColumn('se.executive_user_id', 'interactions.user_id');
            })
            ->count();
    }

    private function countMappedContactedInteractions(array $supervisorIds, Carbon $startDate, Carbon $endDate): int
    {
        if (empty($supervisorIds)) {
            return 0;
        }

        return (int) Interaction::query()
            ->where('status_general', 'contactado')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereExists(function ($subQuery) use ($supervisorIds) {
                $subQuery->selectRaw('1')
                    ->from('supervisor_executive as se')
                    ->whereIn('se.supervisor_user_id', $supervisorIds)
                    ->where(function ($campaignQuery) {
                        $campaignQuery->whereNull('se.campaign_id')
                            ->orWhereColumn('se.campaign_id', 'interactions.campaign_id');
                    })
                    ->whereColumn('se.executive_user_id', 'interactions.user_id');
            })
            ->count();
    }

    public function agreements(Request $request): View
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('Gerencia')) {
            return view('dashboard');
        }

        $filters = [
            'ruc' => trim((string) $request->input('ruc', '')),
            'supervisor_validation_status' => trim((string) $request->input('supervisor_validation_status', '')),
            'management_status' => trim((string) $request->input('management_status', '')),
            'sisac_status' => trim((string) $request->input('sisac_status', '')),
        ];

        $query = Sale::query()
            ->with([
                'lead.phones',
                'campaign',
                'executive',
                'supervisor',
                'histories.user',
                'postSaleUpdates.user',
                'validationUpdates.user',
            ])
            ->where('status', Sale::STATUS_ACCEPTED);

        if ($filters['ruc'] !== '') {
            $query->where('customer_ruc', 'like', '%'.$filters['ruc'].'%');
        }

        foreach (['supervisor_validation_status', 'management_status'] as $field) {
            if ($filters[$field] !== '') {
                $query->where($field, $filters[$field]);
            }
        }

        if ($filters['sisac_status'] !== '') {
            $query->whereIn('sisac_status', match ($filters['sisac_status']) {
                'en_evaluacion' => ['en_evaluacion', 'pendiente_validacion', 'observado'],
                'activo' => ['activo', 'aprobado'],
                'rechazado' => ['rechazado'],
                'entregado' => ['entregado'],
                default => [$filters['sisac_status']],
            });
        }

        $sales = $query
            ->orderByDesc(DB::raw('COALESCE(supervisor_validated_at, accepted_at, created_at)'))
            ->paginate(10)
            ->withQueryString();

        return view('management.agreements', [
            'sales' => $sales,
            'filters' => $filters,
        ]);
    }

    private function resolveDashboardDateRange(string $from, string $to): array
    {
        try {
            $startDate = Carbon::parse($from)->startOfDay();
            $endDate = Carbon::parse($to)->endOfDay();
        } catch (\Throwable $exception) {
            $fallback = now();

            return [$fallback->copy()->startOfDay(), $fallback->copy()->endOfDay()];
        }

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [$startDate, $endDate];
    }

    private function buildManagementSupervisorRow(User $supervisor, Carbon $startDate, Carbon $endDate): array
    {
        $executiveIds = SupervisorExecutive::query()
            ->where('supervisor_user_id', $supervisor->id)
            ->distinct()
            ->pluck('executive_user_id');

        $executives = User::query()
            ->whereIn('id', $executiveIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $teamLeadQuery = Lead::query()
            ->whereNull('disabled_at')
            ->whereIn('status_specific', array_keys(self::STATUS_OPTIONS));

        $this->applyManagementSupervisorTeamScope($teamLeadQuery, $supervisor->id);
        $teamLeadQuery->whereBetween('updated_at', [$startDate, $endDate]);

        $rawStats = (clone $teamLeadQuery)
            ->selectRaw('COALESCE(assigned_to_user_id, created_by_user_id) as executive_user_id')
            ->selectRaw('status_specific')
            ->selectRaw('COUNT(*) as total')
            ->groupBy(DB::raw('COALESCE(assigned_to_user_id, created_by_user_id)'), 'status_specific')
            ->get()
            ->groupBy('executive_user_id');

        $rawLeadDetails = (clone $teamLeadQuery)
            ->with([
                'phones',
                'interactions' => function ($query) {
                    $query->latest('created_at')->with('offers');
                },
            ])
            ->select([
                'id',
                'assigned_to_user_id',
                'created_by_user_id',
                'status_specific',
                'business_name',
                'ruc',
                'representative_name',
                'dni',
                'last_contact_name',
                'last_contact_phone',
                'current_operator',
                'current_line_count',
                'call_summary',
                'updated_at',
            ])
            ->orderByDesc('updated_at')
            ->get()
            ->groupBy(fn (Lead $lead) => (string) ($lead->assigned_to_user_id ?: $lead->created_by_user_id));

        $executiveRows = $executives
            ->map(function (User $executive) use ($rawStats, $rawLeadDetails) {
                $stats = collect($rawStats->get($executive->id, []))
                    ->pluck('total', 'status_specific');

                $leadDetails = collect($rawLeadDetails->get((string) $executive->id, []))
                    ->groupBy('status_specific');

                $row = [
                    'executive_user_id' => $executive->id,
                    'executive_name' => $executive->name,
                    'lead_details' => [],
                ];

                foreach (self::STATUS_OPTIONS as $status => $label) {
                    $row[$status] = (int) ($stats[$status] ?? 0);
                    $row['lead_details'][$status] = $this->formatLeadDetails(
                        collect($leadDetails->get($status, []))
                    );
                }

                $row['gestion_total'] = collect(array_keys(self::STATUS_OPTIONS))
                    ->sum(fn ($status) => $row[$status]);
                $row['contactado'] = collect(self::CONTACTED_STATUSES)
                    ->sum(fn ($status) => $row[$status]);
                $row['lead_details']['contactado'] = $this->formatLeadDetails(
                    collect(self::CONTACTED_STATUSES)
                        ->flatMap(fn ($status) => collect($leadDetails->get($status, [])))
                        ->sortByDesc(fn (Lead $lead) => optional($lead->updated_at)->getTimestamp() ?? 0)
                        ->values()
                );
                $row['total'] = collect(array_keys(self::STATUS_OPTIONS))
                    ->sum(fn ($status) => $row[$status]);

                return $row;
            })
            ->sortBy([
                ['gestion_total', 'desc'],
                ['acuerdo_aceptado', 'desc'],
                ['negociacion', 'desc'],
                ['executive_name', 'asc'],
            ])
            ->values();

        $row = [
            'supervisor_user_id' => $supervisor->id,
            'supervisor_name' => $supervisor->name,
            'executives' => $executiveRows->values()->all(),
            'lead_details' => [],
        ];

        foreach (self::STATUS_OPTIONS as $status => $label) {
            $row[$status] = (int) $executiveRows->sum($status);
            $row['lead_details'][$status] = $executiveRows
                ->flatMap(fn (array $executive) => $executive['lead_details'][$status] ?? [])
                ->sortByDesc('updated_at_sort')
                ->values()
                ->all();
        }

        $row['contactado'] = (int) $executiveRows->sum('contactado');
        $row['gestion_total'] = (int) $executiveRows->sum('gestion_total');
        $row['lead_details']['contactado'] = $executiveRows
            ->flatMap(fn (array $executive) => $executive['lead_details']['contactado'] ?? [])
            ->sortByDesc('updated_at_sort')
            ->values()
            ->all();
        $row['total'] = (int) $executiveRows->sum('total');
        $row['executives_count'] = (int) $executives->count();
        $row['executives_with_portfolio'] = (int) $executiveRows->where('gestion_total', '>', 0)->count();

        return $row;
    }

    private function applyManagementSupervisorTeamScope($query, int $supervisorUserId): void
    {
        $query->whereExists(function ($subQuery) use ($supervisorUserId) {
            $subQuery->selectRaw('1')
                ->from('supervisor_executive as se')
                ->where('se.supervisor_user_id', $supervisorUserId)
                ->where(function ($campaignQuery) {
                    $campaignQuery->whereNull('se.campaign_id')
                        ->orWhereColumn('se.campaign_id', 'leads.campaign_id');
                })
                ->where(function ($executiveQuery) {
                    $executiveQuery->whereColumn('se.executive_user_id', 'leads.created_by_user_id')
                        ->orWhereColumn('se.executive_user_id', 'leads.assigned_to_user_id');
                });
        });
    }

    private function buildDashboardLeadDetail(Lead $lead): array
    {
        $commercialStatuses = array_flip(self::POSITIVE_STATUSES);

        return [
            'id' => $lead->id,
            'business_name' => $lead->business_name ?: '-',
            'ruc' => $lead->ruc ?: '-',
            'representative_name' => $lead->representative_name ?: '-',
            'dni' => $lead->dni ?: '-',
            'last_contact_name' => $lead->last_contact_name ?: '-',
            'last_contact_phone' => $lead->last_contact_phone ?: '-',
            'lead_phones' => $lead->phones
                ->pluck('phone')
                ->filter()
                ->values()
                ->all(),
            'current_operator' => $lead->current_operator ?: '-',
            'current_line_count' => $lead->current_line_count ?? '-',
            'call_summary' => $lead->call_summary ?: '',
            'updated_at_label' => optional($lead->updated_at)->format('d/m/Y H:i') ?: '-',
            'updated_at_date' => optional($lead->updated_at)->format('Y-m-d') ?: '',
            'updated_at_sort' => optional($lead->updated_at)->timestamp ?: 0,
            'show_commercial_detail' => isset($commercialStatuses[$lead->status_specific]),
            'commercial_snapshot' => isset($commercialStatuses[$lead->status_specific])
                ? $this->buildCommercialSnapshot($lead)
                : null,
        ];
    }

    private function formatLeadDetails($leads): array
    {
        return $leads
            ->map(fn (Lead $lead) => $this->buildDashboardLeadDetail($lead))
            ->values()
            ->all();
    }

    private function buildCommercialSnapshot(Lead $lead): array
    {
        $latestInteraction = $lead->interactions->sortByDesc('created_at')->first();
        $offers = $latestInteraction?->offers ?? collect();
        $mobileOffers = $offers->where('product_type', 'movil')->values();
        $fixedOffer = $offers->firstWhere('product_type', 'fijo');

        $products = [];

        if ($mobileOffers->isNotEmpty()) {
            $mobileBits = $mobileOffers->map(function ($mobileOffer) {
                $bits = [];

                if ($mobileOffer->mobile_mode) {
                    $bits[] = match ($mobileOffer->mobile_mode) {
                        'portabilidad' => 'Portabilidad',
                        'alta_nueva' => 'Alta nueva',
                        'porta_alta' => 'Porta + Alta',
                        default => ucfirst(str_replace('_', ' ', (string) $mobileOffer->mobile_mode)),
                    };
                }

                if (! is_null($mobileOffer->portability_lines)) {
                    $bits[] = 'Porta: '.$mobileOffer->portability_lines;
                }

                if (! empty($mobileOffer->portability_promotion_name)) {
                    $bits[] = $mobileOffer->portability_promotion_name;
                }

                if (! is_null($mobileOffer->new_lines)) {
                    $bits[] = 'Altas: '.$mobileOffer->new_lines;
                }

                if (! empty($mobileOffer->new_promotion_name)) {
                    $bits[] = $mobileOffer->new_promotion_name;
                }

                $monthlyAmount = (float) ($mobileOffer->portability_monthly ?? 0) + (float) ($mobileOffer->new_monthly ?? 0);

                if ($monthlyAmount > 0) {
                    $bits[] = 'S/ '.number_format($monthlyAmount, 2);
                }

                return implode(' · ', array_filter($bits));
            })->filter()->values()->all();

            $products[] = [
                'label' => 'Móvil',
                'detail' => ! empty($mobileBits) ? implode(' || ', $mobileBits) : 'Sin detalle',
            ];
        }

        if ($fixedOffer) {
            $fixedBits = [];

            if ($fixedOffer->internet_speed) {
                $fixedBits[] = $fixedOffer->internet_speed;
            }

            if (! is_null($fixedOffer->fixed_monthly)) {
                $fixedBits[] = 'S/ '.number_format((float) $fixedOffer->fixed_monthly, 2);
            }

            $products[] = [
                'label' => 'Fijo',
                'detail' => ! empty($fixedBits) ? implode(' · ', $fixedBits) : 'Sin detalle',
            ];
        }

        return [
            'contact_phone' => $latestInteraction?->contact_phone ?: ($lead->last_contact_phone ?: '-'),
            'call_detail' => $latestInteraction?->call_detail ?: ($lead->call_summary ?: '-'),
            'products' => $products,
        ];
    }
}
