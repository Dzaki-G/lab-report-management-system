<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            LHP Siap Kirim ke Customer
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- Page Title --}}
        <div>
            <h2 class="text-xl font-bold text-gray-800">LHP Siap Kirim ke Customer</h2>
            <p class="text-sm text-gray-500 mt-1">Daftar LHP yang telah ditandatangani dan siap dikirimkan ke customer.</p>
        </div>

        {{-- Menunggu Dikirim --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center gap-3">
                <h3 class="text-base font-semibold text-gray-800">Menunggu Dikirim</h3>
                @if($ready->isNotEmpty())
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                        {{ $ready->count() }}
                    </span>
                @endif
            </div>

            @if($ready->isEmpty())
                <div class="px-6 py-12 text-center text-gray-400 italic text-sm">
                    Tidak ada LHP yang menunggu dikirim.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Terima / LHP</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">TTD UPA</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($ready as $form)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="font-medium text-gray-900">{{ $form->no_terima_sampel ?? '-' }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $form->lhp_number ?? 'No LHP belum diset' }}</p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="font-medium text-gray-900">{{ $form->customer_name ?? '-' }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $form->customer_phone ?? '' }}</p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600">
                                        {{ \Carbon\Carbon::parse($form->deadline_date)->format('d M Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600 text-xs">
                                        {{ $form->lhp_signed_upa_at ? \Carbon\Carbon::parse($form->lhp_signed_upa_at)->format('d M Y H:i') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            @if($form->lhp_google_file_id)
                                                <a href="https://docs.google.com/document/d/{{ $form->lhp_google_file_id }}/export?format=pdf"
                                                   target="_blank"
                                                   class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-lg font-medium transition-colors">
                                                    Unduh LHP
                                                </a>
                                                <a href="https://docs.google.com/document/d/{{ $form->lhp_google_file_id }}/edit"
                                                   target="_blank"
                                                   class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs rounded-lg font-medium transition-colors">
                                                    Buka
                                                </a>
                                            @else
                                                <span class="text-xs text-gray-400 italic">LHP belum tersedia</span>
                                            @endif
                                            <form method="POST" action="{{ route('admin.mark-sent', $form) }}"
                                                  onsubmit="return confirm('Tandai LHP untuk {{ addslashes($form->customer_name ?? $form->no_terima_sampel) }} sudah dikirim ke customer?')">
                                                @csrf
                                                <button type="submit"
                                                        class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs rounded-lg font-medium transition-colors">
                                                    Tandai Selesai
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Baru Diselesaikan --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-800">Baru Diselesaikan</h3>
                <p class="text-xs text-gray-400 mt-0.5">20 form terakhir yang sudah dikirim ke customer</p>
            </div>

            @if($recentDone->isEmpty())
                <div class="px-6 py-12 text-center text-gray-400 italic text-sm">Belum ada form yang selesai.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Terima / LHP</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selesai</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">LHP</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($recentDone as $form)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="font-medium text-gray-900">{{ $form->no_terima_sampel ?? '-' }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $form->lhp_number ?? '-' }}</p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <p class="text-gray-900">{{ $form->customer_name ?? '-' }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $form->customer_phone ?? '' }}</p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600 text-xs">
                                        {{ \Carbon\Carbon::parse($form->updated_at)->format('d M Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($form->lhp_google_file_id)
                                            <a href="https://docs.google.com/document/d/{{ $form->lhp_google_file_id }}/export?format=pdf"
                                               target="_blank"
                                               class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs rounded-lg font-medium transition-colors">
                                                Unduh LHP
                                            </a>
                                        @else
                                            <span class="text-gray-400 text-xs">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        </div>
    </div>
</x-app-layout>
