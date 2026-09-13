<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Form Pengujian: {{ $form->no_terima_sampel ?? $form->lhp_number ?? '-' }}
            </h2>
            <a href="{{ route('form.index') }}" class="text-blue-600 hover:underline">← Kembali ke Daftar</a>
        </div>
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

            {{-- Form Info Card --}}
            <div class="bg-white/90 backdrop-blur-sm overflow-hidden shadow-sm rounded-2xl border border-gray-100 mb-8 transition-all hover:shadow-md">
                <div class="p-8">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Informasi Form
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">No. Terima Sampel</p>
                            <p class="font-medium text-gray-900">{{ $form->no_terima_sampel ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">No. LHP</p>
                            <p class="font-medium text-gray-900">{{ $form->lhp_number ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Tanggal Masuk</p>
                            <p class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($form->received_date)->format('d M Y') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Deadline</p>
                            <p class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($form->deadline_date)->format('d M Y') }}</p>
                        </div>
                    </div>

                    {{-- Customer Info --}}
                    @if($form->customer_name || $form->customer_phone)
                        <div class="mt-4 pt-4 border-t">
                            <p class="text-sm font-medium text-gray-500 mb-2">Info Customer</p>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">Nama</p>
                                    <p class="font-medium text-gray-900">{{ $form->customer_name ?? '-' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">No. HP</p>
                                    <p class="font-medium text-gray-900">{{ $form->customer_phone ?? '-' }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Progress Steps --}}
                    <div class="mt-8 pt-6 border-t border-gray-100">
                        <p class="text-sm font-bold tracking-wide text-gray-500 uppercase mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Progress Verifikasi
                        </p>
                        <div class="flex items-center justify-between overflow-x-auto pb-4">
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
                                <div class="flex flex-col items-center min-w-[70px] group">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold shadow-sm transition-transform duration-300 group-hover:scale-110
                                        {{ $num <= $currentIndex + 1 ? ($form->status == 'selesai' ? 'bg-gradient-to-r from-emerald-400 to-teal-500 text-white shadow-emerald-500/30' : 'bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-indigo-500/30') : 'bg-gray-100 border-2 border-dashed border-gray-300 text-gray-400' }}">
                                        @if($num <= $currentIndex)
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        @else
                                            {{ $num }}
                                        @endif
                                    </div>
                                    <span class="text-xs mt-3 text-center {{ $form->status == $step['status'] ? 'font-bold text-indigo-600' : 'text-gray-500 font-medium' }}">{{ $step['label'] }}</span>
                                    @if($verifierName = $form->getVerifierName($step['status']))
                                        <span class="text-[10px] text-gray-500 mt-1 text-center leading-tight">{{ $verifierName }}</span>
                                    @endif
                                </div>
                                @if($num < 5)
                                    <div class="flex-1 h-1.5 mx-2 min-w-6 rounded-full {{ $num <= $currentIndex ? 'bg-gradient-to-r from-blue-500 to-indigo-500' : 'bg-gray-100' }}"></div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-4">
                        @php
                            $statusColors = [
                                'dalam_pengujian'        => 'bg-blue-100 text-blue-800',
                                'menunggu_review_divisi' => 'bg-indigo-100 text-indigo-800',
                                'ttd_upa'                => 'bg-orange-100 text-orange-800',
                                'selesai'                => 'bg-green-100 text-green-800',
                            ];
                            $statusLabels = [
                                'dalam_pengujian'        => 'Dalam Pengujian',
                                'menunggu_review_divisi' => 'Menunggu Review Divisi',
                                'ttd_upa'                => 'Menunggu TTD Kepala UPA',
                                'selesai'                => 'Selesai',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$form->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ $statusLabels[$form->status] ?? $form->status }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- SPU Document Card --}}
            <div class="bg-gradient-to-br from-indigo-50 to-white overflow-hidden shadow-sm rounded-2xl border border-indigo-100 mb-8 transition-all hover:shadow-md">
                <div class="p-8">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h3 class="text-xl font-bold text-indigo-900 flex items-center gap-2">
                                <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                Dokumen LHP
                            </h3>
                            @if($form->lhp_uploaded_at)
                                <p class="text-sm font-medium text-indigo-600/70 mt-1">
                                    Di-generate: {{ \Carbon\Carbon::parse($form->lhp_uploaded_at)->format('d M Y H:i') }}
                                </p>
                            @else
                                <p class="text-sm font-medium text-gray-500 mt-1">Belum di-generate</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-3 w-full md:w-auto">
                            @if($form->lhp_google_file_id)
                                <a href="https://docs.google.com/document/d/{{ $form->lhp_google_file_id }}/edit" target="_blank"
                                   class="inline-flex items-center justify-center px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white shadow-md shadow-emerald-500/20 text-sm font-bold rounded-xl transition-all hover:-translate-y-0.5 w-full md:w-auto">
                                    📄 Lihat LHP
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Admin: manually set/override LHP number --}}
                    @if(auth()->user()->role_id === \App\Enums\Role::ADMIN)
                        <div class="mt-5 pt-5 border-t border-indigo-100">
                            <form action="{{ route('admin.update-lhp-number', $form) }}" method="POST"
                                  class="flex flex-col sm:flex-row gap-3 items-end">
                                @csrf
                                <div class="flex-1">
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">No. LHP</label>
                                    <input type="text" name="lhp_number"
                                           value="{{ $form->lhp_number }}"
                                           placeholder="001/LHP/NK/09/2026"
                                           class="w-full text-sm font-medium bg-white border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                                </div>
                                <button type="submit"
                                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg transition-colors shadow-sm whitespace-nowrap">
                                    Simpan No. LHP
                                </button>
                            </form>
                        </div>
                    @endif
                    </div>
                </div>
            </div>

            {{-- Samples List --}}
            <div class="bg-white/90 backdrop-blur-sm overflow-hidden shadow-sm rounded-2xl border border-gray-100 mb-8 transition-all hover:shadow-md">
                <div class="p-8">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                            Daftar Sampel ({{ $form->samples->count() }})
                        </h3>
                    </div>

                    @if($form->samples->isEmpty())
                        <div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-xl p-10 text-center">
                            <p class="text-gray-500 font-medium">Belum ada sampel yang ditambahkan</p>
                        </div>
                    @else
                        <div class="space-y-6">
                            @foreach($form->samples as $index => $sample)
                                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                                    <div class="absolute top-0 left-0 w-1.5 h-full bg-indigo-500 opacity-80"></div>
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h4 class="font-medium text-gray-900">
                                                {{ $index + 1 }}. {{ $sample->sample_name }}
                                            </h4>
                                            <p class="text-sm text-gray-500">Kode: {{ $sample->sample_code }}</p>
                                        </div>
                                    </div>

                                    @if($sample->notes)
                                        <div class="mb-3 p-2 bg-gray-50 border-l-4 border-gray-300 rounded-r">
                                            <p class="text-sm text-gray-600">📝 <span class="font-medium">Catatan:</span> {{ $sample->notes }}</p>
                                        </div>
                                    @endif

                                    {{-- Parameters List --}}
                                    <div class="mt-5 pt-4 border-t border-gray-100">
                                        <p class="text-xs font-bold text-gray-600 uppercase tracking-wide mb-3 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                            Parameter Pengujian
                                        </p>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                            @forelse($sample->sampleParameters as $sp)
                                                @php
                                                    $paramStatusColors = [
                                                        'pending' => 'bg-gray-50 text-gray-700 border-gray-200',
                                                        'in_progress' => 'bg-blue-50 text-blue-700 border-blue-200',
                                                        'done' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                    ];
                                                @endphp
                                                <div class="flex flex-col p-3 rounded-xl border {{ $paramStatusColors[$sp->status] ?? 'bg-gray-50 border-gray-200' }} transition-colors">
                                                    <div class="flex justify-between items-start mb-2">
                                                        <span class="font-bold text-sm leading-tight">{{ $sp->parameter->name ?? 'N/A' }}</span>
                                                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full {{ $sp->status === 'done' ? 'bg-emerald-600 text-white' : ($sp->status === 'in_progress' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600') }}">{{ ucfirst($sp->status) }}</span>
                                                    </div>
                                                    <div class="mt-auto">
                                                        @if($sp->filledByAnalyst)
                                                            <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-md bg-white/60 shadow-sm">
                                                                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                                {{ $sp->filledByAnalyst->full_name }}
                                                            </span>
                                                        @else
                                                            <span class="text-xs text-gray-400 font-medium italic">Belum diisi</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="col-span-full text-sm text-gray-400 font-medium py-2">Tidak ada parameter yang dipilih</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- SP3 Documents (Admin edits SPPP & IK) --}}
            @if($form->sp3Documents && $form->sp3Documents->isNotEmpty())
                <div class="bg-white/90 backdrop-blur-sm overflow-hidden shadow-sm rounded-2xl border border-gray-100 mb-8 transition-all hover:shadow-md">
                    <div class="p-8">
                        <h3 class="text-xl font-bold text-gray-800 mb-2 flex items-center gap-2">
                            <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            Dokumen SP3 ({{ $form->sp3Documents->count() }})
                        </h3>
                        <p class="text-sm font-medium text-gray-500 mb-6">Isi No. SPPP dan Instruksi Kerja untuk setiap SP3.</p>

                        <div class="space-y-6">
                            @foreach($form->sp3Documents as $sp3)
                                @php
                                    $sp3GenStatus = $sp3->doc_generation_status;
                                    $sp3Generating = in_array($sp3GenStatus, ['queued', 'processing']);
                                    $sp3Failed = $sp3GenStatus === 'failed';
                                    $sp3Done = !empty($sp3->google_doc_id);
                                @endphp
                                <div class="group bg-gray-50/50 rounded-xl p-5 border border-gray-200 hover:border-indigo-300 hover:shadow-md transition-all duration-300"
                                     data-sp3-id="{{ $sp3->id }}"
                                     data-sp3-done="{{ $sp3Done ? 'true' : 'false' }}">
                                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5 gap-3">
                                        <div>
                                            <h4 class="font-bold text-gray-900 flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                                {{ $sp3->sp3_number }}
                                            </h4>
                                            <p class="text-sm font-medium text-gray-500 pl-4 mt-0.5">Parameter: {{ $sp3->parameter->name ?? '-' }}</p>
                                        </div>
                                        {{-- SP3 doc link / status --}}
                                        @if($sp3Done)
                                            <a href="https://docs.google.com/document/d/{{ $sp3->google_doc_id }}" target="_blank"
                                               class="text-blue-600 hover:underline text-sm">Buka Dokumen</a>
                                        @elseif($sp3Generating)
                                            <span class="flex items-center gap-1.5 text-sm text-amber-600 font-medium" data-sp3-spinner="{{ $sp3->id }}">
                                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                                </svg>
                                                Sedang digenerate...
                                            </span>
                                        @elseif($sp3Failed)
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-red-500" title="{{ $sp3->doc_generation_error }}">⚠ Gagal</span>
                                                <form method="POST" action="{{ route('admin.sp3-retry-doc', $sp3) }}">
                                                    @csrf
                                                    <button type="submit" class="text-sm px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition">
                                                        ↺ Coba Lagi
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>

                                    <form action="{{ route('admin.update-sp3-info', $sp3) }}" method="POST"
                                          class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end bg-white p-4 rounded-lg border border-gray-100 shadow-sm">
                                        @csrf
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">No. SPPP</label>
                                            <input type="text" name="no_sppp" value="{{ $sp3->no_sppp }}"
                                                   placeholder="001/SPPP/..."
                                                   class="w-full text-sm font-medium bg-gray-50 border-gray-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Instruksi Kerja (IK)</label>
                                            <input type="text" name="ik" value="{{ $sp3->ik }}"
                                                   placeholder="IK.01.01..."
                                                   class="w-full text-sm font-medium bg-gray-50 border-gray-200 rounded-lg focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all shadow-sm">
                                        </div>
                                        <div>
                                            <button type="submit"
                                                    class="w-full bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white shadow-md shadow-indigo-500/20 hover:shadow-lg hover:shadow-indigo-500/30 hover:-translate-y-0.5 py-2 px-4 rounded-lg text-sm font-bold transition-all flex items-center justify-center gap-2">
                                                Simpan / Update Dokumen
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @php
        $hasGeneratingSp3 = $form->sp3Documents->contains(fn($s) =>
            in_array($s->doc_generation_status, ['queued', 'processing']) && !$s->google_doc_id
        );
    @endphp

    @if($hasGeneratingSp3)
    <script>
        (function () {
            const cards = document.querySelectorAll('[data-sp3-id]');
            const pending = {};

            cards.forEach(card => {
                if (card.dataset.sp3Done === 'false') {
                    pending[card.dataset.sp3Id] = { delay: 3000, attempts: 0 };
                }
            });

            function pollOne(sp3Id, state) {
                if (state.attempts++ > 30) return;
                fetch(`/sp3/${sp3Id}/doc-status`, { headers: { Accept: 'application/json' } })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'completed' || data.status === 'failed' || data.google_doc_id) {
                            location.reload();
                            return;
                        }
                        state.delay = Math.min(state.delay * 1.4, 12000);
                        setTimeout(() => pollOne(sp3Id, state), state.delay);
                    })
                    .catch(() => {
                        state.delay = Math.min(state.delay * 1.4, 12000);
                        setTimeout(() => pollOne(sp3Id, state), state.delay);
                    });
            }

            Object.entries(pending).forEach(([id, state]) => {
                setTimeout(() => pollOne(id, state), state.delay);
            });
        })();
    </script>
    @endif
</x-app-layout>
