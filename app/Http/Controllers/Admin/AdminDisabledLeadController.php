<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;

class AdminDisabledLeadController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.disabled-leads.index', [
            'leads' => Lead::with('campaign', 'phones')
                ->whereNotNull('disabled_at')
                ->orderByDesc('disabled_at')
                ->paginate(20),
        ]);
    }

    public function reactivate(Lead $lead)
    {
        $lead->update([
            'disabled_at' => null,
            'disabled_reason' => null,
            'assigned_to_user_id' => null,
            'taken_at' => null,
            'released_at' => null,
            'no_contact_attempts' => 0,
            'delivery_status' => 'disponible',
            'status_general' => 'sin_contacto',
            'status_specific' => 'sin_gestion',
            'status_final' => 'sin_gestion',
        ]);

        return back()->with('success', 'Lead reactivado correctamente.');
    }
}