<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Monitoring Analis</h2>
            <form method="GET" action="{{ route('admin.monitoring') }}" id="filter-form"
                  class="flex items-center gap-2 flex-wrap">
                <select name="year" onchange="document.getElementById('filter-form').submit()"
                        class="text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                    @foreach($years as $yr)
                        <option value="{{ $yr }}" {{ $selectedYear == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                    @endforeach
                </select>
                <select name="month" onchange="document.getElementById('filter-form').submit()"
                        class="text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                    <option value="">Semua Bulan</option>
                    @foreach($months as $num => $label)
                        <option value="{{ $num }}" {{ $selectedMonth == $num ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="analyst_id" onchange="document.getElementById('filter-form').submit()"
                        class="text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                    <option value="">Semua Analis</option>
                    @foreach($analysts as $a)
                        <option value="{{ $a->user_id }}" {{ $selectedAnalyst == $a->user_id ? 'selected' : '' }}>
                            {{ $a->full_name }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="tab" id="active-tab-input" value="{{ request('tab', 'ringkasan') }}">
            </form>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Period label --}}
            <p class="text-sm text-gray-500">
                Data:
                <span class="font-semibold text-gray-700">
                    {{ $selectedMonth ? $months[$selectedMonth].' '.$selectedYear : 'Seluruh '.$selectedYear }}
                </span>
                @if($selectedAnalyst)
                    &bull; Analis: <span class="font-semibold text-gray-700">{{ $analysts->firstWhere('user_id', $selectedAnalyst)?->full_name }}</span>
                @endif
            </p>

            {{-- KPI Summary Bar --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Total Analis Aktif</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $overallStats['total_analysts'] }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">SP3 Ditugaskan</p>
                    <p class="text-3xl font-bold text-indigo-600">{{ $overallStats['total_assigned'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">periode ini</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">SP3 Belum Ditugaskan</p>
                    <p class="text-3xl font-bold text-amber-500">{{ $overallStats['total_unassigned'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">sedang aktif</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Analis Tersibuk</p>
                    <p class="text-lg font-bold text-gray-900 truncate">{{ $overallStats['busiest_name'] }}</p>
                    @if($overallStats['busiest_count'] > 0)
                        <p class="text-xs text-gray-400 mt-1">{{ $overallStats['busiest_count'] }} SP3 aktif</p>
                    @endif
                </div>
            </div>

            {{-- Tabs --}}
            @php $activeTab = request('tab', 'ringkasan'); @endphp
            <div class="flex gap-2 border-b border-gray-200">
                <button onclick="switchTab('ringkasan')" id="tab-btn-ringkasan"
                        class="tab-btn px-5 py-2.5 text-sm font-bold border-b-2 -mb-px transition-colors
                               {{ $activeTab === 'ringkasan' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Ringkasan
                </button>
                <button onclick="switchTab('log')" id="tab-btn-log"
                        class="tab-btn px-5 py-2.5 text-sm font-bold border-b-2 -mb-px transition-colors
                               {{ $activeTab === 'log' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Log Penugasan
                    <span class="ml-1 px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 text-xs">{{ $log->total() }}</span>
                </button>
            </div>

            {{-- TAB: Ringkasan --}}
            <div id="panel-ringkasan" class="{{ $activeTab !== 'ringkasan' ? 'hidden' : '' }}">
                @if($analystStats->isEmpty())
                    <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400">
                        Tidak ada analis aktif.
                    </div>
                @else
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        @foreach($analystStats as $index => $stat)
                            @php
                                $analyst   = $stat['analyst'];
                                $initials  = collect(explode(' ', $analyst->full_name))->take(2)->map(fn($w) => strtoupper($w[0] ?? ''))->implode('');
                                $isBusiest = $index === 0 && $stat['active_assigned'] > 0;
                            @endphp
                            <div class="bg-white rounded-2xl border shadow-sm overflow-hidden hover:shadow-md transition-all
                                {{ $isBusiest ? 'border-indigo-300' : 'border-gray-100' }}">

                                {{-- Card Header --}}
                                <div class="p-5 flex items-center gap-4 {{ $isBusiest ? 'bg-indigo-50/60' : 'bg-gray-50/40' }}">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-sm font-bold text-white flex-shrink-0
                                        {{ $isBusiest ? 'bg-gradient-to-br from-indigo-500 to-blue-600' : 'bg-gradient-to-br from-gray-400 to-gray-500' }}">
                                        {{ $initials }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="font-bold text-gray-900 truncate">{{ $analyst->full_name }}</p>
                                            @if($isBusiest)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-700">Tersibuk</span>
                                            @endif
                                            @if($stat['overdue'] > 0)
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">{{ $stat['overdue'] }} Terlambat</span>
                                            @endif
                                        </div>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $analyst->username }}</p>
                                    </div>
                                </div>

                                {{-- Stats Grid --}}
                                <div class="px-5 py-4 grid grid-cols-3 gap-3 border-b border-gray-100">
                                    <div class="text-center">
                                        <p class="text-2xl font-bold text-gray-900">{{ $stat['total_assigned'] }}</p>
                                        <p class="text-[11px] text-gray-500 mt-0.5">Ditugaskan</p>
                                    </div>
                                    <div class="text-center border-x border-gray-100">
                                        <p class="text-2xl font-bold {{ $stat['active_assigned'] > 0 ? 'text-indigo-600' : 'text-gray-400' }}">{{ $stat['active_assigned'] }}</p>
                                        <p class="text-[11px] text-gray-500 mt-0.5">Aktif</p>
                                    </div>
                                    <div class="text-center">
                                        <p class="text-2xl font-bold text-emerald-600">{{ $stat['completed'] }}</p>
                                        <p class="text-[11px] text-gray-500 mt-0.5">Selesai</p>
                                    </div>
                                </div>

                                {{-- Secondary stats --}}
                                <div class="px-5 py-3 grid grid-cols-2 gap-2 border-b border-gray-100">
                                    <div class="flex items-center justify-between bg-emerald-50 rounded-lg px-3 py-2">
                                        <span class="text-xs text-gray-600">Sudah Dilihat</span>
                                        <span class="font-bold text-emerald-700">{{ $stat['viewed'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between bg-amber-50 rounded-lg px-3 py-2">
                                        <span class="text-xs text-gray-600">Belum Dilihat</span>
                                        <span class="font-bold text-amber-700">{{ $stat['not_viewed'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between bg-red-50 rounded-lg px-3 py-2">
                                        <span class="text-xs text-gray-600">Terlambat</span>
                                        <span class="font-bold text-red-700">{{ $stat['overdue'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between bg-purple-50 rounded-lg px-3 py-2">
                                        <span class="text-xs text-gray-600">Jenis Parameter</span>
                                        <span class="font-bold text-purple-700">{{ $stat['parameters_count'] }}</span>
                                    </div>
                                </div>

                                {{-- Accordion --}}
                                @if($stat['sp3_details']->isNotEmpty())
                                    <button onclick="toggleAccordion('acc-{{ $analyst->user_id }}')"
                                            class="w-full px-5 py-3 text-left text-xs font-semibold text-indigo-600 hover:bg-indigo-50 transition-colors flex items-center justify-between">
                                        <span>Lihat Detail SP3 ({{ $stat['sp3_details']->count() }})</span>
                                        <svg id="icon-{{ $analyst->user_id }}" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <div id="acc-{{ $analyst->user_id }}" class="hidden border-t border-gray-100 overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-100 text-xs">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-2 text-left font-bold text-gray-500 uppercase">No. SP3</th>
                                                    <th class="px-4 py-2 text-left font-bold text-gray-500 uppercase">Parameter</th>
                                                    <th class="px-4 py-2 text-left font-bold text-gray-500 uppercase">No. Terima</th>
                                                    <th class="px-4 py-2 text-left font-bold text-gray-500 uppercase">Ditugaskan</th>
                                                    <th class="px-4 py-2 text-left font-bold text-gray-500 uppercase">Dilihat</th>
                                                    <th class="px-4 py-2 text-left font-bold text-gray-500 uppercase">Status</th>
                                                    <th class="px-4 py-2 text-left font-bold text-gray-500 uppercase">Deadline</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 bg-white">
                                                @foreach($stat['sp3_details'] as $sp3)
                                                    @php
                                                        $dl = \Carbon\Carbon::parse($sp3->form?->deadline_date);
                                                        $isOverdueRow = $sp3->form && $sp3->form->status === 'dalam_pengujian' && $dl->isPast();
                                                        $reviewLabels = ['pending' => ['bg-gray-100 text-gray-600','Pending'], 'rejected' => ['bg-red-100 text-red-700','Ditolak'], 'resubmitted' => ['bg-amber-100 text-amber-700','Dikirim Ulang'], 'approved' => ['bg-emerald-100 text-emerald-700','Disetujui']];
                                                        [$rvClass, $rvLabel] = $reviewLabels[$sp3->review_status] ?? ['bg-gray-100 text-gray-600', $sp3->review_status];
                                                    @endphp
                                                    <tr class="{{ $isOverdueRow ? 'bg-red-50/40' : '' }}">
                                                        <td class="px-4 py-2 font-medium text-gray-800">{{ $sp3->sp3_number }}</td>
                                                        <td class="px-4 py-2 text-gray-600">{{ $sp3->parameter?->name ?? '-' }}</td>
                                                        <td class="px-4 py-2 text-gray-600">{{ $sp3->form?->no_terima_sampel ?? '-' }}</td>
                                                        <td class="px-4 py-2 text-gray-500">{{ $sp3->assigned_at?->format('d M H:i') ?? '-' }}</td>
                                                        <td class="px-4 py-2">
                                                            @if($sp3->first_viewed_at)
                                                                <span class="text-emerald-700">{{ $sp3->first_viewed_at->format('d M H:i') }}</span>
                                                            @else
                                                                <span class="text-amber-600">Belum</span>
                                                            @endif
                                                        </td>
                                                        <td class="px-4 py-2"><span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $rvClass }}">{{ $rvLabel }}</span></td>
                                                        <td class="px-4 py-2 {{ $isOverdueRow ? 'text-red-600 font-semibold' : 'text-gray-600' }}">{{ $dl->format('d M Y') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="px-5 py-3 text-xs text-gray-400 italic">Belum ada penugasan pada periode ini.</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- TAB: Log Penugasan --}}
            <div id="panel-log" class="{{ $activeTab !== 'log' ? 'hidden' : '' }}">
                @if($log->isEmpty())
                    <div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400">
                        Belum ada penugasan pada periode ini.
                    </div>
                @else
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Analis</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Parameter</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">No. SP3</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">No. Terima</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Ditugaskan</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Dilihat</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Diinput</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Respon</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Durasi</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wide">Status SP3</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($log as $sp3)
                                        @php
                                            $dl = \Carbon\Carbon::parse($sp3->form?->deadline_date);
                                            $isOverdue = $sp3->form
                                                && $sp3->form->status === 'dalam_pengujian'
                                                && $dl->isPast()
                                                && !$sp3->result_submitted_at;
                                            $isDone    = (bool) $sp3->result_submitted_at;
                                            $notViewed = !$sp3->first_viewed_at;
                                            $rowBg = $isDone ? 'bg-emerald-50/30' : ($isOverdue ? 'bg-red-50/50' : ($notViewed ? 'bg-amber-50/40' : ''));
                                            $reviewLabels = [
                                                'pending'     => ['bg-gray-100 text-gray-600',   'Pending'],
                                                'rejected'    => ['bg-red-100 text-red-700',     'Ditolak'],
                                                'resubmitted' => ['bg-amber-100 text-amber-700', 'Dikirim Ulang'],
                                                'approved'    => ['bg-emerald-100 text-emerald-700', 'Disetujui'],
                                            ];
                                            [$rvClass, $rvLabel] = $reviewLabels[$sp3->review_status] ?? ['bg-gray-100 text-gray-600', $sp3->review_status];
                                        @endphp
                                        <tr class="{{ $rowBg }} hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap">
                                                {{ $sp3->assignedAnalyst?->full_name ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                                {{ $sp3->parameter?->name ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3 font-mono text-gray-700 whitespace-nowrap">
                                                {{ $sp3->sp3_number }}
                                            </td>
                                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                                {{ $sp3->form?->no_terima_sampel ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                                {{ $sp3->assigned_at?->format('d M Y H:i') ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                @if($sp3->first_viewed_at)
                                                    <span class="text-emerald-700 font-medium">{{ $sp3->first_viewed_at->format('d M Y H:i') }}</span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 text-amber-600">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>Belum
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                @if($sp3->result_submitted_at)
                                                    <span class="text-emerald-700 font-medium">{{ $sp3->result_submitted_at->format('d M Y H:i') }}</span>
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                                {{ $sp3->response_time ?? '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                                {{ $sp3->turnaround ?? '—' }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $rvClass }}">{{ $rvLabel }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($log->hasPages())
                            <div class="px-6 py-4 border-t border-gray-100">
                                {{ $log->links() }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        function switchTab(name) {
            ['ringkasan', 'log'].forEach(t => {
                document.getElementById('panel-' + t).classList.toggle('hidden', t !== name);
                const btn = document.getElementById('tab-btn-' + t);
                btn.classList.toggle('border-indigo-600', t === name);
                btn.classList.toggle('text-indigo-600', t === name);
                btn.classList.toggle('border-transparent', t !== name);
                btn.classList.toggle('text-gray-500', t !== name);
            });
            document.getElementById('active-tab-input').value = name;
        }

        function toggleAccordion(id) {
            const el   = document.getElementById(id);
            const aid  = id.replace('acc-', '');
            const icon = document.getElementById('icon-' + aid);
            el.classList.toggle('hidden');
            if (icon) icon.classList.toggle('rotate-180');
        }
    </script>
    @endpush
</x-app-layout>