<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadWorkSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExecutiveTmoSessionController extends Controller
{
    private const ALLOWED_MODULES = ['a_negociar', 'mi_chamba'];

    public function start(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'lead_id' => ['required', 'integer', 'exists:leads,id'],
            'module_name' => ['required', 'string', 'in:a_negociar,mi_chamba'],
            'route_name' => ['nullable', 'string', 'max:120'],
        ]);

        $lead = $this->resolveLeadForExecutive($user->id, (int) $validated['lead_id']);
        $now = now();

        $supervisorUserId = $lead->supervisor_user_id ?: DB::table('supervisor_executive')
            ->where('executive_user_id', $user->id)
            ->value('supervisor_user_id');

        LeadWorkSession::query()
            ->where('executive_user_id', $user->id)
            ->whereNull('ended_at')
            ->update([
                'ended_at' => $now,
                'last_heartbeat_at' => $now,
                'updated_at' => $now,
            ]);

        $session = LeadWorkSession::create([
            'lead_id' => $lead->id,
            'campaign_id' => $lead->campaign_id,
            'executive_user_id' => $user->id,
            'supervisor_user_id' => $supervisorUserId,
            'module_name' => $validated['module_name'],
            'route_name' => $validated['route_name'] ?? null,
            'started_at' => $now,
            'last_heartbeat_at' => $now,
        ]);

        return response()->json([
            'ok' => true,
            'session_id' => $session->id,
            'started_at' => $session->started_at?->toIso8601String(),
        ]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'session_id' => ['required', 'integer', 'exists:lead_work_sessions,id'],
        ]);

        $session = LeadWorkSession::query()
            ->where('id', $validated['session_id'])
            ->where('executive_user_id', $user->id)
            ->whereNull('ended_at')
            ->firstOrFail();

        $session->forceFill([
            'last_heartbeat_at' => now(),
        ])->save();

        return response()->json([
            'ok' => true,
            'session_id' => $session->id,
            'last_heartbeat_at' => $session->last_heartbeat_at?->toIso8601String(),
        ]);
    }

    public function stop(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'session_id' => ['required', 'integer', 'exists:lead_work_sessions,id'],
        ]);

        $session = LeadWorkSession::query()
            ->where('id', $validated['session_id'])
            ->where('executive_user_id', $user->id)
            ->firstOrFail();

        if ($session->ended_at === null) {
            $session->forceFill([
                'last_heartbeat_at' => now(),
                'ended_at' => now(),
            ])->save();
        }

        return response()->json([
            'ok' => true,
            'session_id' => $session->id,
        ]);
    }

    private function resolveLeadForExecutive(int $userId, int $leadId): Lead
    {
        return Lead::query()
            ->where('id', $leadId)
            ->where(function ($query) use ($userId) {
                $query->where('assigned_to_user_id', $userId)
                    ->orWhere('created_by_user_id', $userId);
            })
            ->firstOrFail();
    }
}
