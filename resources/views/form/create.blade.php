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

                        {{-- Section 1: Penerimaan --}}
                        <div class="mb-8 p-6 bg-gray-50/50 border border-gray-100 rounded-2xl shadow-sm">
                            <h3 class="text-xl font-bold mb-6 text-gray-800 flex items-center gap-2">
                                <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Penerimaan
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">
                                        No. LHP
                                        <span class="normal-case font-normal text-gray-400 ml-1">(auto)</span>
                                    </label>
                                    <input type="text" name="lhp_number" value="{{ old('lhp_number', $nextLhpNumber) }}"
                                           placeholder="{{ $nextLhpNumber }}"
                                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 font-mono transition-all">
                                    <p class="text-xs text-gray-400 mt-1">Otomatis — ubah jika perlu</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">No. Terima Sampel</label>
                                    <input type="text" name="no_terima_sampel" value="{{ old('no_terima_sampel') }}"
                                           placeholder="0725/001/12/M"
                                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Tanggal Terima *</label>
                                    <input type="date" name="received_date" value="{{ old('received_date', date('Y-m-d')) }}"
                                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all" required>
                                    <p class="text-xs text-gray-400 mt-1">Deadline otomatis +12 hari kerja</p>
                                </div>
                            </div>
                        </div>

                        {{-- Section 2: Pelanggan --}}
                        <div class="mb-8 p-6 bg-gray-50/50 border border-gray-100 rounded-2xl shadow-sm">
                            <h3 class="text-xl font-bold mb-6 text-gray-800 flex items-center gap-2">
                                <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Pelanggan
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Nama</label>
                                    <input type="text" name="customer_name" value="{{ old('customer_name') }}"
                                           placeholder="PT / Nama lengkap"
                                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Telepon</label>
                                    <input type="text" name="customer_phone" value="{{ old('customer_phone') }}"
                                           placeholder="08xxxxxxxxxx"
                                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Personil Penghubung</label>
                                    <input type="text" name="contact_person" value="{{ old('contact_person') }}"
                                           placeholder="Nama penghubung (opsional)"
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
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Alamat</label>
                                    <input type="text" name="customer_address" value="{{ old('customer_address') }}"
                                           placeholder="Alamat pelanggan"
                                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                </div>
                            </div>
                        </div>

                        {{-- Section 3: Deskripsi Sampel --}}
                        <div class="mb-8 p-6 bg-gray-50/50 border border-gray-100 rounded-2xl shadow-sm">
                            <h3 class="text-xl font-bold mb-6 text-gray-800 flex items-center gap-2">
                                <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                Deskripsi Sampel
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Sampel Uji</label>
                                    <input type="text" name="sample_type" value="{{ old('sample_type') }}"
                                           placeholder="Tanah, Air, dll"
                                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Matriks Uji</label>
                                    <input type="text" name="sample_matrix" value="{{ old('sample_matrix') }}"
                                           placeholder="Tanah, Air Sungai, dll"
                                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Nama Sampel</label>
                                    <input type="text" name="sample_name_label" value="{{ old('sample_name_label') }}"
                                           placeholder="Nama deskriptif sampel"
                                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Bentuk</label>
                                    <input type="text" name="sample_form" value="{{ old('sample_form') }}"
                                           placeholder="Padat, Cair, dll"
                                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Jumlah</label>
                                    <input type="number" name="sample_count" value="{{ old('sample_count') }}"
                                           placeholder="1" min="1"
                                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Kemasan</label>
                                    <input type="text" name="sample_packing" value="{{ old('sample_packing') }}"
                                           placeholder="Plastik, Botol, dll"
                                           class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                </div>
                            </div>
                        </div>

                        {{-- Section 4: Daftar Sampel Pengujian --}}
                        <div class="mb-10">
                            <div class="mb-6 flex items-center justify-between">
                                <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                                    <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                    Daftar Sampel Pengujian
                                </h3>
                            </div>

                            <div id="samplesContainer" class="space-y-6"></div>

                            <div class="flex justify-center mt-8">
                                <button type="button" id="addSample"
                                        class="group flex items-center gap-2 border-2 border-dashed border-indigo-300 hover:border-indigo-500 bg-indigo-50/50 hover:bg-indigo-50 text-indigo-600 px-8 py-3.5 rounded-xl font-semibold transition-all hover:shadow-md">
                                    <svg class="w-5 h-5 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Tambah Sampel Uji
                                </button>
                            </div>
                        </div>

                        {{-- Section 5: Detail SP3 per Parameter (dynamic) --}}
                        <div class="mb-8 p-6 bg-amber-50/50 border border-amber-100 rounded-2xl shadow-sm" id="sp3DetailsSection" style="display:none">
                            <h3 class="text-xl font-bold mb-1 text-gray-800 flex items-center gap-2">
                                <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Detail SP3 per Parameter
                            </h3>
                            <p class="text-sm text-gray-500 mb-5">Isi nomor SP3 dan Instruksi Kerja untuk setiap parameter yang dipilih. Muncul otomatis saat parameter diceklis.</p>
                            <div id="sp3DetailsList" class="space-y-4"></div>
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

    {{-- Quick-add Parameter modal (outside #formPengujian to avoid nested forms) --}}
    <div id="parameterModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Tambah Parameter Baru</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Nama Parameter *</label>
                    <input type="text" data-field="name" class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Instrumen</label>
                        <input type="text" data-field="instrument" class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Kategori</label>
                        <input type="text" data-field="category" class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Metode Default</label>
                        <input type="text" data-field="default_method" class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Satuan Default</label>
                        <input type="text" data-field="default_unit" class="w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
                <p id="parameterModalError" class="hidden text-sm text-red-600"></p>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" id="parameterModalCancel" class="px-5 py-2.5 rounded-lg font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200">Batal</button>
                <button type="button" id="parameterModalSave" class="px-5 py-2.5 rounded-lg font-semibold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60">Simpan</button>
            </div>
        </div>
    </div>

    {{-- Sample Template (hidden) --}}
    <template id="sampleTemplate">
        <div class="sample-item p-6 border border-gray-200 rounded-2xl bg-white shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group" data-index="__INDEX__">
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
                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">
                        Kode Sampel *
                        <span class="normal-case font-normal text-gray-400 ml-1">(auto)</span>
                    </label>
                    <input type="text" name="samples[__INDEX__][sample_code]"
                           class="sample-code-input w-full border-gray-200 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-mono" required>
                    <p class="text-xs text-gray-400 mt-1">Otomatis — ubah jika perlu</p>
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
                {{-- Filled by JS (paramLabelHtml) so quick-added parameters share the same markup --}}
                <div class="param-grid grid grid-cols-2 md:grid-cols-4 gap-3"></div>
                <button type="button" class="add-parameter-btn mt-3 flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 px-3 py-1.5 rounded-lg transition-colors">
                    <svg class="w-4 h-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Parameter Baru
                </button>
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

            // Parameter list (array keeps the name ordering; an id-keyed object would re-sort by id)
            @php($parameterList = $parameters->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values())
            const parameterList = @json($parameterList);
            const parameterNames = {};
            parameterList.forEach(p => parameterNames[p.id] = p.name);

            function escapeHtml(str) {
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

            function paramLabelHtml(index, id, name) {
                return `
                    <label class="relative flex items-center p-3 cursor-pointer rounded-xl border border-gray-200 hover:border-indigo-400 bg-white hover:bg-indigo-50/50 transition-all group overflow-hidden">
                        <input type="checkbox" name="samples[${index}][parameters][]"
                               value="${id}"
                               class="peer rounded-md border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 absolute opacity-0 z-[-1]">
                        <div class="w-5 h-5 rounded border border-gray-300 mr-3 flex items-center justify-center flex-shrink-0 peer-checked:bg-indigo-600 peer-checked:border-indigo-600 transition-colors">
                            <svg class="w-3.5 h-3.5 text-white opacity-0 peer-checked:opacity-100 transition-opacity" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700 peer-checked:text-indigo-900">${escapeHtml(name)}</span>
                        <div class="absolute inset-0 bg-indigo-50/50 opacity-0 peer-checked:opacity-100 peer-checked:border-indigo-500 border rounded-xl pointer-events-none transition-all z-0"></div>
                    </label>`;
            }

            // Fill the template's parameter grid once; every cloned sample inherits it
            const templateGrid = template.content.querySelector('.param-grid');
            templateGrid.innerHTML = parameterList.map(p => paramLabelHtml('__INDEX__', p.id, p.name)).join('');

            function addSample() {
                const clone = template.content.cloneNode(true);
                const html = clone.querySelector('.sample-item').outerHTML
                    .replace(/__INDEX__/g, sampleIndex)
                    .replace(/__NUMBER__/g, sampleIndex + 1);

                container.insertAdjacentHTML('beforeend', html);

                // Auto-fill sample code for this new sample
                const allSamples = container.querySelectorAll('.sample-item');
                const newSample = allSamples[allSamples.length - 1];
                const codeInput = newSample.querySelector('.sample-code-input');
                if (codeInput) {
                    const seq = nextSampleCodeSeq + (allSamples.length - 1);
                    codeInput.value = String(seq).padStart(3, '0') + sampleCodeSuffix;
                    codeInput.placeholder = codeInput.value;
                }

                sampleIndex++;
                updateSampleNumbers();
            }

            function updateSampleNumbers() {
                const samples = container.querySelectorAll('.sample-item');
                samples.forEach((sample, index) => {
                    const numberEl = sample.querySelector('.sample-number');
                    if (numberEl) numberEl.textContent = index + 1;
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

            const nextSpppSeq = {{ $nextSpppSeq }};
            const spppSuffix = @json($spppSuffix);
            const nextSampleCodeSeq = {{ $nextSampleCodeSeq }};
            const sampleCodeSuffix = @json($sampleCodeSuffix);

            // Add first sample on load
            addSample();

            // SP3 detail tracking
            const sp3Section = document.getElementById('sp3DetailsSection');
            const sp3List = document.getElementById('sp3DetailsList');

            function padSeq(n) {
                return String(n).padStart(3, '0');
            }

            function updateSp3Details() {
                const checked = new Set();
                document.querySelectorAll('input[type=checkbox][name*="[parameters][]"]:checked').forEach(cb => {
                    checked.add(cb.value);
                });

                document.querySelectorAll('.sp3-detail-row').forEach(row => {
                    if (!checked.has(row.dataset.parameterId)) row.remove();
                });

                checked.forEach(paramId => {
                    if (!document.querySelector(`.sp3-detail-row[data-parameter-id="${paramId}"]`)) {
                        const name = parameterNames[paramId] ?? `Parameter ${paramId}`;
                        const existingCount = document.querySelectorAll('.sp3-detail-row').length;
                        const spppNumber = padSeq(nextSpppSeq + existingCount) + spppSuffix;
                        const row = document.createElement('div');
                        row.className = 'sp3-detail-row grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-white border border-amber-200 rounded-xl';
                        row.dataset.parameterId = paramId;
                        row.innerHTML = `
                            <div class="md:col-span-2">
                                <p class="text-sm font-bold text-amber-700 mb-3">Parameter: ${name}</p>
                                <input type="hidden" name="sp3_details[${paramId}][parameter_id]" value="${paramId}">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">No. SP3 <span class="text-red-500">*</span></label>
                                <input type="text" name="sp3_details[${paramId}][no_sppp]"
                                       value="${spppNumber}"
                                       required
                                       class="w-full text-sm border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                                <p class="text-xs text-gray-400 mt-1">Otomatis — ubah jika perlu</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wide mb-2">Instruksi Kerja (IK)</label>
                                <input type="text" name="sp3_details[${paramId}][ik]"
                                       placeholder="IK.01.01..."
                                       class="w-full text-sm border-gray-200 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                            </div>
                        `;
                        sp3List.appendChild(row);
                    }
                });

                sp3Section.style.display = checked.size > 0 ? 'block' : 'none';
            }

            container.addEventListener('change', function(e) {
                if (e.target.matches('input[type=checkbox][name*="[parameters][]"]')) {
                    updateSp3Details();
                }
            });

            // Quick-add Parameter modal
            const modal = document.getElementById('parameterModal');
            const modalError = document.getElementById('parameterModalError');
            const modalSave = document.getElementById('parameterModalSave');
            const modalFields = modal.querySelectorAll('[data-field]');
            let targetSample = null;

            function openParameterModal(sampleEl) {
                targetSample = sampleEl;
                modalFields.forEach(input => input.value = '');
                modalError.classList.add('hidden');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.querySelector('[data-field="name"]').focus();
            }

            function closeParameterModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                targetSample = null;
            }

            function addParameterOption(id, name) {
                parameterNames[id] = name;
                templateGrid.insertAdjacentHTML('beforeend', paramLabelHtml('__INDEX__', id, name));
                container.querySelectorAll('.sample-item').forEach(sample => {
                    sample.querySelector('.param-grid')
                        .insertAdjacentHTML('beforeend', paramLabelHtml(sample.dataset.index, id, name));
                });

                if (targetSample) {
                    const cb = targetSample.querySelector(`input[type=checkbox][name*="[parameters][]"][value="${id}"]`);
                    if (cb) {
                        cb.checked = true;
                        cb.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }
            }

            container.addEventListener('click', function(e) {
                const btn = e.target.closest('.add-parameter-btn');
                if (btn) openParameterModal(btn.closest('.sample-item'));
            });

            document.getElementById('parameterModalCancel').addEventListener('click', closeParameterModal);
            modal.addEventListener('click', e => { if (e.target === modal) closeParameterModal(); });
            modal.addEventListener('keydown', e => {
                if (e.key === 'Escape') closeParameterModal();
                if (e.key === 'Enter') { e.preventDefault(); modalSave.click(); }
            });

            modalSave.addEventListener('click', async function() {
                const payload = {};
                modalFields.forEach(input => payload[input.dataset.field] = input.value.trim() || null);

                if (!payload.name) {
                    modalError.textContent = 'Nama parameter wajib diisi.';
                    modalError.classList.remove('hidden');
                    return;
                }

                modalSave.disabled = true;
                modalError.classList.add('hidden');

                try {
                    const res = await fetch(@json(route('parameters.quick-store')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify(payload),
                    });
                    const data = await res.json();

                    if (!res.ok) {
                        const firstError = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Gagal menyimpan parameter.');
                        modalError.textContent = firstError;
                        modalError.classList.remove('hidden');
                        return;
                    }

                    addParameterOption(data.id, data.name);
                    closeParameterModal();
                } catch (err) {
                    modalError.textContent = 'Terjadi kesalahan jaringan. Coba lagi.';
                    modalError.classList.remove('hidden');
                } finally {
                    modalSave.disabled = false;
                }
            });
        });
    </script>
</x-app-layout>
