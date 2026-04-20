<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Validasi Akhir Form Pengujian
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Form Menunggu Validasi Akhir</h3>
                    
                    @if($forms->isEmpty())
                        <p class="text-gray-500 text-center py-8">Tidak ada form yang menunggu validasi</p>
                    @else
                        <div class="space-y-4">
                            @foreach($forms as $form)
                                <div class="p-4 border rounded-lg bg-amber-50 border-amber-200">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-bold text-gray-900">{{ $form->form_number }}</p>
                                            <p class="text-sm text-gray-600">{{ $form->customer_name }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ $form->samples->count() }} sampel • 
                                                Deadline: {{ \Carbon\Carbon::parse($form->deadline_date)->format('d M Y') }}
                                            </p>
                                        </div>
                                        <div class="flex space-x-2">
                                            <a href="{{ route('form.show', $form) }}" 
                                               class="text-blue-600 hover:text-blue-800 text-sm font-medium">Detail</a>
                                            <form method="POST" action="{{ route('form.approve-validasi', $form) }}" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm font-medium">
                                                    ✓ Validasi Selesai
                                                </button>
                                            </form>
                                            <button type="button" 
                                                    onclick="showRejectModal({{ $form->id }}, '{{ $form->form_number }}')"
                                                    class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm font-medium">
                                                ✗ Tolak
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Reject Modal --}}
    <div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Tolak Validasi <span id="rejectFormNumber"></span></h3>
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
                        Tolak
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showRejectModal(formId, formNumber) {
            document.getElementById('rejectFormNumber').textContent = formNumber;
            document.getElementById('rejectForm').action = '/form-pengujian/' + formId + '/reject-validasi';
            document.getElementById('rejectModal').classList.remove('hidden');
            document.getElementById('rejectModal').classList.add('flex');
        }
        function hideRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
            document.getElementById('rejectModal').classList.remove('flex');
        }
    </script>
</x-app-layout>
