<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Reset Password: {{ $user->full_name }}
            </h2>
            <a href="{{ route('users.index') }}" class="text-blue-600 hover:underline">← Kembali</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    {{-- User Info --}}
                    <div class="flex items-center mb-6 pb-6 border-b">
                        <div class="h-16 w-16 rounded-full bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white font-bold text-2xl">
                            {{ strtoupper(substr($user->full_name ?? $user->username, 0, 1)) }}
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">{{ $user->full_name }}</h3>
                            <p class="text-sm text-gray-500">{{ $user->username }}</p>
                        </div>
                    </div>

                    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-sm text-yellow-700">
                            <strong>⚠️ Perhatian:</strong> Password baru akan langsung aktif setelah disimpan. 
                            User harus login ulang dengan password baru.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('users.password.update', $user) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru *</label>
                            <input type="password" name="password" required
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"
                                   placeholder="Min. 6 karakter">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password *</label>
                            <input type="password" name="password_confirmation" required
                                   class="w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"
                                   placeholder="Ulangi password baru">
                        </div>

                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('users.index') }}" 
                               class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                                Batal
                            </a>
                            <button type="submit" 
                                    class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-md font-medium transition">
                                Reset Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
