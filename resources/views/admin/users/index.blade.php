<x-app-layout>
    <div class="py-8">
        <div
            class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6"
            x-data="{
                createOpen: {{ $errors->any() ? 'true' : 'false' }},
                detailOpen: false,
                createRole: '',
                selectedUser: null,
                roleUsesAssignments(role) {
                    return ['Ejecutivo', 'Supervisor'].includes(role || '');
                },
                roleUsesSupervisor(role) {
                    return (role || '') === 'Ejecutivo';
                },
                syncCreateAssignments() {
                    if (!this.roleUsesAssignments(this.createRole)) {
                        if (this.$refs.createCampaignField) this.$refs.createCampaignField.value = '';
                        if (this.$refs.createSupervisorField) this.$refs.createSupervisorField.value = '';
                        return;
                    }

                    if (!this.roleUsesSupervisor(this.createRole) && this.$refs.createSupervisorField) {
                        this.$refs.createSupervisorField.value = '';
                    }
                },
                syncDetailAssignments() {
                    if (!this.selectedUser) {
                        return;
                    }

                    if (!this.roleUsesAssignments(this.selectedUser.role)) {
                        this.selectedUser.campaign_id = '';
                        this.selectedUser.supervisor_user_id = '';
                        return;
                    }

                    if (!this.roleUsesSupervisor(this.selectedUser.role)) {
                        this.selectedUser.supervisor_user_id = '';
                    }
                },
                openCreate() {
                    if (this.$refs.createForm) {
                        this.$refs.createForm.reset();
                    }
                    this.createRole = '';
                    this.createOpen = true;
                },
                closeCreate() {
                    this.createOpen = false;
                    this.createRole = '';
                    if (this.$refs.createForm) {
                        this.$refs.createForm.reset();
                    }
                },
                openDetail(user) {
                    this.selectedUser = { ...user };
                    this.syncDetailAssignments();
                    this.detailOpen = true;
                },
                closeDetail() {
                    this.detailOpen = false;
                    this.selectedUser = null;
                }
            }"
        >
            @if(session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ implode(' | ', $errors->all()) }}
                </div>
            @endif

            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Usuarios</h1>
                        <p class="text-sm text-gray-500 mt-1">
                            Crea usuarios y administra rol, campaña, supervisor y contraseña.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="openCreate()"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-black text-white text-sm font-medium hover:bg-gray-800 transition"
                    >
                        + Crear usuario
                    </button>
                </div>
            </div>

            {{-- Modal crear --}}
            <div
                x-show="createOpen"
                x-transition
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="display: none;"
            >
                <div class="absolute inset-0 bg-black/40" @click="closeCreate()"></div>

                <div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-xl p-6">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Crear usuario</h2>
                            <p class="text-sm text-gray-500 mt-1">Registra una nueva cuenta del sistema.</p>
                        </div>

                        <button
                            type="button"
                            @click="closeCreate()"
                            class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-300 text-gray-600 hover:bg-gray-50"
                        >
                            ✕
                        </button>
                    </div>

                    <form x-ref="createForm" method="POST" action="{{ route('admin.users.store') }}" class="grid grid-cols-1 md:grid-cols-2 gap-4" autocomplete="off">
                        @csrf

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nombre</label>
                            <input type="text" name="name" class="mt-1 block w-full rounded border-gray-300" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Correo</label>
                            <input type="email" name="email" class="mt-1 block w-full rounded border-gray-300" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contraseña</label>
                            <input type="password" name="password" class="mt-1 block w-full rounded border-gray-300" required>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Rol</label>
                            <select name="role" x-model="createRole" @change="syncCreateAssignments()" class="mt-1 block w-full rounded border-gray-300" required>
                                <option value="">-- Selecciona --</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="roleUsesAssignments(createRole)" x-transition style="display: none;">
                            <label class="block text-sm font-medium text-gray-700">Campaña</label>
                            <select x-ref="createCampaignField" name="campaign_id" class="mt-1 block w-full rounded border-gray-300">
                                <option value="">-- Selecciona --</option>
                                @foreach($campaigns as $campaign)
                                    <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="roleUsesSupervisor(createRole)" x-transition style="display: none;">
                            <label class="block text-sm font-medium text-gray-700">Supervisor</label>
                            <select x-ref="createSupervisorField" name="supervisor_user_id" class="mt-1 block w-full rounded border-gray-300">
                                <option value="">-- Selecciona --</option>
                                @foreach($supervisors as $supervisor)
                                    <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-2 flex justify-end gap-3 pt-2">
                            <button
                                type="button"
                                @click="closeCreate()"
                                class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                            >
                                Cancelar
                            </button>

                            <button type="submit" class="px-4 py-2 rounded-xl bg-black text-white">
                                Crear usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg p-6">
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white">
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse">
                            <thead class="bg-slate-900">
                                <tr class="text-left text-[11px] font-semibold uppercase tracking-[0.16em] text-white/80">
                                    <th class="px-4 py-3">Nombre</th>
                                    <th class="px-4 py-3">Correo</th>
                                    <th class="px-4 py-3">Rol</th>
                                    <th class="px-4 py-3">Campaña</th>
                                    <th class="px-4 py-3">Supervisor</th>
                                    <th class="px-4 py-3 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach($users as $user)
                                    @php
                                        $currentRole = $user->roles->first()?->name;
                                        $currentCampaignId = $user->campaigns->first()?->id;
                                        $currentCampaignName = $user->campaigns->first()?->name;
                                        $currentSupervisorId = $executiveSupervisorMap[$user->id] ?? null;
                                        $currentSupervisorName = $supervisors->firstWhere('id', $currentSupervisorId)?->name;
                                        $userDetail = [
                                            'id' => $user->id,
                                            'name' => $user->name,
                                            'email' => $user->email,
                                            'role' => $currentRole,
                                            'campaign_id' => $currentCampaignId,
                                            'campaign_name' => $currentCampaignName,
                                            'supervisor_user_id' => $currentSupervisorId,
                                            'supervisor_name' => $currentSupervisorName,
                                        ];
                                    @endphp

                                    <tr class="align-middle text-sm text-slate-700">
                                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $user->name }}</td>
                                        <td class="px-4 py-3">{{ $user->email }}</td>
                                        <td class="px-4 py-3">{{ $currentRole ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $currentCampaignName ?? '-' }}</td>
                                        <td class="px-4 py-3">{{ $currentSupervisorName ?? '-' }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex justify-end">
                                                <button
                                                    type="button"
                                                    x-on:click="openDetail(JSON.parse($el.dataset.user))"
                                                    data-user='@json($userDetail)'
                                                    class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-300 text-slate-700 font-semibold hover:bg-slate-50 transition"
                                                >
                                                    Detalle
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-6">
                    {{ $users->links() }}
                </div>
            </div>

            {{-- Modal detalle / editar --}}
            <div
                x-show="detailOpen && selectedUser"
                x-transition
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="display: none;"
            >
                <div class="absolute inset-0 bg-black/40" @click="closeDetail()"></div>

                <div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-xl p-6" x-show="selectedUser">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Detalle de usuario</h2>
                            <p class="text-sm text-gray-500 mt-1">Revisa y actualiza la cuenta seleccionada.</p>
                        </div>

                        <button
                            type="button"
                            @click="closeDetail()"
                            class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-gray-300 text-gray-600 hover:bg-gray-50"
                        >
                            ✕
                        </button>
                    </div>

                    <template x-if="selectedUser">
                        <form :action="`/admin/users/${selectedUser.id}`" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @csrf
                            @method('PUT')

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nombre</label>
                                <input type="text" name="name" x-model="selectedUser.name" class="mt-1 block w-full rounded border-gray-300" required>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Correo</label>
                                <input type="text" :value="selectedUser.email" class="mt-1 block w-full rounded border-gray-300 bg-gray-100" disabled>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Contraseña <span class="text-gray-400 text-xs font-normal">(dejar vacío para no cambiar)</span></label>
                                <input type="password" name="password" class="mt-1 block w-full rounded border-gray-300">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Rol</label>
                                <select name="role" x-model="selectedUser.role" @change="syncDetailAssignments()" class="mt-1 block w-full rounded border-gray-300" required>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div x-show="roleUsesAssignments(selectedUser.role)" x-transition style="display: none;">
                                <label class="block text-sm font-medium text-gray-700">Campaña</label>
                                <select name="campaign_id" x-model="selectedUser.campaign_id" class="mt-1 block w-full rounded border-gray-300">
                                    <option value="">-- Selecciona --</option>
                                    @foreach($campaigns as $campaign)
                                        <option value="{{ $campaign->id }}">{{ $campaign->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div x-show="roleUsesSupervisor(selectedUser.role)" x-transition style="display: none;">
                                <label class="block text-sm font-medium text-gray-700">Supervisor</label>
                                <select name="supervisor_user_id" x-model="selectedUser.supervisor_user_id" class="mt-1 block w-full rounded border-gray-300">
                                    <option value="">-- Selecciona --</option>
                                    @foreach($supervisors as $supervisor)
                                        <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="md:col-span-2 flex justify-end gap-3 pt-2">
                                <button
                                    type="button"
                                    @click="closeDetail()"
                                    class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
                                >
                                    Cancelar
                                </button>

                                <button type="submit" class="px-4 py-2 rounded-xl bg-black text-white">
                                    Confirmar actualización
                                </button>
                            </div>
                        </form>
                    </template>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
