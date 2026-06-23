<?php

namespace App\Http\Controllers;

use App\Models\Interaction;
use App\Models\ReminderNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ReminderNotificationController extends Controller
{
    private const STAGE_LABELS = [
        't_minus_5' => 'Faltan menos de 5 minutos',
        't_minus_4' => 'Faltan menos de 4 minutos',
    ];

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'interaction_id' => ['required', 'integer', 'exists:interactions,id'],
            'reminder_stage' => ['required', 'string', 'in:t_minus_5,t_minus_4'],
        ]);

        $interaction = Interaction::query()
            ->with('lead:id,full_name,business_name,representative_name')
            ->where('id', $validated['interaction_id'])
            ->where('user_id', $user->id)
            ->whereIn('interaction_type', ['a_negociar', 'edicion_mi_chamba'])
            ->where('status_specific', 'reprogramado')
            ->firstOrFail();

        $lead = $interaction->lead;
        $leadLabel = $lead?->business_name
            ?: $lead?->representative_name
            ?: $lead?->full_name
            ?: 'Cliente sin nombre';
        $stage = $validated['reminder_stage'];
        $stageLabel = self::STAGE_LABELS[$stage];

        $notification = ReminderNotification::firstOrCreate(
            $this->notificationLookupAttributes($user->id, $interaction->id, $interaction->next_contact_at, 't_minus_5'),
            [
                'lead_id' => $interaction->lead_id,
                'lead_label' => $leadLabel,
                'title' => 'Recordatorio de llamada',
                'message' => 'Debes volver a llamar a '.$leadLabel.' a las '.$interaction->next_contact_at?->setTimezone(config('app.timezone'))->format('d/m/Y H:i').'.',
                'notified_at' => now(),
            ]
        );

        return response()->json([
            'ok' => true,
            'notification' => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'stage_label' => $stageLabel,
                'notified_at_label' => $notification->notified_at?->setTimezone(config('app.timezone'))->format('d/m/Y H:i'),
                'mark_read_url' => route('work.reminder-notifications.read', $notification),
                'open_url' => route('work.reminder-notifications.open', $notification),
            ],
            'unread_count' => ReminderNotification::query()
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->count(),
        ]);
    }

    public function pulse(Request $request)
    {
        $user = $request->user();

        $this->purgeInactiveNotifications($user->id);
        $this->syncDueNotifications($user->id);

        return response()->json([
            'ok' => true,
            'unread_count' => ReminderNotification::query()
                ->where('user_id', $user->id)
                ->whereHas('lead', function ($query) {
                    $query->where('status_specific', 'reprogramado');
                })
                ->whereNull('read_at')
                ->count(),
            'notifications' => $this->notificationList($user->id),
            'scheduled_reminders' => $this->scheduledReminders($user->id),
            'recent_toast_notifications' => $this->recentToastNotifications($user->id),
            'server_now_iso' => now()->setTimezone(config('app.timezone'))->toIso8601String(),
        ]);
    }

    public function open(Request $request, ReminderNotification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if ($notification->read_at === null) {
            $notification->forceFill([
                'read_at' => now(),
            ])->save();
        }

        return redirect()->route('my-work.show', $notification->lead_id);
    }

    public function markAsRead(Request $request, ReminderNotification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if ($notification->read_at === null) {
            $notification->forceFill([
                'read_at' => now(),
            ])->save();
        }

        return back();
    }

    public function markAllAsRead(Request $request)
    {
        ReminderNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return back();
    }

    private function syncDueNotifications(int $userId): void
    {
        $dueInteractions = Interaction::query()
            ->with('lead:id,full_name,business_name,representative_name')
            ->where('user_id', $userId)
            ->whereIn('interaction_type', ['a_negociar', 'edicion_mi_chamba'])
            ->where('status_specific', 'reprogramado')
            ->whereHas('lead', function ($query) {
                $query->where('status_specific', 'reprogramado');
            })
            ->whereNotNull('next_contact_at')
            ->where('next_contact_at', '>', now())
            ->where('next_contact_at', '<=', now()->addMinutes(5))
            ->get();

        foreach ($dueInteractions as $interaction) {
            $lead = $interaction->lead;
            $leadLabel = $lead?->business_name
                ?: $lead?->representative_name
                ?: $lead?->full_name
                ?: 'Cliente sin nombre';

            ReminderNotification::firstOrCreate(
                $this->notificationLookupAttributes($userId, $interaction->id, $interaction->next_contact_at, 't_minus_5'),
                [
                    'lead_id' => $interaction->lead_id,
                    'lead_label' => $leadLabel,
                    'title' => 'Recordatorio de llamada',
                    'message' => 'Debes volver a llamar a '.$leadLabel.' a las '.$interaction->next_contact_at?->setTimezone(config('app.timezone'))->format('d/m/Y H:i').'.',
                    'notified_at' => now(),
                ]
            );
        }
    }

    private function notificationList(int $userId): array
    {
        return ReminderNotification::query()
            ->where('user_id', $userId)
            ->whereHas('lead', function ($query) {
                $query->where('status_specific', 'reprogramado');
            })
            ->orderByRaw('read_at IS NULL DESC')
            ->orderByDesc('notified_at')
            ->limit(8)
            ->get()
            ->map(fn (ReminderNotification $notification) => $this->formatNotification($notification))
            ->values()
            ->all();
    }

    private function scheduledReminders(int $userId): array
    {
        return Interaction::query()
            ->with('lead:id,full_name,business_name,representative_name')
            ->where('user_id', $userId)
            ->whereIn('interaction_type', ['a_negociar', 'edicion_mi_chamba'])
            ->where('status_specific', 'reprogramado')
            ->whereHas('lead', function ($query) {
                $query->where('status_specific', 'reprogramado');
            })
            ->whereNotNull('next_contact_at')
            ->where('next_contact_at', '>', now())
            ->where('next_contact_at', '<=', now()->addMinutes(5))
            ->orderBy('next_contact_at')
            ->limit(12)
            ->get()
            ->map(function (Interaction $interaction) {
                $lead = $interaction->lead;
                $leadLabel = $lead?->business_name
                    ?: $lead?->representative_name
                    ?: $lead?->full_name
                    ?: 'Cliente sin nombre';

                return [
                    'interaction_id' => $interaction->id,
                    'lead_id' => $interaction->lead_id,
                    'lead_label' => $leadLabel,
                    'next_contact_at' => $interaction->next_contact_at?->setTimezone(config('app.timezone'))->toIso8601String(),
                    'next_contact_at_label' => $interaction->next_contact_at?->setTimezone(config('app.timezone'))->format('d/m/Y H:i'),
                    'open_url' => route('my-work.show', $interaction->lead_id),
                ];
            })
            ->values()
            ->all();
    }

    private function recentToastNotifications(int $userId): array
    {
        return ReminderNotification::query()
            ->where('user_id', $userId)
            ->whereHas('lead', function ($query) {
                $query->where('status_specific', 'reprogramado');
            })
            ->whereNull('read_at')
            ->where('notified_at', '>=', now()->subSeconds(50))
            ->orderByDesc('notified_at')
            ->limit(4)
            ->get()
            ->map(function (ReminderNotification $notification) {
                $formatted = $this->formatNotification($notification);

                return [
                    'id' => $formatted['id'],
                    'title' => $formatted['title'],
                    'message' => $formatted['message'],
                    'stage_label' => $formatted['stage_label'],
                    'open_url' => $formatted['open_url'],
                    'storage_key' => $formatted['storage_key'],
                ];
            })
            ->values()
            ->all();
    }

    private function purgeInactiveNotifications(int $userId): void
    {
        ReminderNotification::query()
            ->where('user_id', $userId)
            ->whereHas('lead', function ($query) {
                $query->where('status_specific', '!=', 'reprogramado');
            })
            ->delete();
    }

    private function formatNotification(ReminderNotification $notification): array
    {
        $hasReminderStageColumn = $this->hasReminderStageColumn();
        $stage = $hasReminderStageColumn ? $notification->reminder_stage : 't_minus_5';

        return [
            'id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'read_at' => $notification->read_at?->setTimezone(config('app.timezone'))->toIso8601String(),
            'stage_label' => $stage === 't_minus_4'
                ? self::STAGE_LABELS['t_minus_4']
                : self::STAGE_LABELS['t_minus_5'],
            'notified_at_label' => $notification->notified_at?->setTimezone(config('app.timezone'))->format('d/m/Y H:i'),
            'mark_read_url' => route('work.reminder-notifications.read', $notification),
            'open_url' => route('work.reminder-notifications.open', $notification),
            'storage_key' => 'call-reminder-'.$notification->interaction_id.'-'.$notification->scheduled_for?->setTimezone(config('app.timezone'))->toIso8601String().'-'.$stage,
        ];
    }

    private function notificationLookupAttributes(int $userId, int $interactionId, $scheduledFor, string $stage): array
    {
        $attributes = [
            'user_id' => $userId,
            'interaction_id' => $interactionId,
            'scheduled_for' => $scheduledFor,
        ];

        if ($this->hasReminderStageColumn()) {
            $attributes['reminder_stage'] = $stage;
        }

        return $attributes;
    }

    private function hasReminderStageColumn(): bool
    {
        return Schema::hasTable('reminder_notifications')
            && Schema::hasColumn('reminder_notifications', 'reminder_stage');
    }
}
