<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Assign Analis
            </h2>
            <a href="{{ route('form.show', $sampleParameter->sample->form_pengujian_id) }}" class="text-blue-600 hover:underline">← Kembali</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            {{-- Info Card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Detail Parameter</h3>
                    
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
                            <p class="text-sm text-gray-500">Sampel</p>
                            <p class="font-medium">{{ $sampleParameter->sample->sample_name }} ({{ $sampleParameter->sample->sample_code }})</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Parameter</p>
                            <p class="font-medium text-blue-600">{{ $sampleParameter->parameter->name }}</p>
                        </div>
                    </div>

                    @if($sampleParameter->assignedAnalyst)
                        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                            <p class="text-sm text-yellow-700">
                                <strong>Saat ini ditugaskan ke:</strong> {{ $sampleParameter->assignedAnalyst->full_name }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Assign Form --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Pilih Analis</h3>

                    @if($analysts->isEmpty())
                        <div class="p-4 bg-red-50 border border-red-200 rounded text-red-700">
                            Belum ada user dengan role Analis. Silakan tambahkan user analis terlebih dahulu.
                        </div>
                    @else
                        <form method="POST" action="{{ route('sample-parameters.assign', $sampleParameter) }}">
                            @csrf
                            @method('PATCH')

                            <div class="mb-6">
                                <div class="space-y-2">
                                    @foreach($analysts as $analyst)
                                        <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50 {{ $sampleParameter->assigned_analyst_id == $analyst->user_id ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                                            <input type="radio" name="assigned_analyst_id" value="{{ $analyst->user_id }}"
                                                   class="mr-3" 
                                                   {{ $sampleParameter->assigned_analyst_id == $analyst->user_id ? 'checked' : '' }}>
                                            <div>
                                                <p class="font-medium text-gray-900">{{ $analyst->full_name }}</p>
                                                <p class="text-sm text-gray-500">{{ $analyst->username }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium">
                                    Simpan Penugasan
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
