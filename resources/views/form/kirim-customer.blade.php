<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Kirim Hasil ke Customer
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Form Siap Dikirim ke Customer</h3>
                    
                    @if($forms->isEmpty())
                        <p class="text-gray-500 text-center py-8">Tidak ada form yang menunggu pengiriman</p>
                    @else
                        <div class="space-y-4">
                            @foreach($forms as $form)
                                <div class="p-4 border rounded-lg bg-green-50 border-green-200">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-bold text-gray-900">{{ $form->form_number }}</p>
                                            <p class="text-sm text-gray-600">{{ $form->customer_name }}</p>
                                            <p class="text-xs text-gray-500">
                                                {{ $form->samples->count() }} sampel • 
                                                Deadline: {{ \Carbon\Carbon::parse($form->deadline_date)->format('d M Y') }}
                                            </p>
                                        </div>
                                        <div class="flex space-x-2">
                                            <a href="{{ route('form.show', $form) }}" 
                                               class="text-blue-600 hover:text-blue-800 text-sm font-medium">Detail</a>
                                            
                                            {{-- Tombol Konfirmasi Kirim --}}
                                            <form method="POST" action="{{ route('form.konfirmasi-kirim', $form) }}" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                        onclick="return confirm('Konfirmasi bahwa hasil pengujian telah dikirim ke customer? Status form akan menjadi SELESAI.')"
                                                        class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm font-medium">
                                                    ✓ Konfirmasi Terkirim
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    {{-- Status info --}}
                                    <div class="mt-2 flex items-center text-xs text-gray-600">
                                        <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full mr-2">Sudah ditandatangani Kepala UPA</span>
                                        <span>Siap kirim hasil via WhatsApp/Email ke Customer</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
