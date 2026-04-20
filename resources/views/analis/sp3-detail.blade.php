<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detail SP3: {{ $sp3->sp3_number }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Back Link -->
            <div class="mb-4">
                <a href="{{ route('analis.dashboard') }}" class="text-blue-600 hover:underline text-sm">
                    ← Kembali ke Dashboard
                </a>
            </div>

            <!-- Flash Messages -->
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <!-- SP3 Info Card -->
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">Informasi SP3</h3>
                
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">No. SP3:</span>
                        <span class="font-medium ml-2">{{ $sp3->sp3_number }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">No. SPPP:</span>
                        <span class="font-medium ml-2">{{ $sp3->no_sppp ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Parameter:</span>
                        <span class="font-medium ml-2">{{ $sp3->parameter->name ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Instruksi Kerja:</span>
                        <span class="font-medium ml-2">{{ $sp3->ik ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Form:</span>
                        <span class="font-medium ml-2">{{ $sp3->form->form_number ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Status:</span>
                        @if($sp3->needs_revision)
                            <span class="px-2 py-1 rounded text-xs bg-red-100 text-red-800">
                                Perlu Revisi
                            </span>
                        @elseif($sp3->lcp_google_file_url)
                            <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-800">
                                LCP Sudah Diinput
                            </span>
                        @else
                            <span class="px-2 py-1 rounded text-xs bg-yellow-100 text-yellow-800">
                                Belum Ada LCP
                            </span>
                        @endif
                    </div>
                </div>

                <!-- SP3 Document Link -->
                @if($sp3->google_doc_id)
                    <div class="mt-4 pt-4 border-t">
                        <a href="https://docs.google.com/document/d/{{ $sp3->google_doc_id }}" 
                           target="_blank"
                           class="inline-flex items-center text-blue-600 hover:underline">
                            Lihat Dokumen SP3 di Google Docs
                        </a>
                    </div>
                @endif
            </div>

            <!-- Revision Warning Banner -->
            @if($sp3->needs_revision)
                <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <div>
                            <h4 class="text-red-800 font-semibold">Revisi Diperlukan</h4>
                            <p class="text-red-700 text-sm mt-1">
                                Kepala Divisi menolak LCP ini. Silakan periksa dan perbaiki, kemudian submit ulang link LCP.
                            </p>
                            @if($sp3->revision_note)
                                <div class="mt-2 p-3 bg-red-100 rounded text-sm text-red-800">
                                    <strong>Catatan:</strong> {{ $sp3->revision_note }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- LCP Input Section -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">
                    📋 Input Link LCP (Lembar Catatan Pengujian)
                </h3>
                
                @if($sp3->lcp_google_file_url)
                    <!-- LCP Already Exists -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-800 font-medium">✓ LCP sudah diinput</p>
                                <p class="text-sm text-green-600 mt-1 break-all">
                                    {{ Str::limit($sp3->lcp_google_file_url, 60) }}
                                </p>
                            </div>
                            <a href="{{ $sp3->lcp_google_file_url }}" 
                               target="_blank"
                               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                                📄 Buka LCP
                            </a>
                        </div>
                    </div>
                @endif

                <!-- Link Input Form -->
                <form action="{{ route('analis.sp3.submit-lcp', $sp3) }}" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <!-- Button to Google Drive LCP Folder -->
                        <div class="text-center mb-4">
                            <a href="https://drive.google.com/drive/folders/1AZoJZl2U0DwCW7kIQYt-fTITHkuKcA08" 
                               target="_blank"
                               class="inline-flex items-center px-6 py-3 bg-yellow-500 hover:bg-yellow-600 text-white font-medium rounded-lg transition">
                                📁 Buka Folder LCP di Google Drive
                            </a>
                        </div>
                        
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ $sp3->lcp_google_file_url ? 'Update Link LCP (Opsional)' : 'Link Google Docs/Drive LCP *' }}
                        </label>
                        <input type="url" 
                               name="lcp_link" 
                               value="{{ old('lcp_link', $sp3->lcp_google_file_url) }}"
                               placeholder="https://docs.google.com/... atau https://drive.google.com/..."
                               class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               {{ $sp3->lcp_google_file_url ? '' : 'required' }}>
                        @error('lcp_link')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="bg-blue-50 border-l-4 border-blue-400 p-3 mb-4 text-sm">
                        <p class="text-blue-700">
                            💡 <strong>Langkah:</strong> Buat dokumen LCP di Google Docs/Sheets, 
                            lalu copy-paste link-nya di atas.
                        </p>
                    </div>

                    <button type="submit" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition">
                        💾 Simpan Link LCP
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
