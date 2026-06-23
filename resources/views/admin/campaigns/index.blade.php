<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
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

            <div class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 bg-gradient-to-r from-slate-950 via-slate-900 to-slate-800 px-6 py-8 text-white sm:px-8">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-2xl">
                            <p class="text-sm font-medium uppercase tracking-[0.22em] text-slate-300">Panel admin</p>
                            <h1 class="mt-3 text-3xl font-semibold tracking-tight">Campañas</h1>
                            <p class="mt-2 text-sm text-slate-300 sm:text-base">
                                Crea, renombra y revisa el pulso general de cada campaña desde una sola vista.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Campañas</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $campaignTotals['campaigns'] }}</div>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Usuarios</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $campaignTotals['users'] }}</div>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Leads</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $campaignTotals['leads'] }}</div>
                            </div>

                            <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur">
                                <div class="text-xs uppercase tracking-[0.2em] text-slate-400">Ventas</div>
                                <div class="mt-2 text-2xl font-semibold">{{ $campaignTotals['sales'] }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-6 p-6 sm:p-8 xl:grid-cols-[360px_minmax(0,1fr)]">
                    <div class="rounded-3xl border border-slate-200 bg-slate-50/80 p-5">
                        <div class="mb-5">
                            <h2 class="text-lg font-semibold text-slate-900">Nueva campaña</h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Agrega una campaña con un nombre claro para asignarla rápidamente a tus usuarios.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('admin.campaigns.store') }}" class="space-y-4">
                            @csrf

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Nombre</label>
                                <input
                                    type="text"
                                    name="name"
                                    placeholder="Ej. Campaña Lima Norte"
                                    class="mt-2 w-full rounded-2xl border-slate-300 bg-white px-4 py-3 text-slate-900 shadow-sm focus:border-slate-900 focus:ring-slate-900"
                                    required
                                >
                            </div>

                            <button
                                type="submit"
                                class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                            >
                                Crear campaña
                            </button>
                        </form>
                    </div>

                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-900">Campañas registradas</h2>
                                <p class="mt-1 text-sm text-slate-500">Edita el nombre de cada campaña y revisa su actividad.</p>
                            </div>
                        </div>

                        @forelse($campaigns as $campaign)
                            <form method="POST" action="{{ route('admin.campaigns.update', $campaign) }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-slate-300 hover:shadow-md">
                                @csrf
                                @method('PUT')

                                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="flex-1">
                                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                            <div class="flex-1">
                                                <div class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">
                                                    Campaña activa
                                                </div>

                                                <div class="mt-4">
                                                    <label class="block text-sm font-medium text-slate-700">Nombre de campaña</label>
                                                    <input
                                                        type="text"
                                                        name="name"
                                                        value="{{ $campaign->name }}"
                                                        class="mt-2 w-full rounded-2xl border-slate-300 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm focus:border-slate-900 focus:bg-white focus:ring-slate-900"
                                                        required
                                                    >
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-3 gap-3 md:min-w-[260px]">
                                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-4 text-center">
                                                    <div class="text-xs uppercase tracking-[0.18em] text-slate-400">Usuarios</div>
                                                    <div class="mt-2 text-xl font-semibold text-slate-900">{{ $campaign->users_count }}</div>
                                                </div>

                                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-4 text-center">
                                                    <div class="text-xs uppercase tracking-[0.18em] text-slate-400">Leads</div>
                                                    <div class="mt-2 text-xl font-semibold text-slate-900">{{ $campaign->leads_count }}</div>
                                                </div>

                                                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-4 text-center">
                                                    <div class="text-xs uppercase tracking-[0.18em] text-slate-400">Ventas</div>
                                                    <div class="mt-2 text-xl font-semibold text-slate-900">{{ $campaign->sales_count }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex justify-end">
                                        <button
                                            type="submit"
                                            class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-950 hover:bg-slate-950 hover:text-white"
                                        >
                                            Guardar cambios
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @empty
                            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
                                <h3 class="text-lg font-semibold text-slate-900">Aun no hay campañas registradas</h3>
                                <p class="mt-2 text-sm text-slate-500">
                                    Crea la primera campaña desde el panel lateral para empezar a asignar usuarios y leads.
                                </p>
                            </div>
                        @endforelse

                        @if ($campaigns->hasPages())
                            <div class="pt-2">
                                {{ $campaigns->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
