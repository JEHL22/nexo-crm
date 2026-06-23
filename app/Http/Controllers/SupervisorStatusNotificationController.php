<?php

namespace App\Http\Controllers;

use App\Models\SupervisorStatusNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupervisorStatusNotificationController extends Controller
{
    private const STATUS_LABELS = [
        'en_evaluacion' => 'En evaluación',
        'pendiente_validacion' => 'En evaluación',
        'observado' => 'En evaluación',
        'activo' => 'Activo',
        'aprobado' => 'Activo',
        'rechazado' => 'Rechazado',
        'entregado' => 'Entregado',
    ];

    public function pulse(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user && $user->hasRole('Supervisor'), 403);

        return response()->json([
            'ok' => true,
            'unread_count' => SupervisorStatusNotification::query()
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->count(),
            'notifications' => $this->notificationList($user->id),
            'recent_toast_notifications' => $this->recentToastNotifications($user->id),
            'server_now_iso' => now()->setTimezone(config('app.timezone'))->toIso8601String(),
        ]);
    }

    public function open(Request $request, SupervisorStatusNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()?->id, 403);

        if ($notification->read_at === null) {
            $notification->forceFill([
                'read_at' => now(),
            ])->save();
        }

        return redirect()->route('supervisor.agreements.index', [
            'traceability_sale' => $notification->sale_id,
        ]);
    }

    public function markAsRead(Request $request, SupervisorStatusNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === $request->user()?->id, 403);

        if ($notification->read_at === null) {
            $notification->forceFill([
                'read_at' => now(),
            ])->save();
        }

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        SupervisorStatusNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return back();
    }

    private function notificationList(int $userId): array
    {
        return SupervisorStatusNotification::query()
            ->where('user_id', $userId)
            ->orderByRaw('read_at IS NULL DESC')
            ->orderByDesc('notified_at')
            ->limit(8)
            ->get()
            ->map(fn (SupervisorStatusNotification $notification) => $this->formatNotification($notification))
            ->values()
            ->all();
    }

    private function recentToastNotifications(int $userId): array
    {
        return SupervisorStatusNotification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->where('notified_at', '>=', now()->subSeconds(50))
            ->orderByDesc('notified_at')
            ->limit(4)
            ->get()
            ->map(function (SupervisorStatusNotification $notification) {
                $formatted = $this->formatNotification($notification);

                return [
                    'id' => $formatted['id'],
                    'title' => $formatted['title'],
                    'message' => $formatted['message'],
                    'status_label' => $formatted['status_label'],
                    'open_url' => $formatted['open_url'],
                    'storage_key' => $formatted['storage_key'],
                ];
            })
            ->values()
            ->all();
    }

    private function formatNotification(SupervisorStatusNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'read_at' => $notification->read_at?->setTimezone(config('app.timezone'))->toIso8601String(),
            'status_label' => self::STATUS_LABELS[$notification->current_status] ?? ucfirst(str_replace('_', ' ', $notification->current_status)),
            'notified_at_label' => $notification->notified_at?->setTimezone(config('app.timezone'))->format('d/m/Y H:i'),
            'mark_read_url' => route('supervisor.status-notifications.read', $notification),
            'open_url' => route('supervisor.status-notifications.open', $notification),
            'storage_key' => 'supervisor-status-notification-'.$notification->id,
        ];
    }
}
