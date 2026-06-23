<?php

namespace App\Http\Controllers;

use App\Models\ExecutiveActivityEvent;
use App\Models\ExecutiveActivitySession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExecutiveActivitySessionController extends Controller
{
    private const ALLOWED_MODULES = ['a_negociar', 'mi_chamba', 'mis_ventas', 'mi_cobertura'];
    private const ALLOWED_EVENT_TYPES = ['focus_lost', 'focus_regained', 'page_action'];

    public function ensure(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('Ejecutivo'), 403);

        $validated = $request->validate([
            'module_name' => ['required', 'string', 'in:a_negociar,mi_chamba,mis_ventas,mi_cobertura'],
            'route_name' => ['nullable', 'string', 'max:120'],
            'page_url' => ['nullable', 'string', 'max:255'],
            'is_focused' => ['nullable', 'boolean'],
        ]);

        $now = now();
        $supervisorUserId = DB::table('supervisor_executive')
            ->where('executive_user_id', $user->id)
            ->value('supervisor_user_id');
        $campaignId = $user->campaigns()->value('campaigns.id');

        $session = ExecutiveActivitySession::query()
            ->where('executive_user_id', $user->id)
            ->whereNull('logout_at')
            ->latest('login_at')
            ->first();

        $isFocused = array_key_exists('is_focused', $validated)
            ? (bool) $validated['is_focused']
            : true;

        if (!$session) {
            $session = ExecutiveActivitySession::create([
                'executive_user_id' => $user->id,
                'supervisor_user_id' => $supervisorUserId,
                'campaign_id' => $campaignId,
                'login_at' => $now,
                'last_seen_at' => $now,
                'current_module_name' => $validated['module_name'],
                'current_route_name' => $validated['route_name'] ?? null,
                'current_page_url' => $validated['page_url'] ?? null,
                'current_page_entered_at' => $now,
                'is_crm_focused' => $isFocused,
                'last_focus_change_at' => $now,
            ]);

            $this->storeEvent($session, 'session_started', 'Inicio de sesión CRM', $validated['module_name'], $validated['route_name'] ?? null, $validated['page_url'] ?? null);
            $this->storeEvent($session, 'page_view', 'Ingreso a pantalla', $validated['module_name'], $validated['route_name'] ?? null, $validated['page_url'] ?? null);
        } else {
            $hasChangedPage = $session->current_module_name !== $validated['module_name']
                || $session->current_route_name !== ($validated['route_name'] ?? null)
                || $session->current_page_url !== ($validated['page_url'] ?? null);

            $totalBlurredSeconds = (int) $session->total_blurred_seconds;
            $lastFocusChangeAt = $session->last_focus_change_at;

            if ($isFocused && !$session->is_crm_focused && $lastFocusChangeAt) {
                $totalBlurredSeconds += max(0, $lastFocusChangeAt->diffInSeconds($now));
                $lastFocusChangeAt = $now;
            } elseif (!$isFocused && $session->is_crm_focused) {
                $lastFocusChangeAt = $now;
            }

            $session->forceFill([
                'supervisor_user_id' => $supervisorUserId,
                'campaign_id' => $campaignId,
                'last_seen_at' => $now,
                'current_module_name' => $validated['module_name'],
                'current_route_name' => $validated['route_name'] ?? null,
                'current_page_url' => $validated['page_url'] ?? null,
                'current_page_entered_at' => $hasChangedPage ? $now : ($session->current_page_entered_at ?: $now),
                'is_crm_focused' => $isFocused,
                'last_focus_change_at' => $lastFocusChangeAt,
                'total_blurred_seconds' => $totalBlurredSeconds,
            ])->save();

            if ($hasChangedPage) {
                $this->storeEvent($session, 'page_view', 'Cambio de pantalla', $validated['module_name'], $validated['route_name'] ?? null, $validated['page_url'] ?? null);
            }
        }

        return response()->json([
            'ok' => true,
            'session_id' => $session->id,
            'login_at' => $session->login_at?->toIso8601String(),
        ]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('Ejecutivo'), 403);

        $validated = $request->validate([
            'module_name' => ['required', 'string', 'in:a_negociar,mi_chamba,mis_ventas,mi_cobertura'],
            'route_name' => ['nullable', 'string', 'max:120'],
            'page_url' => ['nullable', 'string', 'max:255'],
            'is_focused' => ['nullable', 'boolean'],
        ]);

        $session = ExecutiveActivitySession::query()
            ->where('executive_user_id', $user->id)
            ->whereNull('logout_at')
            ->latest('login_at')
            ->firstOrFail();

        $now = now();
        $isFocused = array_key_exists('is_focused', $validated)
            ? (bool) $validated['is_focused']
            : (bool) $session->is_crm_focused;

        $hasChangedPage = $session->current_module_name !== $validated['module_name']
            || $session->current_route_name !== ($validated['route_name'] ?? null)
            || $session->current_page_url !== ($validated['page_url'] ?? null);

        $totalBlurredSeconds = (int) $session->total_blurred_seconds;
        $lastFocusChangeAt = $session->last_focus_change_at;

        if ($isFocused && !$session->is_crm_focused && $lastFocusChangeAt) {
            $totalBlurredSeconds += max(0, $lastFocusChangeAt->diffInSeconds($now));
            $lastFocusChangeAt = $now;
        } elseif (!$isFocused && $session->is_crm_focused) {
            $lastFocusChangeAt = $now;
        }

        $session->forceFill([
            'last_seen_at' => $now,
            'current_module_name' => $validated['module_name'],
            'current_route_name' => $validated['route_name'] ?? null,
            'current_page_url' => $validated['page_url'] ?? null,
            'current_page_entered_at' => $hasChangedPage ? $now : $session->current_page_entered_at,
            'is_crm_focused' => $isFocused,
            'last_focus_change_at' => $lastFocusChangeAt,
            'total_blurred_seconds' => $totalBlurredSeconds,
        ])->save();

        if ($hasChangedPage) {
            $this->storeEvent($session, 'page_view', 'Cambio de pantalla', $validated['module_name'], $validated['route_name'] ?? null, $validated['page_url'] ?? null);
        }

        return response()->json(['ok' => true]);
    }

    public function event(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('Ejecutivo'), 403);

        $validated = $request->validate([
            'event_type' => ['required', 'string', 'in:focus_lost,focus_regained,page_action'],
            'label' => ['nullable', 'string', 'max:160'],
            'module_name' => ['required', 'string', 'in:a_negociar,mi_chamba,mis_ventas,mi_cobertura'],
            'route_name' => ['nullable', 'string', 'max:120'],
            'page_url' => ['nullable', 'string', 'max:255'],
            'meta' => ['nullable', 'array'],
        ]);

        $session = ExecutiveActivitySession::query()
            ->where('executive_user_id', $user->id)
            ->whereNull('logout_at')
            ->latest('login_at')
            ->firstOrFail();

        $now = now();

        if ($validated['event_type'] === 'focus_lost' && $session->is_crm_focused) {
            $session->forceFill([
                'is_crm_focused' => false,
                'last_focus_change_at' => $now,
                'last_seen_at' => $now,
            ])->save();
        }

        if ($validated['event_type'] === 'focus_regained' && !$session->is_crm_focused) {
            $blurredSeconds = $session->last_focus_change_at
                ? max(0, $session->last_focus_change_at->diffInSeconds($now))
                : 0;

            $session->forceFill([
                'is_crm_focused' => true,
                'last_focus_change_at' => $now,
                'last_seen_at' => $now,
                'total_blurred_seconds' => (int) $session->total_blurred_seconds + $blurredSeconds,
            ])->save();
        }

        if ($validated['event_type'] === 'page_action') {
            $session->forceFill([
                'last_seen_at' => $now,
                'current_module_name' => $validated['module_name'],
                'current_route_name' => $validated['route_name'] ?? null,
                'current_page_url' => $validated['page_url'] ?? null,
            ])->save();
        }

        $this->storeEvent(
            $session,
            $validated['event_type'],
            $validated['label'] ?? null,
            $validated['module_name'],
            $validated['route_name'] ?? null,
            $validated['page_url'] ?? null,
            $validated['meta'] ?? null
        );

        return response()->json(['ok' => true]);
    }

    public function stop(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('Ejecutivo'), 403);

        $session = ExecutiveActivitySession::query()
            ->where('executive_user_id', $user->id)
            ->whereNull('logout_at')
            ->latest('login_at')
            ->first();

        if ($session) {
            $this->closeSession($session, 'Cierre de sesión CRM');
        }

        return response()->json(['ok' => true]);
    }

    public static function closeOpenSessionsForExecutive(int $userId, ?string $label = null): void
    {
        ExecutiveActivitySession::query()
            ->where('executive_user_id', $userId)
            ->whereNull('logout_at')
            ->get()
            ->each(function (ExecutiveActivitySession $session) use ($label) {
                app(self::class)->closeSession($session, $label ?: 'Cierre de sesión CRM');
            });
    }

    private function closeSession(ExecutiveActivitySession $session, string $label): void
    {
        $now = now();
        $totalBlurredSeconds = (int) $session->total_blurred_seconds;

        if (!$session->is_crm_focused && $session->last_focus_change_at) {
            $totalBlurredSeconds += max(0, $session->last_focus_change_at->diffInSeconds($now));
        }

        $session->forceFill([
            'last_seen_at' => $now,
            'logout_at' => $now,
            'total_blurred_seconds' => $totalBlurredSeconds,
        ])->save();

        $this->storeEvent(
            $session,
            'logout',
            $label,
            $session->current_module_name,
            $session->current_route_name,
            $session->current_page_url
        );
    }

    private function storeEvent(
        ExecutiveActivitySession $session,
        string $eventType,
        ?string $label,
        ?string $moduleName,
        ?string $routeName,
        ?string $pageUrl,
        ?array $meta = null
    ): void {
        ExecutiveActivityEvent::create([
            'session_id' => $session->id,
            'executive_user_id' => $session->executive_user_id,
            'supervisor_user_id' => $session->supervisor_user_id,
            'event_type' => $eventType,
            'label' => $label,
            'module_name' => $moduleName,
            'route_name' => $routeName,
            'page_url' => $pageUrl,
            'meta' => $meta,
            'occurred_at' => now(),
        ]);
    }
}
