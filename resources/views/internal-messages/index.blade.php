<x-app-layout>
    @php
        $isManagementScope = $scope === 'management';
        $isSupervisorScope = $scope === 'supervisor';
        $accentText = 'text-slate-700';
        $accentButton = 'bg-slate-900 hover:bg-slate-800';
        $ringFocus = 'focus:border-slate-400 focus:ring-slate-400';
        $executiveCount = $recipientGroups['Ejecutivo']->count();
        $supervisorCount = collect($recipientGroups['Supervisor'] ?? [])->count();
        $scopeEyebrow = $isManagementScope
            ? 'Seguimiento de Gerencia'
            : ($isSupervisorScope ? 'Seguimiento de Supervisor' : 'Panel Admin');
        $scopeDescription = $isSupervisorScope
            ? 'Envía avisos directos a tus ejecutivos asignados. Puedes seleccionar uno o varios destinatarios y el mensaje aparecerá como popup en su sesión.'
            : 'Envía avisos directos a ejecutivos y supervisores. Puedes seleccionar uno o varios destinatarios y el mensaje aparecerá como popup en su sesión.';
        $recipientDescription = $isSupervisorScope
            ? 'Selecciona uno o varios ejecutivos asignados a tu equipo.'
            : 'Selecciona uno o varios supervisores y/o ejecutivos.';
        $reachLabel = $isSupervisorScope
            ? 'Ejecutivos asignados seleccionados'
            : 'Ejecutivos y Supervisores seleccionados';
    @endphp

    <div class="py-6">
        <div
            class="mx-auto max-w-[1600px] space-y-5 px-3 sm:px-4 lg:px-5"
            x-data="{
                selectedRecipients: @js(collect(old('recipient_user_ids', []))->map(fn ($id) => (int) $id)->values()->all()),
                toggleGroup(groupIds) {
                    const everySelected = groupIds.every((id) => this.selectedRecipients.includes(id));
                    if (everySelected) {
                        this.selectedRecipients = this.selectedRecipients.filter((id) => !groupIds.includes(id));
                        return;
                    }

                    this.selectedRecipients = Array.from(new Set([...this.selectedRecipients, ...groupIds]));
                },
                syncCheckboxes() {
                    this.$nextTick(() => {
                        document.querySelectorAll('[data-recipient-checkbox]').forEach((checkbox) => {
                            checkbox.checked = this.selectedRecipients.includes(Number(checkbox.value));
                        });
                    });
                }
            }"
        >
            @if(session('success'))
                <div class="rounded-2xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ implode(' | ', $errors->all()) }}
                </div>
            @endif

            <div class="overflow-hidden rounded-[28px] bg-white shadow-sm ring-1 ring-black/5">
                <div class="border-b border-slate-100 bg-gradient-to-r from-slate-100 via-white to-cyan-50 px-5 py-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                        <div class="max-w-3xl">
                            <p class="text-sm font-semibold uppercase tracking-[0.25em] {{ $accentText }}">
                                {{ $scopeEyebrow }}
                            </p>
                            <h1 class="mt-2 text-3xl font-bold text-gray-900">Mensajes internos</h1>
                            <p class="mt-2 text-sm text-gray-500">
                                {{ $scopeDescription }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl bg-white px-4 py-3 text-center shadow-sm ring-1 ring-black/5">
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Ejecutivos</div>
                                <div class="mt-2 text-2xl font-black text-gray-900">{{ $executiveCount }}</div>
                            </div>
                            @unless($isSupervisorScope)
                                <div class="rounded-2xl bg-white px-4 py-3 text-center shadow-sm ring-1 ring-black/5">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Supervisores</div>
                                    <div class="mt-2 text-2xl font-black text-gray-900">{{ $supervisorCount }}</div>
                                </div>
                            @else
                                <div class="rounded-2xl bg-white px-4 py-3 text-center shadow-sm ring-1 ring-black/5">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Alcance</div>
                                    <div class="mt-2 text-sm font-black uppercase tracking-[0.18em] text-gray-900">Mi equipo</div>
                                </div>
                            @endunless
                            <div class="rounded-2xl bg-white px-4 py-3 text-center shadow-sm ring-1 ring-black/5">
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Recientes</div>
                                <div class="mt-2 text-2xl font-black text-gray-900">{{ $recentMessages->count() }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <form method="POST" action="{{ $storeRoute }}" class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)]">
                        @csrf

                        <div class="space-y-5">
                            <div class="rounded-3xl border border-gray-200 bg-gray-50 p-5">
                                <h2 class="text-lg font-bold text-gray-900">Redactar mensaje</h2>
                                <p class="mt-1 text-sm text-gray-500">
                                    Usa un título corto y un mensaje claro para que el popup sea fácil de leer.
                                </p>

                                <div class="mt-4 grid gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Título</label>
                                        <input
                                            type="text"
                                            name="title"
                                            value="{{ old('title') }}"
                                            class="mt-1 block w-full rounded-xl border-gray-300 text-sm {{ $ringFocus }}"
                                            placeholder="Ej: Reunión comercial, cambio de meta, aviso operativo"
                                            maxlength="120"
                                        >
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Mensaje</label>
                                        <textarea
                                            name="message"
                                            rows="6"
                                            class="mt-1 block w-full rounded-xl border-gray-300 text-sm {{ $ringFocus }}"
                                            placeholder="Escribe el mensaje que verán en el popup..."
                                            required
                                        >{{ old('message') }}</textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-3xl border border-gray-200 bg-white p-5">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h2 class="text-lg font-bold text-gray-900">Destinatarios</h2>
                                        <p class="mt-1 text-sm text-gray-500">{{ $recipientDescription }}</p>
                                    </div>
                                    <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                        <span x-text="selectedRecipients.length"></span> seleccionado(s)
                                    </div>
                                </div>

                                <div class="mt-4 space-y-4">
                                    @foreach($recipientGroups as $roleName => $users)
                                        @php
                                            $groupIds = $users->pluck('id')->values();
                                        @endphp
                                        <div class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4">
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                <div>
                                                    <div class="text-sm font-semibold text-gray-900">{{ $roleName }}</div>
                                                    <div class="text-xs text-gray-500">{{ $users->count() }} usuario(s)</div>
                                                </div>
                                                <button
                                                    type="button"
                                                    @click="toggleGroup(@js($groupIds)); syncCheckboxes()"
                                                    class="inline-flex items-center justify-center rounded-full border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-100"
                                                >
                                                    Seleccionar grupo
                                                </button>
                                            </div>

                                            <div class="mt-4 grid gap-3 md:grid-cols-2">
                                                @forelse($users as $recipient)
                                                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-gray-200 bg-white px-4 py-3 transition hover:border-gray-300 hover:bg-gray-50">
                                                        <input
                                                            type="checkbox"
                                                            name="recipient_user_ids[]"
                                                            value="{{ $recipient->id }}"
                                                            class="mt-1 rounded border-gray-300 text-slate-900 focus:ring-slate-500"
                                                            data-recipient-checkbox
                                                            x-model.number="selectedRecipients"
                                                            @checked(in_array($recipient->id, old('recipient_user_ids', []), true))
                                                        >
                                                        <span class="min-w-0">
                                                            <span class="block text-sm font-semibold text-gray-900">{{ $recipient->name }}</span>
                                                            <span class="mt-1 block truncate text-xs text-gray-500">{{ $recipient->email }}</span>
                                                        </span>
                                                    </label>
                                                @empty
                                                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white px-4 py-5 text-sm text-gray-500">
                                                        No hay usuarios de este rol disponibles.
                                                    </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="space-y-5">
                            <div class="rounded-3xl border border-gray-200 bg-white p-5">
                                <h2 class="text-lg font-bold text-gray-900">Enviar</h2>
                                <p class="mt-1 text-sm text-gray-500">
                                    El popup aparecerá cuando el usuario destinatario esté dentro del CRM y se refresque su sesión.
                                </p>

                                <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-700">
                                    <div class="font-semibold text-slate-900">Resumen</div>
                                    <div class="mt-2">Destinatarios: <span class="font-semibold" x-text="selectedRecipients.length"></span></div>
                                    <div class="mt-1">Alcance: {{ $reachLabel }}</div>
                                </div>

                                <div class="mt-4 flex justify-end">
                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-2xl px-4 py-3 text-sm font-semibold text-white transition {{ $accentButton }}"
                                    >
                                        Enviar mensaje
                                    </button>
                                </div>
                            </div>

                            <div class="rounded-3xl border border-gray-200 bg-white p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h2 class="text-lg font-bold text-gray-900">Mensajes recientes</h2>
                                        <p class="mt-1 text-sm text-gray-500">Últimos mensajes enviados desde este módulo.</p>
                                    </div>
                                </div>

                                <div class="mt-4 space-y-3">
                                    @forelse($recentMessages as $message)
                                        @php
                                            $recipientNames = $message->recipients->pluck('user.name')->filter()->values();
                                        @endphp
                                        <div class="rounded-2xl border border-gray-200 bg-gray-50 px-4 py-4">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0">
                                                    <div class="text-sm font-semibold text-gray-900">{{ $message->title ?: 'Nuevo mensaje interno' }}</div>
                                                    <div class="mt-1 text-xs text-gray-500">
                                                        {{ optional($message->created_at)->setTimezone(config('app.timezone'))->format('d/m/Y H:i') }}
                                                    </div>
                                                </div>
                                                <div class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                                    {{ $message->recipients->count() }} destinatario(s)
                                                </div>
                                            </div>

                                            <div class="mt-3 text-sm text-gray-700">
                                                {{ $message->message }}
                                            </div>

                                            <div class="mt-3 text-xs text-gray-500">
                                                Para:
                                                {{ $recipientNames->take(4)->implode(', ') }}
                                                @if($recipientNames->count() > 4)
                                                    y {{ $recipientNames->count() - 4 }} más
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="rounded-2xl border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-500">
                                            Aún no has enviado mensajes desde este módulo.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
