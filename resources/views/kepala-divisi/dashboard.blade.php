<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard Kepala Divisi Teknis
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

            {{-- Pending Review --}}
            @if($pendingReview->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-indigo-700 mb-4">🔬 Form Menunggu Review SP3</h3>
                        <p class="text-sm text-gray-500 mb-4">Semua hasil telah diisi oleh analis. Tinjau setiap SP3 dan approve atau tolak per parameter.</p>
                        <div class="space-y-3">
                            @foreach($pendingReview as $form)
                                <div class="p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-bold text-gray-900">{{ $form->no_terima_sampel ?? $form->lhp_number ?? '-' }}</p>
                                            <p class="text-sm text-gray-600">{{ $form->customer_name }}</p>
                                            <div class="flex flex-wrap gap-2 mt-1">
                                                @php
                                                    $approved = $form->sp3Documents->where('review_status', 'approved')->count();
                                                    $total    = $form->sp3Documents->count();
                                                    $rejected = $form->sp3Documents->whereIn('review_status', ['rejected','resubmitted'])->count();
                                                @endphp
                                                <span class="text-xs text-gray-500">{{ $total }} SP3</span>
                                                <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">{{ $approved }} disetujui</span>
                                                @if($rejected > 0)
                                                    <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700">{{ $rejected }} perbaikan</span>
                                                @endif
                                            </div>
                                        </div>
                                        <a href="{{ route('kepala-divisi.review-lhp', $form) }}"
                                           class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap">
                                            📋 Review SP3
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- In Progress --}}
            @if($inProgress->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-blue-700 mb-4">⏳ Sedang Dalam Pengujian</h3>
                        <div class="space-y-2">
                            @foreach($inProgress as $form)
                                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200">
                                    <div>
                                        <p class="font-medium text-gray-800">{{ $form->no_terima_sampel ?? $form->lhp_number ?? '-' }}</p>
                                        <p class="text-sm text-gray-500">{{ $form->customer_name }}</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-700">Dalam Pengujian</span>
                                        <a href="{{ route('kepala-divisi.show', $form) }}"
                                           class="text-blue-600 hover:underline text-sm">Detail</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Recently Processed --}}
            @if($recentlyApproved->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-green-700 mb-4">✅ Sudah Diproses</h3>
                        <div class="space-y-2">
                            @foreach($recentlyApproved as $form)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border">
                                    <div>
                                        <p class="font-medium text-gray-800">{{ $form->no_terima_sampel ?? $form->lhp_number ?? '-' }}</p>
                                        <p class="text-sm text-gray-500">{{ $form->customer_name }}</p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs px-2 py-1 rounded-full
                                            {{ $form->status === 'selesai' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ $form->status === 'selesai' ? 'Selesai' : 'Menunggu TTD UPA' }}
                                        </span>
                                        <a href="{{ route('kepala-divisi.show', $form) }}"
                                           class="text-blue-600 hover:underline text-sm">Detail</a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Empty State --}}
            @if($pendingReview->isEmpty() && $inProgress->isEmpty() && $recentlyApproved->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <p class="text-gray-500 text-lg">Tidak ada form yang menunggu review</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
