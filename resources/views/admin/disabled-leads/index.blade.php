<x-app-layout>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white shadow rounded-lg p-6">
                <h1 class="text-2xl font-bold mb-6">Leads deshabilitados</h1>

                <div class="space-y-4">
                    @forelse($leads as $lead)
                        @php
                            $phone = optional($lead->phones->first())->phone;
                        @endphp

                        <div class="border rounded-2xl p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="text-sm space-y-1">
                                <div><span class="font-semibold">RUC:</span> {{ $lead->ruc }}</div>
                                <div><span class="font-semibold">Razón Social:</span> {{ $lead->business_name }}</div>
                                <div><span class="font-semibold">Teléfono:</span> {{ $phone ?? '-' }}</div>
                                <div><span class="font-semibold">Motivo:</span> {{ $lead->disabled_reason ?? '-' }}</div>
                                <div><span class="font-semibold">Fecha deshabilitado:</span> {{ optional($lead->disabled_at)->format('d/m/Y H:i') }}</div>
                            </div>

                            <form method="POST" action="{{ route('admin.disabled-leads.reactivate', $lead) }}">
                                @csrf
                                <button type="submit" class="px-4 py-2 rounded-xl bg-black text-white">
                                    Reactivar
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="border border-dashed border-gray-300 rounded-xl p-8 text-center text-gray-500">
                            No hay leads deshabilitados por ahora.
                        </div>
                    @endforelse
                </div>

                <div class="mt-6">
                    {{ $leads->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>