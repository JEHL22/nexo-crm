<?php

namespace App\Providers;

use App\Models\Interaction;
use App\Models\ReminderNotification;
use App\Models\SupervisorStatusNotification;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $user = Auth::user();

            $view->with('executiveReminderNotifications', collect());
            $view->with('executiveReminderUnreadCount', 0);
            $view->with('executiveScheduledReminders', []);
            $view->with('executiveRecentToastNotifications', []);
            $view->with('executiveReminderServerNowIso', null);

            $view->with('supervisorStatusNotifications', collect());
            $view->with('supervisorStatusNotificationUnreadCount', 0);
            $view->with('supervisorRecentStatusToastNotifications', []);
            $view->with('supervisorStatusNotificationServerNowIso', null);

            if (!$user || !method_exists($user, 'hasRole')) {
                return;
            }

            if ($user->hasRole('Supervisor')) {
                if (!Schema::hasTable('supervisor_status_notifications')) {
                    return;
                }

                $notifications = SupervisorStatusNotification::query()
                    ->where('user_id', $user->id)
                    ->orderByRaw('read_at IS NULL DESC')
                    ->orderByDesc('notified_at')
                    ->limit(8)
                    ->get();

                $recentToastNotifications = SupervisorStatusNotification::query()
                    ->where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->where('notified_at', '>=', now()->subSeconds(50))
                    ->orderByDesc('notified_at')
                    ->limit(4)
                    ->get()
                    ->map(function (SupervisorStatusNotification $notification) {
                        return [
                            'id' => $notification->id,
                            'title' => $notification->title,
                            'message' => $notification->message,
                            'status_label' => match ($notification->current_status) {
                                'en_evaluacion', 'pendiente_validacion', 'observado' => 'En evaluación',
                                'activo', 'aprobado' => 'Activo',
                                'rechazado' => 'Rechazado',
                                'entregado' => 'Entregado',
                                default => ucfirst(str_replace('_', ' ', $notification->current_status)),
                            },
                            'open_url' => route('supervisor.status-notifications.open', $notification),
                            'storage_key' => 'supervisor-status-notification-'.$notification->id,
                        ];
                    })
                    ->values()
                    ->all();

                $view->with('supervisorStatusNotifications', $notifications);
                $view->with('supervisorStatusNotificationUnreadCount', SupervisorStatusNotification::query()
                    ->where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->count());
                $view->with('supervisorRecentStatusToastNotifications', $recentToastNotifications);
                $view->with('supervisorStatusNotificationServerNowIso', now()->setTimezone(config('app.timezone'))->toIso8601String());

                return;
            }

            if (!$user->hasRole('Ejecutivo')) {
                return;
            }

            if (!Schema::hasTable('reminder_notifications') || !Schema::hasTable('interactions')) {
                return;
            }

            $hasReminderStageColumn = Schema::hasColumn('reminder_notifications', 'reminder_stage');

            ReminderNotification::query()
                ->where('user_id', $user->id)
                ->whereHas('lead', function ($query) {
                    $query->where('status_specific', '!=', 'reprogramado');
                })
                ->delete();

            $dueInteractions = Interaction::query()
                ->with('lead:id,full_name,business_name,representative_name')
                ->where('user_id', $user->id)
                ->whereIn('interaction_type', ['a_negociar', 'edicion_mi_chamba'])
                ->where('status_specific', 'reprogramado')
                ->whereHas('lead', function ($query) {
                    $query->where('status_specific', 'reprogramado');
                })
                ->whereNotNull('next_contact_at')
                ->where('next_contact_at', '>', now())
                ->where('next_contact_at', '<=', now()->addMinutes(5))
                ->get();

            $syncReminderNotification = function (Interaction $interaction, string $stage, int $minutesBefore) use ($user, $hasReminderStageColumn) {
                $lead = $interaction->lead;
                $leadLabel = $lead?->business_name
                    ?: $lead?->representative_name
                    ?: $lead?->full_name
                    ?: 'Cliente sin nombre';

                if ($interaction->next_contact_at && $interaction->next_contact_at->lessThanOrEqualTo(now()->addMinutes($minutesBefore))) {
                    $attributes = [
                        'user_id' => $user->id,
                        'interaction_id' => $interaction->id,
                        'scheduled_for' => $interaction->next_contact_at,
                    ];

                    if ($hasReminderStageColumn) {
                        $attributes['reminder_stage'] = $stage;
                    }

                    ReminderNotification::firstOrCreate(
                        $attributes,
                        [
                            'lead_id' => $interaction->lead_id,
                            'lead_label' => $leadLabel,
                            'title' => 'Recordatorio de llamada',
                            'message' => 'Debes volver a llamar a '.$leadLabel.' a las '.$interaction->next_contact_at?->setTimezone(config('app.timezone'))->format('d/m/Y H:i').'.',
                            'notified_at' => now(),
                        ]
                    );
                }
            };

            foreach ($dueInteractions as $interaction) {
                $syncReminderNotification($interaction, 't_minus_5', 5);
            }

            $notifications = ReminderNotification::query()
                ->where('user_id', $user->id)
                ->whereHas('lead', function ($query) {
                    $query->where('status_specific', 'reprogramado');
                })
                ->orderByRaw('read_at IS NULL DESC')
                ->orderByDesc('notified_at')
                ->limit(8)
                ->get();

            $unreadCount = ReminderNotification::query()
                ->where('user_id', $user->id)
                ->whereHas('lead', function ($query) {
                    $query->where('status_specific', 'reprogramado');
                })
                ->whereNull('read_at')
                ->count();

            $scheduledReminders = Interaction::query()
                ->with('lead:id,full_name,business_name,representative_name')
                ->where('user_id', $user->id)
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

            $recentToastNotifications = ReminderNotification::query()
                ->where('user_id', $user->id)
                ->whereHas('lead', function ($query) {
                    $query->where('status_specific', 'reprogramado');
                })
                ->whereNull('read_at')
                ->where('notified_at', '>=', now()->subSeconds(50))
                ->orderByDesc('notified_at')
                ->limit(4)
                ->get()
                ->map(function (ReminderNotification $notification) use ($hasReminderStageColumn) {
                    return [
                        'id' => $notification->id,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'stage_label' => $hasReminderStageColumn && $notification->reminder_stage === 't_minus_4'
                            ? 'Faltan menos de 4 minutos'
                            : 'Faltan menos de 5 minutos',
                        'open_url' => route('work.reminder-notifications.open', $notification),
                        'storage_key' => 'call-reminder-'.$notification->interaction_id.'-'.$notification->scheduled_for?->setTimezone(config('app.timezone'))->toIso8601String().'-'.($hasReminderStageColumn ? $notification->reminder_stage : 't_minus_5'),
                    ];
                })
                ->values()
                ->all();

            $view->with('executiveReminderNotifications', $notifications);
            $view->with('executiveReminderUnreadCount', $unreadCount);
            $view->with('executiveScheduledReminders', $scheduledReminders);
            $view->with('executiveRecentToastNotifications', $recentToastNotifications);
            $view->with('executiveReminderServerNowIso', now()->setTimezone(config('app.timezone'))->toIso8601String());
        });
    }
}
