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
                    @foreach($leads as $lead)
                        @php
                            $phone = optional($lead->phones->first())->phone;
                        @endphp

                        <div class="border rounded-xl p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                            <div class="text-sm space-y-1">
                                <div><span class="font-semibold">RUC:</span> {{ $lead->ruc }}</div>
                                <div><span class="font-semibold">Razón Social:</span> {{ $lead->business_name }}</div>
                                <div><span class="font-semibold">Teléfono:</span> {{ $phone ?? '-' }}</div>
                                <div><span class="font-semibold">Motivo:</span> {{ $lead->disabled_reason ?? '-' }}</div>
                            </div>

                            <form method="POST" action="{{ route('admin.disabled-leads.reactivate', $lead) }}">
                                @csrf
                                <button type="submit" class="px-4 py-2 rounded-xl bg-black text-white">
                                    Reactivar
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $leads->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>