<x-app-layout>
    <x-slot name="header">
        <div>
            <div class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Configuración personal</div>
            <h2 class="mt-1 text-3xl font-semibold text-slate-900">
                Mi perfil
            </h2>
            <p class="mt-2 max-w-3xl text-sm text-slate-500">
                Ajusta solo tu experiencia visual: foto, colores y modo claro u oscuro.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-7">
                <div class="max-w-4xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
