<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Welcome Message --}}
            <div class="relative bg-gradient-to-r from-blue-700 via-indigo-700 to-indigo-900 rounded-2xl p-8 mb-8 shadow-xl overflow-hidden text-white">
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-blue-500/20 rounded-full blur-2xl"></div>
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-extrabold tracking-tight mb-2">Selamat datang, {{ $user->full_name ?? $user->username }}! 👋</h1>
                        <p class="text-blue-100 font-medium text-lg opacity-90">
                            @switch($role)
                                @case(\App\Enums\Role::SUPER_ADMIN)
                                    Super Administrator - Kelola seluruh sistem
                                    @break
                                @case(\App\Enums\Role::ADMIN)
                                    Administrator - Kelola form pengujian
                                    @break
                                @case(\App\Enums\Role::KEPALA_UPA)
                                    Kepala UPA - Verifikasi pengujian
                                    @break
                                @case(\App\Enums\Role::KEPALA_DIVISI)
                                    Kepala Divisi Teknis - Verifikasi teknis
                                    @break
                                @case(\App\Enums\Role::ANALIS)
                                    Analis - Pengujian sampel
                                    @break
                            @endswitch
                        </p>
                    </div>
                </div>
            </div>

            {{-- Super Admin Dashboard --}}
            @if($role == \App\Enums\Role::SUPER_ADMIN)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-500/10 rounded-full blur-xl group-hover:bg-blue-500/20 transition-all"></div>
                        <div class="flex items-center relative z-10">
                            <div class="p-4 bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl text-blue-600 shadow-sm">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            </div>
                            <div class="ml-5">
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total User</p>
                                <p class="text-3xl font-extrabold text-gray-800 tracking-tight">{{ $totalUsers }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-green-500/10 rounded-full blur-xl group-hover:bg-green-500/20 transition-all"></div>
                        <div class="flex items-center relative z-10">
                            <div class="p-4 bg-gradient-to-br from-green-100 to-green-200 rounded-xl text-green-600 shadow-sm">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div class="ml-5">
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">User Aktif</p>
                                <p class="text-3xl font-extrabold text-gray-800 tracking-tight">{{ $activeUsers }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:-translate-y-1 hover:shadow-xl transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-purple-500/10 rounded-full blur-xl group-hover:bg-purple-500/20 transition-all"></div>
                        <div class="flex items-center relative z-10">
                            <div class="p-4 bg-gradient-to-br from-purple-100 to-purple-200 rounded-xl text-purple-600 shadow-sm">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                            </div>
                            <div class="ml-5">
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Total Form</p>
                                <p class="text-3xl font-extrabold text-gray-800 tracking-tight">{{ $totalForms }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Danger Zone --}}
                <div class="mt-8 bg-white rounded-2xl shadow-sm border border-red-200 p-8 relative overflow-hidden group">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-red-500/5 rounded-full blur-3xl group-hover:bg-red-500/10 transition-colors"></div>
                    <div class="relative z-10">
                        <h3 class="text-xl font-extrabold text-red-600 mb-2 flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Danger Zone
                        </h3>
                        <p class="text-sm text-gray-600 mb-6 bg-red-50 text-red-800 p-4 rounded-xl border border-red-100">
                            Hapus semua data form pengujian, sampel, parameter, SP3, notifikasi, dan data terkait lainnya. 
                            <strong class="font-extrabold">Aksi ini bersifat permanen dan tidak dapat dibatalkan.</strong>
                        </p>

                        @if(session('success'))
                            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-sm font-bold flex items-center gap-2 shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                {{ session('success') }}
                            </div>
                        @endif

                        @error('confirmation')
                            <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm font-bold flex items-center gap-2 shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                {{ $message }}
                            </div>
                        @enderror

                        <form action="{{ route('admin.clear-forms') }}" method="POST" class="flex flex-col sm:flex-row items-end gap-4">
                            @csrf
                            <div class="flex-1 w-full sm:w-auto">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Ketik <span class="bg-gray-100 px-2 py-0.5 rounded font-mono text-red-600">HAPUS SEMUA</span> untuk konfirmasi</label>
                                <input type="text" name="confirmation" 
                                       placeholder="HAPUS SEMUA"
                                       class="w-full border-red-300 rounded-xl shadow-sm text-sm focus:ring-red-500 focus:border-red-500">
                            </div>
                            <button type="submit" 
                                    class="w-full sm:w-auto px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-xl shadow-md shadow-red-500/20 transition-all hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                Hapus Semua
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Admin Dashboard --}}
            @if($role == \App\Enums\Role::ADMIN)

                {{-- Quick Action: Create New Form --}}
                <div class="mb-8">
                    <a href="{{ route('form.create') }}" 
                       class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 shadow-lg shadow-emerald-500/30 text-white font-bold rounded-2xl transition-all hover:-translate-y-1">
                        <div class="p-2 bg-white/20 rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                        </div>
                        <span class="text-lg">Buat Form Pengujian Baru</span>
                    </a>
                </div>



                {{-- Deadline Alerts --}}
                @if($upcomingDeadlines->isNotEmpty())
                    <div class="bg-gradient-to-r from-red-50 to-orange-50 border border-red-200 rounded-2xl p-6 mb-8 shadow-sm relative overflow-hidden">
                        <div class="absolute -right-10 -top-10 w-40 h-40 bg-red-500/10 rounded-full blur-3xl"></div>
                        <h3 class="text-lg font-bold text-red-700 mb-4 flex items-center gap-2 relative z-10">
                            <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            Deadline Mendekat (3 Hari)
                        </h3>
                        <div class="space-y-3 relative z-10">
                            @foreach($upcomingDeadlines as $form)
                                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-4 bg-white/80 backdrop-blur rounded-xl border border-red-100 shadow-sm hover:shadow-md transition-all group">
                                    <div class="mb-3 sm:mb-0">
                                        <span class="font-bold text-gray-900">{{ $form->no_terima_sampel ?? $form->lhp_number ?? '-' }}</span>
                                        <span class="text-gray-500 ml-2 font-medium">· {{ $form->customer_name }}</span>
                                    </div>
                                    <div class="flex items-center flex-wrap gap-3 w-full sm:w-auto">
                                        <span class="text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide bg-{{ $form->status == 'dalam_pengujian' ? 'blue' : 'amber' }}-100 text-{{ $form->status == 'dalam_pengujian' ? 'blue' : 'amber' }}-700">
                                            {{ $form->status_label }}
                                        </span>
                                        <span class="text-red-600 font-bold bg-red-50 px-3 py-1 rounded-full text-sm">
                                            ⏱️ {{ \Carbon\Carbon::parse($form->deadline_date)->format('d M Y') }}
                                        </span>
                                        <a href="{{ route('form.show', $form) }}" class="inline-flex items-center gap-1 text-sm font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded-lg transition-colors ml-auto sm:ml-2">
                                            Detail <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 mb-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                        Distribusi Status Form
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        @php
                            $statusLabels = [
                                'dalam_pengujian'       => ['label' => 'Analisis',       'bg' => 'bg-blue-50',    'border' => 'border-blue-100',   'num' => 'text-blue-600',   'lbl' => 'text-blue-700'],
                                'menunggu_review_divisi'=> ['label' => 'Review Divisi',  'bg' => 'bg-purple-50',  'border' => 'border-purple-100', 'num' => 'text-purple-600', 'lbl' => 'text-purple-700'],
                                'ttd_upa'               => ['label' => 'TTD UPA',        'bg' => 'bg-orange-50',  'border' => 'border-orange-100', 'num' => 'text-orange-600', 'lbl' => 'text-orange-700'],
                                'kirim_customer'        => ['label' => 'Kirim Customer', 'bg' => 'bg-pink-50',    'border' => 'border-pink-100',   'num' => 'text-pink-600',   'lbl' => 'text-pink-700'],
                                'selesai'               => ['label' => 'Selesai',        'bg' => 'bg-green-50',   'border' => 'border-green-100',  'num' => 'text-green-600',  'lbl' => 'text-green-700'],
                            ];
                        @endphp
                        @foreach($statusLabels as $status => $info)
                            <div class="text-center p-5 {{ $info['bg'] }} hover:brightness-95 rounded-xl border {{ $info['border'] }} transition-colors duration-200">
                                <p class="text-3xl font-extrabold {{ $info['num'] }} tracking-tight">{{ $statusCounts[$status] ?? 0 }}</p>
                                <p class="text-[11px] font-bold {{ $info['lbl'] }} uppercase tracking-wider mt-2">{{ $info['label'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Kepala UPA Dashboard --}}
            @if($role == \App\Enums\Role::KEPALA_UPA)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-500/10 rounded-full blur-xl group-hover:bg-blue-500/20 transition-all"></div>
                        <div class="flex items-center justify-between relative z-10">
                            <div>
                                <p class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-1">Tugas Tanda Tangan</p>
                                <p class="text-4xl font-extrabold text-blue-700 tracking-tight">{{ $pendingTtd }}</p>
                            </div>
                            <div class="p-4 bg-white/60 backdrop-blur-sm rounded-xl text-blue-600 shadow-sm">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-emerald-50 to-green-50 border border-emerald-200 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/10 rounded-full blur-xl group-hover:bg-emerald-500/20 transition-all"></div>
                        <div class="flex items-center justify-between relative z-10">
                            <div>
                                <p class="text-xs font-bold text-emerald-600 uppercase tracking-widest mb-1">Total Pending</p>
                                <p class="text-4xl font-extrabold text-emerald-700 tracking-tight">{{ $totalPending }}</p>
                            </div>
                            <div class="p-4 bg-white/60 backdrop-blur-sm rounded-xl text-emerald-600 shadow-sm">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            </div>
                        </div>
                    </div>
                </div>
                @if($totalPending > 0)
                    <div class="mt-8 mb-4 text-center">
                        <a href="{{ route('kepala-upa.dashboard') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-8 py-3.5 rounded-xl font-bold shadow-md shadow-blue-500/20 hover:shadow-lg hover:-translate-y-0.5 transition-all">
                            Lihat Verifikasi <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </a>
                    </div>
                @endif
            @endif

            {{-- Kepala Divisi Dashboard --}}
            @if($role == \App\Enums\Role::KEPALA_DIVISI)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-purple-50 to-fuchsia-50 border border-purple-200 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-purple-500/10 rounded-full blur-xl group-hover:bg-purple-500/20 transition-all"></div>
                        <div class="flex items-center justify-between relative z-10">
                            <div>
                                <p class="text-xs font-bold text-purple-600 uppercase tracking-widest mb-1">Menunggu Review</p>
                                <p class="text-4xl font-extrabold text-purple-700 tracking-tight">{{ $pendingReview }}</p>
                            </div>
                            <div class="p-4 bg-white/60 backdrop-blur-sm rounded-xl text-purple-600 shadow-sm">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-200 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/10 rounded-full blur-xl group-hover:bg-emerald-500/20 transition-all"></div>
                        <div class="flex items-center justify-between relative z-10">
                            <div>
                                <p class="text-xs font-bold text-emerald-600 uppercase tracking-widest mb-1">Total Pending</p>
                                <p class="text-4xl font-extrabold text-emerald-700 tracking-tight">{{ $totalPending }}</p>
                            </div>
                            <div class="p-4 bg-white/60 backdrop-blur-sm rounded-xl text-emerald-600 shadow-sm">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            </div>
                        </div>
                    </div>
                </div>
                @if($totalPending > 0)
                    <div class="mt-8 mb-4 text-center">
                        <a href="{{ route('kepala-divisi.dashboard') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white px-8 py-3.5 rounded-xl font-bold shadow-md shadow-purple-500/20 hover:shadow-lg hover:-translate-y-0.5 transition-all">
                            Lihat Verifikasi <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </a>
                    </div>
                @endif
            @endif

            {{-- Analis Dashboard --}}
            @if($role == \App\Enums\Role::ANALIS)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-500/10 rounded-full blur-xl group-hover:bg-blue-500/20 transition-all"></div>
                        <div class="flex items-center justify-between relative z-10">
                            <div>
                                <p class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-1">Form Ditugaskan</p>
                                <p class="text-4xl font-extrabold text-blue-700 tracking-tight">{{ $myForms }}</p>
                            </div>
                            <div class="p-4 bg-white/60 backdrop-blur-sm rounded-xl text-blue-600 shadow-sm">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-emerald-50 to-green-50 border border-emerald-200 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/10 rounded-full blur-xl group-hover:bg-emerald-500/20 transition-all"></div>
                        <div class="flex items-center justify-between relative z-10">
                            <div>
                                <p class="text-xs font-bold text-emerald-600 uppercase tracking-widest mb-1">Selesai</p>
                                <p class="text-4xl font-extrabold text-emerald-700 tracking-tight">{{ $completedForms }}</p>
                            </div>
                            <div class="p-4 bg-white/60 backdrop-blur-sm rounded-xl text-emerald-600 shadow-sm">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                        </div>
                    </div>
                </div>
                @if($myForms > 0)
                    <div class="mt-8 mb-4 text-center">
                        <a href="{{ route('analis.dashboard') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white px-8 py-3.5 rounded-xl font-bold shadow-md shadow-blue-500/20 hover:shadow-lg hover:-translate-y-0.5 transition-all">
                            Lihat Tugas <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </a>
                    </div>
                @endif
            @endif

            {{-- Form List Section for all roles except Super Admin --}}
            @if($role != \App\Enums\Role::SUPER_ADMIN)
                @php
                    $currentTab = request('tab', 'aktif');
                @endphp
                <div class="mt-8" id="form-list">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-2xl font-extrabold text-gray-900 tracking-tight flex items-center gap-3">
                                <div class="p-2 bg-indigo-100 rounded-lg text-indigo-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                </div>
                                Daftar Form Pengujian
                            </h3>
                            <p class="text-gray-500 mt-1">Kelola dan pantau semua form pengujian di sistem</p>
                        </div>
                        @if(isset($defaultFilter) && $defaultFilter)
                            <a href="{{ route('dashboard', ['tab' => $currentTab]) }}#form-list" 
                               class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-sm font-bold transition-colors">
                                Lihat Semua Form →
                            </a>
                        @endif
                    </div>

                    {{-- Default Filter Indicator --}}
                    @if(isset($defaultFilter) && $defaultFilter)
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 mb-6 flex items-center justify-between shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="bg-blue-100 p-2 rounded-lg text-blue-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <span class="text-sm font-medium text-blue-800">
                                    @switch($defaultFilter)
                                        @case('kepala_upa')
                                            Menampilkan form yang menunggu <strong>Verifikasi UPA</strong>
                                            @break
                                        @case('kepala_divisi')
                                            Menampilkan form yang menunggu <strong>Verifikasi Divisi</strong>
                                            @break
                                        @case('analis')
                                            Menampilkan form yang tersedia untuk <strong>Pengujian</strong>
                                            @break
                                    @endswitch
                                </span>
                            </div>
                        </div>
                    @endif
                    
                    {{-- Summary Cards --}}
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:-translate-y-1 hover:shadow-md transition-all duration-300 group overflow-hidden relative">
                            <div class="absolute -right-4 -top-4 w-20 h-20 bg-gray-50 rounded-full group-hover:bg-gray-100 transition-colors"></div>
                            <div class="relative z-10 flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1">Total Form</p>
                                    <p class="text-3xl font-extrabold text-gray-900">{{ $formStatusCounts['total'] }}</p>
                                </div>
                                <div class="p-3 bg-gray-50 rounded-xl text-gray-400 group-hover:text-gray-600 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl shadow-sm border border-blue-100 p-5 hover:-translate-y-1 hover:shadow-md transition-all duration-300 group overflow-hidden relative">
                            <div class="absolute -right-4 -top-4 w-20 h-20 bg-blue-100/50 rounded-full group-hover:bg-blue-200/50 transition-colors"></div>
                            <div class="relative z-10 flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-1">Aktif</p>
                                    <p class="text-3xl font-extrabold text-blue-800">{{ $formStatusCounts['active'] }}</p>
                                </div>
                                <div class="p-3 bg-white/60 rounded-xl text-blue-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-emerald-50 to-green-50 rounded-2xl shadow-sm border border-emerald-100 p-5 hover:-translate-y-1 hover:shadow-md transition-all duration-300 group overflow-hidden relative">
                            <div class="absolute -right-4 -top-4 w-20 h-20 bg-emerald-100/50 rounded-full group-hover:bg-emerald-200/50 transition-colors"></div>
                            <div class="relative z-10 flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-widest mb-1">Selesai</p>
                                    <p class="text-3xl font-extrabold text-emerald-800">{{ $formStatusCounts['selesai'] }}</p>
                                </div>
                                <div class="p-3 bg-white/60 rounded-xl text-emerald-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gradient-to-br from-orange-50 to-red-50 rounded-2xl shadow-sm border border-orange-100 p-5 hover:-translate-y-1 hover:shadow-md transition-all duration-300 group overflow-hidden relative">
                            <div class="absolute -right-4 -top-4 w-20 h-20 bg-orange-100/50 rounded-full group-hover:bg-orange-200/50 transition-colors"></div>
                            <div class="relative z-10 flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-bold text-orange-600 uppercase tracking-widest mb-1">Deadline Dekat</p>
                                    <p class="text-3xl font-extrabold text-orange-800">{{ $formStatusCounts['overdue'] }}</p>
                                </div>
                                <div class="p-3 bg-white/60 rounded-xl text-orange-500">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Filter Section --}}
                    <div class="bg-white/80 backdrop-blur-xl border border-gray-200 rounded-2xl shadow-sm mb-8 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                            <h4 class="font-bold text-gray-700">Filter & Pencarian</h4>
                        </div>
                        <form method="GET" action="{{ route('dashboard') }}#form-list" class="p-6">
                            {{-- Preserve current tab --}}
                            <input type="hidden" name="tab" value="{{ $currentTab }}">
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                {{-- Search --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Pencarian</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                        </div>
                                        <input type="text" name="search" value="{{ request('search') }}" 
                                               placeholder="No. Form atau nama customer..."
                                               class="pl-10 w-full border-gray-300 rounded-xl shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    </div>
                                </div>
                                
                                {{-- Tahap Filter --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Tahap Form</label>
                                    <select name="stage" class="w-full border-gray-300 rounded-xl shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">Semua Tahap</option>
                                        @foreach($statusOptions as $key => $label)
                                            <option value="{{ $key }}" {{ request('stage') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Urutkan --}}
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Urutkan Berdasarkan</label>
                                    @php
                                        $currentSort = request('sort', 'deadline_date');
                                        $currentDir = request('dir', 'asc');
                                        $sortOptions = [
                                            'deadline_date' => 'Deadline',
                                            'no_terima_sampel' => 'No. Terima Sampel'
                                        ];
                                    @endphp
                                    <div class="flex gap-2">
                                        @foreach($sortOptions as $sortKey => $sortLabel)
                                            @php
                                                $isActive = $currentSort == $sortKey;
                                                $nextDir = ($isActive && $currentDir == 'asc') ? 'desc' : 'asc';
                                                $arrow = $isActive ? ($currentDir == 'asc' ? '↑' : '↓') : '';
                                            @endphp
                                            <a href="{{ route('dashboard', array_merge(request()->except(['sort', 'dir']), ['sort' => $sortKey, 'dir' => $nextDir])) }}#form-list"
                                               class="flex-1 text-center px-4 py-2 text-sm font-bold rounded-xl border transition-all duration-200
                                                      {{ $isActive 
                                                          ? 'bg-indigo-600 text-white border-indigo-600 shadow-md hover:bg-indigo-700' 
                                                          : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50' }}">
                                                {{ $sortLabel }} {{ $arrow }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Quick Filters & Form Actions --}}
                            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-gray-100">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider mr-2">Quick:</span>
                                    <a href="{{ route('dashboard', ['tab' => $currentTab, 'quick' => 'deadline_3days']) }}#form-list" 
                                       class="px-4 py-1.5 rounded-full text-xs font-bold transition-all duration-200 hover:-translate-y-0.5 shadow-sm
                                              {{ request('quick') == 'deadline_3days' ? 'bg-orange-500 text-white shadow-orange-500/30' : 'bg-orange-50 text-orange-700 hover:bg-orange-100' }}">
                                        Deadline 3 Hari
                                    </a>
                                    <a href="{{ route('dashboard', ['tab' => $currentTab, 'quick' => 'overdue']) }}#form-list" 
                                       class="px-4 py-1.5 rounded-full text-xs font-bold transition-all duration-200 hover:-translate-y-0.5 shadow-sm
                                              {{ request('quick') == 'overdue' ? 'bg-red-600 text-white shadow-red-500/30' : 'bg-red-50 text-red-700 hover:bg-red-100' }}">
                                        Terlambat
                                    </a>
                                </div>
                                <div class="flex items-center gap-3 w-full sm:w-auto">
                                    <a href="{{ route('dashboard', ['tab' => $currentTab]) }}#form-list" class="flex-1 sm:flex-none text-center px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-sm transition-colors">Reset</a>
                                    <button type="submit" class="flex-1 sm:flex-none px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-sm shadow-md shadow-indigo-500/30 transition-all hover:-translate-y-0.5">Terapkan Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Tab Navigation --}}
                    <div class="mb-6 flex space-x-2">
                        <a href="{{ route('dashboard', ['tab' => 'aktif']) }}#form-list"
                           class="flex items-center px-6 py-3 rounded-xl font-bold text-sm transition-all
                                  {{ $currentTab == 'aktif' 
                                      ? 'bg-white shadow-sm text-indigo-700 border border-gray-200' 
                                      : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50' }}">
                            📋 Form Aktif
                            <span class="ml-2 px-2.5 py-0.5 rounded-full text-xs font-extrabold {{ $currentTab == 'aktif' ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-200 text-gray-600' }}">
                                {{ $formStatusCounts['active'] }}
                            </span>
                        </a>
                        <a href="{{ route('dashboard', ['tab' => 'selesai']) }}#form-list"
                           class="flex items-center px-6 py-3 rounded-xl font-bold text-sm transition-all
                                  {{ $currentTab == 'selesai' 
                                      ? 'bg-white shadow-sm text-emerald-700 border border-gray-200' 
                                      : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200/50' }}">
                            ✅ Form Selesai
                            <span class="ml-2 px-2.5 py-0.5 rounded-full text-xs font-extrabold {{ $currentTab == 'selesai' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600' }}">
                                {{ $formStatusCounts['selesai'] }}
                            </span>
                        </a>
                    </div>

                    {{-- Results Table --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 flex items-center justify-between bg-gray-50/50 border-b border-gray-100">
                            <span class="text-sm font-bold text-gray-600 bg-white px-3 py-1 rounded-full border border-gray-200 shadow-sm">
                                Menampilkan {{ $forms->firstItem() ?? 0 }} - {{ $forms->lastItem() ?? 0 }} dari {{ $forms->total() }} form
                            </span>
                        </div>

                        @if($forms->isEmpty())
                            <div class="p-8 text-center text-gray-500">
                                Tidak ada form yang sesuai dengan filter
                            </div>
                        @else
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50/80 backdrop-blur-sm">
                                        <tr>
                                            <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-gray-500 uppercase tracking-widest">No. Form</th>
                                            <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-gray-500 uppercase tracking-widest">Customer</th>
                                            <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-gray-500 uppercase tracking-widest">Deadline</th>
                                            <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-gray-500 uppercase tracking-widest">Sisa Waktu</th>
                                            <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-gray-500 uppercase tracking-widest">Status</th>
                                            <th scope="col" class="px-6 py-4 text-left text-xs font-extrabold text-gray-500 uppercase tracking-widest">Perlu Tindakan</th>
                                            <th scope="col" class="px-6 py-4 text-center text-xs font-extrabold text-gray-500 uppercase tracking-widest">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        @foreach($forms as $form)
                                            @php
                                                $deadline = \Carbon\Carbon::parse($form->deadline_date);
                                                $today = \Carbon\Carbon::today();
                                                $daysLeft = $today->diffInDays($deadline, false);
                                                $isOverdue = $daysLeft < 0;
                                                $isUrgent = $daysLeft >= 0 && $daysLeft <= 3;
                                                $isCompleted = in_array($form->status, ['selesai', 'ditolak']);
                                                
                                                $statusColors = [
                                                    'verifikasi_upa_1' => 'bg-yellow-100 text-yellow-800',
                                                    'verifikasi_divisi' => 'bg-purple-100 text-purple-800',
                                                    'dalam_pengujian' => 'bg-blue-100 text-blue-800',
                                                    'verifikasi_hasil_divisi' => 'bg-indigo-100 text-indigo-800',
                                                    'input_lhp' => 'bg-cyan-100 text-cyan-800',
                                                    'ttd_divisi_lhp' => 'bg-amber-100 text-amber-800',
                                                    'ttd_upa' => 'bg-orange-100 text-orange-800',
                                                    'kirim_customer' => 'bg-pink-100 text-pink-800',
                                                    'verifikasi_hasil_upa' => 'bg-orange-100 text-orange-800',
                                                    'validasi_admin' => 'bg-amber-100 text-amber-800',
                                                    'selesai' => 'bg-green-100 text-green-800',
                                                    'ditolak' => 'bg-red-100 text-red-800',
                                                ];
                                            @endphp
                                            <tr class="hover:bg-indigo-50/30 transition-colors duration-150 group">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <div class="h-10 w-1 bg-indigo-500 rounded-r-md hidden group-hover:block absolute left-0"></div>
                                                        <span class="font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">{{ $form->no_terima_sampel ?? $form->lhp_number ?? '-' }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-700">{{ $form->customer_name }}</div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm {{ $isOverdue && !$isCompleted ? 'text-red-600 font-bold bg-red-50 px-2 py-1 rounded-lg inline-block' : 'text-gray-600 font-medium' }}">
                                                        {{ $deadline->format('d M Y') }}
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @if($isCompleted)
                                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-gray-100 text-gray-500">
                                                            <div class="w-1.5 h-1.5 rounded-full bg-gray-400"></div>
                                                            Selesai
                                                        </span>
                                                    @elseif($isOverdue)
                                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-red-100 text-red-800 animate-pulse border border-red-200">
                                                            <div class="w-1.5 h-1.5 rounded-full bg-red-500"></div>
                                                            Telat {{ abs($daysLeft) }} hari
                                                        </span>
                                                    @elseif($daysLeft == 0)
                                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-orange-100 text-orange-800 border border-orange-200">
                                                            <div class="w-1.5 h-1.5 rounded-full bg-orange-500 animate-ping absolute"></div>
                                                            <div class="w-1.5 h-1.5 rounded-full bg-orange-500 relative"></div>
                                                            Hari ini!
                                                        </span>
                                                    @elseif($isUrgent)
                                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-yellow-100 text-yellow-800 border border-yellow-200">
                                                            <div class="w-1.5 h-1.5 rounded-full bg-yellow-500"></div>
                                                            {{ $daysLeft }} hari lagi
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                                                            {{ $daysLeft }} hari lagi
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold {{ $statusColors[$form->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                        {{ $form->status_label }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    @php
                                                        $actionRequired = match($form->status) {
                                                            'draft' => ['role' => 'Admin', 'bg' => 'bg-gray-100 text-gray-800 border border-gray-200'],
                                                            'verifikasi_upa_1' => ['role' => 'Kepala UPA', 'bg' => 'bg-yellow-100 text-yellow-800 border border-yellow-200'],
                                                            'verifikasi_divisi' => ['role' => 'Kepala Divisi', 'bg' => 'bg-purple-100 text-purple-800 border border-purple-200'],
                                                            'dalam_pengujian' => [
                                                                'role' => 'Analis',
                                                                'bg' => 'bg-blue-100 text-blue-800 border border-blue-200',
                                                                'users' => $form->samples->flatMap->sampleParameters->pluck('filledByAnalyst')->filter()->unique('user_id')->pluck('full_name')->take(2)->implode(', ')
                                                            ],
                                                            'verifikasi_hasil_divisi' => ['role' => 'Kepala Divisi', 'bg' => 'bg-indigo-100 text-indigo-800 border border-indigo-200'],
                                                            'input_lhp' => ['role' => 'Admin', 'bg' => 'bg-cyan-100 text-cyan-800 border border-cyan-200'],
                                                            'ttd_divisi_lhp' => ['role' => 'Kepala Divisi', 'bg' => 'bg-amber-100 text-amber-800 border border-amber-200'],
                                                            'ttd_upa' => ['role' => 'Kepala UPA', 'bg' => 'bg-orange-100 text-orange-800 border border-orange-200'],
                                                            'kirim_customer' => ['role' => 'Admin', 'bg' => 'bg-pink-100 text-pink-800 border border-pink-200'],
                                                            'verifikasi_hasil_upa' => ['role' => 'Kepala UPA', 'bg' => 'bg-orange-100 text-orange-800 border border-orange-200'],
                                                            'validasi_admin' => ['role' => 'Admin', 'bg' => 'bg-amber-100 text-amber-800 border border-amber-200'],
                                                            'selesai' => ['role' => 'Selesai', 'bg' => 'bg-green-100 text-green-800 border border-green-200'],
                                                            'ditolak' => ['role' => 'Revisi', 'bg' => 'bg-red-100 text-red-800 border border-red-200'],
                                                            default => ['role' => '-', 'bg' => 'bg-gray-100 text-gray-800'],
                                                        };
                                                    @endphp
                                                    <span class="inline-flex px-3 py-1 rounded-xl text-xs font-bold {{ $actionRequired['bg'] }}">
                                                        {{ $actionRequired['role'] }}
                                                    </span>
                                                    @if(!empty($actionRequired['users']))
                                                        <div class="text-[11px] font-medium text-gray-500 mt-1.5 flex items-center gap-1">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                                            {{ \Illuminate\Support\Str::limit($actionRequired['users'], 20) }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                                    <a href="{{ $role == \App\Enums\Role::KEPALA_DIVISI ? route('kepala-divisi.show', $form) : ($role == \App\Enums\Role::KEPALA_UPA ? route('kepala-upa.show', $form) : route('form-list.show', $form)) }}"
                                                       class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all duration-200 group-hover:scale-110 shadow-sm hover:shadow-md" title="Detail">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-4 border-t border-gray-200">
                                {{ $forms->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
