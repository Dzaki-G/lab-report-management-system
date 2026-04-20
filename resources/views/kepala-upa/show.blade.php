<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Form {{ $form->form_number }}
            </h2>
            <a href="{{ route('kepala-upa.dashboard') }}" class="text-blue-600 hover:underline">← Kembali</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Progress Steps --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Progress Verifikasi</h3>
                    <div class="flex items-center justify-between overflow-x-auto pb-2">
                        @php
                            $steps = [
                                1 => ['status' => 'verifikasi_upa_1', 'label' => 'Ver. UPA'],
                                2 => ['status' => 'verifikasi_divisi', 'label' => 'Ver. Divisi'],
                                3 => ['status' => 'dalam_pengujian', 'label' => 'Pengujian'],
                                4 => ['status' => 'verifikasi_hasil_divisi', 'label' => 'Ver. Hasil'],
                                5 => ['status' => 'input_lhp', 'label' => 'Input LHP'],
                                6 => ['status' => 'ttd_divisi_lhp', 'label' => 'TTD Divisi'],
                                7 => ['status' => 'ttd_upa', 'label' => 'TTD UPA'],
                                8 => ['status' => 'kirim_customer', 'label' => 'Kirim'],
                                9 => ['status' => 'selesai', 'label' => 'Selesai'],
                            ];
                            $statusOrder = array_column($steps, 'status');
                            $currentIndex = array_search($form->status, $statusOrder);
                            if ($currentIndex === false) $currentIndex = -1;
                        @endphp
                        @foreach($steps as $num => $step)
                            <div class="flex flex-col items-center min-w-[60px]">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                                    {{ $num <= $currentIndex + 1 ? ($form->status == 'selesai' ? 'bg-green-500 text-white' : 'bg-blue-500 text-white') : 'bg-gray-200 text-gray-500' }}">
                                    @if($num <= $currentIndex)
                                        ✓
                                    @else
                                        {{ $num }}
                                    @endif
                                </div>
                                <span class="text-xs mt-1 text-center {{ $form->status == $step['status'] ? 'font-bold text-blue-600' : 'text-gray-500' }}">{{ $step['label'] }}</span>
                                @if($verifierName = $form->getVerifierName($step['status']))
                                    <span class="text-[10px] text-gray-500 mt-0.5 text-center leading-tight">{{ $verifierName }}</span>
                                @endif
                            </div>
                            @if($num < 9)
                                <div class="flex-1 h-1 mx-1 min-w-4 {{ $num <= $currentIndex ? 'bg-blue-500' : 'bg-gray-200' }}"></div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Action Buttons (Only for Kepala UPA when status matches) --}}
            @if(in_array($form->status, ['verifikasi_upa_1', 'ttd_upa']))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 border-l-4 border-blue-500">
                    <div class="p-6 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Tindakan Diperlukan</h3>
                            <p class="text-gray-600">
                                @if($form->status == 'verifikasi_upa_1')
                                    Form menunggu verifikasi penerimaan sampel.
                                @else
                                    Form menunggu tanda tangan Kepala UPA.
                                @endif
                            </p>
                        </div>
                        <div class="flex space-x-3">
                            <button type="button" 
                                    onclick="showRejectModal({{ $form->id }}, '{{ $form->form_number }}')"
                                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md font-medium">
                                ✗ Tolak
                            </button>
                            <form method="POST" action="{{ route('kepala-upa.approve', $form) }}" class="inline">
                                @csrf
                                <button type="submit" 
                                        onclick="return confirm('Apakah Anda yakin?')"
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-medium">
                                    @if($form->status == 'verifikasi_upa_1')
                                        ✓ Verifikasi
                                    @else
                                        ✓ Tanda Tangan Selesai
                                    @endif
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

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
                                Tolak
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

            {{-- Form Info --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-4">Informasi Form</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div><span class="text-gray-500">No Form:</span> <strong>{{ $form->form_number }}</strong></div>
                                <div><span class="text-gray-500">Customer:</span> <strong>{{ $form->customer_name }}</strong></div>
                                <div><span class="text-gray-500">Tanggal Masuk:</span> {{ \Carbon\Carbon::parse($form->received_date)->format('d M Y') }}</div>
                                <div><span class="text-gray-500">Deadline:</span> {{ \Carbon\Carbon::parse($form->deadline_date)->format('d M Y') }}</div>
                                <div><span class="text-gray-500">Status:</span> 
                                    <span class="px-2 py-1 rounded text-sm bg-blue-100 text-blue-700">{{ $form->status_label }}</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- SPU Document --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Dokumen SPU</h3>
                    <div class="flex items-center space-x-4">
                         @if($form->spu_signed_doc_id)
                            <a href="https://docs.google.com/document/d/{{ $form->spu_signed_doc_id }}/edit" target="_blank"
                               class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md">
                                📄 Lihat SPU (Signed)
                            </a>
                        @elseif($form->spu_unsigned_doc_id)
                            <a href="https://docs.google.com/document/d/{{ $form->spu_unsigned_doc_id }}/edit" target="_blank"
                               class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-md">
                                📄 Lihat SPU (Unsigned)
                            </a>
                            <p class="text-sm text-gray-500 ml-2">Harap periksa dokumen ini sebelum melakukan verifikasi/tanda tangan.</p>
                        @else
                            <p class="text-gray-500 italic">Dokumen belum tersedia.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Samples & Results --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Daftar Sampel & Hasil Pengujian</h3>
                    @foreach($form->samples as $sample)
                        <div class="mb-4 p-4 border rounded-lg">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <p class="font-bold text-gray-900">{{ $sample->sample_code }} - {{ $sample->sample_name }}</p>
                                    <p class="text-sm text-gray-500">Jumlah: {{ $sample->quantity }} {{ $sample->unit }}</p>
                                </div>
                            </div>
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Parameter</th>
                                        <th class="px-3 py-2 text-left">Status</th>
                                        <th class="px-3 py-2 text-left">Hasil</th>
                                        <th class="px-3 py-2 text-left">Analis</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach($sample->sampleParameters as $sp)
                                        <tr>
                                            <td class="px-3 py-2">{{ $sp->parameter->name ?? 'N/A' }}</td>
                                            <td class="px-3 py-2">
                                                @if($sp->status === 'done')
                                                    <span class="text-green-600">✓ Selesai</span>
                                                @elseif($sp->status === 'in_progress')
                                                    <span class="text-blue-600">⏳ Sedang dikerjakan</span>
                                                @else
                                                    <span class="text-gray-500">Pending</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 font-medium">
                                                {{ $sp->analysisResult->result_value ?? '-' }}
                                            </td>
                                            <td class="px-3 py-2 text-gray-600">
                                                {{ $sp->assignedAnalyst->full_name ?? '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            {{-- Log Verifikasi --}}
            @if($form->verifications->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4">Log Verifikasi</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-amber-100">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Keterangan</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($form->verifications->sortByDesc('created_at') as $log)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm">
                                                <span class="text-gray-600">{{ \App\Models\FormVerification::getStatusLabel($log->from_status) }}</span>
                                                <span class="text-gray-400 mx-1">→</span>
                                                <span class="font-medium text-gray-900">{{ \App\Models\FormVerification::getStatusLabel($log->to_status) }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-sm">
                                                <span class="font-medium {{ $log->action === 'reject' ? 'text-red-600' : 'text-green-600' }}">{{ $log->action_label }}</span>
                                                <span class="text-gray-600">oleh {{ $log->verifier->full_name ?? 'System' }}</span>
                                                @if($log->note)
                                                    <div class="text-red-600 text-xs mt-1">Catatan: {{ $log->note }}</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500">
                                                {{ $log->created_at->format('d M Y, H:i') }} WIB
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
