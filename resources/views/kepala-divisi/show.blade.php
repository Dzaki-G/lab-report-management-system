<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Form {{ $form->no_terima_sampel ?? '-' }}
            </h2>
            <a href="{{ route('kepala-divisi.dashboard') }}" class="text-blue-600 hover:underline">← Kembali</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Form Info --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-4">Informasi Form</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div><span class="text-gray-500">No. Terima Sampel:</span> <strong>{{ $form->no_terima_sampel ?? '-' }}</strong></div>
                                <div><span class="text-gray-500">Customer:</span> <strong>{{ $form->customer_name }}</strong></div>
                                <div><span class="text-gray-500">Tanggal Masuk:</span> {{ \Carbon\Carbon::parse($form->received_date)->format('d M Y') }}</div>
                                <div>
                                    <span class="text-gray-500">Deadline:</span>
                                    @php
                                        $deadline = \Carbon\Carbon::parse($form->deadline_date);
                                        $today = \Carbon\Carbon::today();
                                        $daysLeft = $today->diffInDays($deadline, false);
                                        $isOverdue = $daysLeft < 0;
                                        $isUrgent = !$isOverdue && $daysLeft <= 3;
                                        $isCompleted = in_array($form->status, ['selesai', 'ditolak']);
                                    @endphp
                                    <strong>{{ $deadline->format('d M Y') }}</strong>
                                    @if($isCompleted)
                                        <span class="text-sm text-gray-400">-</span>
                                    @elseif($isOverdue)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 border border-red-200">{{ abs($daysLeft) }} hari terlambat</span>
                                    @elseif($daysLeft == 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-50 text-red-700 border border-red-200">Hari ini!</span>
                                    @elseif($isUrgent)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 border border-amber-200">{{ $daysLeft }} hari lagi</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">{{ $daysLeft }} hari lagi</span>
                                    @endif
                                </div>
                                <div><span class="text-gray-500">Status:</span> 
                                    <span class="px-2 py-1 rounded text-sm bg-blue-100 text-blue-700">{{ $form->status_label }}</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- SP3 Documents --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Dokumen SP3</h3>
                    <div class="space-y-3">
                        @forelse($form->sp3Documents as $sp3)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border">
                                <div>
                                    <p class="font-medium text-gray-800">{{ $sp3->sp3_number }}</p>
                                    <p class="text-sm text-gray-500">Parameter: {{ $sp3->parameter->name ?? '-' }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    @if($sp3->google_doc_id)
                                        <a href="https://docs.google.com/document/d/{{ $sp3->google_doc_id }}/edit" target="_blank"
                                           class="text-blue-600 hover:underline text-sm">📄 Buka SP3</a>
                                    @endif
                                    <span class="text-xs px-2 py-1 rounded
                                        {{ $sp3->review_status === 'approved' ? 'bg-green-100 text-green-700' :
                                           ($sp3->review_status === 'rejected' ? 'bg-red-100 text-red-700' :
                                           ($sp3->review_status === 'resubmitted' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600')) }}">
                                        {{ ucfirst($sp3->review_status) }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 italic text-sm">Belum ada dokumen SP3.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- LHP Document --}}
            @if($form->lhp_google_file_id && in_array($form->status, ['ttd_upa', 'selesai']))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 border-l-4 border-green-500">
                    <div class="p-6 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Dokumen LHP</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Dibuat {{ \Carbon\Carbon::parse($form->lhp_uploaded_at)->format('d M Y, H:i') }} WIB
                                @if($form->lhp_signed_upa_at)
                                    · Ditandatangani UPA {{ \Carbon\Carbon::parse($form->lhp_signed_upa_at)->format('d M Y, H:i') }} WIB
                                @endif
                            </p>
                        </div>
                        <a href="https://docs.google.com/document/d/{{ $form->lhp_google_file_id }}/edit"
                           target="_blank"
                           class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium">
                            📄 Buka LHP
                        </a>
                    </div>
                </div>
            @endif

            {{-- Log Verifikasi --}}
            @if($form->verifications->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
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

