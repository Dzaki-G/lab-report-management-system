<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Review SP3 — {{ $form->no_terima_sampel ?? '-' }}
            </h2>
            <a href="{{ route('kepala-divisi.show', $form) }}" class="text-blue-600 hover:underline">← Kembali</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif
            @if(session('info'))
                <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg">
                    {{ session('info') }}
                </div>
            @endif

            {{-- Summary bar --}}
            @php
                $totalSp3    = $form->sp3Documents->count();
                $approvedCnt = $form->sp3Documents->where('review_status', 'approved')->count();
                $rejectedCnt = $form->sp3Documents->whereIn('review_status', ['rejected', 'resubmitted'])->count();
                $pendingCnt  = $totalSp3 - $approvedCnt - $rejectedCnt;
                $allApproved = $totalSp3 > 0 && $approvedCnt === $totalSp3;
                $lhpGenerated = !empty($form->lhp_google_file_id);
                $lhpStatus = $form->lhp_generation_status;
                $lhpGenerating = in_array($lhpStatus, ['queued', 'processing']);
                $lhpFailed = $lhpStatus === 'failed';
            @endphp
            <div class="bg-white shadow-sm sm:rounded-lg p-5">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="font-semibold text-gray-800">{{ $form->customer_name }}</p>
                        <p class="text-sm text-gray-500">No. Terima: {{ $form->no_terima_sampel ?? '-' }} · Deadline: {{ \Carbon\Carbon::parse($form->deadline_date)->format('d M Y') }}</p>
                    </div>
                    <div class="flex gap-3 text-sm">
                        <span class="px-3 py-1 bg-gray-100 rounded-full text-gray-600">{{ $pendingCnt }} menunggu</span>
                        <span class="px-3 py-1 bg-red-100 rounded-full text-red-700">{{ $rejectedCnt }} ditolak/perbaikan</span>
                        <span class="px-3 py-1 bg-green-100 rounded-full text-green-700">{{ $approvedCnt }}/{{ $totalSp3 }} disetujui</span>
                    </div>
                </div>
            </div>

            {{-- SP3 group box with Generate LHP button --}}
            <div class="bg-gray-100 shadow sm:rounded-lg overflow-hidden border border-gray-300">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-300 bg-gray-200">
                    <h3 class="text-base font-semibold text-gray-800">{{ $form->lhp_number ?? 'No LHP belum digenerate' }}</h3>
                    <div>
                        @if($lhpGenerated)
                            <a href="https://docs.google.com/document/d/{{ $form->lhp_google_file_id }}/edit"
                               target="_blank"
                               class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg font-medium transition">
                                📄 Buka LHP
                            </a>
                        @elseif($lhpGenerating)
                            <button type="button" disabled id="lhp-generating-btn"
                                    class="px-4 py-2 bg-amber-500 text-white text-sm rounded-lg font-medium cursor-not-allowed flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Sedang digenerate...
                            </button>
                        @elseif($lhpFailed)
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-red-600">⚠ Generate gagal</span>
                                <form method="POST" action="{{ route('kepala-divisi.generate-lhp', $form) }}">
                                    @csrf
                                    <button type="submit"
                                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg font-medium transition">
                                        ↺ Coba Lagi
                                    </button>
                                </form>
                            </div>
                        @elseif($allApproved)
                            <form method="POST" action="{{ route('kepala-divisi.generate-lhp', $form) }}">
                                @csrf
                                <button type="submit"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg font-medium transition">
                                    📋 Generate LHP
                                </button>
                            </form>
                        @else
                            <button type="button" disabled
                                    title="Setujui semua SP3 terlebih dahulu"
                                    class="px-4 py-2 bg-gray-200 text-gray-400 text-sm rounded-lg font-medium cursor-not-allowed">
                                📋 Generate LHP
                            </button>
                        @endif
                    </div>
                </div>
                <div class="p-5 space-y-6">

            {{-- Per-SP3 review cards --}}
            @foreach($form->sp3Documents as $sp3)
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden
                    {{ $sp3->review_status === 'approved'    ? 'border-l-4 border-green-500' :
                       ($sp3->review_status === 'rejected'   ? 'border-l-4 border-red-500' :
                       ($sp3->review_status === 'resubmitted'? 'border-l-4 border-yellow-400' : 'border-l-4 border-gray-300')) }}">

                    {{-- SP3 card header --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 bg-gray-50 border-b">
                        <div class="flex items-center gap-3">
                            <div>
                                <p class="font-semibold text-gray-800">{{ $sp3->no_sppp ?? $sp3->sp3_number }}</p>
                                <p class="text-sm text-gray-500">Parameter: {{ $sp3->parameter->name ?? '-' }}</p>
                            </div>
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium
                                {{ $sp3->review_status === 'approved'    ? 'bg-green-100 text-green-700' :
                                   ($sp3->review_status === 'rejected'   ? 'bg-red-100 text-red-700' :
                                   ($sp3->review_status === 'resubmitted'? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-500')) }}">
                                {{ match($sp3->review_status) {
                                    'approved'    => 'Disetujui',
                                    'rejected'    => 'Menunggu Perbaikan',
                                    'resubmitted' => 'Sudah Diperbaiki',
                                    default       => 'Belum Direview',
                                } }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($sp3->google_doc_id)
                                <a href="https://docs.google.com/document/d/{{ $sp3->google_doc_id }}/edit"
                                   target="_blank"
                                   class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg font-medium transition">📄 Buka SP3</a>
                            @endif
                            @if(in_array($sp3->review_status, ['pending', 'resubmitted']))
                                <form method="POST" action="{{ route('kepala-divisi.sp3.approve', $sp3) }}" class="inline">
                                    @csrf
                                    <button type="button"
                                            onclick="openApproveModal('{{ route('kepala-divisi.sp3.approve', $sp3) }}', '{{ $sp3->no_sppp ?? $sp3->sp3_number }}')"
                                            class="px-4 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm rounded-lg font-medium transition">
                                        ✓ Setujui
                                    </button>
                                </form>
                                <button type="button"
                                        onclick="openRejectModal('{{ route('kepala-divisi.sp3.reject', $sp3) }}')"
                                        class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm rounded-lg font-medium transition">
                                    ✗ Tolak
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Rejection / resubmission note --}}
                    @if($sp3->rejection_note && in_array($sp3->review_status, ['rejected', 'resubmitted']))
                        <div class="px-5 py-3 bg-red-50 border-b border-red-100 flex items-start gap-2 text-sm">
                            <span class="text-red-400 mt-0.5 flex-shrink-0">⚠</span>
                            <div>
                                <span class="font-medium text-red-700">Catatan penolakan</span>
                                @if($sp3->rejectedByUser)
                                    <span class="text-red-400 text-xs"> · oleh {{ $sp3->rejectedByUser->full_name }}</span>
                                @endif
                                @if($sp3->rejected_at)
                                    <span class="text-red-400 text-xs"> · {{ \Carbon\Carbon::parse($sp3->rejected_at)->format('d M Y, H:i') }}</span>
                                @endif
                                <p class="text-red-600 mt-1">{{ $sp3->rejection_note }}</p>
                                @if($sp3->review_status === 'resubmitted')
                                    <p class="text-yellow-600 mt-1 text-xs font-medium">Analis telah memperbaiki — harap periksa kembali.</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Results table --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase w-10">No</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Nama Sampel / Kode</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Parameter Uji</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Satuan</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Hasil*</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Metode Uji</th>
                                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Analis</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse($sp3->samples as $i => $sample)
                                    @php
                                        $sampleParam = $form->samples
                                            ->firstWhere('id', $sample->id)
                                            ?->sampleParameters
                                            ->firstWhere('parameter_id', $sp3->parameter_id);
                                        $result = $sampleParam?->analysisResult;
                                    @endphp
                                    <tr class="{{ $loop->even ? 'bg-gray-50/50' : '' }}">
                                        <td class="px-4 py-2.5 text-gray-400">{{ $i + 1 }}</td>
                                        <td class="px-4 py-2.5">
                                            <p class="font-medium text-gray-800">{{ $sample->sample_name }}</p>
                                            <p class="text-xs text-gray-400">{{ $sample->sample_code }}</p>
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-700">{{ $sp3->parameter->name ?? '-' }}</td>
                                        <td class="px-4 py-2.5 text-gray-500">{{ $result?->result_unit ?? $sampleParam?->parameter?->default_unit ?? '-' }}</td>
                                        <td class="px-4 py-2.5">
                                            @if($result && $result->result_value !== null)
                                                <span class="font-medium text-gray-900">{{ $result->result_value }}</span>
                                            @else
                                                <span class="text-red-400 text-xs italic">Belum diisi</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-600">{{ $sampleParam?->method ?? $result?->method ?? '-' }}</td>
                                        <td class="px-4 py-2.5 text-gray-500 text-xs">{{ $sampleParam?->filledByAnalyst?->full_name ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-6 text-center text-gray-400 italic">Tidak ada sampel untuk SP3 ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- LCP link (if attached) --}}
                    @if($sp3->lcp_google_file_url)
                        <div class="px-5 py-2.5 bg-blue-50 border-t border-blue-100 text-sm flex items-center gap-2">
                            <a href="{{ $sp3->lcp_google_file_url }}" target="_blank"
                               class="text-blue-600 hover:underline">📎 Lihat LCP</a>
                            @if($sp3->lcp_uploaded_at)
                                <span class="text-gray-400 text-xs">· {{ \Carbon\Carbon::parse($sp3->lcp_uploaded_at)->format('d M Y') }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach

                </div>{{-- end p-5 space-y-6 --}}
            </div>{{-- end SP3 group box --}}

        </div>
    </div>

    {{-- Approve confirmation modal --}}
    <div id="approveModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4 shadow-xl">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">Setujui SP3</h3>
            <p class="text-sm text-gray-500 mb-4">Apakah Anda yakin ingin menyetujui <span id="approveSpNumber" class="font-semibold text-gray-800"></span>?</p>
            <form id="approveForm" method="POST">
                @csrf
                <div class="flex gap-3">
                    <button type="button" onclick="closeApproveModal()"
                            class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm">
                        Batal
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium">
                        ✓ Setujui SP3
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Shared reject modal (action URL set dynamically) --}}
    <div id="rejectModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4 shadow-xl">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">Tolak SP3</h3>
            <p class="text-sm text-gray-500 mb-4">Analis yang mengisi SP3 ini akan dinotifikasi untuk melakukan perbaikan.</p>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Catatan Penolakan <span class="text-red-500">*</span>
                    </label>
                    <textarea name="note" rows="4" required
                              class="w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-red-500 focus:border-red-500"
                              placeholder="Jelaskan apa yang perlu diperbaiki oleh analis..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeRejectModal()"
                            class="flex-1 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm">
                        Batal
                    </button>
                    <button type="submit"
                            class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium">
                        Tolak SP3
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($lhpGenerating)
    <script>
        (function () {
            const statusUrl = "{{ route('kepala-divisi.lhp-status', $form) }}";
            let delay = 3000;
            const maxDelay = 12000;
            let attempts = 0;
            const maxAttempts = 40;

            function poll() {
                if (attempts++ >= maxAttempts) return;
                fetch(statusUrl, { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'completed' || data.status === 'failed' || data.lhp_file_id) {
                            location.reload();
                            return;
                        }
                        delay = Math.min(delay * 1.4, maxDelay);
                        setTimeout(poll, delay);
                    })
                    .catch(() => {
                        delay = Math.min(delay * 1.4, maxDelay);
                        setTimeout(poll, delay);
                    });
            }

            setTimeout(poll, delay);
        })();
    </script>
    @endif

    <script>
        function openApproveModal(actionUrl, spNumber) {
            document.getElementById('approveForm').action = actionUrl;
            document.getElementById('approveSpNumber').textContent = spNumber;
            const modal = document.getElementById('approveModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function closeApproveModal() {
            const modal = document.getElementById('approveModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        document.getElementById('approveModal').addEventListener('click', function (e) {
            if (e.target === this) closeApproveModal();
        });
        function openRejectModal(actionUrl) {
            document.getElementById('rejectForm').action = actionUrl;
            const modal = document.getElementById('rejectModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function closeRejectModal() {
            const modal = document.getElementById('rejectModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        document.getElementById('rejectModal').addEventListener('click', function (e) {
            if (e.target === this) closeRejectModal();
        });
    </script>
</x-app-layout>
