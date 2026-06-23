<?php

namespace App\Http\Controllers;

use App\Models\ExecutiveActivitySession;
use App\Models\Interaction;
use App\Models\Lead;
use App\Models\Sale;
use App\Models\SupervisorExecutive;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupervisorDashboardController extends MyWorkController
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

    private const CARRYOVER_STATUSES = [
        'negociacion',
        'reprogramado',
        'acuerdo_aceptado',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('Supervisor'), 403);

        $today = now()->format('Y-m-d');
        $from = trim((string) $request->input('from', $today));
        $to = trim((string) $request->input('to', $today));

        $executiveIds = SupervisorExecutive::query()
            ->where('supervisor_user_id', $user->id)
            ->distinct()
            ->pluck('executive_user_id');

        $executives = User::query()
            ->whereIn('id', $executiveIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $monthlyActivationStarts = $this->resolveExecutiveMonthlyActivationStarts($user->id, $executives);
        $monthWeeks = $this->buildMonthLogicalWeeks(now());
        $goalMetricCards = $this->buildGoalMetricCards($user->id, $executives->count());
        $carryoverMetrics = $this->buildTeamCarryoverMetrics($user->id, $executives, $monthlyActivationStarts);
        $weeklyCarryoverMetrics = $this->buildTeamWeeklyCarryoverMetrics($user->id, $executives, $monthlyActivationStarts);
        $weeklyMonthBreakdown = $this->buildTeamWeeklyMonthBreakdown($user->id, $executives, $monthlyActivationStarts, $monthWeeks);
        $goalCompletionFlags = $this->buildTeamGoalCompletionFlags($user->id, $executives, $carryoverMetrics);

        $teamLeadQuery = Lead::query()
            ->whereNull('disabled_at')
            ->whereIn('status_specific', array_keys(self::STATUS_OPTIONS));

        $this->applySupervisorTeamBaseScope($teamLeadQuery, $user->id);

        [$startDate, $endDate] = $this->resolveDashboardDateRange($from, $to);

        if ($startDate && $endDate) {
            $teamLeadQuery->whereBetween('updated_at', [$startDate, $endDate]);
        }

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

        $rows = $executives
            ->map(function (User $executive) use (
                $rawStats,
                $rawLeadDetails,
                $carryoverMetrics,
                $weeklyCarryoverMetrics,
                $weeklyMonthBreakdown,
                $monthWeeks,
                $goalCompletionFlags
            ) {
                $stats = collect($rawStats->get($executive->id, []))
                    ->pluck('total', 'status_specific');

                $leadDetails = collect($rawLeadDetails->get((string) $executive->id, []))
                    ->groupBy('status_specific');

                $row = [
                    'executive_user_id' => $executive->id,
                    'executive_name' => $executive->name,
                    'lead_details' => [],
                    'carryover' => $carryoverMetrics[$executive->id] ?? $this->emptyCarryoverMetrics(),
                    'weekly_carryover' => $weeklyCarryoverMetrics[$executive->id] ?? $this->emptyWeeklyCarryoverMetrics(),
                    'weekly_month_breakdown' => $weeklyMonthBreakdown[$executive->id] ?? $this->emptyWeeklyMonthBreakdown($monthWeeks),
                    'goal_completion' => $goalCompletionFlags[$executive->id] ?? $this->emptyGoalCompletionFlags(),
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

        $teamTotals = [];

        foreach (self::STATUS_OPTIONS as $status => $label) {
            $teamTotals[$status] = (int) $rows->sum($status);
        }

        $teamTotals['contactado'] = (int) $rows->sum('contactado');
        $teamTotals['gestion_total'] = (int) $rows->sum('gestion_total');
        $teamTotals['total'] = (int) $rows->sum('total');
        $teamTotals['executives'] = (int) $rows->count();
        $teamTotals['executives_with_portfolio'] = (int) $rows->where('gestion_total', '>', 0)->count();

        return view('supervisor.dashboard.index', [
            'executives' => $executives,
            'filters' => [
                'from' => $from,
                'to' => $to,
            ],
            'statusOptions' => self::STATUS_OPTIONS,
            'positiveStatuses' => self::POSITIVE_STATUSES,
            'negativeStatuses' => self::NEGATIVE_STATUSES,
            'goalMetricCards' => $goalMetricCards,
            'dashboardPayload' => [
                'statuses' => collect(self::STATUS_OPTIONS)->map(function (string $label, string $status) {
                    return [
                        'key' => $status,
                        'label' => $label,
                        'tone' => in_array($status, self::POSITIVE_STATUSES, true)
                            ? 'positive'
                            : (in_array($status, self::NEGATIVE_STATUSES, true) ? 'negative' : 'neutral'),
                    ];
                })->values(),
                'month_weeks' => collect($monthWeeks)
                    ->map(fn (array $week) => [
                        'week_key' => $week['week_key'],
                        'label' => $week['label'],
                    ])
                    ->values(),
                'executives' => $rows->values(),
                'team_totals' => $teamTotals,
            ],
        ]);
    }

    private function buildGoalMetricCards(int $supervisorUserId, int $executiveCount): array
    {
        $weekStart = now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = now()->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
        $monthStart = now()->startOfMonth()->startOfDay();
        $monthEnd = now()->copy()->endOfMonth()->endOfDay();

        $monthlySalesActual = $this->countTeamAcceptedAgreements($supervisorUserId, $monthStart, $monthEnd);
        $weeklySalesActual = $this->countTeamAcceptedAgreements($supervisorUserId, $weekStart, $weekEnd);
        $monthlyNegotiationActual = $this->countTeamInteractionsBySpecificStatus($supervisorUserId, 'negociacion', $monthStart, $monthEnd);
        $weeklyNegotiationActual = $this->countTeamInteractionsBySpecificStatus($supervisorUserId, 'negociacion', $weekStart, $weekEnd);
        $monthlyContactedActual = $this->countTeamContactedInteractions($supervisorUserId, $monthStart, $monthEnd);
        $weeklyContactedActual = $this->countTeamContactedInteractions($supervisorUserId, $weekStart, $weekEnd);

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

    private function buildTeamWeeklyCarryoverMetrics(
        int $supervisorUserId,
        Collection $executives,
        array $monthlyActivationStarts
    ): array {
        $today = now()->startOfDay();
        $monthEnd = $today->copy()->endOfMonth()->endOfDay();
        $currentMonthStart = $today->copy()->startOfMonth()->startOfDay();
        $currentMonthKey = $this->monthKeyForDate($today);
        $referenceWorkingDay = $this->resolveWeeklyReferenceDay($today);
        $metricsDateRange = $this->resolveHistoricalMetricsDateRange($monthlyActivationStarts, $monthEnd);
        $interactionStatuses = array_values(array_diff(self::CARRYOVER_STATUSES, ['acuerdo_aceptado']));
        $interactionDailyTotals = $metricsDateRange
            ? $this->teamInteractionDailyTotalsByExecutiveStatus(
                $supervisorUserId,
                $interactionStatuses,
                $metricsDateRange['start'],
                $metricsDateRange['end']
            )
            : [];
        $agreementDailyTotals = $metricsDateRange
            ? $this->teamAcceptedAgreementDailyTotalsByExecutive(
                $supervisorUserId,
                $metricsDateRange['start'],
                $metricsDateRange['end']
            )
            : [];

        return $executives
            ->mapWithKeys(function (User $executive) use (
                $monthlyActivationStarts,
                $currentMonthStart,
                $currentMonthKey,
                $referenceWorkingDay,
                $monthEnd,
                $interactionDailyTotals,
                $agreementDailyTotals
            ) {
                $executiveMonthlyStarts = $monthlyActivationStarts[$executive->id] ?? [];
                $currentMonthActivationStart = $executiveMonthlyStarts[$currentMonthKey] ?? null;
                $activeWeeks = $this->buildActiveBusinessWeekWindows($currentMonthActivationStart, $referenceWorkingDay, $monthEnd);
                $currentWeek = ! empty($activeWeeks) ? end($activeWeeks) : null;
                $metrics = [];

                foreach (self::CARRYOVER_STATUSES as $status) {
                    $dailyGoal = $this->dashboardGoalDailyPerExecutive($status);
                    $beforeCurrentWeekDate = $currentWeek
                        ? $currentWeek['start']->copy()->subDay()
                        : $currentMonthStart->copy()->subDay();
                    $expectedBeforeCurrentWeek = $this->sumExpectedTotalsThroughDate(
                        $executiveMonthlyStarts,
                        $beforeCurrentWeekDate,
                        $dailyGoal
                    );
                    $actualBeforeCurrentWeek = $this->sumActualTotalsThroughDate(
                        $executive->id,
                        $status,
                        $executiveMonthlyStarts,
                        $beforeCurrentWeekDate,
                        $interactionDailyTotals,
                        $agreementDailyTotals
                    );
                    $carryoverPending = max(0, $expectedBeforeCurrentWeek - $actualBeforeCurrentWeek);

                    if (! $currentWeek || ! $referenceWorkingDay) {
                        $metrics[$status] = $this->buildWeeklyCarryoverMetricSnapshot(
                            0,
                            0,
                            $carryoverPending
                        );

                        continue;
                    }

                    $currentWeekGoal = (int) $currentWeek['business_days'] * $dailyGoal;
                    $currentWeekActual = $this->sumStatusTotalsBetween(
                        $executive->id,
                        $status,
                        $currentWeek['start'],
                        $referenceWorkingDay,
                        $interactionDailyTotals,
                        $agreementDailyTotals,
                        true
                    );

                    $metrics[$status] = $this->buildWeeklyCarryoverMetricSnapshot(
                        $currentWeekActual,
                        $currentWeekGoal,
                        $carryoverPending
                    );
                }

                return [$executive->id => $metrics];
            })
            ->all();
    }

    private function buildTeamWeeklyMonthBreakdown(
        int $supervisorUserId,
        Collection $executives,
        array $monthlyActivationStarts,
        array $monthWeeks
    ): array {
        $today = now()->startOfDay();
        $monthEnd = $today->copy()->endOfMonth()->endOfDay();
        $currentMonthStart = $today->copy()->startOfMonth()->startOfDay();
        $currentMonthKey = $this->monthKeyForDate($today);
        $metricsDateRange = $this->resolveHistoricalMetricsDateRange($monthlyActivationStarts, $monthEnd);
        $interactionStatuses = array_values(array_diff(self::CARRYOVER_STATUSES, ['acuerdo_aceptado']));
        $interactionDailyTotals = $metricsDateRange
            ? $this->teamInteractionDailyTotalsByExecutiveStatus(
                $supervisorUserId,
                $interactionStatuses,
                $metricsDateRange['start'],
                $metricsDateRange['end']
            )
            : [];
        $agreementDailyTotals = $metricsDateRange
            ? $this->teamAcceptedAgreementDailyTotalsByExecutive(
                $supervisorUserId,
                $metricsDateRange['start'],
                $metricsDateRange['end']
            )
            : [];

        return $executives
            ->mapWithKeys(function (User $executive) use (
                $monthlyActivationStarts,
                $monthWeeks,
                $today,
                $currentMonthStart,
                $currentMonthKey,
                $interactionDailyTotals,
                $agreementDailyTotals
            ) {
                $executiveMonthlyStarts = $monthlyActivationStarts[$executive->id] ?? [];
                $currentMonthActivationStart = $executiveMonthlyStarts[$currentMonthKey] ?? null;
                $metrics = $this->emptyWeeklyMonthBreakdown($monthWeeks);

                foreach (self::CARRYOVER_STATUSES as $status) {
                    $dailyGoal = $this->dashboardGoalDailyPerExecutive($status);
                    $inheritedCarryover = max(0, $this->sumExpectedTotalsThroughDate(
                        $executiveMonthlyStarts,
                        $currentMonthStart->copy()->subDay(),
                        $dailyGoal
                    ) - $this->sumActualTotalsThroughDate(
                        $executive->id,
                        $status,
                        $executiveMonthlyStarts,
                        $currentMonthStart->copy()->subDay(),
                        $interactionDailyTotals,
                        $agreementDailyTotals
                    ));
                    $runningCarryover = $inheritedCarryover;

                    foreach ($monthWeeks as $week) {
                        $weekKey = $week['week_key'];
                        $weekStart = $week['start'] ?? null;
                        $weekEnd = $week['end'] ?? null;

                        if (
                            ! $currentMonthActivationStart
                            || ! $weekStart
                            || ! $weekEnd
                            || $currentMonthActivationStart->gt($weekEnd)
                            || $weekStart->gt($today)
                        ) {
                            $metrics[$status][$weekKey] = $this->buildWeeklyMonthBreakdownSnapshot(
                                $weekKey,
                                $week['label'],
                                0,
                                0,
                                $runningCarryover,
                                0,
                                false
                            );

                            continue;
                        }

                        $effectiveStart = $currentMonthActivationStart->gt($weekStart)
                            ? $currentMonthActivationStart->copy()
                            : $weekStart->copy();
                        $baseGoal = $this->countBusinessDaysBetween($effectiveStart, $weekEnd) * $dailyGoal;
                        $actualEnd = $weekEnd->lt($today)
                            ? $weekEnd->copy()
                            : $today->copy();
                        $actual = $this->sumStatusTotalsBetween(
                            $executive->id,
                            $status,
                            $effectiveStart,
                            $actualEnd,
                            $interactionDailyTotals,
                            $agreementDailyTotals,
                            true
                        );
                        $currentTarget = $runningCarryover + $baseGoal;

                        $metrics[$status][$weekKey] = $this->buildWeeklyMonthBreakdownSnapshot(
                            $weekKey,
                            $week['label'],
                            $actual,
                            $currentTarget,
                            $runningCarryover,
                            $baseGoal,
                            true
                        );

                        if ($weekEnd->lte($today)) {
                            $runningCarryover = max(0, $currentTarget - $actual);
                        }
                    }
                }

                return [$executive->id => $metrics];
            })
            ->all();
    }

    private function buildTeamCarryoverMetrics(
        int $supervisorUserId,
        Collection $executives,
        array $monthlyActivationStarts
    ): array {
        $today = now()->startOfDay();
        $monthEnd = $today->copy()->endOfMonth()->endOfDay();
        $yesterday = $today->copy()->subDay();
        $countsTodayAsBusinessDay = $this->isBusinessDay($today);
        $currentMonthKey = $this->monthKeyForDate($today);
        $metricsDateRange = $this->resolveHistoricalMetricsDateRange($monthlyActivationStarts, $monthEnd);
        $interactionStatuses = array_values(array_diff(self::CARRYOVER_STATUSES, ['acuerdo_aceptado']));
        $interactionDailyTotals = $metricsDateRange
            ? $this->teamInteractionDailyTotalsByExecutiveStatus(
                $supervisorUserId,
                $interactionStatuses,
                $metricsDateRange['start'],
                $metricsDateRange['end']
            )
            : [];
        $agreementDailyTotals = $metricsDateRange
            ? $this->teamAcceptedAgreementDailyTotalsByExecutive(
                $supervisorUserId,
                $metricsDateRange['start'],
                $metricsDateRange['end']
            )
            : [];

        return $executives
            ->mapWithKeys(function (User $executive) use (
                $monthlyActivationStarts,
                $today,
                $yesterday,
                $countsTodayAsBusinessDay,
                $currentMonthKey,
                $interactionDailyTotals,
                $agreementDailyTotals
            ) {
                $executiveMonthlyStarts = $monthlyActivationStarts[$executive->id] ?? [];
                $currentMonthActivationStart = $executiveMonthlyStarts[$currentMonthKey] ?? null;
                $hasStartedCurrentMonth = $currentMonthActivationStart && ! $currentMonthActivationStart->gt($today);
                $metrics = [];

                foreach (self::CARRYOVER_STATUSES as $status) {
                    $dailyGoal = $this->dashboardGoalDailyPerExecutive($status);
                    $expectedBeforeToday = $this->sumExpectedTotalsThroughDate(
                        $executiveMonthlyStarts,
                        $yesterday,
                        $dailyGoal
                    );
                    $actualBeforeToday = $this->sumActualTotalsThroughDate(
                        $executive->id,
                        $status,
                        $executiveMonthlyStarts,
                        $yesterday,
                        $interactionDailyTotals,
                        $agreementDailyTotals
                    );
                    $carryoverPending = max(0, $expectedBeforeToday - $actualBeforeToday);
                    $todayActual = $hasStartedCurrentMonth
                        ? $this->sumStatusTotalsBetween(
                            $executive->id,
                            $status,
                            $today,
                            $today,
                            $interactionDailyTotals,
                            $agreementDailyTotals
                        )
                        : 0;

                    $metrics[$status] = $this->buildCarryoverMetricSnapshot(
                        $todayActual,
                        $hasStartedCurrentMonth ? $dailyGoal : 0,
                        $carryoverPending,
                        $carryoverPending + ($hasStartedCurrentMonth && $countsTodayAsBusinessDay ? $dailyGoal : 0)
                    );
                }

                return [$executive->id => $metrics];
            })
            ->all();
    }

    private function buildTeamGoalCompletionFlags(
        int $supervisorUserId,
        Collection $executives,
        array $carryoverMetrics
    ): array {
        $today = now()->startOfDay();
        $todayContactedTotals = $this->teamContactedInteractionTotalsByExecutive(
            $supervisorUserId,
            $today,
            $today->copy()->endOfDay()
        );
        $contactadoDailyGoal = $this->dashboardGoalDailyPerExecutive('contactado');

        return $executives
            ->mapWithKeys(function (User $executive) use ($carryoverMetrics, $todayContactedTotals, $contactadoDailyGoal) {
                $executiveCarryover = $carryoverMetrics[$executive->id] ?? $this->emptyCarryoverMetrics();
                $negotiationTarget = (int) ($executiveCarryover['negociacion']['current_target'] ?? 0);
                $rescheduledTarget = (int) ($executiveCarryover['reprogramado']['current_target'] ?? 0);
                $agreementTarget = (int) ($executiveCarryover['acuerdo_aceptado']['current_target'] ?? 0);

                return [
                    $executive->id => [
                        'contactado' => (int) ($todayContactedTotals[$executive->id] ?? 0) >= $contactadoDailyGoal,
                        'negociacion' => $negotiationTarget > 0
                            && (int) ($executiveCarryover['negociacion']['today_actual'] ?? 0) >= $negotiationTarget,
                        'reprogramado' => $rescheduledTarget > 0
                            && (int) ($executiveCarryover['reprogramado']['today_actual'] ?? 0) >= $rescheduledTarget,
                        'acuerdo_aceptado' => $agreementTarget > 0
                            && (int) ($executiveCarryover['acuerdo_aceptado']['today_actual'] ?? 0) >= $agreementTarget,
                    ],
                ];
            })
            ->all();
    }

    private function resolveExecutiveMonthlyActivationStarts(int $supervisorUserId, Collection $executives): array
    {
        if ($executives->isEmpty()) {
            return [];
        }

        $monthEnd = now()->copy()->endOfMonth()->endOfDay();

        return ExecutiveActivitySession::query()
            ->select(['executive_user_id', 'login_at'])
            ->whereIn('executive_user_id', $executives->pluck('id'))
            ->where('login_at', '<=', $monthEnd)
            ->whereExists(function ($subQuery) use ($supervisorUserId) {
                $subQuery->selectRaw('1')
                    ->from('supervisor_executive as se')
                    ->where('se.supervisor_user_id', $supervisorUserId)
                    ->where(function ($campaignQuery) {
                        $campaignQuery->whereNull('se.campaign_id')
                            ->orWhereColumn('se.campaign_id', 'executive_activity_sessions.campaign_id');
                    })
                    ->whereColumn('se.executive_user_id', 'executive_activity_sessions.executive_user_id');
            })
            ->orderBy('login_at')
            ->get()
            ->groupBy('executive_user_id')
            ->map(function (Collection $sessions) {
                return $sessions
                    ->groupBy(fn ($session) => $this->monthKeyForDate($session->login_at))
                    ->map(function (Collection $monthSessions) {
                        $loginAt = $monthSessions->first()?->login_at;

                        if (! $loginAt instanceof Carbon) {
                            return null;
                        }

                        return $this->normalizeActivationStart($loginAt);
                    })
                    ->filter()
                    ->sortKeys()
                    ->all();
            })
            ->filter()
            ->all();
    }

    private function teamInteractionDailyTotalsByExecutiveStatus(
        int $supervisorUserId,
        array $statuses,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        if (empty($statuses) || $startDate->gt($endDate)) {
            return [];
        }

        return Interaction::query()
            ->selectRaw('user_id as executive_user_id')
            ->selectRaw('status_specific')
            ->selectRaw('DATE(created_at) as activity_date')
            ->selectRaw('COUNT(*) as total')
            ->whereIn('status_specific', $statuses)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereExists(function ($subQuery) use ($supervisorUserId) {
                $subQuery->selectRaw('1')
                    ->from('supervisor_executive as se')
                    ->where('se.supervisor_user_id', $supervisorUserId)
                    ->where(function ($campaignQuery) {
                        $campaignQuery->whereNull('se.campaign_id')
                            ->orWhereColumn('se.campaign_id', 'interactions.campaign_id');
                    })
                    ->whereColumn('se.executive_user_id', 'interactions.user_id');
            })
            ->groupBy('user_id', 'status_specific', DB::raw('DATE(created_at)'))
            ->get()
            ->groupBy('executive_user_id')
            ->map(fn (Collection $executiveRows) => $executiveRows
                ->groupBy('status_specific')
                ->map(fn (Collection $statusRows) => $statusRows
                    ->mapWithKeys(fn ($row) => [(string) $row->activity_date => (int) $row->total])
                    ->all())
                ->all())
            ->all();
    }

    private function teamAcceptedAgreementDailyTotalsByExecutive(
        int $supervisorUserId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        if ($startDate->gt($endDate)) {
            return [];
        }

        return Sale::query()
            ->selectRaw('executive_user_id')
            ->selectRaw('DATE(accepted_at) as activity_date')
            ->selectRaw('COUNT(*) as total')
            ->where('status', Sale::STATUS_ACCEPTED)
            ->whereBetween('accepted_at', [$startDate, $endDate])
            ->whereExists(function ($subQuery) use ($supervisorUserId) {
                $subQuery->selectRaw('1')
                    ->from('supervisor_executive as se')
                    ->where('se.supervisor_user_id', $supervisorUserId)
                    ->where(function ($campaignQuery) {
                        $campaignQuery->whereNull('se.campaign_id')
                            ->orWhereColumn('se.campaign_id', 'sales.campaign_id');
                    })
                    ->whereColumn('se.executive_user_id', 'sales.executive_user_id');
            })
            ->groupBy('executive_user_id', DB::raw('DATE(accepted_at)'))
            ->get()
            ->groupBy('executive_user_id')
            ->map(fn (Collection $rows) => $rows
                ->mapWithKeys(fn ($row) => [(string) $row->activity_date => (int) $row->total])
                ->all())
            ->all();
    }

    private function teamContactedInteractionTotalsByExecutive(
        int $supervisorUserId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        if ($startDate->gt($endDate)) {
            return [];
        }

        return Interaction::query()
            ->selectRaw('user_id as executive_user_id')
            ->selectRaw('COUNT(*) as total')
            ->where('status_general', 'contactado')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereExists(function ($subQuery) use ($supervisorUserId) {
                $subQuery->selectRaw('1')
                    ->from('supervisor_executive as se')
                    ->where('se.supervisor_user_id', $supervisorUserId)
                    ->where(function ($campaignQuery) {
                        $campaignQuery->whereNull('se.campaign_id')
                            ->orWhereColumn('se.campaign_id', 'interactions.campaign_id');
                    })
                    ->whereColumn('se.executive_user_id', 'interactions.user_id');
            })
            ->groupBy('user_id')
            ->pluck('total', 'executive_user_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    private function buildCarryoverMetricSnapshot(
        int $todayActual,
        int $dailyGoal,
        int $carryoverPending,
        int $currentTarget
    ): array {
        return [
            'today_actual' => $todayActual,
            'daily_goal' => $dailyGoal,
            'carryover_pending' => $carryoverPending,
            'current_target' => $currentTarget,
        ];
    }

    private function buildWeeklyCarryoverMetricSnapshot(
        int $currentWeekActual,
        int $weeklyGoal,
        int $carryoverPending
    ): array {
        return [
            'current_week_actual' => $currentWeekActual,
            'weekly_goal' => $weeklyGoal,
            'carryover_pending' => $carryoverPending,
            'current_target' => $weeklyGoal + $carryoverPending,
        ];
    }

    private function emptyCarryoverMetrics(): array
    {
        return collect(self::CARRYOVER_STATUSES)
            ->mapWithKeys(fn (string $status) => [
                $status => $this->inactiveDailyCarryoverMetricSnapshot(),
            ])
            ->all();
    }

    private function emptyWeeklyCarryoverMetrics(): array
    {
        return collect(self::CARRYOVER_STATUSES)
            ->mapWithKeys(fn (string $status) => [
                $status => $this->inactiveWeeklyCarryoverMetricSnapshot(),
            ])
            ->all();
    }

    private function emptyWeeklyMonthBreakdown(array $monthWeeks): array
    {
        return collect(self::CARRYOVER_STATUSES)
            ->mapWithKeys(fn (string $status) => [
                $status => collect($monthWeeks)
                    ->mapWithKeys(fn (array $week) => [
                        $week['week_key'] => $this->buildWeeklyMonthBreakdownSnapshot(
                            $week['week_key'],
                            $week['label'],
                            0,
                            0,
                            0,
                            0,
                            false
                        ),
                    ])
                    ->all(),
            ])
            ->all();
    }

    private function emptyGoalCompletionFlags(): array
    {
        return [
            'contactado' => false,
            'negociacion' => false,
            'reprogramado' => false,
            'acuerdo_aceptado' => false,
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

    private function dashboardGoalDailyPerExecutive(string $metric): int
    {
        $dailyGoals = config('dashboard_goals.daily_per_executive', []);

        if (array_key_exists($metric, $dailyGoals)) {
            return max(0, (int) $dailyGoals[$metric]);
        }

        $weeklyGoal = $this->dashboardGoalWeeklyPerExecutive($metric);

        if ($weeklyGoal % 5 !== 0) {
            throw new \RuntimeException('La meta semanal debe ser divisible entre 5 para calcular arrastre diario: '.$metric);
        }

        return intdiv($weeklyGoal, 5);
    }

    private function normalizeActivationStart(Carbon $loginAt): ?Carbon
    {
        $monthEnd = $loginAt->copy()->endOfMonth()->endOfDay();

        return $this->nextBusinessDay($loginAt->copy()->startOfDay(), $monthEnd->copy()->startOfDay());
    }

    private function nextBusinessDay(Carbon $date, ?Carbon $maxDate = null): ?Carbon
    {
        $cursor = $date->copy()->startOfDay();
        $limit = $maxDate?->copy()->startOfDay();

        while (! $this->isBusinessDay($cursor)) {
            $cursor->addDay();

            if ($limit && $cursor->gt($limit)) {
                return null;
            }
        }

        return $limit && $cursor->gt($limit) ? null : $cursor;
    }

    private function previousBusinessDay(Carbon $date, ?Carbon $minDate = null): ?Carbon
    {
        $cursor = $date->copy()->startOfDay();
        $limit = $minDate?->copy()->startOfDay();

        while (! $this->isBusinessDay($cursor)) {
            $cursor->subDay();

            if ($limit && $cursor->lt($limit)) {
                return null;
            }
        }

        return $limit && $cursor->lt($limit) ? null : $cursor;
    }

    private function resolveWeeklyReferenceDay(Carbon $today): ?Carbon
    {
        if ($this->isBusinessDay($today)) {
            return $today->copy()->startOfDay();
        }

        return $this->previousBusinessDay($today, $today->copy()->startOfMonth());
    }

    private function buildActiveBusinessWeekWindows(?Carbon $activationStart, ?Carbon $referenceDay, Carbon $monthEnd): array
    {
        if (! $activationStart || ! $referenceDay || $activationStart->gt($referenceDay)) {
            return [];
        }

        $monthBusinessEnd = $this->previousBusinessDay($monthEnd, $activationStart);

        if (! $monthBusinessEnd || $activationStart->gt($monthBusinessEnd)) {
            return [];
        }

        $windows = [];
        $windowStart = $activationStart->copy();

        while ($windowStart && $windowStart->lte($referenceDay) && $windowStart->lte($monthBusinessEnd)) {
            $windowEnd = $this->businessWeekEnd($windowStart, $monthBusinessEnd);

            if (! $windowEnd || $windowEnd->lt($windowStart)) {
                break;
            }

            $windows[] = [
                'start' => $windowStart->copy(),
                'end' => $windowEnd->copy(),
                'business_days' => $this->countBusinessDaysBetween($windowStart, $windowEnd),
            ];

            $windowStart = $this->nextBusinessDay($windowEnd->copy()->addDay(), $monthBusinessEnd);
        }

        return $windows;
    }

    private function buildMonthLogicalWeeks(Carbon $referenceDate): array
    {
        $monthStart = $referenceDate->copy()->startOfMonth()->startOfDay();
        $monthEnd = $referenceDate->copy()->endOfMonth()->endOfDay();
        $monthBusinessEnd = $this->previousBusinessDay($monthEnd, $monthStart);
        $nextStart = $monthBusinessEnd
            ? $this->nextBusinessDay($monthStart, $monthBusinessEnd)
            : null;
        $weeks = [];

        for ($weekNumber = 1; $weekNumber <= 4; $weekNumber++) {
            $weekKey = 'week_'.$weekNumber;

            if (! $nextStart || ! $monthBusinessEnd || $nextStart->gt($monthBusinessEnd)) {
                $weeks[] = [
                    'week_key' => $weekKey,
                    'label' => 'Semana '.$weekNumber,
                    'start' => null,
                    'end' => null,
                ];

                continue;
            }

            $weekEnd = $weekNumber < 4
                ? $this->businessWeekEnd($nextStart, $monthBusinessEnd)
                : $monthBusinessEnd->copy();

            $weeks[] = [
                'week_key' => $weekKey,
                'label' => 'Semana '.$weekNumber,
                'start' => $nextStart->copy(),
                'end' => $weekEnd?->copy(),
            ];

            $nextStart = $weekEnd
                ? $this->nextBusinessDay($weekEnd->copy()->addDay(), $monthBusinessEnd)
                : null;
        }

        return $weeks;
    }

    private function businessWeekEnd(Carbon $startDate, Carbon $monthBusinessEnd): ?Carbon
    {
        $cursor = $startDate->copy()->startOfDay();

        while ($cursor->dayOfWeekIso < Carbon::FRIDAY && $cursor->lt($monthBusinessEnd)) {
            $cursor->addDay();
        }

        if ($cursor->gt($monthBusinessEnd)) {
            return $monthBusinessEnd->copy();
        }

        return $this->isBusinessDay($cursor)
            ? $cursor
            : $this->previousBusinessDay($cursor, $startDate);
    }

    private function sumStatusTotalsBetween(
        int $executiveUserId,
        string $status,
        Carbon $startDate,
        Carbon $endDate,
        array $interactionDailyTotals,
        array $agreementDailyTotals,
        bool $businessDaysOnly = false
    ): int {
        if ($startDate->gt($endDate)) {
            return 0;
        }

        $statusTotals = $status === 'acuerdo_aceptado'
            ? ($agreementDailyTotals[$executiveUserId] ?? [])
            : ($interactionDailyTotals[$executiveUserId][$status] ?? []);
        $total = 0;
        $cursor = $startDate->copy()->startOfDay();
        $lastDay = $endDate->copy()->startOfDay();

        while ($cursor->lte($lastDay)) {
            if (! $businessDaysOnly || $this->isBusinessDay($cursor)) {
                $total += (int) ($statusTotals[$cursor->format('Y-m-d')] ?? 0);
            }

            $cursor->addDay();
        }

        return $total;
    }

    private function resolveHistoricalMetricsDateRange(array $monthlyActivationStarts, Carbon $defaultEnd): ?array
    {
        $earliestActivationStart = collect($monthlyActivationStarts)
            ->flatMap(fn (array $executiveMonths) => array_values($executiveMonths))
            ->filter(fn ($date) => $date instanceof Carbon)
            ->sortBy(fn (Carbon $date) => $date->getTimestamp())
            ->first();

        if (! $earliestActivationStart instanceof Carbon) {
            return null;
        }

        return [
            'start' => $earliestActivationStart->copy()->startOfDay(),
            'end' => $defaultEnd->copy()->endOfDay(),
        ];
    }

    private function sumExpectedTotalsThroughDate(array $monthlyStarts, Carbon $endDate, int $dailyGoal): int
    {
        if ($dailyGoal <= 0) {
            return 0;
        }

        $expected = 0;

        foreach ($monthlyStarts as $monthKey => $activationStart) {
            if (! $activationStart instanceof Carbon || $activationStart->gt($endDate)) {
                continue;
            }

            $monthEnd = $this->endOfMonthFromKey($monthKey);
            $effectiveEnd = $monthEnd->lt($endDate) ? $monthEnd : $endDate->copy()->endOfDay();

            if ($activationStart->gt($effectiveEnd)) {
                continue;
            }

            $expected += $this->countBusinessDaysBetween($activationStart, $effectiveEnd) * $dailyGoal;
        }

        return $expected;
    }

    private function sumActualTotalsThroughDate(
        int $executiveUserId,
        string $status,
        array $monthlyStarts,
        Carbon $endDate,
        array $interactionDailyTotals,
        array $agreementDailyTotals
    ): int {
        $actual = 0;

        foreach ($monthlyStarts as $monthKey => $activationStart) {
            if (! $activationStart instanceof Carbon || $activationStart->gt($endDate)) {
                continue;
            }

            $monthEnd = $this->endOfMonthFromKey($monthKey);
            $effectiveEnd = $monthEnd->lt($endDate) ? $monthEnd : $endDate->copy()->endOfDay();

            if ($activationStart->gt($effectiveEnd)) {
                continue;
            }

            $actual += $this->sumStatusTotalsBetween(
                $executiveUserId,
                $status,
                $activationStart,
                $effectiveEnd,
                $interactionDailyTotals,
                $agreementDailyTotals,
                true
            );
        }

        return $actual;
    }

    private function monthKeyForDate(Carbon $date): string
    {
        return $date->format('Y-m');
    }

    private function endOfMonthFromKey(string $monthKey): Carbon
    {
        return Carbon::createFromFormat('Y-m-d', $monthKey.'-01', config('app.timezone'))
            ->endOfMonth()
            ->endOfDay();
    }

    private function inactiveDailyCarryoverMetricSnapshot(): array
    {
        return [
            'today_actual' => 0,
            'daily_goal' => 0,
            'carryover_pending' => 0,
            'current_target' => 0,
        ];
    }

    private function inactiveWeeklyCarryoverMetricSnapshot(): array
    {
        return $this->buildWeeklyCarryoverMetricSnapshot(0, 0, 0);
    }

    private function buildWeeklyMonthBreakdownSnapshot(
        string $weekKey,
        string $label,
        int $actual,
        int $goal,
        int $carryoverPending,
        int $baseGoal,
        bool $isApplicable
    ): array {
        return [
            'week_key' => $weekKey,
            'label' => $label,
            'actual' => $actual,
            'goal' => $goal,
            'base_goal' => $baseGoal,
            'carryover_pending' => $carryoverPending,
            'current_target' => $goal,
            'progress_label' => $isApplicable ? $actual.'/'.$goal : 'N/A',
            'met_goal' => $isApplicable && $goal > 0 && $actual >= $goal,
            'is_applicable' => $isApplicable,
        ];
    }

    private function countBusinessDaysBetween(Carbon $startDate, Carbon $endDate): int
    {
        if ($startDate->gt($endDate)) {
            return 0;
        }

        // Punto de extensión futuro: excluir aquí feriados o días operativos descontados.
        $cursor = $startDate->copy()->startOfDay();
        $lastDay = $endDate->copy()->startOfDay();
        $businessDays = 0;

        while ($cursor->lte($lastDay)) {
            if ($this->isBusinessDay($cursor)) {
                $businessDays++;
            }

            $cursor->addDay();
        }

        return $businessDays;
    }

    private function isBusinessDay(Carbon $date): bool
    {
        return $date->isWeekday();
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

    private function countTeamAcceptedAgreements(int $supervisorUserId, Carbon $startDate, Carbon $endDate): int
    {
        return (int) Sale::query()
            ->where('status', Sale::STATUS_ACCEPTED)
            ->whereBetween('accepted_at', [$startDate, $endDate])
            ->whereExists(function ($subQuery) use ($supervisorUserId) {
                $subQuery->selectRaw('1')
                    ->from('supervisor_executive as se')
                    ->where('se.supervisor_user_id', $supervisorUserId)
                    ->where(function ($campaignQuery) {
                        $campaignQuery->whereNull('se.campaign_id')
                            ->orWhereColumn('se.campaign_id', 'sales.campaign_id');
                    })
                    ->whereColumn('se.executive_user_id', 'sales.executive_user_id');
            })
            ->count();
    }

    private function countTeamInteractionsBySpecificStatus(
        int $supervisorUserId,
        string $specificStatus,
        Carbon $startDate,
        Carbon $endDate
    ): int {
        return (int) Interaction::query()
            ->where('status_specific', $specificStatus)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereExists(function ($subQuery) use ($supervisorUserId) {
                $subQuery->selectRaw('1')
                    ->from('supervisor_executive as se')
                    ->where('se.supervisor_user_id', $supervisorUserId)
                    ->where(function ($campaignQuery) {
                        $campaignQuery->whereNull('se.campaign_id')
                            ->orWhereColumn('se.campaign_id', 'interactions.campaign_id');
                    })
                    ->whereColumn('se.executive_user_id', 'interactions.user_id');
            })
            ->count();
    }

    private function countTeamContactedInteractions(int $supervisorUserId, Carbon $startDate, Carbon $endDate): int
    {
        return (int) Interaction::query()
            ->where('status_general', 'contactado')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereExists(function ($subQuery) use ($supervisorUserId) {
                $subQuery->selectRaw('1')
                    ->from('supervisor_executive as se')
                    ->where('se.supervisor_user_id', $supervisorUserId)
                    ->where(function ($campaignQuery) {
                        $campaignQuery->whereNull('se.campaign_id')
                            ->orWhereColumn('se.campaign_id', 'interactions.campaign_id');
                    })
                    ->whereColumn('se.executive_user_id', 'interactions.user_id');
            })
            ->count();
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

    private function formatLeadDetails(Collection $leads): array
    {
        $commercialStatuses = array_flip(self::POSITIVE_STATUSES);

        return $leads
            ->map(function (Lead $lead) use ($commercialStatuses) {
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
                    'show_commercial_detail' => isset($commercialStatuses[$lead->status_specific]),
                    'commercial_snapshot' => isset($commercialStatuses[$lead->status_specific])
                        ? $this->buildCommercialSnapshot($lead)
                        : null,
                ];
            })
            ->values()
            ->all();
    }
}
