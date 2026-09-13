<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Form {{ $form->no_terima_sampel ?? '-' }}
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
                                1 => ['status' => 'dalam_pengujian',        'label' => 'Analisis'],
                                2 => ['status' => 'menunggu_review_divisi', 'label' => 'Review Divisi'],
                                3 => ['status' => 'ttd_upa',                'label' => 'TTD UPA'],
                                4 => ['status' => 'kirim_customer',         'label' => 'Kirim Customer'],
                                5 => ['status' => 'selesai',                'label' => 'Selesai'],
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
                            @if($num < 5)
                                <div class="flex-1 h-1 mx-1 min-w-4 {{ $num <= $currentIndex ? 'bg-blue-500' : 'bg-gray-200' }}"></div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            @if($form->status === 'ttd_upa')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 border-l-4 border-blue-500">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Tindakan Diperlukan</h3>
                                <p class="text-gray-600">Form menunggu tanda tangan Kepala UPA pada dokumen LHP.</p>
                            </div>
                            <div class="flex space-x-3">
                                <form method="POST" action="{{ route('kepala-upa.sign-lhp', $form) }}" class="inline">
                                    @csrf
                                    <button type="submit"
                                            onclick="return confirm('Apakah Anda yakin ingin menandatangani LHP ini?')"
                                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-medium">
                                        ✍️ Tanda Tangan LHP
                                    </button>
                                </form>
                            </div>
                        </div>
                        @if(!auth()->user()->signature_drive_file_id)
                            <div class="mt-3 flex items-center gap-2 text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-4 py-2">
                                <span>⚠</span>
                                <span>Anda belum mengupload tanda tangan digital. LHP akan ditandatangani dengan nama teks saja.</span>
                                <a href="{{ route('signature.show') }}" class="ml-auto font-medium underline hover:text-amber-900 whitespace-nowrap">Upload Sekarang</a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Form Info --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-4">Informasi Form</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div><span class="text-gray-500">No. Terima Sampel:</span> <strong>{{ $form->no_terima_sampel ?? '-' }}</strong></div>
                                <div><span class="text-gray-500">Customer:</span> <strong>{{ $form->customer_name }}</strong></div>
                                <div><span class="text-gray-500">Tanggal Masuk:</span> {{ \Carbon\Carbon::parse($form->received_date)->format('d M Y') }}</div>
                                <div>
                                    <span class="text-gray-500">Deadline:</span>
                                    @php
                                        $deadline = \Carbon\Carbon::parse($form->deadline_date);
                                        $today = \Carbon\Carbon::today();
                                        $daysLeft = $today->diffInDays($deadline, false);
                                        $isOverdue = $daysLeft < 0;
                                        $isUrgent = !$isOverdue && $daysLeft <= 3;
                                        $isCompleted = in_array($form->status, ['selesai', 'ditolak']);
                                    @endphp
                                    <strong>{{ $deadline->format('d M Y') }}</strong>
                                    @if($isCompleted)
                                        <span class="text-sm text-gray-400">-</span>
                                    @elseif($isOverdue)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 border border-red-200">{{ abs($daysLeft) }} hari terlambat</span>
                                    @elseif($daysLeft == 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-50 text-red-700 border border-red-200">Hari ini!</span>
                                    @elseif($isUrgent)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200">{{ $daysLeft }} hari lagi</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">{{ $daysLeft }} hari lagi</span>
                                    @endif
                                </div>
                                <div><span class="text-gray-500">Status:</span> 
                                    <span class="px-2 py-1 rounded text-sm bg-blue-100 text-blue-700">{{ $form->status_label }}</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- LHP Document --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Dokumen LHP</h3>
                    <div class="flex items-center space-x-4">
                        @if($form->lhp_google_file_id)
                            <a href="https://docs.google.com/document/d/{{ $form->lhp_google_file_id }}/edit" target="_blank"
                               class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md">
                                📄 Lihat Dokumen LHP
                            </a>
                            <p class="text-sm text-gray-500">Harap periksa dokumen ini sebelum menandatangani.</p>
                        @else
                            <p class="text-gray-500 italic">Dokumen LHP belum tersedia.</p>
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
                                                {{ $sp->filledByAnalyst->full_name ?? '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
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
</x-app-layout>
