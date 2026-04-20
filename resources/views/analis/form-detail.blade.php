<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                🔬 Pengujian Sampel
            </h2>
            <a href="{{ route('analis.dashboard') }}" class="text-blue-600 hover:underline">← Kembali ke Dashboard</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Form Info --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Informasi Pengujian</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Tanggal Masuk</p>
                            <p class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($form->received_date)->format('d M Y') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Deadline</p>
                            <p class="font-medium text-gray-900 {{ \Carbon\Carbon::parse($form->deadline_date)->isPast() ? 'text-red-600' : '' }}">
                                {{ \Carbon\Carbon::parse($form->deadline_date)->format('d M Y') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Total Sampel</p>
                            <p class="font-medium text-gray-900">{{ $form->samples->count() }} sampel</p>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    @php
                        $done = $form->samples->flatMap->sampleParameters->where('status', 'done')->count();
                        $inProgress = $form->samples->flatMap->sampleParameters->where('status', 'in_progress')->count();
                        $total = $form->samples->flatMap->sampleParameters->count();
                        $percentage = $total > 0 ? ($done/$total)*100 : 0;
                    @endphp
                    <div class="mt-4 pt-4 border-t">
                        <div class="flex justify-between text-sm text-gray-500 mb-1">
                            <span>Progress Pengujian</span>
                            <span>{{ $done }}/{{ $total }} selesai ({{ number_format($percentage, 0) }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-blue-600 h-3 rounded-full transition-all" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Samples & Parameters --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-4">Daftar Sampel & Parameter</h3>
                    
                    <div class="space-y-6">
                        @foreach($form->samples as $index => $sample)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h4 class="font-medium text-gray-900">
                                            {{ $index + 1 }}. {{ $sample->sample_name }}
                                        </h4>
                                        <p class="text-sm text-gray-500">Kode: {{ $sample->sample_code }}</p>
                                    </div>
                                    <span class="text-sm text-gray-600">
                                        Jumlah: <strong>{{ $sample->quantity }}</strong> {{ $sample->unit ?? 'pcs' }}
                                    </span>
                                </div>

                                @if($sample->description)
                                    <div class="mb-3 p-2 bg-yellow-50 border-l-4 border-yellow-400 rounded-r">
                                        <p class="text-sm text-yellow-800">📝 <span class="font-medium">Keterangan:</span> {{ $sample->description }}</p>
                                    </div>
                                @endif

                                {{-- Parameters --}}
                                <div class="space-y-2">
                                    @foreach($sample->sampleParameters as $sp)
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-gray-100 border-gray-300',
                                                'in_progress' => 'bg-blue-100 border-blue-300',
                                                'done' => 'bg-green-100 border-green-300',
                                            ];
                                        @endphp
                                        <div class="flex items-center justify-between p-3 rounded border {{ $statusColors[$sp->status] ?? 'bg-gray-100 border-gray-300' }}">
                                            <div>
                                                <span class="font-medium">{{ $sp->parameter->name ?? 'N/A' }}</span>
                                                @if($sp->status === 'done' && $sp->analysisResult)
                                                    <span class="text-sm text-green-700 ml-2">
                                                        Hasil: <strong>{{ $sp->analysisResult->result_value }}</strong>
                                                    </span>
                                                @endif
                                            </div>
                                            <div>
                                                @if($sp->status === 'pending')
                                                    <form method="POST" action="{{ route('analis.start', $sp) }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm font-medium">
                                                            Mulai Kerjakan
                                                        </button>
                                                    </form>
                                                @elseif($sp->status === 'in_progress')
                                                    <a href="{{ route('analis.input', $sp) }}" 
                                                       class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm font-medium">
                                                        Input Hasil
                                                    </a>
                                                @else
                                                    <span class="text-green-600 font-medium text-sm">✓ Selesai</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
