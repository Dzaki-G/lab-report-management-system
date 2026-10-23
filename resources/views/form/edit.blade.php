<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Form Pengujian: {{ $form->no_terima_sampel ?? $form->lhp_number ?? $form->form_number }}
            </h2>
            <a href="{{ route('form.show', $form) }}" class="text-blue-600 hover:underline">← Kembali</a>
        </div>
    </x-slot>

    @php
        $inputClass = 'w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all';
        $labelClass = 'block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2';
        $receivedDate = $form->received_date ? \Carbon\Carbon::parse($form->received_date)->format('Y-m-d') : '';
    @endphp

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

                    <div class="mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-sm">
                        Mengubah <span class="font-semibold">kode</span> atau <span class="font-semibold">nama sampel</span> akan menggenerate ulang dokumen SP3 yang memuat sampel tersebut. Parameter uji tidak dapat diubah.
                    </div>

                    <form method="POST" action="{{ route('form.update', $form) }}" id="formPengujianEdit">
                        @csrf
                        @method('PUT')

                        {{-- Section 1: Penerimaan --}}
                        <div class="mb-8 p-6 bg-gray-50/50 border border-gray-100 rounded-2xl shadow-sm">
                            <h3 class="text-xl font-bold mb-6 text-gray-800">Penerimaan</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                                <div>
                                    <label class="{{ $labelClass }}">No. LHP</label>
                                    <input type="text" name="lhp_number" value="{{ old('lhp_number', $form->lhp_number) }}"
                                           class="{{ $inputClass }} font-mono">
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}">No. Terima Sampel</label>
                                    <input type="text" name="no_terima_sampel" value="{{ old('no_terima_sampel', $form->no_terima_sampel) }}"
                                           placeholder="0725/001/12/M" class="{{ $inputClass }}">
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}">Tanggal Terima *</label>
                                    <input type="date" name="received_date" value="{{ old('received_date', $receivedDate) }}"
                                           class="{{ $inputClass }}" required>
                                    <p class="text-xs text-gray-400 mt-1">Deadline dihitung ulang +12 hari kerja</p>
                                </div>
                            </div>
                        </div>

                        {{-- Section 2: Pelanggan --}}
                        <div class="mb-8 p-6 bg-gray-50/50 border border-gray-100 rounded-2xl shadow-sm">
                            <h3 class="text-xl font-bold mb-6 text-gray-800">Pelanggan</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                @foreach ([
                                    'customer_name' => 'Nama',
                                    'customer_phone' => 'Telepon',
                                    'contact_person' => 'Personil Penghubung',
                                    'customer_institution' => 'Asal Instansi',
                                    'customer_position' => 'Jabatan',
                                    'customer_address' => 'Alamat',
                                ] as $field => $label)
                                    <div>
                                        <label class="{{ $labelClass }}">{{ $label }}</label>
                                        <input type="text" name="{{ $field }}" value="{{ old($field, $form->$field) }}"
                                               class="{{ $inputClass }}">
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Section 3: Deskripsi Sampel --}}
                        <div class="mb-8 p-6 bg-gray-50/50 border border-gray-100 rounded-2xl shadow-sm">
                            <h3 class="text-xl font-bold mb-6 text-gray-800">Deskripsi Sampel</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                @foreach ([
                                    'sample_type' => 'Sampel Uji',
                                    'sample_matrix' => 'Matriks Uji',
                                    'sample_name_label' => 'Nama Sampel',
                                    'sample_form' => 'Bentuk',
                                ] as $field => $label)
                                    <div>
                                        <label class="{{ $labelClass }}">{{ $label }}</label>
                                        <input type="text" name="{{ $field }}" value="{{ old($field, $form->$field) }}"
                                               class="{{ $inputClass }}">
                                    </div>
                                @endforeach
                                <div>
                                    <label class="{{ $labelClass }}">Jumlah</label>
                                    <input type="number" name="sample_count" min="1" value="{{ old('sample_count', $form->sample_count) }}"
                                           class="{{ $inputClass }}">
                                </div>
                                <div>
                                    <label class="{{ $labelClass }}">Kemasan</label>
                                    <input type="text" name="sample_packing" value="{{ old('sample_packing', $form->sample_packing) }}"
                                           class="{{ $inputClass }}">
                                </div>
                            </div>
                        </div>

                        {{-- Section 4: Daftar Sampel --}}
                        <div class="mb-10">
                            <h3 class="text-xl font-bold mb-6 text-gray-800">Daftar Sampel Pengujian ({{ $form->samples->count() }})</h3>
                            <div class="space-y-6">
                                @foreach ($form->samples as $i => $sample)
                                    <div class="p-6 border border-gray-200 rounded-2xl bg-white shadow-sm relative overflow-hidden">
                                        <div class="absolute top-0 left-0 w-1.5 h-full bg-indigo-500 opacity-80"></div>
                                        <input type="hidden" name="samples[{{ $i }}][id]" value="{{ $sample->id }}">

                                        <h4 class="font-bold text-lg text-gray-800 mb-5 pl-2 flex items-center gap-2">
                                            <span class="bg-indigo-100 text-indigo-700 w-8 h-8 rounded-full flex items-center justify-center text-sm">{{ $i + 1 }}</span>
                                            Detail Sampel
                                        </h4>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5 pl-2">
                                            <div>
                                                <label class="{{ $labelClass }}">Kode Sampel *</label>
                                                <input type="text" name="samples[{{ $i }}][sample_code]"
                                                       value="{{ old("samples.$i.sample_code", $sample->sample_code) }}"
                                                       class="{{ $inputClass }} font-mono" required>
                                            </div>
                                            <div>
                                                <label class="{{ $labelClass }}">Nama Sampel *</label>
                                                <input type="text" name="samples[{{ $i }}][sample_name]"
                                                       value="{{ old("samples.$i.sample_name", $sample->sample_name) }}"
                                                       class="{{ $inputClass }}" required>
                                            </div>
                                        </div>

                                        <div class="mb-5 pl-2">
                                            <label class="{{ $labelClass }}">Catatan Khusus <span class="normal-case font-normal text-gray-400 ml-1">(Opsional)</span></label>
                                            <textarea name="samples[{{ $i }}][notes]" rows="2"
                                                      class="{{ $inputClass }} bg-gray-50/50">{{ old("samples.$i.notes", $sample->notes) }}</textarea>
                                        </div>

                                        <div class="pl-2 pt-4 border-t border-gray-100">
                                            <p class="{{ $labelClass }}">Parameter Uji <span class="normal-case font-normal text-gray-400 ml-1">(tidak dapat diubah)</span></p>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($sample->sampleParameters as $sp)
                                                    <span class="px-3 py-1 rounded-full bg-gray-100 text-gray-700 text-sm">{{ $sp->parameter?->name ?? '-' }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                            <a href="{{ route('form.show', $form) }}"
                               class="px-6 py-3.5 rounded-xl font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition-all">
                                Batal
                            </a>
                            <button type="submit"
                                    class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-lg shadow-indigo-500/30 px-8 py-3.5 rounded-xl font-bold transition-all">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('formPengujianEdit').addEventListener('submit', function () {
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-not-allowed');
            btn.textContent = 'Menyimpan...';
        });
    </script>
</x-app-layout>
