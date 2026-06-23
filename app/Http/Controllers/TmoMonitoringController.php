<?php

namespace App\Http\Controllers;

use App\Models\Interaction;
use App\Models\LeadWorkSession;
use App\Models\Sale;
use App\Models\SupervisorExecutive;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TmoMonitoringController extends Controller
{
    public function supervisor(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('Supervisor'), 403);

        return $this->renderPage($request, 'supervisor');
    }

    public function supervisorPulse(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('Supervisor'), 403);

        return $this->renderPulse($request, 'supervisor');
    }

    public function management(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('Gerencia'), 403);

        return $this->renderPage($request, 'management');
    }

    public function managementPulse(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('Gerencia'), 403);

        return $this->renderPulse($request, 'management');
    }

    private function renderPage(Request $request, string $scope): View
    {
        [$filters, $supervisors, $executives] = $this->resolveFilters($request, $scope);
        $payload = $this->buildPayload($request->user(), $scope, $filters);

        return view('monitoring.index', [
            'scope' => $scope,
            'filters' => $filters,
            'supervisors' => $supervisors,
            'executives' => $executives,
            'pulseRoute' => $scope === 'supervisor'
                ? route('supervisor.tmo.pulse', $filters)
                : route('management.tmo.pulse', $filters),
            'pageRoute' => $scope === 'supervisor'
                ? route('supervisor.tmo.index')
                : route('management.tmo.index'),
            'payload' => $payload,
        ]);
    }

    private function renderPulse(Request $request, string $scope): JsonResponse
    {
        [$filters] = $this->resolveFilters($request, $scope);
        $payload = $this->buildPayload($request->user(), $scope, $filters);

        return response()->json([
            'ok' => true,
            'updated_at_label' => now()->setTimezone(config('app.timezone'))->format('d/m/Y H:i:s'),
            'board_html' => view('monitoring.partials.board', [
                'scope' => $scope,
                'payload' => $payload,
            ])->render(),
        ]);
    }

    private function resolveFilters(Request $request, string $scope): array
    {
        $dateFrom = $request->filled('date_from')
            ? Carbon::parse((string) $request->input('date_from'))->startOfDay()
            : now()->startOfDay();
        $dateTo = $request->filled('date_to')
            ? Carbon::parse((string) $request->input('date_to'))->endOfDay()
            : now()->endOfDay();

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        $filters = [
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'executive_user_id' => $request->integer('executive_user_id') ?: null,
            'supervisor_user_id' => $scope === 'management' ? ($request->integer('supervisor_user_id') ?: null) : null,
        ];

        $supervisors = $scope === 'management'
            ? User::role('Supervisor')->orderBy('name')->get(['id', 'name'])
            : collect();

        $executiveIds = $scope === 'management'
            ? SupervisorExecutive::query()->distinct()->pluck('executive_user_id')
            : SupervisorExecutive::query()
                ->where('supervisor_user_id', $request->user()->id)
                ->distinct()
                ->pluck('executive_user_id');

        $executives = User::query()
            ->whereIn('id', $executiveIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        return [$filters, $supervisors, $executives];
    }

    private function buildPayload(User $viewer, string $scope, array $filters): array
    {
        $activeCutoff = now()->subSeconds(75);

        $sessionQuery = LeadWorkSession::query()
            ->with([
                'lead:id,ruc,business_name,representative_name,status_specific,status_final',
                'executive:id,name',
                'supervisor:id,name',
            ]);

        $sessionQuery = $this->applyScopeFilters($sessionQuery, $viewer, $scope, $filters);
        $executiveRoster = $this->buildExecutiveRoster($viewer, $scope, $filters);

        $allSessions = (clone $sessionQuery)
            ->whereBetween('started_at', [$filters['date_from'].' 00:00:00', $filters['date_to'].' 23:59:59'])
            ->orderByDesc('started_at')
            ->get();

        $activeSessions = (clone $sessionQuery)
            ->whereNull('ended_at')
            ->where('last_heartbeat_at', '>=', $activeCutoff)
            ->orderByDesc('last_heartbeat_at')
            ->get()
            ->map(function (LeadWorkSession $session) {
                $elapsedSeconds = $session->started_at?->diffInSeconds(now()) ?? 0;

                return [
                    'executive_user_id' => $session->executive_user_id,
                    'lead_id' => $session->lead_id,
                    'lead_label' => $session->lead?->business_name ?: $session->lead?->representative_name ?: 'Lead sin nombre',
                    'lead_ruc' => $session->lead?->ruc ?: '-',
                    'lead_status' => $session->lead?->status_specific ?: '-',
                    'executive_name' => $session->executive?->name ?: 'Sin ejecutivo',
                    'supervisor_name' => $session->supervisor?->name ?: 'Sin supervisor',
                    'module_label' => $session->module_name === 'a_negociar' ? 'A negociar' : 'Mi chamba',
                    'started_at_label' => $session->started_at?->setTimezone(config('app.timezone'))->format('d/m/Y H:i:s'),
                    'elapsed_seconds' => $elapsedSeconds,
                    'is_over_threshold' => $elapsedSeconds > 60,
                ];
            })
            ->values();

        $activeByExecutive = $activeSessions->keyBy('executive_user_id');
        $executiveStatuses = $executiveRoster
            ->map(function (array $executive) use ($activeByExecutive) {
                $activeSession = $activeByExecutive->get($executive['executive_user_id']);

                return [
                    'executive_user_id' => $executive['executive_user_id'],
                    'executive_name' => $executive['executive_name'],
                    'supervisor_name' => $executive['supervisor_name'],
                    'is_active' => (bool) $activeSession,
                    'is_over_threshold' => (bool) ($activeSession['is_over_threshold'] ?? false),
                    'module_label' => $activeSession['module_label'] ?? 'Sin gestión activa',
                    'lead_label' => $activeSession['lead_label'] ?? 'Sin lead en curso',
                    'lead_ruc' => $activeSession['lead_ruc'] ?? '-',
                    'lead_status' => $activeSession['lead_status'] ?? '-',
                    'elapsed_seconds' => (int) ($activeSession['elapsed_seconds'] ?? 0),
                    'started_at_label' => $activeSession['started_at_label'] ?? 'Sin sesión',
                ];
            })
            ->sortBy([
                [fn (array $row) => $row['is_active'] ? 0 : 1, 'asc'],
                [fn (array $row) => $row['is_over_threshold'] ? 0 : 1, 'asc'],
                ['executive_name', 'asc'],
            ])
            ->values();

        $historicalRows = $this->buildHistoricalRows($allSessions, $scope);
        $agreementRows = $this->buildAgreementRows($viewer, $scope, $filters);

        $summaryCards = [
            ['label' => 'Sesiones activas', 'value' => $activeSessions->count()],
            ['label' => 'Ejecutivos monitoreados', 'value' => $executiveRoster->count()],
            ['label' => 'Ejecutivos en alerta > 1 min', 'value' => $activeSessions->where('is_over_threshold', true)->pluck('executive_name')->unique()->count()],
            ['label' => 'TMO promedio a acuerdo', 'value' => $this->formatDuration((int) round($agreementRows->avg('tmo_to_agreement_seconds') ?: 0))],
        ];

        return [
            'summary_cards' => $summaryCards,
            'active_sessions' => $activeSessions,
            'executive_statuses' => $executiveStatuses,
            'historical_rows' => $historicalRows,
            'agreement_rows' => $agreementRows,
        ];
    }

    private function applyScopeFilters($query, User $viewer, string $scope, array $filters)
    {
        if ($scope === 'supervisor') {
            $executiveIds = SupervisorExecutive::query()
                ->where('supervisor_user_id', $viewer->id)
                ->pluck('executive_user_id');

            $query->whereIn('executive_user_id', $executiveIds);
        } else {
            if ($filters['supervisor_user_id']) {
                $query->where('supervisor_user_id', $filters['supervisor_user_id']);
            }
        }

        if ($filters['executive_user_id']) {
            $query->where('executive_user_id', $filters['executive_user_id']);
        }

        return $query;
    }

    private function buildHistoricalRows(Collection $sessions, string $scope): Collection
    {
        return $sessions
            ->groupBy('executive_user_id')
            ->map(function (Collection $executiveSessions) use ($scope) {
                $executive = $executiveSessions->first()?->executive;
                $supervisor = $executiveSessions->first()?->supervisor;
                $totalSeconds = $executiveSessions->sum(fn (LeadWorkSession $session) => $session->duration_seconds);
                $uniqueLeadCount = $executiveSessions->pluck('lead_id')->unique()->count();
                $avgSecondsPerLead = $uniqueLeadCount > 0 ? (int) round($totalSeconds / $uniqueLeadCount) : 0;
                $aNegociarSessions = $executiveSessions->where('module_name', 'a_negociar');
                $myWorkSessions = $executiveSessions->where('module_name', 'mi_chamba');
                $maxLeadSeconds = $executiveSessions
                    ->groupBy('lead_id')
                    ->map(fn (Collection $leadSessions) => $leadSessions->sum(fn (LeadWorkSession $session) => $session->duration_seconds))
                    ->max() ?: 0;

                return [
                    'executive_name' => $executive?->name ?: 'Sin ejecutivo',
                    'supervisor_name' => $supervisor?->name ?: 'Sin supervisor',
                    'supervisor_label' => $scope === 'management' ? ($supervisor?->name ?: 'Sin supervisor') : null,
                    'session_count' => $executiveSessions->count(),
                    'lead_count' => $uniqueLeadCount,
                    'total_seconds' => $totalSeconds,
                    'a_negociar_lead_count' => $aNegociarSessions->pluck('lead_id')->unique()->count(),
                    'a_negociar_total_seconds' => $aNegociarSessions->sum(fn (LeadWorkSession $session) => $session->duration_seconds),
                    'mi_chamba_lead_count' => $myWorkSessions->pluck('lead_id')->unique()->count(),
                    'mi_chamba_total_seconds' => $myWorkSessions->sum(fn (LeadWorkSession $session) => $session->duration_seconds),
                    'avg_seconds_per_lead' => $avgSecondsPerLead,
                    'max_lead_seconds' => $maxLeadSeconds,
                ];
            })
            ->sortBy([
                ['avg_seconds_per_lead', 'desc'],
                ['executive_name', 'asc'],
            ])
            ->values();
    }

    private function buildExecutiveRoster(User $viewer, string $scope, array $filters): Collection
    {
        $query = SupervisorExecutive::query()
            ->join('users as executives', 'executives.id', '=', 'supervisor_executive.executive_user_id')
            ->leftJoin('users as supervisors', 'supervisors.id', '=', 'supervisor_executive.supervisor_user_id')
            ->select([
                'executives.id as executive_user_id',
                'executives.name as executive_name',
                'supervisors.name as supervisor_name',
            ]);

        if ($scope === 'supervisor') {
            $query->where('supervisor_executive.supervisor_user_id', $viewer->id);
        } elseif ($filters['supervisor_user_id']) {
            $query->where('supervisor_executive.supervisor_user_id', $filters['supervisor_user_id']);
        }

        if ($filters['executive_user_id']) {
            $query->where('supervisor_executive.executive_user_id', $filters['executive_user_id']);
        }

        return $query
            ->orderBy('executives.name')
            ->distinct()
            ->get()
            ->map(fn ($row) => [
                'executive_user_id' => (int) $row->executive_user_id,
                'executive_name' => $row->executive_name,
                'supervisor_name' => $row->supervisor_name ?: 'Sin supervisor',
            ])
            ->values();
    }

    private function buildAgreementRows(User $viewer, string $scope, array $filters): Collection
    {
        $query = Sale::query()
            ->with(['executive:id,name', 'supervisor:id,name', 'lead:id,ruc,business_name,representative_name'])
            ->whereNotNull('accepted_at')
            ->whereNotNull('tmo_to_agreement_seconds')
            ->whereBetween('accepted_at', [$filters['date_from'].' 00:00:00', $filters['date_to'].' 23:59:59']);

        if ($scope === 'supervisor') {
            $query->where('supervisor_user_id', $viewer->id);
        } elseif ($filters['supervisor_user_id']) {
            $query->where('supervisor_user_id', $filters['supervisor_user_id']);
        }

        if ($filters['executive_user_id']) {
            $query->where('executive_user_id', $filters['executive_user_id']);
        }

        return $query
            ->latest('accepted_at')
            ->limit(20)
            ->get()
            ->map(function (Sale $sale) use ($scope) {
                $timeline = $this->buildAgreementTimeline($sale);

                return [
                    'sale_id' => $sale->id,
                    'lead_label' => $sale->customer_business_name ?: $sale->lead?->business_name ?: $sale->lead?->representative_name ?: 'Lead sin nombre',
                    'lead_ruc' => $sale->customer_ruc ?: $sale->lead?->ruc ?: '-',
                    'executive_name' => $sale->executive?->name ?: 'Sin ejecutivo',
                    'supervisor_label' => $scope === 'management' ? ($sale->supervisor?->name ?: 'Sin supervisor') : null,
                    'accepted_at_label' => $sale->accepted_at?->setTimezone(config('app.timezone'))->format('d/m/Y H:i'),
                    'tmo_to_agreement_seconds' => $sale->tmo_to_agreement_seconds,
                    'tmo_to_agreement_label' => $this->formatDuration((int) ($sale->tmo_to_agreement_seconds ?? 0)),
                    'timeline' => $timeline,
                ];
            })
            ->values();
    }

    private function buildAgreementTimeline(Sale $sale): array
    {
        if (!$sale->accepted_at || !$sale->lead_id || !$sale->executive_user_id) {
            return [];
        }

        $interactions = Interaction::query()
            ->where('lead_id', $sale->lead_id)
            ->where('user_id', $sale->executive_user_id)
            ->where('created_at', '<=', $sale->accepted_at)
            ->orderBy('created_at')
            ->get(['status_specific', 'interaction_type', 'created_at']);

        if ($interactions->isEmpty()) {
            return [];
        }

        $firstTimestamp = $interactions->first()->created_at;

        return $interactions->values()->map(function (Interaction $interaction, int $index) use ($interactions, $sale, $firstTimestamp) {
            $nextTimestamp = optional($interactions->get($index + 1))->created_at ?: $sale->accepted_at;
            $durationSeconds = max(0, $interaction->created_at->diffInSeconds($nextTimestamp));
            $cumulativeSeconds = max(0, $firstTimestamp->diffInSeconds($interaction->created_at));

            return [
                'status_label' => $this->statusLabel($interaction->status_specific),
                'module_label' => $interaction->interaction_type === 'a_negociar' ? 'A negociar' : ($interaction->interaction_type === 'edicion_mi_chamba' ? 'Mi chamba' : 'Acuerdo aceptado'),
                'entered_at_label' => $interaction->created_at->setTimezone(config('app.timezone'))->format('d/m/Y H:i'),
                'duration_label' => $this->formatDuration($durationSeconds),
                'cumulative_label' => $this->formatDuration($cumulativeSeconds),
            ];
        })->all();
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'reprogramado' => 'Reprogramado',
            'negociacion' => 'Negociación',
            'acuerdo_aceptado' => 'Acuerdo aceptado',
            'no_desea' => 'No desea',
            default => $status ?: '-',
        };
    }

    private function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%02dh %02dm %02ds', $hours, $minutes, $remainingSeconds);
        }

        return sprintf('%02dm %02ds', $minutes, $remainingSeconds);
    }
}
