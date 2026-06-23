<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\Request;

class AdminCampaignController extends Controller
{
    public function index()
    {
        $campaigns = Campaign::withCount(['users', 'leads', 'sales'])
            ->orderBy('name')
            ->paginate(8);

        return view('admin.campaigns.index', [
            'campaigns' => $campaigns,
            'campaignTotals' => [
                'campaigns' => Campaign::count(),
                'users' => Campaign::withCount('users')->get()->sum('users_count'),
                'leads' => Campaign::withCount('leads')->get()->sum('leads_count'),
                'sales' => Campaign::withCount('sales')->get()->sum('sales_count'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:campaigns,name',
        ]);

        Campaign::create([
            'name' => $validated['name'],
        ]);

        return back()->with('success', 'Campaña creada correctamente.');
    }

    public function update(Request $request, Campaign $campaign)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:campaigns,name,' . $campaign->id,
        ]);

        $campaign->update([
            'name' => $validated['name'],
        ]);

        return back()->with('success', 'Campaña actualizada correctamente.');
    }
}
