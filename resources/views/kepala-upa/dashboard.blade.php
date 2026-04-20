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

            {{-- Pending Initial Verification --}}
            @if($pendingVerification->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-yellow-700 mb-4">📋 Menunggu Verifikasi Penerimaan Sampel</h3>
                        <div class="space-y-3">
                            @foreach($pendingVerification as $form)
                                <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-bold text-gray-900">{{ $form->form_number }}</p>
                                            <p class="text-sm text-gray-600">{{ $form->customer_name }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ $form->samples->count() }} sampel • 
                                                Tanggal Masuk: {{ \Carbon\Carbon::parse($form->received_date)->format('d M Y') }}
                                            </p>
                                        </div>
                                        <div class="flex space-x-2">
                                            <a href="{{ route('kepala-upa.show', $form) }}" 
                                               class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm font-medium">📋 Detail</a>
                                            <form method="POST" action="{{ route('kepala-upa.approve', $form) }}" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm font-medium">
                                                    ✓ Approve
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

            {{-- Pending TTD --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-blue-700 mb-4">✍️ Menunggu Tanda Tangan Dokumen</h3>
                    
                    @if($pendingTtd->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($pendingTtd as $form)
                                <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-bold text-gray-900">{{ $form->form_number }}</p>
                                            <p class="text-sm text-gray-600">{{ $form->customer_name }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ $form->samples->count() }} sampel • 
                                                LHP siap ditandatangani
                                            </p>
                                        </div>
                                        <div class="flex space-x-2">
                                            <a href="{{ route('kepala-upa.show', $form) }}" 
                                               class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm font-medium">📋 Detail</a>
                                            <form method="POST" action="{{ route('kepala-upa.approve', $form) }}" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm font-medium">
                                                    ✓ Tanda Tangan
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
                    @else
                        <p class="text-gray-500 text-sm italic">Tidak ada dokumen yang perlu ditandatangani saat ini.</p>
                    @endif
                </div>
            </div>

            {{-- Empty State --}}
            @if($pendingVerification->isEmpty() && $pendingTtd->isEmpty())
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
            document.getElementById('rejectForm').action = '/kepala-upa/form/' + formId + '/reject';
            document.getElementById('rejectModal').classList.remove('hidden');
            document.getElementById('rejectModal').classList.add('flex');
        }
        function hideRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
            document.getElementById('rejectModal').classList.remove('flex');
        }
    </script>
</x-app-layout>
