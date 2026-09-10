<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $sampleParameter->analysisResult ? 'Edit' : 'Input' }} Hasil Pengujian
            </h2>
            <a href="{{ route('analis.dashboard') }}" class="text-blue-600 hover:underline">← Kembali</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">{{ session('error') }}</div>
            @endif

            {{-- Context card --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-6">
                <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wide mb-4">Informasi Sampel</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-400">No. Terima Sampel</p>
                        <p class="font-semibold text-gray-800">{{ $sampleParameter->sample->form->no_terima_sampel ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400">Parameter</p>
                        <p class="font-bold text-indigo-600 text-base">{{ $sampleParameter->parameter->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400">Kode Sampel</p>
                        <p class="font-semibold font-mono text-gray-800">{{ $sampleParameter->sample->sample_code }}</p>
                    </div>
                    <div>
                        <p class="text-gray-400">Nama Sampel</p>
                        <p class="font-semibold text-gray-800">{{ $sampleParameter->sample->sample_name }}</p>
                    </div>
                </div>
            </div>

            {{-- Input form --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                @if ($errors->any())
                    <div class="mb-5 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                        <ul class="list-disc pl-4 space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('analis.store', $sampleParameter) }}">
                    @csrf

                    {{-- Hasil + Satuan --}}
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">
                            Hasil Pengujian <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-3">
                            <input type="text" name="result_value"
                                   value="{{ old('result_value', $sampleParameter->analysisResult?->result_value) }}"
                                   class="flex-1 border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-lg font-medium transition-all"
                                   placeholder="Nilai hasil..."
                                   required autofocus>
                            <input type="text" name="result_unit"
                                   value="{{ old('result_unit', $sampleParameter->analysisResult?->result_unit ?? $sampleParameter->parameter->default_unit ?? '') }}"
                                   list="unit-list"
                                   class="w-36 border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
                                   placeholder="Satuan">
                            <datalist id="unit-list">
                                @foreach($units as $unit)
                                    <option value="{{ $unit->symbol ?? $unit->name }}">{{ $unit->name }}</option>
                                @endforeach
                            </datalist>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Pilih satuan dari daftar atau ketik langsung</p>
                    </div>

                    {{-- Metode --}}
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">
                            Metode <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="method"
                               value="{{ old('method', $defaultMethod) }}"
                               class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
                               placeholder="Metode pengujian..."
                               required>
                        <p class="text-xs text-gray-400 mt-1">Pre-filled dari parameter — ubah jika berbeda</p>
                    </div>

                    {{-- Merek/Tipe Alat --}}
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">
                            Merek/Tipe Alat <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="instrument"
                               value="{{ old('instrument', $defaultInstrument) }}"
                               class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
                               placeholder="Agilent ICP-OES 5900..."
                               required>
                        <p class="text-xs text-gray-400 mt-1">Pre-filled dari parameter — ubah jika berbeda</p>
                    </div>

                    {{-- Tanggal Analisis --}}
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">
                            Tanggal Analisis <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="analysis_date"
                               value="{{ old('analysis_date', $sampleParameter->analysisResult?->analysis_date?->format('Y-m-d') ?? date('Y-m-d')) }}"
                               class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all"
                               required>
                        <p class="text-xs text-gray-400 mt-1">Default hari ini — ubah sesuai tanggal analisis sebenarnya</p>
                    </div>

                    {{-- Catatan --}}
                    <div class="mb-6">
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Catatan</label>
                        <textarea name="notes" rows="2"
                                  class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all bg-gray-50/50"
                                  placeholder="Catatan tambahan (opsional)...">{{ old('notes', $sampleParameter->analysisResult?->notes) }}</textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <a href="{{ route('analis.dashboard') }}"
                           class="px-5 py-2.5 border border-gray-300 rounded-xl text-gray-700 text-sm font-semibold hover:bg-gray-50 transition-colors">
                            Batal
                        </a>
                        <button type="submit"
                                class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white rounded-xl text-sm font-bold shadow-md shadow-indigo-500/20 hover:-translate-y-0.5 transition-all">
                            Simpan Hasil
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
