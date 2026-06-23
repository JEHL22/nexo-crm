<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'usersCount' => User::count(),
            'campaignsCount' => Campaign::count(),
            'disabledLeadsCount' => Lead::whereNotNull('disabled_at')->count(),
        ]);
    }
}