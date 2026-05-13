<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Daftar Semua Form Pengujian
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Summary Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-8">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center hover:-translate-y-1 hover:shadow-xl transition-all duration-300 relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-br from-gray-50 to-white opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative z-10">
                        <p class="text-4xl font-extrabold text-gray-800 tracking-tight">{{ $statusCounts['total'] }}</p>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-2">Total Form</p>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl shadow-sm p-6 text-center border border-blue-100 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-900/10 transition-all duration-300 relative overflow-hidden group">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-500/10 rounded-full blur-xl group-hover:bg-blue-500/20 transition-all"></div>
                    <div class="relative z-10">
                        <p class="text-4xl font-extrabold text-blue-700 tracking-tight">{{ $statusCounts['active'] }}</p>
                        <p class="text-xs font-bold text-blue-500 uppercase tracking-widest mt-2">Aktif</p>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-2xl shadow-sm p-6 text-center border border-emerald-100 hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-900/10 transition-all duration-300 relative overflow-hidden group">
                    <div class="absolute -left-4 -bottom-4 w-24 h-24 bg-emerald-500/10 rounded-full blur-xl group-hover:bg-emerald-500/20 transition-all"></div>
                    <div class="relative z-10">
                        <p class="text-4xl font-extrabold text-emerald-600 tracking-tight">{{ $statusCounts['selesai'] }}</p>
                        <p class="text-xs font-bold text-emerald-500 uppercase tracking-widest mt-2">Selesai</p>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-rose-50 to-red-50 rounded-2xl shadow-sm p-6 text-center border border-rose-100 hover:-translate-y-1 hover:shadow-xl hover:shadow-rose-900/10 transition-all duration-300 relative overflow-hidden group">
                    <div class="absolute right-0 bottom-0 w-24 h-24 bg-rose-500/10 rounded-full blur-xl group-hover:bg-rose-500/20 transition-all"></div>
                    <div class="relative z-10">
                        <p class="text-4xl font-extrabold text-rose-600 tracking-tight">{{ $statusCounts['overdue'] }}</p>
                        <p class="text-xs font-bold text-rose-500 uppercase tracking-widest mt-2">Terlambat</p>
                    </div>
                </div>
            </div>

            {{-- Filter Section --}}
            <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-sm border border-gray-100 mb-8 overflow-hidden transition-all hover:shadow-md">
                <div class="p-5 bg-gradient-to-r from-gray-50 to-white border-b border-gray-100 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                    <h3 class="font-semibold text-gray-800 tracking-tight">Pencarian & Penyaringan</h3>
                </div>
                <form method="GET" action="{{ route('form-list.index') }}" class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        {{-- Search --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cari</label>
                            <input type="text" name="search" value="{{ request('search') }}" 
                                   placeholder="No. Form / Customer"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                        </div>

                        {{-- Status Filter --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                <option value="">Semua Status</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif (Belum Selesai)</option>
                                <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai</option>
                                <option value="ditolak" {{ request('status') == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                            </select>
                        </div>

                        {{-- Stage Filter --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tahap Workflow</label>
                            <select name="stage" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                <option value="">Semua Tahap</option>
                                @foreach($statusOptions as $key => $label)
                                    <option value="{{ $key }}" {{ request('stage') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Sort --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Urutkan</label>
                            <select name="sort" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                <option value="deadline_date" {{ request('sort') == 'deadline_date' ? 'selected' : '' }}>Deadline</option>
                                <option value="received_date" {{ request('sort') == 'received_date' ? 'selected' : '' }}>Tanggal Masuk</option>
                                <option value="form_number" {{ request('sort') == 'form_number' ? 'selected' : '' }}>No. Form</option>
                                <option value="customer_name" {{ request('sort') == 'customer_name' ? 'selected' : '' }}>Customer</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        {{-- Deadline Range --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Deadline Dari</label>
                            <input type="date" name="deadline_from" value="{{ request('deadline_from') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Deadline Sampai</label>
                            <input type="date" name="deadline_to" value="{{ request('deadline_to') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                        </div>

                        {{-- Received Date Range --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Masuk Dari</label>
                            <input type="date" name="received_from" value="{{ request('received_from') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Masuk Sampai</label>
                            <input type="date" name="received_to" value="{{ request('received_to') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        {{-- Quick Filters --}}
                        <div class="flex flex-wrap gap-2">
                            <span class="text-sm text-gray-500">Quick:</span>
                            <a href="{{ route('form-list.index', ['quick' => 'deadline_3days']) }}" 
                               class="px-3 py-1 rounded-full text-xs font-medium {{ request('quick') == 'deadline_3days' ? 'bg-red-600 text-white' : 'bg-red-100 text-red-700 hover:bg-red-200' }}">
                                Deadline 3 Hari
                            </a>
                            <a href="{{ route('form-list.index', ['quick' => 'deadline_week']) }}" 
                               class="px-3 py-1 rounded-full text-xs font-medium {{ request('quick') == 'deadline_week' ? 'bg-yellow-600 text-white' : 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200' }}">
                                Deadline Minggu Ini
                            </a>
                            <a href="{{ route('form-list.index', ['quick' => 'overdue']) }}" 
                               class="px-3 py-1 rounded-full text-xs font-medium {{ request('quick') == 'overdue' ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
                                Terlambat
                            </a>
                            <a href="{{ route('form-list.index', ['status' => 'active']) }}" 
                               class="px-3 py-1 rounded-full text-xs font-medium {{ request('status') == 'active' && !request('quick') ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-700 hover:bg-blue-200' }}">
                                Aktif
                            </a>
                            <a href="{{ route('form-list.index', ['status' => 'selesai']) }}" 
                               class="px-3 py-1 rounded-full text-xs font-medium {{ request('status') == 'selesai' ? 'bg-green-600 text-white' : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                                Selesai
                            </a>
                        </div>

                        <div class="flex gap-3">
                            <a href="{{ route('form-list.index') }}" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-50 hover:text-gray-900 focus:ring-2 focus:ring-offset-2 focus:ring-gray-200 transition-all shadow-sm">
                                Reset
                            </a>
                            <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-blue-600 text-white rounded-xl text-sm font-medium shadow-md shadow-indigo-500/30 hover:shadow-lg hover:shadow-indigo-500/40 hover:-translate-y-0.5 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all">
                                Terapkan Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Results Table --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <span class="text-sm font-medium text-gray-500">
                        Menampilkan {{ $forms->firstItem() ?? 0 }} - {{ $forms->lastItem() ?? 0 }} dari {{ $forms->total() }} form
                    </span>
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-500">Urutan:</label>
                        <select onchange="window.location.href=this.value" class="border-gray-300 rounded text-sm">
                            <option value="{{ request()->fullUrlWithQuery(['dir' => 'asc']) }}" {{ request('dir', 'asc') == 'asc' ? 'selected' : '' }}>Ascending ↑</option>
                            <option value="{{ request()->fullUrlWithQuery(['dir' => 'desc']) }}" {{ request('dir') == 'desc' ? 'selected' : '' }}>Descending ↓</option>
                        </select>
                    </div>
                </div>

                @if($forms->isEmpty())
                    <div class="p-8 text-center text-gray-500">
                        Tidak ada form yang sesuai dengan filter
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50/80 border-b border-gray-200">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">No. Form</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Masuk</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Deadline</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Sisa Waktu</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Sampel</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
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
                                            'input_lhp' => 'bg-pink-100 text-pink-800',
                                            'ttd_divisi_lhp' => 'bg-amber-100 text-amber-800',
                                            'ttd_upa' => 'bg-orange-100 text-orange-800',
                                            'kirim_customer' => 'bg-teal-100 text-teal-800',
                                            'selesai' => 'bg-green-100 text-green-800',
                                            'ditolak' => 'bg-red-100 text-red-800',
                                        ];
                                    @endphp
                                    <tr class="hover:bg-indigo-50/30 transition-colors duration-150 group">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="font-semibold text-gray-900">{{ $form->form_number }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-700 font-medium">
                                            @if(auth()->user()->role_id == \App\Enums\Role::ANALIS)
                                                <span class="text-gray-400 italic">*** Dirahasiakan ***</span>
                                            @else
                                                {{ $form->customer_name }}
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ \Carbon\Carbon::parse($form->received_date)->format('d M Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm {{ $isOverdue && !$isCompleted ? 'text-red-600 font-bold' : 'text-gray-500' }}">
                                            {{ $deadline->format('d M Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($isCompleted)
                                                <span class="text-sm text-gray-300">-</span>
                                            @elseif($isOverdue)
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-red-100 text-red-800 border border-red-200">
                                                    {{ abs($daysLeft) }} hari terlambat
                                                </span>
                                            @elseif($daysLeft == 0)
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-red-50 text-red-700 border border-red-200">
                                                    Hari ini!
                                                </span>
                                            @elseif($isUrgent)
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                    {{ $daysLeft }} hari lagi
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    {{ $daysLeft }} hari lagi
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100 text-xs font-bold text-gray-600">{{ $form->samples->count() }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold shadow-sm {{ $statusColors[$form->status] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ $form->status_label }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('form-list.show', $form) }}" 
                                               class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-900 text-sm font-semibold transition-colors group-hover:underline">
                                                Lihat Detail
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="p-4 border-t border-gray-200">
                        {{ $forms->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
