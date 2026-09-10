<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard Kepala UPA
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Pending TTD --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-blue-700 mb-4">✍️ Menunggu Tanda Tangan LHP</h3>

                    @if($pendingTtd->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($pendingTtd as $form)
                                <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-bold text-gray-900">{{ $form->no_terima_sampel ?? $form->lhp_number ?? '-' }}</p>
                                            <p class="text-sm text-gray-600">{{ $form->customer_name }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ $form->samples->count() }} sampel •
                                                LHP siap ditandatangani
                                            </p>
                                        </div>
                                        <div class="flex space-x-2">
                                            <a href="{{ route('kepala-upa.show', $form) }}"
                                               class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm font-medium">📋 Detail</a>
                                            <form method="POST" action="{{ route('kepala-upa.sign-lhp', $form) }}" class="inline">
                                                @csrf
                                                <button type="submit"
                                                        onclick="return confirm('Tanda tangani LHP ini?')"
                                                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm font-medium">
                                                    ✍️ Tanda Tangan
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500 text-sm italic">Tidak ada dokumen yang perlu ditandatangani saat ini.</p>
                    @endif
                </div>
            </div>

            @if($pendingTtd->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <p class="text-gray-500 text-lg">Tidak ada form yang menunggu tanda tangan</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
