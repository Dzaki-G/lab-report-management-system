     <x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Input Link LHP (Laporan Hasil Pengujian)
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="mb-4">
                        <p class="text-gray-600">
                            Berikut adalah daftar form yang telah selesai diverifikasi oleh Kepala Divisi dan siap untuk diinput link LHP.
                        </p>
                    </div>

                    @if($forms->isEmpty())
                        <div class="p-8 text-center text-gray-500 bg-gray-50 rounded-lg">
                            Tidak ada form yang menunggu input LHP saat ini.
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. Form</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parameter</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sisa Waktu</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($forms as $form)
                                        @php
                                            $deadline = \Carbon\Carbon::parse($form->deadline_date);
                                            $today = \Carbon\Carbon::today();
                                            $daysLeft = $today->diffInDays($deadline, false);
                                            $isOverdue = $daysLeft < 0;
                                            $isUrgent = !$isOverdue && $daysLeft <= 3;
                                        @endphp
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                                {{ $form->form_number }}
                                                <div class="text-xs text-gray-400 mt-1">{{ \Carbon\Carbon::parse($form->received_date)->format('d M Y') }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                                                {{ $form->customer_name }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                                                {{ $form->samples->sum(fn($s) => $s->sampleParameters->count()) }} Parameter
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($isOverdue)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                                                        {{ abs($daysLeft) }} hari terlambat
                                                    </span>
                                                @elseif($daysLeft == 0)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                                                        Hari ini!
                                                    </span>
                                                @elseif($isUrgent)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200">
                                                        {{ $daysLeft }} hari lagi
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                        {{ $daysLeft }} hari lagi
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('form.input-lhp.show', $form) }}" class="text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-md">
                                                    📄 Input Link LHP
                                                </a>
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
    </div>
</x-app-layout>
