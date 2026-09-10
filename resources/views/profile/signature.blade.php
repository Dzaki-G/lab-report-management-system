<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tanda Tangan Digital
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">

                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Tanda Tangan Anda</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Upload gambar tanda tangan (PNG transparan disarankan). Digunakan untuk dokumen LHP.
                    </p>
                </div>

                @if($hasSignature)
                    <div class="border rounded-lg p-4 bg-gray-50 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <img src="https://drive.google.com/uc?export=view&id={{ auth()->user()->signature_drive_file_id }}"
                                 alt="Tanda tangan"
                                 class="h-16 object-contain border bg-white rounded p-1">
                            <span class="text-sm text-green-700 font-medium">Tanda tangan tersimpan</span>
                        </div>
                        <form method="POST" action="{{ route('signature.destroy') }}"
                              onsubmit="return confirm('Hapus tanda tangan?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="text-sm text-red-600 hover:underline">
                                Hapus
                            </button>
                        </form>
                    </div>
                @else
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center text-gray-400 text-sm">
                        Belum ada tanda tangan tersimpan.
                    </div>
                @endif

                <form method="POST" action="{{ route('signature.upload') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $hasSignature ? 'Ganti Tanda Tangan' : 'Upload Tanda Tangan' }}
                        </label>
                        <input type="file" name="signature" accept="image/png,image/jpeg"
                               class="block w-full text-sm text-gray-600 border border-gray-300 rounded-lg cursor-pointer
                                      file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0
                                      file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700
                                      hover:file:bg-blue-100">
                        @error('signature')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-400 mt-1">PNG dengan background transparan disarankan. Maks 2MB.</p>
                    </div>
                    <button type="submit"
                            class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                        Simpan Tanda Tangan
                    </button>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
