<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard Analis</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">{{ session('error') }}</div>
            @endif

            {{-- Tabs --}}
            <div class="flex gap-2 mb-6 border-b border-gray-200">
                <button onclick="showTab('aktif')" id="tab-aktif"
                        class="tab-btn px-5 py-2.5 text-sm font-bold border-b-2 border-indigo-600 text-indigo-600 -mb-px transition-colors">
                    Aktif <span class="ml-1 px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 text-xs">{{ $activeSp3s->count() }}</span>
                </button>
                <button onclick="showTab('riwayat')" id="tab-riwayat"
                        class="tab-btn px-5 py-2.5 text-sm font-bold border-b-2 border-transparent text-gray-500 hover:text-gray-700 -mb-px transition-colors">
                    Riwayat <span class="ml-1 px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs">{{ $historySp3s->count() }}</span>
                </button>
            </div>

            {{-- Tab: Aktif --}}
            <div id="panel-aktif">
                @if($activeSp3s->isEmpty())
                    <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400">
                        Tidak ada SP3 yang sedang dalam pengujian.
                    </div>
                @else
                    <div class="space-y-5">
                        @foreach($activeSp3s as $sp3)
                            @php
                                $sps       = $sp3->sampleParameters;
                                $total     = $sps->count();
                                $done      = $sps->where('status', 'done')->count();
                                $pct       = $total > 0 ? round($done / $total * 100) : 0;
                                $isRejected   = $sp3->review_status === 'rejected';
                                $isResubmitted = $sp3->review_status === 'resubmitted';
                            @endphp
                            <div class="bg-white rounded-2xl border shadow-sm overflow-hidden
                                {{ $isRejected ? 'border-red-300' : ($isResubmitted ? 'border-amber-300' : 'border-gray-100') }}">

                                {{-- Card Header --}}
                                <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3
                                    {{ $isRejected ? 'bg-red-50' : ($isResubmitted ? 'bg-amber-50' : 'bg-gray-50/50') }}">
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="font-bold text-gray-900 text-lg">{{ $sp3->sp3_number }}</span>
                                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
                                                {{ $sp3->parameter->name ?? '-' }}
                                            </span>
                                            @if($isRejected)
                                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">⚠ DITOLAK</span>
                                            @elseif($isResubmitted)
                                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">↩ Dikirim Ulang</span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-gray-500 mt-1 flex items-center gap-2 flex-wrap">
                                            No. Terima: <span class="font-medium text-gray-700">{{ $sp3->form->no_terima_sampel ?? '-' }}</span>
                                            &bull; Deadline: <span class="font-medium text-gray-700">{{ \Carbon\Carbon::parse($sp3->form->deadline_date)->format('d M Y') }}</span>
                                            @php
                                                $dl = \Carbon\Carbon::parse($sp3->form->deadline_date);
                                                $dlDays = \Carbon\Carbon::today()->diffInDays($dl, false);
                                                $dlCompleted = in_array($sp3->form->status, ['selesai', 'ditolak']);
                                            @endphp
                                            @if(!$dlCompleted)
                                                @if($dlDays < 0)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 border border-red-200">{{ abs($dlDays) }} hari terlambat</span>
                                                @elseif($dlDays == 0)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-50 text-red-700 border border-red-200">Hari ini!</span>
                                                @elseif($dlDays <= 3)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200">{{ $dlDays }} hari lagi</span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">{{ $dlDays }} hari lagi</span>
                                                @endif
                                            @endif
                                        </p>
                                    </div>
                                    <div class="flex flex-col items-end gap-1 min-w-[120px]">
                                        <span class="text-sm font-bold {{ $done === $total ? 'text-emerald-600' : 'text-gray-600' }}">
                                            {{ $done }}/{{ $total }} selesai
                                        </span>
                                        <div class="w-32 h-2 bg-gray-200 rounded-full overflow-hidden">
                                            <div class="h-2 rounded-full {{ $done === $total ? 'bg-emerald-500' : 'bg-indigo-500' }} transition-all"
                                                 style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Rejection note --}}
                                @if($isRejected && $sp3->rejection_note)
                                    <div class="px-5 py-3 bg-red-50 border-t border-red-200 flex items-start gap-2">
                                        <span class="text-red-500 mt-0.5">⚠</span>
                                        <div>
                                            <p class="text-sm font-semibold text-red-700">Catatan Kepala Divisi:</p>
                                            <p class="text-sm text-red-600">{{ $sp3->rejection_note }}</p>
                                        </div>
                                    </div>
                                @endif

                                {{-- Sample rows --}}
                                <div class="divide-y divide-gray-100">
                                    @forelse($sps as $sp)
                                        <div class="flex items-center justify-between px-5 py-3 hover:bg-gray-50/50 transition-colors">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="w-2 h-2 rounded-full flex-shrink-0 {{ $sp->status === 'done' ? 'bg-emerald-500' : 'bg-gray-300' }}"></div>
                                                <div class="min-w-0">
                                                    <p class="font-mono text-sm font-semibold text-gray-800 truncate">{{ $sp->sample->sample_code ?? '-' }}</p>
                                                    <p class="text-xs text-gray-500 truncate">{{ $sp->sample->sample_name ?? '-' }}</p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3 flex-shrink-0 ml-4">
                                                @if($sp->status === 'done')
                                                    <div class="text-right hidden sm:block">
                                                        <p class="text-xs text-gray-500">{{ $sp->analysisResult?->result_value ?? '-' }} {{ $sp->analysisResult?->result_unit ?? '' }}</p>
                                                        <p class="text-xs text-gray-400">{{ $sp->filledByAnalyst?->full_name ?? '' }}</p>
                                                    </div>
                                                    <span class="px-2 py-1 rounded-md text-xs font-semibold bg-emerald-100 text-emerald-700">Selesai</span>
                                                    <a href="{{ route('analis.input', $sp) }}"
                                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                                                        Edit
                                                    </a>
                                                @else
                                                    <span class="px-2 py-1 rounded-md text-xs font-semibold bg-amber-100 text-amber-700">Pending</span>
                                                    <a href="{{ route('analis.input', $sp) }}"
                                                       class="px-3 py-1.5 rounded-lg text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white transition-colors">
                                                        Input
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <p class="px-5 py-3 text-sm text-gray-400 italic">Tidak ada sampel.</p>
                                    @endforelse
                                </div>

                                {{-- LCP footer --}}
                                <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/30 flex items-center justify-between gap-3 flex-wrap">
                                    <div class="flex items-center gap-2 text-sm">
                                        @if($sp3->lcp_google_file_url)
                                            <span class="text-emerald-600 font-medium">✓ LCP sudah diinput</span>
                                            <a href="{{ $sp3->lcp_google_file_url }}" target="_blank"
                                               class="text-blue-600 hover:underline text-xs">Lihat</a>
                                        @else
                                            <span class="text-gray-400 text-xs">LCP belum diinput</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('analis.sp3.show', $sp3) }}"
                                       class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-purple-100 hover:bg-purple-200 text-purple-700 transition-colors">
                                        {{ $sp3->lcp_google_file_url ? 'Update LCP' : 'Input LCP' }}
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Tab: Riwayat --}}
            <div id="panel-riwayat" class="hidden">
                @if($historySp3s->isEmpty())
                    <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400">
                        Belum ada riwayat pengerjaan.
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($historySp3s as $sp3)
                            @php
                                $dl = \Carbon\Carbon::parse($sp3->form->deadline_date);
                                $dlDays = \Carbon\Carbon::today()->diffInDays($dl, false);
                                $dlCompleted = in_array($sp3->form->status, ['selesai', 'ditolak']);
                            @endphp
                            <div class="bg-white rounded-xl border border-gray-100 p-4 flex items-center justify-between gap-3 shadow-sm">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-gray-800">{{ $sp3->sp3_number }}</span>
                                        <span class="text-xs text-gray-500">{{ $sp3->parameter->name ?? '-' }}</span>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        No. Terima: {{ $sp3->form->no_terima_sampel ?? '-' }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0 flex-wrap justify-end">
                                    {{-- Sisa Waktu badge — visible on card --}}
                                    @if($dlCompleted)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">Selesai</span>
                                    @elseif($dlDays < 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 border border-red-200">{{ abs($dlDays) }} hari terlambat</span>
                                    @elseif($dlDays == 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-50 text-red-700 border border-red-200">Hari ini!</span>
                                    @elseif($dlDays <= 3)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200">{{ $dlDays }} hari lagi</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">{{ $dlDays }} hari lagi</span>
                                    @endif
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        {{ $sp3->form->status === 'selesai' ? 'bg-green-100 text-green-700' : 'bg-indigo-100 text-indigo-700' }}">
                                        {{ \App\Models\FormVerification::getStatusLabel($sp3->form->status) }}
                                    </span>
                                    <button onclick="openRiwayatModal('modal-{{ $sp3->id }}')"
                                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition-colors">
                                        Detail
                                    </button>
                                </div>
                            </div>

                            {{-- Modal for this SP3 --}}
                            <div id="modal-{{ $sp3->id }}"
                                 class="fixed inset-0 z-50 hidden flex items-center justify-center p-4"
                                 onclick="closeRiwayatModal(event, 'modal-{{ $sp3->id }}')">
                                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
                                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] overflow-y-auto">
                                    {{-- Modal Header --}}
                                    <div class="sticky top-0 bg-white border-b border-gray-100 px-6 py-4 flex items-start justify-between rounded-t-2xl">
                                        <div>
                                            <h3 class="font-bold text-gray-900 text-lg">{{ $sp3->sp3_number }}</h3>
                                            <p class="text-sm text-gray-500">{{ $sp3->parameter->name ?? '-' }} &bull; {{ $sp3->form->no_terima_sampel ?? '-' }}</p>
                                        </div>
                                        <button onclick="closeRiwayatModalById('modal-{{ $sp3->id }}')"
                                                class="ml-4 text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                                    </div>

                                    {{-- Modal Body --}}
                                    <div class="px-6 py-4 space-y-4">
                                        {{-- Deadline & Sisa Waktu --}}
                                        <div class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-3">
                                            <div>
                                                <p class="text-xs text-gray-500">Deadline</p>
                                                <p class="font-semibold text-gray-800">{{ $dl->format('d M Y') }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-xs text-gray-500 mb-1">Sisa Waktu</p>
                                                @if($dlCompleted)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">Selesai</span>
                                                @elseif($dlDays < 0)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 border border-red-200">{{ abs($dlDays) }} hari terlambat</span>
                                                @elseif($dlDays == 0)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-50 text-red-700 border border-red-200">Hari ini!</span>
                                                @elseif($dlDays <= 3)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200">{{ $dlDays }} hari lagi</span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">{{ $dlDays }} hari lagi</span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Status Form --}}
                                        <div class="flex items-center gap-3">
                                            <span class="text-xs text-gray-500">Status Form:</span>
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                                {{ $sp3->form->status === 'selesai' ? 'bg-green-100 text-green-700' : 'bg-indigo-100 text-indigo-700' }}">
                                                {{ \App\Models\FormVerification::getStatusLabel($sp3->form->status) }}
                                            </span>
                                        </div>

                                        {{-- Results inputted by this analyst --}}
                                        <div>
                                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Hasil Input Anda</p>
                                            @if($sp3->sampleParameters->isEmpty())
                                                <p class="text-sm text-gray-400 italic">Tidak ada data hasil.</p>
                                            @else
                                                <div class="divide-y divide-gray-100 rounded-xl border border-gray-100 overflow-hidden">
                                                    @foreach($sp3->sampleParameters as $sp)
                                                        <div class="flex items-center justify-between px-4 py-3 bg-gray-50/50">
                                                            <div>
                                                                <p class="text-sm font-medium text-gray-800">{{ $sp->sample->sample_name ?? '-' }}</p>
                                                                <p class="text-xs text-gray-400 font-mono">{{ $sp->sample->sample_code ?? '-' }}</p>
                                                            </div>
                                                            <div class="text-right">
                                                                @if($sp->analysisResult)
                                                                    <p class="text-sm font-bold text-gray-900">
                                                                        {{ $sp->analysisResult->result_value }}
                                                                        @if($sp->analysisResult->result_unit)
                                                                            <span class="text-xs font-normal text-gray-500">{{ $sp->analysisResult->result_unit }}</span>
                                                                        @endif
                                                                    </p>
                                                                    @if($sp->analysisResult->notes)
                                                                        <p class="text-xs text-gray-400">{{ $sp->analysisResult->notes }}</p>
                                                                    @endif
                                                                @else
                                                                    <span class="text-xs text-gray-400 italic">Belum ada hasil</span>
                                                                @endif
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium mt-1
                                                                    {{ $sp->status === 'done' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                                                    {{ $sp->status === 'done' ? 'Selesai' : 'Pending' }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function showTab(name) {
            ['aktif', 'riwayat'].forEach(t => {
                document.getElementById('panel-' + t).classList.toggle('hidden', t !== name);
                const btn = document.getElementById('tab-' + t);
                btn.classList.toggle('border-indigo-600', t === name);
                btn.classList.toggle('text-indigo-600', t === name);
                btn.classList.toggle('border-transparent', t !== name);
                btn.classList.toggle('text-gray-500', t !== name);
            });
        }

        function openRiwayatModal(id) {
            document.getElementById(id).classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeRiwayatModalById(id) {
            document.getElementById(id).classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        function closeRiwayatModal(event, id) {
            if (event.target === document.getElementById(id) || event.target.classList.contains('absolute')) {
                closeRiwayatModalById(id);
            }
        }
    </script>
</x-app-layout>
