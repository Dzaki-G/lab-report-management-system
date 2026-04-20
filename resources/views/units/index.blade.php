<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Kelola Satuan
            </h2>
            <a href="{{ route('units.create') }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                + Tambah Satuan
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($units->isEmpty())
                        <p class="text-gray-500 text-center py-8">Belum ada satuan. Klik tombol "Tambah Satuan" untuk menambah.</p>
                    @else
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Nama Satuan</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Simbol</th>
                                    <th class="text-center py-3 px-4 font-semibold text-gray-700">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($units as $unit)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="py-3 px-4">{{ $unit->name }}</td>
                                        <td class="py-3 px-4 text-gray-600">{{ $unit->symbol ?? '-' }}</td>
                                        <td class="py-3 px-4 text-center">
                                            <div class="flex justify-center space-x-2">
                                                <a href="{{ route('units.edit', $unit) }}" 
                                                   class="text-blue-600 hover:text-blue-800 text-sm">Edit</a>
                                                <form method="POST" action="{{ route('units.destroy', $unit) }}" 
                                                      class="inline" onsubmit="return confirm('Yakin hapus satuan ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
