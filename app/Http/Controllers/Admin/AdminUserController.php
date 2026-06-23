<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AdminUserController extends Controller
{
    private const ROLES_WITH_ASSIGNMENTS = [
        'Ejecutivo',
        'Supervisor',
    ];

    public function index()
    {
        $users = User::with(['campaigns', 'roles'])
            ->orderBy('name')
            ->paginate(12);

        $campaigns = Campaign::orderBy('name')->get();

        $roles = Role::whereIn('name', [
            'Gerencia',
            'Supervisor',
            'Ejecutivo',
            'Postventa',
            'Mesa de Control',
            'RRHH',
            'MKT',
            'administrador de promociones',
        ])->orderBy('name')->get();

        $supervisors = User::query()
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Supervisor');
            })
            ->orderBy('name')
            ->get();

        $executiveSupervisorMap = DB::table('supervisor_executive')
            ->pluck('supervisor_user_id', 'executive_user_id');

        return view('admin.users.index', compact(
            'users',
            'campaigns',
            'roles',
            'supervisors',
            'executiveSupervisorMap'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:Gerencia,Supervisor,Ejecutivo,Postventa,Mesa de Control,RRHH,MKT,administrador de promociones',
            'campaign_id' => 'nullable|exists:campaigns,id',
            'supervisor_user_id' => 'nullable|exists:users,id',
        ], [
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $validated = $this->normalizeAssignmentFields($validated);

        if (! empty($validated['supervisor_user_id'])) {
            $isSupervisor = User::query()
                ->whereHas('roles', fn ($q) => $q->where('name', 'Supervisor'))
                ->where('id', $validated['supervisor_user_id'])
                ->exists();

            if (! $isSupervisor) {
                throw ValidationException::withMessages([
                    'supervisor_user_id' => 'El usuario seleccionado no tiene el rol de Supervisor.',
                ]);
            }
        }

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $role = Role::where('name', $validated['role'])->firstOrFail();

            DB::table('model_has_roles')->insert([
                'role_id' => $role->id,
                'model_type' => User::class,
                'model_id' => $user->id,
            ]);

            if (! empty($validated['campaign_id'])) {
                $user->campaigns()->sync([$validated['campaign_id']]);
            } else {
                $user->campaigns()->sync([]);
            }

            if ($validated['role'] === 'Ejecutivo' && ! empty($validated['supervisor_user_id'])) {
                DB::table('supervisor_executive')->updateOrInsert(
                    ['executive_user_id' => $user->id],
                    [
                        'supervisor_user_id' => $validated['supervisor_user_id'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        });

        return back()->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|min:8',
            'role' => 'required|string|in:Gerencia,Supervisor,Ejecutivo,Postventa,Mesa de Control,RRHH,MKT,administrador de promociones',
            'campaign_id' => 'nullable|exists:campaigns,id',
            'supervisor_user_id' => 'nullable|exists:users,id',
        ], [
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $validated = $this->normalizeAssignmentFields($validated);

        if (! empty($validated['supervisor_user_id'])) {
            $isSupervisor = User::query()
                ->whereHas('roles', fn ($q) => $q->where('name', 'Supervisor'))
                ->where('id', $validated['supervisor_user_id'])
                ->exists();

            if (! $isSupervisor) {
                throw ValidationException::withMessages([
                    'supervisor_user_id' => 'El usuario seleccionado no tiene el rol de Supervisor.',
                ]);
            }
        }

        DB::transaction(function () use ($validated, $user) {
            $data = [
                'name' => $validated['name'],
            ];

            if (filled($validated['password'] ?? null)) {
                $data['password'] = Hash::make($validated['password']);
            }

            $user->update($data);

            $role = Role::where('name', $validated['role'])->firstOrFail();

            // Reemplaza syncRoles para evitar el error de model_has_roles
            DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->where('model_id', $user->id)
                ->delete();

            DB::table('model_has_roles')->insert([
                'role_id' => $role->id,
                'model_type' => User::class,
                'model_id' => $user->id,
            ]);

            if (! empty($validated['campaign_id'])) {
                $user->campaigns()->sync([$validated['campaign_id']]);
            } else {
                $user->campaigns()->sync([]);
            }

            DB::table('supervisor_executive')
                ->where('executive_user_id', $user->id)
                ->delete();

            if ($validated['role'] === 'Ejecutivo' && ! empty($validated['supervisor_user_id'])) {
                DB::table('supervisor_executive')->insert([
                    'supervisor_user_id' => $validated['supervisor_user_id'],
                    'executive_user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    private function normalizeAssignmentFields(array $validated): array
    {
        $role = $validated['role'] ?? null;

        if (! in_array($role, self::ROLES_WITH_ASSIGNMENTS, true)) {
            $validated['campaign_id'] = null;
            $validated['supervisor_user_id'] = null;

            return $validated;
        }

        if ($role !== 'Ejecutivo') {
            $validated['supervisor_user_id'] = null;
        }

        return $validated;
    }
}
