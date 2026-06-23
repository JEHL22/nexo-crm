<?php

namespace App\Http\Controllers;

use App\Models\ExecutiveActivityEvent;
use App\Models\ExecutiveActivitySession;
use App\Models\SupervisorExecutive;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ExecutiveActivityMonitoringController extends Controller
{
    private const MODULE_LABELS = [
        'a_negociar' => 'A negociar',
        'mi_chamba' => 'Mi chamba',
        'mis_ventas' => 'Mis ventas',
        'mi_cobertura' => 'Mi cobertura',
    ];

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

        return view('activity-monitoring.index', [
            'scope' => $scope,
            'filters' => $filters,
            'supervisors' => $supervisors,
            'executives' => $executives,
            'pulseRoute' => $scope === 'supervisor'
                ? route('supervisor.activity-monitoring.pulse', $filters)
                : route('management.activity-monitoring.pulse', $filters),
            'pageRoute' => $scope === 'supervisor'
                ? route('supervisor.activity-monitoring.index')
                : route('management.activity-monitoring.index'),
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
            'board_html' => view('activity-monitoring.partials.board', [
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
        $sessionQuery = ExecutiveActivitySession::query()
            ->with(['executive:id,name', 'supervisor:id,name']);

        $eventQuery = ExecutiveActivityEvent::query()
            ->with(['executive:id,name', 'supervisor:id,name']);

        $sessionQuery = $this->applyScopeFilters($sessionQuery, $viewer, $scope, $filters);
        $eventQuery = $this->applyScopeFilters($eventQuery, $viewer, $scope, $filters);

        $sessions = (clone $sessionQuery)
            ->whereBetween('login_at', [$filters['date_from'].' 00:00:00', $filters['date_to'].' 23:59:59'])
            ->orderByDesc('login_at')
            ->get();

        $activeCutoff = now()->subSeconds(75);
        $activeSessions = (clone $sessionQuery)
            ->whereNull('logout_at')
            ->where('last_seen_at', '>=', $activeCutoff)
            ->orderByDesc('last_seen_at')
            ->get();

        $events = (clone $eventQuery)
            ->whereBetween('occurred_at', [$filters['date_from'].' 00:00:00', $filters['date_to'].' 23:59:59'])
            ->orderByDesc('occurred_at')
            ->limit(120)
            ->get();

        $liveRows = $activeSessions->map(function (ExecutiveActivitySession $session) {
            $currentSeconds = $session->current_page_entered_at
                ? $session->current_page_entered_at->diffInSeconds(now())
                : 0;

            return [
                'executive_name' => $session->executive?->name ?? 'Sin ejecutivo',
                'supervisor_name' => $session->supervisor?->name ?? 'Sin supervisor',
                'module_label' => self::MODULE_LABELS[$session->current_module_name] ?? ucfirst(str_replace('_', ' ', (string) $session->current_module_name)),
                'route_name' => $session->current_route_name ?: '-',
                'page_url' => $session->current_page_url ?: '-',
                'is_focused' => (bool) $session->is_crm_focused,
                'current_seconds' => $currentSeconds,
                'last_seen_label' => optional($session->last_seen_at)->setTimezone(config('app.timezone'))->format('d/m/Y H:i:s') ?: '-',
            ];
        })->values();

        $historicalRows = $sessions
            ->groupBy('executive_user_id')
            ->map(function (Collection $executiveSessions) use ($scope) {
                $first = $executiveSessions->first();
                $moduleTotals = [
                    'a_negociar' => 0,
                    'mi_chamba' => 0,
                    'mis_ventas' => 0,
                    'mi_cobertura' => 0,
                ];

                foreach ($executiveSessions as $session) {
                    $sessionModuleTotals = $this->resolveModuleTotalsForSession($session);
                    foreach ($sessionModuleTotals as $module => $seconds) {
                        $moduleTotals[$module] += $seconds;
                    }
                }

                $totalSeconds = $executiveSessions->sum(fn (ExecutiveActivitySession $session) => $session->total_session_seconds);
                $blurredSeconds = $executiveSessions->sum(fn (ExecutiveActivitySession $session) => (int) $session->total_blurred_seconds);

                return [
                    'executive_name' => $first?->executive?->name ?? 'Sin ejecutivo',
                    'supervisor_name' => $first?->supervisor?->name ?? 'Sin supervisor',
                    'supervisor_label' => $scope === 'management' ? ($first?->supervisor?->name ?? 'Sin supervisor') : null,
                    'session_count' => $executiveSessions->count(),
                    'total_seconds' => $totalSeconds,
                    'blurred_seconds' => $blurredSeconds,
                    'focused_seconds' => max(0, $totalSeconds - $blurredSeconds),
                    'module_totals' => $moduleTotals,
                ];
            })
            ->sortBy('executive_name')
            ->values();

        $recentEvents = $events->map(function (ExecutiveActivityEvent $event) use ($scope) {
            return [
                'executive_user_id' => $event->executive_user_id,
                'executive_name' => $event->executive?->name ?? 'Sin ejecutivo',
                'supervisor_name' => $scope === 'management' ? ($event->supervisor?->name ?? 'Sin supervisor') : null,
                'label' => $event->label ?: $this->formatEventType($event->event_type),
                'module_label' => self::MODULE_LABELS[$event->module_name] ?? ucfirst(str_replace('_', ' ', (string) $event->module_name)),
                'route_name' => $event->route_name ?: '-',
                'page_url' => $event->page_url ?: '-',
                'occurred_at_label' => optional($event->occurred_at)->setTimezone(config('app.timezone'))->format('d/m/Y H:i:s') ?: '-',
            ];
        })->values();

        $recentEventGroups = $recentEvents
            ->groupBy(fn (array $event) => (string) ($event['executive_user_id'] ?: 'no-executive'))
            ->map(function (Collection $executiveEvents) {
                $first = $executiveEvents->first();

                return [
                    'group_key' => 'executive-'.($first['executive_user_id'] ?: 'no-executive'),
                    'executive_name' => $first['executive_name'],
                    'supervisor_name' => $first['supervisor_name'] ?? null,
                    'event_count' => $executiveEvents->count(),
                    'latest_event_label' => $first['occurred_at_label'] ?? '-',
                    'events' => $executiveEvents->values()->all(),
                ];
            })
            ->sortBy('executive_name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $summaryCards = [
            ['label' => 'Ejecutivos activos', 'value' => $activeSessions->pluck('executive_user_id')->unique()->count()],
            ['label' => 'Tiempo total CRM', 'value' => $this->formatDuration($sessions->sum(fn (ExecutiveActivitySession $session) => $session->total_session_seconds))],
            ['label' => 'Tiempo fuera del CRM', 'value' => $this->formatDuration($sessions->sum(fn (ExecutiveActivitySession $session) => (int) $session->total_blurred_seconds))],
            ['label' => 'Eventos registrados', 'value' => number_format($events->count())],
        ];

        return [
            'summary_cards' => $summaryCards,
            'live_rows' => $liveRows,
            'historical_rows' => $historicalRows,
            'recent_events' => $recentEvents,
            'recent_event_groups' => $recentEventGroups,
        ];
    }

    private function applyScopeFilters($query, User $viewer, string $scope, array $filters)
    {
        if ($scope === 'supervisor') {
            $executiveIds = SupervisorExecutive::query()
                ->where('supervisor_user_id', $viewer->id)
                ->pluck('executive_user_id');

            $query->whereIn('executive_user_id', $executiveIds);
        } elseif ($filters['supervisor_user_id']) {
            $query->where('supervisor_user_id', $filters['supervisor_user_id']);
        }

        if ($filters['executive_user_id']) {
            $query->where('executive_user_id', $filters['executive_user_id']);
        }

        return $query;
    }

    private function resolveModuleTotalsForSession(ExecutiveActivitySession $session): array
    {
        $totals = [
            'a_negociar' => 0,
            'mi_chamba' => 0,
            'mis_ventas' => 0,
            'mi_cobertura' => 0,
        ];

        $events = $session->events()
            ->whereIn('event_type', ['session_started', 'page_view', 'logout'])
            ->orderBy('occurred_at')
            ->get();

        $currentModule = null;
        $lastAt = $session->login_at;

        foreach ($events as $event) {
            if ($currentModule && $lastAt) {
                $seconds = max(0, $lastAt->diffInSeconds($event->occurred_at));
                $totals[$currentModule] = ($totals[$currentModule] ?? 0) + $seconds;
            }

            if (in_array($event->event_type, ['session_started', 'page_view'], true)) {
                $currentModule = $event->module_name;
                $lastAt = $event->occurred_at;
                continue;
            }

            $lastAt = $event->occurred_at;
        }

        if ($currentModule && $lastAt) {
            $end = $session->logout_at ?: $session->last_seen_at;
            if ($end) {
                $totals[$currentModule] = ($totals[$currentModule] ?? 0) + max(0, $lastAt->diffInSeconds($end));
            }
        }

        return $totals;
    }

    private function formatDuration(int $seconds): string
    {
        $safe = max(0, $seconds);
        return gmdate($safe >= 3600 ? 'H:i:s' : 'i:s', $safe);
    }

    private function formatEventType(string $eventType): string
    {
        return match ($eventType) {
            'session_started' => 'Inicio de sesión',
            'page_view' => 'Cambio de pantalla',
            'focus_lost' => 'Salió del CRM',
            'focus_regained' => 'Regresó al CRM',
            'page_action' => 'Acción registrada',
            'logout' => 'Cierre de sesión',
            default => ucfirst(str_replace('_', ' ', $eventType)),
        };
    }
}
