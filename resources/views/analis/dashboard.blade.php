<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard Analis
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Stats Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm text-blue-600 font-medium">SP3 Ditugaskan</p>
                            <p class="text-3xl font-bold text-blue-700">{{ isset($mySp3s) ? $mySp3s->count() : 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm text-red-600 font-medium">Perlu Revisi</p>
                            <p class="text-3xl font-bold text-red-700">{{ isset($mySp3s) ? $mySp3s->where('needs_revision', true)->count() : 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm text-yellow-600 font-medium">LCP Belum Upload</p>
                            <p class="text-3xl font-bold text-yellow-700">{{ isset($mySp3s) ? $mySp3s->whereNull('lcp_google_file_url')->count() : 0 }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <p class="text-sm text-green-600 font-medium">LCP Uploaded</p>
                            <p class="text-3xl font-bold text-green-700">{{ isset($mySp3s) ? $mySp3s->whereNotNull('lcp_google_file_url')->count() : 0 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SP3 Documents --}}
            @if(isset($mySp3s) && $mySp3s->isNotEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-blue-700 mb-2">📋 Tugas Saya (Dokumen SP3)</h3>
                        <p class="text-sm text-gray-500 mb-4">
                            Berikut adalah daftar SP3 yang ditugaskan kepada Anda. Kerjakan pengujian sesuai instruksi kerja, lalu upload LCP.
                        </p>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. SP3</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. SPPP</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parameter</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Instruksi Kerja</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status LCP</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($mySp3s as $sp3)
                                        <tr class="{{ $sp3->needs_revision ? 'bg-red-50' : ($sp3->lcp_google_file_url ? 'bg-green-50' : '') }}">
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-gray-900">{{ $sp3->sp3_number }}</div>
                                                <div class="text-xs text-gray-500">Form: {{ $sp3->form->form_number }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-600">
                                                {{ $sp3->no_sppp ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-600">
                                                {{ $sp3->parameter->name ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-600">
                                                {{ $sp3->ik ?? '-' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                @if($sp3->needs_revision)
                                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-red-100 text-red-700 rounded font-medium">
                                                        Perlu Revisi
                                                    </span>
                                                @elseif($sp3->lcp_google_file_url)
                                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-green-100 text-green-700 rounded">
                                                        Uploaded
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-yellow-100 text-yellow-700 rounded">
                                                        Belum Upload
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex items-center justify-center gap-2">
                                                    {{-- Link to SP3 Document --}}
                                                    @if($sp3->google_doc_id)
                                                        <a href="https://docs.google.com/document/d/{{ $sp3->google_doc_id }}" 
                                                           target="_blank"
                                                           class="inline-flex items-center px-3 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs rounded transition"
                                                           title="Lihat Dokumen SP3">
                                                            📄 Lihat SP3
                                                        </a>
                                                    @endif
                                                    
                                                    {{-- Upload/View LCP --}}
                                                    <a href="{{ route('analis.sp3.show', $sp3) }}" 
                                                       class="{{ $sp3->needs_revision ? 'bg-red-600 hover:bg-red-700' : ($sp3->lcp_google_file_url ? 'bg-green-600 hover:bg-green-700' : 'bg-purple-600 hover:bg-purple-700') }} text-white px-3 py-1 rounded text-xs transition">
                                                        {{ $sp3->needs_revision ? 'Revisi LCP' : ($sp3->lcp_google_file_url ? 'Lihat LCP' : 'Upload LCP') }}
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Empty State --}}
            @if(!isset($mySp3s) || $mySp3s->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <div class="text-6xl mb-4">📋</div>
                        <p class="text-gray-500 text-lg">Belum ada SP3 yang ditugaskan</p>
                        <p class="text-gray-400 text-sm mt-2">
                            SP3 akan muncul setelah Kepala Divisi menugaskan parameter kepada Anda
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
