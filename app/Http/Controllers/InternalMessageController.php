<?php

namespace App\Http\Controllers;

use App\Models\InternalMessage;
use App\Models\InternalMessageRecipient;
use App\Models\SupervisorExecutive;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InternalMessageController extends Controller
{
    private const MESSAGE_TTL_MINUTES = 5;

    public function adminIndex(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && ($user->hasRole('Administrador') || in_array($user->email, config('admin.bootstrap_admin_emails', []), true)), 403);

        return $this->renderIndex($user, 'admin');
    }

    public function managementIndex(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('Gerencia'), 403);

        return $this->renderIndex($user, 'management');
    }

    public function supervisorIndex(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('Supervisor'), 403);

        return $this->renderIndex($user, 'supervisor');
    }

    public function adminStore(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->hasRole('Administrador') || in_array($user->email, config('admin.bootstrap_admin_emails', []), true)), 403);

        return $this->storeMessage($request, 'admin');
    }

    public function managementStore(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('Gerencia'), 403);

        return $this->storeMessage($request, 'management');
    }

    public function supervisorStore(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->hasRole('Supervisor'), 403);

        return $this->storeMessage($request, 'supervisor');
    }

    public function pulse(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->hasRole('Ejecutivo') || $user->hasRole('Supervisor')), 403);

        $now = now();
        $expirationThreshold = $now->copy()->subMinutes(self::MESSAGE_TTL_MINUTES);

        InternalMessageRecipient::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->whereHas('internalMessage', function ($query) use ($expirationThreshold) {
                $query->where('created_at', '<', $expirationThreshold);
            })
            ->update([
                'displayed_at' => $now,
                'read_at' => $now,
            ]);

        $messages = InternalMessageRecipient::query()
            ->with(['internalMessage.sender:id,name'])
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->whereHas('internalMessage', function ($query) use ($expirationThreshold) {
                $query->where('created_at', '>=', $expirationThreshold);
            })
            ->orderByDesc('created_at')
            ->limit(4)
            ->get()
            ->map(fn (InternalMessageRecipient $recipient) => $this->formatRecipientMessage($recipient))
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'messages' => $messages,
        ]);
    }

    public function markDisplayed(Request $request, InternalMessageRecipient $recipient): JsonResponse
    {
        abort_unless($recipient->user_id === $request->user()?->id, 403);

        if ($recipient->displayed_at === null) {
            $recipient->forceFill([
                'displayed_at' => now(),
            ])->save();
        }

        return response()->json(['ok' => true]);
    }

    public function markRead(Request $request, InternalMessageRecipient $recipient): JsonResponse
    {
        abort_unless($recipient->user_id === $request->user()?->id, 403);

        $recipient->forceFill([
            'displayed_at' => $recipient->displayed_at ?: now(),
            'read_at' => $recipient->read_at ?: now(),
        ])->save();

        return response()->json(['ok' => true]);
    }

    private function renderIndex(User $user, string $scope): View
    {
        if ($scope === 'supervisor') {
            $eligibleUsers = User::query()
                ->select('users.id', 'users.name', 'users.email')
                ->join('supervisor_executive as se', 'se.executive_user_id', '=', 'users.id')
                ->where('se.supervisor_user_id', $user->id)
                ->role('Ejecutivo')
                ->orderBy('users.name')
                ->distinct()
                ->get();

            $recipientGroups = [
                'Ejecutivo' => $eligibleUsers->values(),
            ];
        } else {
            $eligibleUsers = User::query()
                ->role(['Ejecutivo', 'Supervisor'])
                ->with('roles:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'email']);

            $recipientGroups = [
                'Supervisor' => $eligibleUsers->filter(fn (User $candidate) => $candidate->hasRole('Supervisor'))->values(),
                'Ejecutivo' => $eligibleUsers->filter(fn (User $candidate) => $candidate->hasRole('Ejecutivo'))->values(),
            ];
        }

        $recentMessages = InternalMessage::query()
            ->with(['recipients.user:id,name', 'sender:id,name'])
            ->where('sender_user_id', $user->id)
            ->latest()
            ->limit(12)
            ->get();

        return view('internal-messages.index', [
            'scope' => $scope,
            'recipientGroups' => $recipientGroups,
            'recentMessages' => $recentMessages,
            'storeRoute' => $scope === 'admin'
                ? route('admin.internal-messages.store')
                : ($scope === 'management'
                    ? route('management.internal-messages.store')
                    : route('supervisor.internal-messages.store')),
            'pageRoute' => $scope === 'admin'
                ? route('admin.internal-messages.index')
                : ($scope === 'management'
                    ? route('management.internal-messages.index')
                    : route('supervisor.internal-messages.index')),
        ]);
    }

    private function storeMessage(Request $request, string $scope): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:2000'],
            'recipient_user_ids' => ['required', 'array', 'min:1'],
            'recipient_user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        if ($scope === 'supervisor') {
            $validCount = SupervisorExecutive::query()
                ->where('supervisor_user_id', $request->user()->id)
                ->whereIn('executive_user_id', $validated['recipient_user_ids'])
                ->distinct()
                ->count();

            if ($validCount !== count($validated['recipient_user_ids'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'recipient_user_ids' => 'Uno o más de los destinatarios seleccionados no pertenece a tu equipo.',
                ]);
            }

            $recipientIds = $validated['recipient_user_ids'];
        } else {
            $validCount = User::query()
                ->role(['Ejecutivo', 'Supervisor'])
                ->whereIn('id', $validated['recipient_user_ids'])
                ->count();

            if ($validCount !== count($validated['recipient_user_ids'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'recipient_user_ids' => 'Uno o más de los destinatarios seleccionados no tiene un rol válido.',
                ]);
            }

            $recipientIds = $validated['recipient_user_ids'];
        }

        DB::transaction(function () use ($request, $validated, $recipientIds) {
            $message = InternalMessage::create([
                'sender_user_id' => $request->user()->id,
                'title' => filled($validated['title']) ? trim((string) $validated['title']) : null,
                'message' => trim((string) $validated['message']),
            ]);

            foreach ($recipientIds as $recipientId) {
                InternalMessageRecipient::create([
                    'internal_message_id' => $message->id,
                    'user_id' => $recipientId,
                ]);
            }
        });

        return redirect()
            ->route(match ($scope) {
                'admin' => 'admin.internal-messages.index',
                'management' => 'management.internal-messages.index',
                'supervisor' => 'supervisor.internal-messages.index',
            })
            ->with('success', 'Mensaje enviado correctamente.');
    }

    private function formatRecipientMessage(InternalMessageRecipient $recipient): array
    {
        $message = $recipient->internalMessage;

        return [
            'id' => $recipient->id,
            'title' => $message?->title ?: 'Nuevo mensaje interno',
            'message' => $message?->message ?: '',
            'sender_name' => $message?->sender?->name ?: 'Sistema',
            'created_at_label' => optional($message?->created_at)->setTimezone(config('app.timezone'))->format('d/m/Y H:i') ?: '-',
            'mark_displayed_url' => route('internal-messages.displayed', $recipient),
            'mark_read_url' => route('internal-messages.read', $recipient),
            'storage_key' => 'internal-message-'.$recipient->id,
        ];
    }
}
