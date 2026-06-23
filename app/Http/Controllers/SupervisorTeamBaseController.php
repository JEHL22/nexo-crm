<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupervisorTeamBaseController extends MyWorkController
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = trim((string) $request->get('search'));
        $executiveUserId = $request->integer('executive_user_id');

        $executives = User::query()
            ->select('users.id', 'users.name')
            ->join('supervisor_executive as se', 'se.executive_user_id', '=', 'users.id')
            ->where('se.supervisor_user_id', $user->id)
            ->orderBy('users.name')
            ->distinct()
            ->get();

        $query = Lead::query()
            ->with([
                'phones',
                'assignedTo',
                'createdBy',
                'interactions' => function ($query) {
                    $query->latest('created_at')->with('offers', 'user');
                },
            ])
            ->where('source', 'mi_base');

        $this->applySupervisorTeamBaseScope($query, $user->id);

        if ($executiveUserId) {
            $query->where(function ($executiveQuery) use ($executiveUserId) {
                $executiveQuery->where('created_by_user_id', $executiveUserId)
                    ->orWhere('assigned_to_user_id', $executiveUserId);
            });
        }

        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('ruc', 'like', "%{$search}%")
                    ->orWhere('business_name', 'like', "%{$search}%");
            });
        }

        $leads = $query
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('supervisor.team-base.index', [
            'leads' => $leads,
            'search' => $search,
            'executiveUserId' => $executiveUserId ?: null,
            'executives' => $executives,
        ]);
    }

    public function updateSisac(Request $request, int $lead)
    {
        $record = $this->resolveMyWorkLeadForViewer(Auth::user(), $lead);

        abort_unless($record->source === 'mi_base', 403);

        return $this->performSisacUpdate($request, $record, 'supervisor.team-base.index');
    }
}
