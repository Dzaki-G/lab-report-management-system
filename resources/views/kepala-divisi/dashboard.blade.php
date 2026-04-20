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

            {{-- Stats Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm text-purple-600 font-medium">Menunggu Verifikasi Form</p>
                            <p class="text-3xl font-bold text-purple-700">{{ $pendingVerification->count() }}</p>
                        </div>
                        <div class="text-purple-400 text-4xl">📋</div>
                    </div>
                </div>
                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm text-indigo-600 font-medium">Menunggu Verifikasi Hasil</p>
                            <p class="text-3xl font-bold text-indigo-700">{{ $pendingResultVerification->count() }}</p>
                        </div>
                        <div class="text-indigo-400 text-4xl">🔬</div>
                    </div>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm text-amber-600 font-medium">Menunggu TTD LHP</p>
                            <p class="text-3xl font-bold text-amber-700">{{ $pendingLhpSignature->count() }}</p>
                        </div>
                        <div class="text-amber-400 text-4xl">✍️</div>
                    </div>
                </div>
            </div>

            {{-- Pending Verification --}}
            @if($pendingVerification->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-purple-700 mb-4">📋 Form Menunggu Verifikasi</h3>
                        <div class="space-y-3">
                            @foreach($pendingVerification as $form)
                                <div class="p-4 bg-purple-50 rounded-lg border border-purple-200">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-bold text-gray-900">{{ $form->form_number }}</p>
                                            <p class="text-sm text-gray-600">{{ $form->customer_name }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ $form->samples->count() }} sampel • 
                                                @php
                                                    $totalParams = $form->samples->sum(fn($s) => $s->sampleParameters->count());
                                                @endphp
                                                {{ $totalParams }} parameter
                                            </p>
                                        </div>
                                        <div class="flex space-x-2">
                                            <a href="{{ route('kepala-divisi.show', $form) }}" 
                                               class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm font-medium">
                                                📋 Assign & Approve
                                            </a>
                                            <button type="button" 
                                                    onclick="showRejectModal({{ $form->id }}, '{{ $form->form_number }}')"
                                                    class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm font-medium">
                                                ✗ Reject
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Pending Result Verification (LCP) --}}
            @if($pendingResultVerification->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-indigo-700 mb-4">🔬 Verifikasi LCP dari Analis</h3>
                        <p class="text-sm text-gray-500 mb-4">Periksa LCP (Lembar Catatan Pengujian) dari analis, lalu approve untuk lanjut ke Admin input LHP.</p>
                        <div class="space-y-4">
                            @foreach($pendingResultVerification as $form)
                                <div class="p-4 bg-indigo-50 rounded-lg border border-indigo-200">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-bold text-gray-900">{{ $form->form_number }}</p>
                                            <p class="text-sm text-gray-600">{{ $form->customer_name }}</p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ $form->sp3Documents->count() }} SP3 •
                                                {{ $form->sp3Documents->whereNotNull('lcp_google_file_url')->count() }} LCP tersedia
                                            </p>
                                            
                                            {{-- LCP Links --}}
                                            <div class="mt-2 space-y-1">
                                                @foreach($form->sp3Documents as $sp3)
                                                    <div class="text-xs">
                                                        <span class="text-gray-600">{{ $sp3->sp3_number }}:</span>
                                                        @if($sp3->lcp_google_file_url)
                                                            <a href="{{ $sp3->lcp_google_file_url }}" target="_blank" class="text-blue-600 hover:underline">
                                                                📄 Lihat LCP
                                                            </a>
                                                        @else
                                                            <span class="text-red-500">⚠ Belum ada LCP</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="flex space-x-2">
                                            <a href="{{ route('kepala-divisi.show', $form) }}" 
                                               class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm font-medium">📋 Detail</a>
                                            <form method="POST" action="{{ route('kepala-divisi.approve-lcp', $form) }}" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm font-medium">
                                                    ✓ Approve LCP → Admin
                                                </button>
                                            </form>
                                            <button type="button" 
                                                    onclick="showRejectModal({{ $form->id }}, '{{ $form->form_number }}')"
                                                    class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm font-medium">
                                                ✗ Reject
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Pending LHP Signature --}}
            @if($pendingLhpSignature->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-amber-700 mb-4">✍️ Menunggu Tanda Tangan LHP</h3>
                        <p class="text-sm text-gray-500 mb-4">Dokumen LHP siap untuk ditandatangani. Setelah Anda tanda tangan, akan diteruskan ke Kepala UPA.</p>
                        <div class="space-y-4">
                            @foreach($pendingLhpSignature as $form)
                                <div class="p-4 bg-amber-50 rounded-lg border border-amber-200">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-bold text-gray-900">{{ $form->form_number }}</p>
                                            <p class="text-sm text-gray-600">{{ $form->customer_name }}</p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                Admin: {{ $form->admin->full_name ?? '-' }}
                                            </p>
                                            
                                            {{-- LHP Link --}}
                                            @if($form->lhp_google_file_url || $form->lhp_link)
                                                <div class="mt-2">
                                                    <a href="{{ $form->lhp_google_file_url ?? $form->lhp_link }}" target="_blank" 
                                                       class="text-blue-600 hover:underline text-sm font-medium">
                                                        📄 Lihat Dokumen LHP
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex space-x-2">
                                            <a href="{{ route('kepala-divisi.show', $form) }}" 
                                               class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm font-medium">📋 Detail</a>
                                            <form method="POST" action="{{ route('kepala-divisi.sign-lhp', $form) }}" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm font-medium">
                                                    ✍️ Tanda Tangan LHP
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            {{-- Empty State --}}
            @if($pendingVerification->isEmpty() && $pendingResultVerification->isEmpty() && $pendingLhpSignature->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <p class="text-gray-500 text-lg">Tidak ada form yang menunggu verifikasi atau tanda tangan</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Reject Modal --}}
    <div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Tolak Form <span id="rejectFormNumber"></span></h3>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Penolakan *</label>
                    <textarea name="note" rows="4" required
                              class="w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500"
                              placeholder="Masukkan alasan penolakan..."></textarea>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="hideRejectModal()" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Batal
                    </button>
                    <button type="submit" 
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md font-medium">
                        Tolak Form
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showRejectModal(formId, formNumber) {
            document.getElementById('rejectFormNumber').textContent = formNumber;
            document.getElementById('rejectForm').action = '/kepala-divisi/form/' + formId + '/reject';
            document.getElementById('rejectModal').classList.remove('hidden');
            document.getElementById('rejectModal').classList.add('flex');
        }
        function hideRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
            document.getElementById('rejectModal').classList.remove('flex');
        }
    </script>
</x-app-layout>
