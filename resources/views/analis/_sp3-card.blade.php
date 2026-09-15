@php
    $sps           = $sp3->sampleParameters;
    $total         = $sps->count();
    $done          = $sps->where('status', 'done')->count();
    $pct           = $total > 0 ? round($done / $total * 100) : 0;
    $isRejected    = $sp3->review_status === 'rejected';
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
                    $dl        = \Carbon\Carbon::parse($sp3->form->deadline_date);
                    $dlDays    = \Carbon\Carbon::today()->diffInDays($dl, false);
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
