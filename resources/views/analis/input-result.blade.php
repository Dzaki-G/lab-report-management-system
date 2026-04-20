<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Input Hasil Pengujian
            </h2>
            <a href="{{ route('analis.dashboard') }}" class="text-blue-600 hover:underline">← Kembali ke Dashboard</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            {{-- Sample Info Card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Informasi Sampel</h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">No. Form</p>
                            <p class="font-medium">{{ $sampleParameter->sample->form->form_number }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Customer</p>
                            <p class="font-medium">{{ $sampleParameter->sample->form->customer_name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Kode Sampel</p>
                            <p class="font-medium">{{ $sampleParameter->sample->sample_code }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Nama Sampel</p>
                            <p class="font-medium">{{ $sampleParameter->sample->sample_name }}</p>
                        </div>
                    </div>

                    <div class="mt-4 pt-4 border-t">
                        <div class="flex items-center space-x-4">
                            <div>
                                <p class="text-sm text-gray-500">Parameter</p>
                                <p class="text-lg font-bold text-blue-600">{{ $sampleParameter->parameter->name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Metode</p>
                                <p class="font-medium">{{ $sampleParameter->method ?? $sampleParameter->parameter->default_method ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Satuan</p>
                                <p class="font-medium">{{ $sampleParameter->parameter->default_unit ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Result Input Form --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Input Hasil Pengujian</h3>

                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('analis.store', $sampleParameter) }}">
                        @csrf

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Hasil Pengujian *
                            </label>
                            <div class="flex space-x-2">
                                <input type="text" name="result_value" 
                                       value="{{ old('result_value', $sampleParameter->analysisResult?->result_value) }}" 
                                       class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-lg"
                                       placeholder="Masukkan nilai hasil..."
                                       required autofocus>
                                <select name="result_unit" 
                                        class="w-40 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Pilih Satuan</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->symbol ?? $unit->name }}" 
                                            {{ old('result_unit', $sampleParameter->analysisResult?->result_unit) == ($unit->symbol ?? $unit->name) ? 'selected' : '' }}>
                                            {{ $unit->symbol ?? $unit->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Pilih satuan dari dropdown di samping</p>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                            <textarea name="notes" rows="3"
                                      class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Catatan tambahan (opsional)...">{{ old('notes', $sampleParameter->analysisResult?->notes) }}</textarea>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('analis.dashboard') }}" 
                               class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Batal
                            </a>
                            <button type="submit" 
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md font-medium">
                                💾 Simpan Hasil
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
