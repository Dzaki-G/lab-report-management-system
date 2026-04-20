<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Form {{ $form->form_number }}
            </h2>
            <a href="{{ route('kepala-divisi.dashboard') }}" class="text-blue-600 hover:underline">← Kembali</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Progress Steps --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Progress Verifikasi</h3>
                    {{-- Progress Steps --}}
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

            {{-- Card 1: Dokumen SPU (Tanda Tangan) --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">1. Dokumen Surat Perintah Uji (SPU)</h3>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border">
                        <div>
                            @if($form->spu_signed_doc_id)
                                <a href="https://docs.google.com/document/d/{{ $form->spu_signed_doc_id }}" target="_blank" 
                                   class="text-blue-600 font-medium hover:underline flex items-center">
                                    📄 Lihat Dokumen SPU
                                </a>
                                <p class="text-xs text-gray-500 mt-1">
                                    Status TTD UPA: 
                                    @if($form->spu_signed_at)
                                        <span class="text-green-600 font-bold">✓ Sudah</span>
                                    @else
                                        <span class="text-orange-500">Belum</span>
                                    @endif
                                    <br>
                                    Status TTD Divisi: 
                                    @if($form->spu_signed_divisi_at)
                                        <span class="text-green-600 font-bold">✓ Sudah ({{ $form->spu_signed_divisi_at->format('d M Y, H:i') }})</span>
                                    @else
                                        <span class="text-orange-500">Belum</span>
                                    @endif
                                </p>
                            @else
                                <span class="text-gray-500 italic">Dokumen SPU belum tersedia</span>
                            @endif
                        </div>
                        
                        @if($form->status === 'verifikasi_divisi' && $form->spu_signed_doc_id && !$form->spu_signed_divisi_at)
                            <div>
                                <h4 class="text-sm font-semibold mb-2 text-gray-700">Aksi Kepala Divisi:</h4>
                                <form action="{{ route('kepala-divisi.sign-spu', $form) }}" method="POST">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Apakah Anda yakin ingin menandatangani SPU ini?')"
                                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
                                        ✍️ Tanda Tangan SPU
                                    </button>
                                </form>
                            </div>
                        @elseif($form->spu_signed_divisi_at)
                            <div class="bg-green-100 text-green-800 px-4 py-2 rounded-md text-sm font-medium flex items-center">
                                ✓ SPU Sudah Ditandatangani
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Card 2: Surat Perintah Persiapan Pengujian (SP3) --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 {{ !$form->spu_signed_divisi_at ? 'opacity-60' : '' }}">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                        2. Surat Perintah Persiapan Pengujian (SP3)
                        @if(!$form->spu_signed_divisi_at)
                            <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">🔒 Selesaikan Step 1 dulu</span>
                        @endif
                    </h3>
                    @if(!$form->spu_signed_divisi_at)
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                            <p class="text-sm text-yellow-700">
                                ⚠️ <strong>SPU belum ditandatangani.</strong> 
                                Silakan tanda tangani SPU di Step 1 terlebih dahulu sebelum mengisi data SP3.
                            </p>
                        </div>
                    @else
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                            <p class="text-sm text-blue-700">
                                Silakan lengkapi data <strong>No. SPPP</strong>, <strong>Instruksi Kerja (IK)</strong>, dan <strong>Analis</strong> untuk setiap dokumen SP3 di bawah ini.
                                <br>Dokumen di Google Docs akan otomatis terupdate saat Anda klik Simpan.
                            </p>
                        </div>
                    @endif

                    <div class="space-y-6">
                        @forelse($form->sp3Documents as $sp3)
                            <div class="border rounded-lg p-4 {{ $sp3->status === 'assigned' ? 'bg-green-50 border-green-200' : 'bg-gray-50' }}">
                                <div class="flex justify-between items-start mb-4 border-b pb-2">
                                    <div>
                                        <h4 class="font-bold text-lg text-gray-800 flex items-center gap-2">
                                            {{ $sp3->sp3_number }}
                                            @if($sp3->status === 'assigned')
                                                <span class="text-xs bg-green-500 text-white px-2 py-1 rounded">✓ Sudah Diisi</span>
                                            @else
                                                <span class="text-xs bg-orange-400 text-white px-2 py-1 rounded">Belum Diisi</span>
                                            @endif
                                        </h4>
                                        <p class="text-sm text-gray-600">Parameter: <strong>{{ $sp3->parameter->name ?? '-' }}</strong></p>
                                    </div>
                                    <a href="https://docs.google.com/document/d/{{ $sp3->google_doc_id }}" target="_blank"
                                       class="text-blue-600 hover:underline text-sm bg-white px-3 py-1 rounded border shadow-sm">
                                        📄 Buka Dokumen SP3
                                    </a>
                                </div>

                                <form action="{{ route('kepala-divisi.update-sp3', $sp3) }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                                    @csrf
                                    
                                    {{-- No SPPP (read-only, filled by Admin) --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">No. SPPP</label>
                                        <div class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 bg-gray-50 text-gray-700">
                                            {{ $sp3->no_sppp ?: 'Belum diisi oleh Admin' }}
                                        </div>
                                    </div>

                                    {{-- IK (read-only, filled by Admin) --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Instruksi Kerja</label>
                                        <div class="w-full text-sm border border-gray-200 rounded-md px-3 py-2 bg-gray-50 text-gray-700">
                                            {{ $sp3->ik ?: 'Belum diisi oleh Admin' }}
                                        </div>
                                    </div>

                                    {{-- Analis --}}
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Analis Bertugas *</label>
                                        <select name="assigned_analyst_id" 
                                                class="w-full text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                required {{ ($form->status !== 'verifikasi_divisi' || !$form->spu_signed_divisi_at) ? 'disabled' : '' }}>
                                            <option value="">-- Pilih Analis --</option>
                                            @foreach($analysts as $analyst)
                                                <option value="{{ $analyst->user_id }}" 
                                                    {{ $sp3->assigned_analyst_id == $analyst->user_id ? 'selected' : '' }}>
                                                    {{ $analyst->full_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Button --}}
                                    <div>
                                        @if($form->status === 'verifikasi_divisi' && $form->spu_signed_divisi_at)
                                            <button type="submit" 
                                                    class="w-full bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-md text-sm font-medium shadow-sm transition">
                                                💾 Simpan & Update Doc
                                            </button>
                                        @elseif(!$form->spu_signed_divisi_at)
                                            <span class="block w-full text-center text-gray-400 text-sm py-2 bg-gray-100 rounded border">
                                                🔒 Selesaikan Step 1
                                            </span>
                                        @else
                                            <span class="block w-full text-center text-gray-500 text-sm py-2 bg-gray-100 rounded border">
                                                Terverifikasi
                                            </span>
                                        @endif
                                    </div>
                                </form>
                            </div>
                        @empty
                            <div class="text-center py-8 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                                <p class="text-gray-500 italic">Belum ada dokumen SP3 yang digenerate.</p>
                                <p class="text-xs text-gray-400">Pastikan SPU sudah diapprove oleh Kepala UPA.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Card 3: Final Approval --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">3. Persetujuan Akhir (Approval)</h3>
                    
                    @if($form->status === 'verifikasi_divisi')
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600 max-w-2xl">
                                <p class="mb-2">Pastikan tahapan berikut selesai sebelum Approve:</p>
                                <ul class="list-disc pl-5 space-y-1">
                                    <li class="{{ $form->spu_signed_divisi_at ? 'text-green-600' : 'text-red-500' }}">
                                        {{ $form->spu_signed_divisi_at ? '✓' : '✗' }} SPU Ditandatangani oleh Kepala Divisi
                                    </li>
                                    <li class="{{ ($form->sp3Documents->count() > 0 && $form->sp3Documents->whereNull('assigned_analyst_id')->count() == 0) ? 'text-green-600' : 'text-red-500' }}">
                                        {{ ($form->sp3Documents->count() > 0 && $form->sp3Documents->whereNull('assigned_analyst_id')->count() == 0) ? '✓' : '✗' }} Semua SP3 sudah dilengkapi dan memiliki Analis ({{ $form->sp3Documents->count() - $form->sp3Documents->whereNull('assigned_analyst_id')->count() }}/{{ $form->sp3Documents->count() }})
                                    </li>
                                </ul>
                            </div>

                            <div class="flex gap-4">
                                <form action="{{ route('kepala-divisi.approve', $form) }}" method="POST">
                                    @csrf
                                    @php
                                        $canApprove = $form->spu_signed_divisi_at && 
                                                      $form->sp3Documents->count() > 0 && 
                                                      $form->sp3Documents->whereNull('assigned_analyst_id')->count() == 0;
                                    @endphp
                                    <button type="submit" 
                                            class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                                            {{ !$canApprove ? 'disabled' : '' }}>
                                        ✓ Approve & Kirim ke Analis
                                    </button>
                                </form>
                                <button onclick="showRejectModal()" 
                                        class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-lg">
                                    ✗ Tolak
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="p-4 bg-green-50 text-green-700 rounded-lg flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Form ini sudah disetujui dan sedang dalam tahap pengujian.
                        </div>
                        
                        @if($form->status === 'verifikasi_hasil_divisi')
                             <!-- Tombol Approve LCP - verifikasi hasil dari analis, kirim ke Admin untuk LHP -->
                             <div class="mt-4 flex justify-end gap-4">
                                <button onclick="showRejectModal()" 
                                        class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg">
                                    ✗ Tolak
                                </button>
                                <form action="{{ route('kepala-divisi.approve-lcp', $form) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg">
                                        ✓ Approve LCP & Kirim ke Admin
                                    </button>
                                </form>
                             </div>
                        @endif
                        
                        @if($form->status === 'ttd_divisi_lhp')
                             <!-- Section TTD LHP oleh Kepala Divisi -->
                             <div class="mt-4 p-4 bg-amber-50 rounded-lg border border-amber-200">
                                <h4 class="font-semibold text-amber-700 mb-3">✍️ Tanda Tangan Dokumen LHP</h4>
                                <p class="text-sm text-gray-600 mb-4">
                                    Dokumen LHP sudah di-input oleh Admin. Silakan review dan tanda tangani untuk melanjutkan ke Kepala UPA.
                                </p>
                                
                                {{-- LHP Document Link --}}
                                @if($form->lhp_google_file_url || $form->lhp_link)
                                    <div class="mb-4">
                                        <a href="{{ $form->lhp_google_file_url ?? $form->lhp_link }}" target="_blank" 
                                           class="inline-flex items-center text-blue-600 hover:underline font-medium">
                                            📄 Lihat Dokumen LHP
                                        </a>
                                    </div>
                                @else
                                    <div class="mb-4 text-red-500">
                                        ⚠️ Dokumen LHP belum tersedia
                                    </div>
                                @endif
                                
                                <form action="{{ route('kepala-divisi.sign-lhp', $form) }}" method="POST">
                                    @csrf
                                    <button type="submit" 
                                            class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg">
                                        ✍️ Tanda Tangan LHP & Kirim ke Kepala UPA
                                    </button>
                                </form>
                             </div>
                        @endif
                    @endif
                </div>
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

    {{-- Reject Modal --}}
    <div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Tolak Form</h3>
            <form action="{{ route('kepala-divisi.reject', $form) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Penolakan</label>
                    <textarea name="note" rows="4" required
                              class="w-full border-gray-300 rounded-md shadow-sm"
                              placeholder="Jelaskan alasan penolakan..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="hideRejectModal()" 
                            class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg">
                        Batal
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg">
                        Tolak
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showRejectModal() {
            document.getElementById('rejectModal').classList.remove('hidden');
            document.getElementById('rejectModal').classList.add('flex');
        }
        function hideRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
            document.getElementById('rejectModal').classList.remove('flex');
        }
        function validateAndSubmit() {
            const selects = document.querySelectorAll('.analyst-select');
            let allSelected = true;
            let firstEmpty = null;
            
            selects.forEach(select => {
                if (!select.value) {
                    allSelected = false;
                    select.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                    if (!firstEmpty) firstEmpty = select;
                } else {
                    select.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
                }
            });
            
            if (!allSelected) {
                document.getElementById('validationError').classList.remove('hidden');
                if (firstEmpty) firstEmpty.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            
            document.getElementById('validationError').classList.add('hidden');
            document.getElementById('assignForm').submit();
        }
    </script>
</x-app-layout>

