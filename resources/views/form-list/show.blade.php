<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Form: {{ $form->form_number }}
            </h2>
            <a href="{{ route('form-list.index') }}" class="text-blue-600 hover:underline">← Kembali ke Daftar</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Form Info --}}
            <div class="bg-white/90 backdrop-blur-sm overflow-hidden shadow-sm rounded-2xl border border-gray-100 mb-8 transition-all hover:shadow-md">
                <div class="p-8">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Informasi Form
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <p class="text-sm text-gray-500">No. Form</p>
                            <p class="font-medium text-gray-900">{{ $form->form_number }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Customer</p>
                            <p class="font-medium text-gray-900">
                                @if(auth()->user()->role_id == \App\Enums\Role::ANALIS)
                                    <span class="text-gray-400 italic">*** Dirahasiakan ***</span>
                                @else
                                    {{ $form->customer_name }}
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Asal Instansi</p>
                            <p class="font-medium text-gray-900">
                                @if(auth()->user()->role_id == \App\Enums\Role::ANALIS)
                                    <span class="text-gray-400 italic">*** Dirahasiakan ***</span>
                                @else
                                    {{ $form->customer_institution ?? '-' }}
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Jabatan</p>
                            <p class="font-medium text-gray-900">
                                @if(auth()->user()->role_id == \App\Enums\Role::ANALIS)
                                    <span class="text-gray-400 italic">*** Dirahasiakan ***</span>
                                @else
                                    {{ $form->customer_position ?? '-' }}
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Tanggal Masuk</p>
                            <p class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($form->received_date)->format('d M Y') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Deadline</p>
                            @php
                                $deadline = \Carbon\Carbon::parse($form->deadline_date);
                                $today = \Carbon\Carbon::today();
                                $daysLeft = $today->diffInDays($deadline, false);
                                $isOverdue = $daysLeft < 0;
                                $isCompleted = in_array($form->status, ['selesai', 'ditolak']);
                            @endphp
                            <p class="font-medium {{ $isOverdue && !$isCompleted ? 'text-red-600' : 'text-gray-900' }}">
                                {{ $deadline->format('d M Y') }}
                                @if(!$isCompleted)
                                    @if($isOverdue)
                                        <span class="text-sm text-red-600">({{ abs($daysLeft) }} hari terlambat)</span>
                                    @elseif($daysLeft == 0)
                                        <span class="text-sm text-red-600">(Hari ini!)</span>
                                    @else
                                        <span class="text-sm text-gray-500">({{ $daysLeft }} hari lagi)</span>
                                    @endif
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Admin</p>
                            <p class="font-medium text-gray-900">{{ $form->admin->full_name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Analis</p>
                            <p class="font-medium text-gray-900">{{ $form->assignedAnalyst->full_name ?? 'Belum ditugaskan' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-500">Status</p>
                            @php
                                $statusColors = [
                                    'verifikasi_upa_1' => 'bg-yellow-100 text-yellow-800',
                                    'verifikasi_divisi' => 'bg-purple-100 text-purple-800',
                                    'dalam_pengujian' => 'bg-blue-100 text-blue-800',
                                    'verifikasi_hasil_divisi' => 'bg-indigo-100 text-indigo-800',
                                    'input_lhp' => 'bg-pink-100 text-pink-800',
                                    'ttd_divisi_lhp' => 'bg-amber-100 text-amber-800',
                                    'ttd_upa' => 'bg-orange-100 text-orange-800',
                                    'kirim_customer' => 'bg-teal-100 text-teal-800',
                                    'selesai' => 'bg-green-100 text-green-800',
                                    'ditolak' => 'bg-red-100 text-red-800',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$form->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $form->status_label }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Progress Steps --}}
            <div class="bg-white/90 backdrop-blur-sm overflow-hidden shadow-sm rounded-2xl border border-gray-100 mb-8 transition-all hover:shadow-md">
                <div class="p-8">
                    <h3 class="text-xl font-bold text-gray-800 mb-8 flex items-center gap-2">
                        <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Progress Verifikasi
                    </h3>
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
                    <div class="flex items-center justify-between overflow-x-auto pb-2">
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
                                    <span class="text-[10px] text-gray-500 mt-0.5 text-center leading-tight">{{ $verifierName }}</span>
                                @endif
                            </div>
                            @if($num < 9)
                                <div class="flex-1 h-1.5 mx-2 min-w-6 rounded-full {{ $num <= $currentIndex ? 'bg-gradient-to-r from-blue-500 to-indigo-500' : 'bg-gray-100' }}"></div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Samples & Parameters --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Daftar Sampel ({{ $form->samples->count() }} sampel)</h3>
                    
                    <div class="space-y-4">
                        @foreach($form->samples as $index => $sample)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h4 class="font-medium text-gray-900">{{ $index + 1 }}. {{ $sample->sample_name }}</h4>
                                        <p class="text-sm text-gray-500">Kode: {{ $sample->sample_code }} | Jumlah: {{ $sample->quantity }} {{ $sample->unit ?? 'pcs' }}</p>
                                    </div>
                                </div>

                                @if($sample->description)
                                    <div class="mb-3 p-2 bg-yellow-50 border-l-4 border-yellow-400 rounded-r">
                                        <p class="text-sm text-yellow-800">📝 <span class="font-medium">Keterangan:</span> {{ $sample->description }}</p>
                                    </div>
                                @endif

                                {{-- Parameters Table --}}
                                <div class="bg-gray-50 rounded-lg overflow-hidden">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Parameter</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Status</th>
                                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Hasil</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach($sample->sampleParameters as $sp)
                                                @php
                                                    $paramStatusColors = [
                                                        'pending' => 'bg-gray-100 text-gray-600',
                                                        'in_progress' => 'bg-blue-100 text-blue-700',
                                                        'done' => 'bg-green-100 text-green-700',
                                                    ];
                                                    $paramStatusLabels = [
                                                        'pending' => 'Menunggu',
                                                        'in_progress' => 'Dikerjakan',
                                                        'done' => 'Selesai',
                                                    ];
                                                @endphp
                                                <tr>
                                                    <td class="px-3 py-2 text-sm font-medium text-gray-900">{{ $sp->parameter->name ?? 'N/A' }}</td>
                                                    <td class="px-3 py-2">
                                                        @php
                                                            // Check for LCP availability for this parameter
                                                            $sp3ForStatus = $form->sp3Documents->firstWhere('parameter_id', $sp->parameter_id);
                                                            $isDone = $sp->status === 'done' || ($sp3ForStatus && $sp3ForStatus->lcp_google_file_url);
                                                            $currentStatus = $isDone ? 'done' : $sp->status;
                                                        @endphp
                                                        <span class="px-2 py-0.5 rounded text-xs {{ $paramStatusColors[$currentStatus] ?? 'bg-gray-100' }}">
                                                            {{ $paramStatusLabels[$currentStatus] ?? $currentStatus }}
                                                        </span>
                                                    </td>
                                                    <td class="px-3 py-2 text-sm text-gray-700">
                                                        <div class="flex flex-col space-y-1">
                                                            @php
                                                                $sp3 = $form->sp3Documents->firstWhere('parameter_id', $sp->parameter_id);
                                                                $hasLcp = $sp3 && $sp3->lcp_google_file_url;
                                                            @endphp

                                                            @if($sp->analysisResult)
                                                                <div>
                                                                    <strong>{{ $sp->analysisResult->result_value }}</strong>
                                                                    @if($sp->analysisResult->notes)
                                                                        <span class="text-gray-400 ml-1">({{ $sp->analysisResult->notes }})</span>
                                                                    @endif
                                                                </div>
                                                            @elseif(!$hasLcp)
                                                                <span class="text-gray-400">-</span>
                                                            @endif
                                                            
                                                            {{-- LCP Link --}}
                                                            @if($hasLcp)
                                                                <a href="{{ $sp3->lcp_google_file_url }}" target="_blank" 
                                                                   class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 w-fit">
                                                                    📄 Lihat LCP
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Document Links Table --}}
            <div class="bg-white/90 backdrop-blur-sm overflow-hidden shadow-sm rounded-2xl border border-gray-100 mb-8 transition-all hover:shadow-md">
                <div class="p-8">
                    <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>
                        Daftar Dokumen
                    </h3>
                    <div class="overflow-x-auto rounded-xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50/80">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Dokumen</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Keterangan</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                {{-- SPU --}}
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">SPU (Surat Perintah Uji)</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $form->no_spu ?? 'Belum ada nomor' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($form->spu_signed_doc_id)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Signed</span>
                                        @elseif($form->spu_unsigned_doc_id)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Draft / Unsigned</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Belum Ada</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        @php
                                            // Construct URL if ID exists but URL doesn't
                                            $spuUrl = $form->spu_signed_doc_url 
                                                ?? ($form->spu_signed_doc_id ? "https://docs.google.com/document/d/{$form->spu_signed_doc_id}" : null)
                                                ?? $form->spu_unsigned_doc_url 
                                                ?? ($form->spu_unsigned_doc_id ? "https://docs.google.com/document/d/{$form->spu_unsigned_doc_id}" : null);
                                        @endphp
                                        
                                        @if($spuUrl)
                                            <a href="{{ $spuUrl }}" target="_blank" class="text-blue-600 hover:text-blue-900 flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                Lihat Dokumen
                                            </a>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>

                                {{-- LHP --}}
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">LHP (Laporan Hasil Pengujian)</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $form->lhp_uploaded_at ? $form->lhp_uploaded_at->format('d M Y H:i') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($form->lhp_google_file_url)
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Tersedia</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Belum Ada</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        @if($form->lhp_google_file_url)
                                            <a href="{{ $form->lhp_google_file_url }}" target="_blank" class="text-blue-600 hover:text-blue-900 flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                Lihat Dokumen
                                            </a>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>

                                {{-- SP3 List --}}
                                @foreach($form->sp3Documents as $sp3)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            SP3 - {{ $sp3->parameter->name ?? 'Parameter' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $sp3->sp3_number ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($sp3->google_doc_url)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Tersedia</span>
                                            @elseif($sp3->google_doc_id)
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Draft</span>
                                            @else
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Belum Ada</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            @php
                                                $sp3Url = $sp3->google_doc_url 
                                                    ?? ($sp3->google_doc_id ? "https://docs.google.com/document/d/{$sp3->google_doc_id}" : null);
                                            @endphp
                                            
                                            @if($sp3Url)
                                                <a href="{{ $sp3Url }}" target="_blank" class="text-blue-600 hover:text-blue-900 flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                                    Lihat Dokumen
                                                </a>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- SP3 Documents Edit (Only visible for Admin) --}}
            @if(strtolower(auth()->user()->role->role_name) === 'admin' && $form->sp3Documents && $form->sp3Documents->isNotEmpty())
                <div class="bg-white/90 backdrop-blur-sm overflow-hidden shadow-sm rounded-2xl border border-gray-100 mb-8 transition-all hover:shadow-md">
                    <div class="p-8">
                        <h3 class="text-xl font-bold text-gray-800 mb-2 flex items-center gap-2">
                            <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            Dokumen SP3 ({{ $form->sp3Documents->count() }})
                        </h3>
                        <p class="text-sm font-medium text-gray-500 mb-6">Sebagai Admin, Anda dapat mengisi No. SPPP dan Instruksi Kerja untuk setiap SP3 dari halaman ini.</p>

                        <div class="space-y-6">
                            @foreach($form->sp3Documents as $sp3)
                                <div class="group bg-gray-50/50 rounded-xl p-5 border border-gray-200 hover:border-indigo-300 hover:shadow-md transition-all duration-300">
                                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5 gap-3">
                                        <div>
                                            <h4 class="font-bold text-gray-900 flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                                {{ $sp3->sp3_number }}
                                            </h4>
                                            <p class="text-sm font-medium text-gray-500 pl-4 mt-0.5">Parameter: {{ $sp3->parameter->name ?? '-' }}</p>
                                        </div>
                                        @if($sp3->google_doc_id)
                                            <a href="https://docs.google.com/document/d/{{ $sp3->google_doc_id }}" target="_blank"
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-lg text-sm font-semibold transition-colors">
                                               <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                               Buka Dokumen
                                            </a>
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
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
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

            {{-- Log Verifikasi --}}
            @if($form->verifications->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
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
