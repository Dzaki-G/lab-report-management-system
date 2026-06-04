<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Buat Form Pengujian
            </h2>
            <a href="{{ route('form.index') }}" class="text-blue-600 hover:underline">← Kembali</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white/90 backdrop-blur-sm overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
                <div class="p-8">
                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                            <ul class="list-disc pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('form.store') }}" id="formPengujian">
                        @csrf

                        {{-- Form Info Section --}}
                        <div class="mb-8 p-6 bg-gray-50/50 border border-gray-100 rounded-2xl shadow-sm">
                            <h3 class="text-xl font-bold mb-6 text-gray-800 flex items-center gap-2">
                                <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Informasi Form Pengujian
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">ID Form *</label>
                                    <div class="flex shadow-sm rounded-lg overflow-hidden">
                                        <span class="inline-flex items-center px-4 text-sm text-gray-600 bg-gray-100 border border-r-0 border-gray-200 font-mono font-bold">
                                            SPU-
                                        </span>
                                        <input type="text" name="form_number" value="{{ old('form_number') }}" 
                                               placeholder="001"
                                               class="flex-1 border-gray-200 bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono transition-all" required>
                                    </div>
                                    <p class="text-xs text-gray-400 mt-2 font-medium">Cth: ketik "001" → "SPU-001"</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">No. SPU</label>
                                    <input type="text" name="no_spu" value="{{ old('no_spu') }}"
                                           placeholder="001/SPU/07/25"
                                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">No. Terima Sampel</label>
                                    <input type="text" name="no_terima_sampel" value="{{ old('no_terima_sampel') }}"
                                           placeholder="0725/001/12/M"
                                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Tanggal Masuk *</label>
                                    <input type="date" name="received_date" value="{{ old('received_date', date('Y-m-d')) }}"
                                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" required>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mt-6 border-t border-gray-200/60 pt-6">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Nama Customer</label>
                                    <input type="text" name="customer_name" value="{{ old('customer_name') }}"
                                           placeholder="Nama lengkap"
                                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">No. HP Customer</label>
                                    <input type="text" name="customer_phone" value="{{ old('customer_phone') }}"
                                           placeholder="08xxxxxxxxxx"
                                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Asal Instansi</label>
                                    <input type="text" name="customer_institution" value="{{ old('customer_institution') }}"
                                           placeholder="Nama perusahaan/kampus"
                                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Jabatan</label>
                                    <input type="text" name="customer_position" value="{{ old('customer_position') }}"
                                           placeholder="Mahasiswa/Peneliti/Staff"
                                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                </div>
                            </div>

                            <div class="mt-4 flex items-start gap-2 text-sm text-indigo-600 bg-indigo-50/50 p-3 rounded-lg border border-indigo-100 w-fit">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span><strong class="font-semibold">Info Deadline:</strong> Otomatis diatur +12 hari dari tanggal masuk.</span>
                            </div>
                        </div>

                        {{-- Samples Section --}}
                        <div class="mb-10">
                            <div class="mb-6 flex items-center justify-between">
                                <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                                    <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                    Daftar Sampel Pengujian
                                </h3>
                            </div>

                            <div id="samplesContainer" class="space-y-6">
                                {{-- Sample template will be cloned here --}}
                            </div>

                            {{-- Add Sample Button - Centered at Bottom --}}
                            <div class="flex justify-center mt-8">
                                <button type="button" id="addSample" 
                                        class="group flex items-center gap-2 border-2 border-dashed border-indigo-300 hover:border-indigo-500 bg-indigo-50/50 hover:bg-indigo-50 text-indigo-600 px-8 py-3.5 rounded-xl font-semibold transition-all hover:shadow-md">
                                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Tambah Sampel Uji
                                </button>
                            </div>
                        </div>

                        <div class="flex justify-end pt-6 border-t border-gray-200">
                            <button type="submit" 
                                    class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 px-8 py-3.5 rounded-xl font-bold font-lg hover:-translate-y-0.5 transition-all flex items-center gap-2 w-full sm:w-auto justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                                Simpan Form Pengujian
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Sample Template (hidden) --}}
    <template id="sampleTemplate">
        <div class="sample-item p-6 border border-gray-200 rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group" data-index="__INDEX__">
            <!-- Decorative accent line -->
            <div class="absolute top-0 left-0 w-1.5 h-full bg-indigo-500 opacity-80"></div>
            
            <div class="flex justify-between items-start mb-5 pl-2">
                <h4 class="font-bold text-lg text-gray-800 flex items-center gap-2">
                    <span class="sample-number bg-indigo-100 text-indigo-700 w-8 h-8 rounded-full flex items-center justify-center text-sm">__NUMBER__</span> 
                    Detail Sampel
                </h4>
                <button type="button" class="remove-sample text-red-500 hover:text-red-700 hover:bg-red-50 px-3 py-1.5 rounded-lg text-sm font-semibold transition-colors flex items-center gap-1">
                    <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    Hapus
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5 pl-2">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Kode Sampel *</label>
                    <input type="text" name="samples[__INDEX__][sample_code]" 
                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-mono" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Nama Sampel *</label>
                    <input type="text" name="samples[__INDEX__][sample_name]" 
                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" required>
                </div>
            </div>

            <div class="mb-5 pl-2">
                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">
                    Catatan Khusus <span class="normal-case font-normal text-gray-400 ml-1">(Opsional)</span>
                </label>
                <textarea name="samples[__INDEX__][notes]" rows="2"
                          class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all bg-gray-50/50" 
                          placeholder="Kondisi sampel, peringatan, dll..."></textarea>
            </div>

            <div class="pl-2 pt-4 border-t border-gray-100">
                <label class="block text-xs font-bold text-gray-800 uppercase tracking-wide mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                    Parameter Uji (Pilih Minimal 1) *
                </label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach($parameters as $parameter)
                        <label class="relative flex items-center p-3 cursor-pointer rounded-xl border border-gray-200 hover:border-indigo-400 bg-white hover:bg-indigo-50/50 transition-all group overflow-hidden">
                            <input type="checkbox" name="samples[__INDEX__][parameters][]" 
                                   value="{{ $parameter->id }}"
                                   class="peer rounded-md border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 absolute opacity-0 z-[-1]">
                            
                            <!-- Custom styling for the selected state via sibling selector in Tailwind isn't fully supported without plugins, 
                                 so we use standard checkboxes but make them look nice -->
                            <div class="w-5 h-5 rounded border border-gray-300 mr-3 flex items-center justify-center flex-shrink-0 peer-checked:bg-indigo-600 peer-checked:border-indigo-600 transition-colors">
                                <svg class="w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-gray-700 peer-checked:text-indigo-900">{{ $parameter->name }}</span>
                            
                            <!-- Highlight background on checked -->
                            <div class="absolute inset-0 bg-indigo-50/50 opacity-0 peer-checked:opacity-100 peer-checked:border-indigo-500 border rounded-xl pointer-events-none transition-all z-0"></div>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('samplesContainer');
            const template = document.getElementById('sampleTemplate');
            const addButton = document.getElementById('addSample');
            const form = document.getElementById('formPengujian');
            let sampleIndex = 0;

            function addSample() {
                const clone = template.content.cloneNode(true);
                const html = clone.querySelector('.sample-item').outerHTML
                    .replace(/__INDEX__/g, sampleIndex)
                    .replace(/__NUMBER__/g, sampleIndex + 1);
                
                container.insertAdjacentHTML('beforeend', html);
                sampleIndex++;
                updateSampleNumbers();
            }

            function updateSampleNumbers() {
                const samples = container.querySelectorAll('.sample-item');
                samples.forEach((sample, index) => {
                    const numberEl = sample.querySelector('.sample-number');
                    if (numberEl) {
                        numberEl.textContent = index + 1;
                    }
                });
            }

            addButton.addEventListener('click', addSample);

            container.addEventListener('click', function(e) {
                const removeBtn = e.target.closest('.remove-sample');
                if (removeBtn) {
                    const samples = container.querySelectorAll('.sample-item');
                    if (samples.length > 1) {
                        removeBtn.closest('.sample-item').remove();
                        updateSampleNumbers();
                    } else {
                        alert('Minimal harus ada 1 sampel');
                    }
                }
            });

            // Prevent double form submission due to slow Google Docs API generation
            form.addEventListener('submit', function() {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
                    submitBtn.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Menyimpan...
                    `;
                }
            });

            // Add first sample on load
            addSample();
        });
    </script>
</x-app-layout>
