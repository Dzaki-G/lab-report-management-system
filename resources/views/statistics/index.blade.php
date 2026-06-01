<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="font-extrabold text-2xl text-[#1a365d] tracking-tight">
                    Statistik &amp; Pelaporan
                </h2>
                <p class="text-sm text-gray-500 mt-1">Ringkasan volume pengujian, parameter terpopuler, dan kinerja laboratorium.</p>
            </div>

            {{-- Year & Month Filter Form --}}
            <form action="{{ route('statistics.index') }}" method="GET" class="flex flex-wrap items-center gap-3 bg-white p-1.5 shadow-sm rounded-xl border border-gray-200">
                <div class="flex items-center gap-1">
                    <label for="month" class="text-xs font-bold text-gray-500 uppercase px-2">Bulan:</label>
                    <select name="month" id="month" onchange="this.form.submit()"
                            class="text-sm font-semibold text-[#1a365d] border-none rounded-lg focus:ring-2 focus:ring-blue-500 py-1 pl-2 pr-8 bg-gray-50 hover:bg-gray-100 transition cursor-pointer">
                        <option value="all" {{ $selectedMonth === 'all' ? 'selected' : '' }}>Semua Bulan</option>
                        @foreach($monthNames as $num => $name)
                            <option value="{{ $num }}" {{ $selectedMonth == $num ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="h-5 w-[1px] bg-gray-200 hidden sm:block"></div>

                <div class="flex items-center gap-1">
                    <label for="year" class="text-xs font-bold text-gray-500 uppercase px-2">Tahun:</label>
                    <select name="year" id="year" onchange="this.form.submit()"
                            class="text-sm font-semibold text-[#1a365d] border-none rounded-lg focus:ring-2 focus:ring-blue-500 py-1 pl-2 pr-8 bg-gray-50 hover:bg-gray-100 transition cursor-pointer">
                        @foreach($availableYears as $year)
                            <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </x-slot>

    <div class="py-6 space-y-6">

        {{-- KPI Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

            {{-- Total Forms --}}
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300 flex items-center gap-4">
                <div class="p-3 rounded-xl bg-blue-50 text-blue-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Form Pengujian</p>
                    <p class="text-2xl font-extrabold text-[#1a365d] mt-0.5">{{ number_format($kpiCounts['total_forms']) }}</p>
                </div>
            </div>

            {{-- Active Forms --}}
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300 flex items-center gap-4">
                <div class="p-3 rounded-xl bg-amber-50 text-amber-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Form Sedang Aktif</p>
                    <p class="text-2xl font-extrabold text-amber-600 mt-0.5">{{ number_format($kpiCounts['active_forms']) }}</p>
                </div>
            </div>

            {{-- Completed Forms --}}
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300 flex items-center gap-4">
                <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Form Telah Selesai</p>
                    <p class="text-2xl font-extrabold text-emerald-600 mt-0.5">{{ number_format($kpiCounts['completed_forms']) }}</p>
                </div>
            </div>

            {{-- Total Samples --}}
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300 flex items-center gap-4">
                <div class="p-3 rounded-xl bg-purple-50 text-purple-600 shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Sampel Diuji</p>
                    <p class="text-2xl font-extrabold text-[#1a365d] mt-0.5">{{ number_format($kpiCounts['total_samples']) }}</p>
                </div>
            </div>

        </div>

        {{-- Row 1: Monthly Volume + Status Distribution --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

            {{-- Monthly Volume Chart --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 lg:col-span-2 space-y-3">
                <div>
                    <h3 class="font-bold text-gray-800 text-base">
                        @if($selectedMonth === 'all')
                            Volume Bulanan (Tahun {{ $selectedYear }})
                        @else
                            Volume Harian (Bulan {{ $monthNames[$selectedMonth] }} {{ $selectedYear }})
                        @endif
                    </h3>
                    <p class="text-xs text-gray-400">
                        @if($selectedMonth === 'all')
                            Tren bulanan pendaftaran form pengujian dan jumlah sampel.
                        @else
                            Tren harian pendaftaran form pengujian dan jumlah sampel pada bulan {{ $monthNames[$selectedMonth] }}.
                        @endif
                    </p>
                </div>
                {{-- Custom Legend --}}
                <div class="flex items-center gap-5 text-xs text-gray-500">
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block w-3 h-3 rounded-sm bg-blue-400"></span>
                        Form Pengujian
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="inline-block w-3 h-3 rounded-sm" style="background:#7F77DD;"></span>
                        Jumlah Sampel (kanan)
                    </span>
                </div>
                <div class="relative" style="height: 280px;">
                    <canvas id="monthlyVolumeChart"
                            role="img"
                            aria-label="Grafik batang volume bulanan form pengujian dan sampel tahun {{ $selectedYear }}">
                        Data volume bulanan {{ $selectedYear }}.
                    </canvas>
                </div>
            </div>

            {{-- Status Distribution Chart --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-3">
                <div>
                    <h3 class="font-bold text-gray-800 text-base">Status Form Pengujian</h3>
                    <p class="text-xs text-gray-400">Distribusi form berdasarkan status alur kerja.</p>
                </div>
                @if(count($statusStats) > 0)
                    <div class="relative flex items-center justify-center" style="height: 200px;">
                        <canvas id="statusDistributionChart"
                                role="img"
                                aria-label="Diagram donat distribusi status form pengujian">
                            Distribusi status form pengujian.
                        </canvas>
                    </div>
                    {{-- Custom Legend --}}
                    <div id="statusLegend" class="flex flex-wrap justify-center gap-x-4 gap-y-1.5 text-xs text-gray-500 pt-1"></div>
                @else
                    <div class="text-center text-gray-400 py-16 text-sm">
                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 19a2 2 0 01-2-2V7a2 2 0 012-2h4l2 2h4a2 2 0 012 2v1M5 19h14a2 2 0 002-2v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5a2 2 0 01-2 2z"/>
                        </svg>
                        Belum ada data status
                    </div>
                @endif
            </div>

        </div>

        {{-- Row 2: Parameters + Analyst Workload --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            {{-- Top 10 Parameters --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-3">
                <div>
                    <h3 class="font-bold text-gray-800 text-base">Parameter Paling Banyak Diuji</h3>
                    <p class="text-xs text-gray-400">10 parameter teratas yang paling sering dipilih untuk pengujian sampel.</p>
                </div>
                @if(count($parameterStats) > 0)
                    <div class="relative" style="height: {{ max(count($parameterStats), 5) * 40 + 60 }}px;">
                        <canvas id="parameterStatsChart"
                                role="img"
                                aria-label="Grafik batang horizontal 10 parameter paling sering diuji">
                            Daftar 10 parameter pengujian teratas.
                        </canvas>
                    </div>
                @else
                    <div class="text-center text-gray-400 py-24 text-sm">
                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                        Belum ada parameter diuji
                    </div>
                @endif
            </div>

            {{-- Analyst Workload --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-3">
                <div>
                    <h3 class="font-bold text-gray-800 text-base">Beban Kerja Analis</h3>
                    <p class="text-xs text-gray-400">Jumlah parameter tugas aktif vs sudah diselesaikan per analis.</p>
                </div>
                @if(count($analystWorkload) > 0)
                    {{-- Custom Legend --}}
                    <div class="flex items-center gap-5 text-xs text-gray-500">
                        <span class="flex items-center gap-1.5">
                            <span class="inline-block w-3 h-3 rounded-sm bg-amber-400"></span>
                            Aktif (Pending / In Progress)
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="inline-block w-3 h-3 rounded-sm bg-emerald-500"></span>
                            Selesai
                        </span>
                    </div>
                    <div class="relative" style="height: 280px;">
                        <canvas id="analystWorkloadChart"
                                role="img"
                                aria-label="Grafik batang bertumpuk beban kerja analis aktif versus selesai">
                            Perbandingan beban kerja aktif dan selesai per analis.
                        </canvas>
                    </div>
                @else
                    <div class="text-center text-gray-400 py-24 text-sm">
                        <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Belum ada penugasan analis
                    </div>
                @endif
            </div>

        </div>

        {{-- Row 3: Yearly Trend --}}
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-3">
            <div>
                <h3 class="font-bold text-gray-800 text-base">Tren Volume Tahunan</h3>
                <p class="text-xs text-gray-400">Pertumbuhan jumlah form pengujian yang masuk dari tahun ke tahun.</p>
            </div>
            @if(count($yearlyForms) > 0)
                <div class="relative" style="height: 200px;">
                    <canvas id="yearlyTrendChart"
                            role="img"
                            aria-label="Grafik garis tren volume tahunan form pengujian">
                        Tren volume form pengujian per tahun.
                    </canvas>
                </div>
            @else
                <div class="text-center text-gray-400 py-20 text-sm">
                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Belum ada data tahunan
                </div>
            @endif
        </div>

    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // Shared grid line color
            const gridColor = 'rgba(0,0,0,0.06)';

            // Shared tick font
            const tickFont = { size: 11 };

            // ─────────────────────────────────────────────────
            // 1. Monthly Volume Chart — Bar (forms) + Line (samples)
            //    Uses dual Y-axis so both series are readable
            // ─────────────────────────────────────────────────
            const ctxMonthly = document.getElementById('monthlyVolumeChart');
            if (ctxMonthly) {
                const volumeLabels       = {!! json_encode($volumeLabels) !!};
                const volumeFormValues   = {!! json_encode($volumeFormValues) !!};
                const volumeSampleValues = {!! json_encode($volumeSampleValues) !!};

                new Chart(ctxMonthly, {
                    type: 'bar',
                    data: {
                        labels: volumeLabels,
                        datasets: [
                            {
                                label: 'Form Pengujian',
                                data: volumeFormValues,
                                backgroundColor: 'rgba(66, 153, 225, 0.75)',
                                borderColor: 'rgba(43, 108, 176, 1)',
                                borderWidth: 1,
                                borderRadius: 4,
                                yAxisID: 'yForms',
                                order: 2
                            },
                            {
                                label: 'Jumlah Sampel',
                                data: volumeSampleValues,
                                type: 'line',
                                borderColor: '#7F77DD',
                                backgroundColor: 'rgba(127, 119, 221, 0.08)',
                                borderWidth: 2,
                                borderDash: [5, 4],
                                pointBackgroundColor: '#7F77DD',
                                pointRadius: 3,
                                pointHoverRadius: 5,
                                tension: 0.35,
                                fill: false,
                                yAxisID: 'ySamples',
                                order: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            yForms: {
                                type: 'linear',
                                position: 'left',
                                beginAtZero: true,
                                ticks: { precision: 0, font: tickFont },
                                grid: { color: gridColor },
                                title: { display: true, text: 'Form', font: { size: 11 }, color: '#6b7280' }
                            },
                            ySamples: {
                                type: 'linear',
                                position: 'right',
                                beginAtZero: true,
                                ticks: { precision: 0, font: tickFont },
                                grid: { drawOnChartArea: false },
                                title: { display: true, text: 'Sampel', font: { size: 11 }, color: '#6b7280' }
                            },
                            x: {
                                ticks: { font: tickFont, autoSkip: false, maxRotation: 0 },
                                grid: { display: false }
                            }
                        }
                    }
                });
            }

            // ─────────────────────────────────────────────────
            // 2. Status Distribution Chart — Doughnut
            // ─────────────────────────────────────────────────
            const ctxStatus = document.getElementById('statusDistributionChart');
            if (ctxStatus) {
                const statusLabels = {!! json_encode(array_keys($statusStats)) !!};
                const statusValues = {!! json_encode(array_values($statusStats)) !!};
                const statusColors = {!! json_encode(array_values($statusColors)) !!};

                // Build HTML legend
                const legendEl = document.getElementById('statusLegend');
                if (legendEl) {
                    statusLabels.forEach(function (label, i) {
                        legendEl.innerHTML +=
                            '<span class="flex items-center gap-1">' +
                            '<span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:' + statusColors[i] + ';flex-shrink:0;"></span>' +
                            '<span>' + label + ' (' + statusValues[i].toLocaleString('id-ID') + ')</span>' +
                            '</span>';
                    });
                }

                new Chart(ctxStatus, {
                    type: 'doughnut',
                    data: {
                        labels: statusLabels,
                        datasets: [{
                            data: statusValues,
                            backgroundColor: statusColors,
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        cutout: '65%'
                    }
                });
            }

            // ─────────────────────────────────────────────────
            // 3. Top 10 Parameters Chart — Horizontal Bar
            // ─────────────────────────────────────────────────
            const ctxParams = document.getElementById('parameterStatsChart');
            if (ctxParams) {
                const paramLabels = {!! json_encode(array_keys($parameterStats)) !!};
                const paramValues = {!! json_encode(array_values($parameterStats)) !!};

                new Chart(ctxParams, {
                    type: 'bar',
                    data: {
                        labels: paramLabels,
                        datasets: [{
                            label: 'Jumlah Sampel Uji',
                            data: paramValues,
                            backgroundColor: 'rgba(20, 184, 166, 0.7)',
                            borderColor: 'rgba(13, 148, 136, 1)',
                            borderWidth: 1,
                            borderRadius: 3
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: { precision: 0, font: tickFont },
                                grid: { color: gridColor }
                            },
                            y: {
                                ticks: { font: tickFont },
                                grid: { display: false }
                            }
                        }
                    }
                });
            }

            // ─────────────────────────────────────────────────
            // 4. Analyst Workload Chart — Stacked Bar
            // ─────────────────────────────────────────────────
            const ctxWorkload = document.getElementById('analystWorkloadChart');
            if (ctxWorkload) {
                const workloadLabels = {!! json_encode(array_keys($analystWorkload)) !!};
                const workloadData   = {!! json_encode(array_values($analystWorkload)) !!};

                const activeData    = workloadData.map(function (d) { return d.active; });
                const completedData = workloadData.map(function (d) { return d.completed; });

                new Chart(ctxWorkload, {
                    type: 'bar',
                    data: {
                        labels: workloadLabels,
                        datasets: [
                            {
                                label: 'Aktif (Pending / In Progress)',
                                data: activeData,
                                backgroundColor: 'rgba(245, 158, 11, 0.8)',
                                borderRadius: 3,
                                stack: 'workload'
                            },
                            {
                                label: 'Selesai',
                                data: completedData,
                                backgroundColor: 'rgba(16, 185, 129, 0.8)',
                                borderRadius: 3,
                                stack: 'workload'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: {
                                stacked: true,
                                ticks: { font: tickFont },
                                grid: { display: false }
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                ticks: { precision: 0, font: tickFont },
                                grid: { color: gridColor }
                            }
                        }
                    }
                });
            }

            // ─────────────────────────────────────────────────
            // 5. Yearly Trend Chart — Line
            // ─────────────────────────────────────────────────
            const ctxYearly = document.getElementById('yearlyTrendChart');
            if (ctxYearly) {
                const yearlyLabels = {!! json_encode(array_keys($yearlyForms)) !!};
                const yearlyValues = {!! json_encode(array_values($yearlyForms)) !!};

                new Chart(ctxYearly, {
                    type: 'line',
                    data: {
                        labels: yearlyLabels,
                        datasets: [{
                            label: 'Form Masuk per Tahun',
                            data: yearlyValues,
                            borderColor: 'rgba(43, 108, 176, 1)',
                            backgroundColor: 'rgba(66, 153, 225, 0.08)',
                            borderWidth: 2.5,
                            pointRadius: 5,
                            pointHoverRadius: 7,
                            pointBackgroundColor: 'rgba(43, 108, 176, 1)',
                            fill: true,
                            tension: 0.15
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: {
                                ticks: { font: tickFont },
                                grid: { display: false }
                            },
                            y: {
                                beginAtZero: false,
                                ticks: { precision: 0, font: tickFont },
                                grid: { color: gridColor }
                            }
                        }
                    }
                });
            }

        });
    </script>
    @endpush

</x-app-layout>