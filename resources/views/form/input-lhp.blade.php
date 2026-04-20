<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Input LHP: {{ $form->form_number }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <!-- Back Link -->
            <div class="mb-4">
                <a href="{{ route('form.input-lhp') }}" class="text-blue-600 hover:underline text-sm">
                    ← Kembali ke Daftar
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

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Left Column: LCP References -->
                <div class="space-y-6">
                    <!-- Form Info -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">Informasi Form</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">No. Form:</span>
                                <span class="font-medium">{{ $form->form_number }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">No. SPU:</span>
                                <span class="font-medium">{{ $form->no_spu ?? $form->form_number }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Tanggal Terima:</span>
                                <span class="font-medium">{{ $form->received_date ? \Carbon\Carbon::parse($form->received_date)->format('d M Y') : '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Jumlah Sampel:</span>
                                <span class="font-medium">{{ $form->samples->count() }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- LCP Documents (from SP3) -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">📋 Dokumen LCP (Referensi)</h3>
                        
                        <div class="space-y-3">
                            @forelse($form->sp3Documents as $sp3)
                                <div class="border rounded-lg p-3 {{ $sp3->lcp_google_file_url ? 'bg-green-50 border-green-200' : 'bg-gray-50' }}">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="font-medium text-sm">{{ $sp3->sp3_number }}</p>
                                            <p class="text-xs text-gray-500">{{ $sp3->parameter->name ?? '-' }}</p>
                                            <p class="text-xs text-gray-500">Analis: {{ $sp3->assignedAnalyst->full_name ?? '-' }}</p>
                                        </div>
                                        <div class="text-right">
                                            @if($sp3->lcp_google_file_url)
                                                <a href="{{ $sp3->lcp_google_file_url }}" 
                                                   target="_blank"
                                                   class="inline-block bg-green-600 hover:bg-green-700 text-white text-xs px-3 py-1 rounded">
                                                    📄 Lihat LCP
                                                </a>
                                            @else
                                                <span class="text-xs text-orange-500">⏳ Belum ada LCP</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500 text-sm text-center py-4">Tidak ada dokumen SP3</p>
                            @endforelse
                        </div>

                        <div class="mt-4 pt-4 border-t">
                            <p class="text-xs text-gray-500">
                                💡 Gunakan LCP di atas sebagai referensi untuk membuat LHP.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right Column: LHP Input -->
                <div>
                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">
                            📑 Input Link LHP (Laporan Hasil Pengujian)
                        </h3>

                        @if($form->lhp_google_file_url)
                            <!-- LHP Already Exists -->
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-green-800 font-medium">✓ LHP sudah diinput</p>
                                        <p class="text-sm text-green-600 mt-1 break-all">
                                            {{ Str::limit($form->lhp_google_file_url, 50) }}
                                        </p>
                                    </div>
                                    <a href="{{ $form->lhp_google_file_url }}" 
                                       target="_blank"
                                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                                        📄 Buka LHP
                                    </a>
                                </div>
                            </div>
                        @endif

                        <!-- Instructions -->
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                            <h4 class="text-blue-800 font-medium text-sm mb-2">Langkah-langkah:</h4>
                            <ol class="text-blue-700 text-sm list-decimal list-inside space-y-1">
                                <li>Lihat semua LCP dari analis (di kiri)</li>
                                <li>Buat dokumen LHP (di luar sistem)</li>
                                <li>Copy-paste link LHP di bawah</li>
                                <li>Submit untuk verifikasi Kepala UPA</li>
                            </ol>
                        </div>

                        <!-- Link Input Form -->
                        <form action="{{ route('form.submit-lhp-link', $form) }}" method="POST">
                            @csrf
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ $form->lhp_google_file_url ? 'Update Link LHP (Opsional)' : 'Link Google Docs/Drive LHP *' }}
                                </label>
                                <input type="url" 
                                       name="lhp_link" 
                                       value="{{ old('lhp_link', $form->lhp_google_file_url) }}"
                                       placeholder="https://docs.google.com/... atau https://drive.google.com/..."
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       {{ $form->lhp_google_file_url ? '' : 'required' }}>
                                @error('lhp_link')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" 
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition">
                                💾 Simpan Link LHP
                            </button>
                        </form>

                        @if($form->lhp_google_file_url)
                            <!-- Submit LHP Button -->
                            <div class="mt-6 pt-6 border-t">
                                <form action="{{ route('form.submit-lhp', $form) }}" method="POST">
                                    @csrf
                                    <button type="submit" 
                                            class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition">
                                        ✅ Submit LHP untuk Verifikasi Kepala UPA
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
