<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                🔔 Notifikasi
            </h2>
            @if($notifications->where('is_read', false)->count() > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="text-sm text-blue-600 hover:underline">
                        Tandai Semua Sudah Dibaca
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-xl flex items-center gap-2">
                    ✅ {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-xl flex items-center gap-2">
                    ⚠️ {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
                <div class="divide-y divide-gray-100">
                    @forelse($notifications as $notif)
                        {{-- Unread = white/blue bg, bright text. Read = gray bg, muted text --}}
                        <div class="p-4 flex items-start gap-3 transition-colors
                            {{ !$notif->is_read ? 'bg-blue-50 hover:bg-blue-100' : 'bg-gray-50 hover:bg-gray-100' }}">
                            
                            {{-- Icon --}}
                            <span class="text-2xl flex-shrink-0 {{ $notif->is_read ? 'opacity-40 grayscale' : '' }}">
                                {{ $notif->icon }}
                            </span>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-sm font-semibold {{ $notif->is_read ? 'text-gray-400' : 'text-gray-900' }}">
                                        {{ $notif->title }}
                                        @if($notif->is_read)
                                            <span class="ml-1 text-xs font-normal text-gray-400">(Sudah dibaca)</span>
                                        @endif
                                    </p>
                                    <span class="text-xs text-gray-400 flex-shrink-0">{{ $notif->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-sm mt-0.5 {{ $notif->is_read ? 'text-gray-400' : 'text-gray-600' }}">
                                    {{ $notif->message }}
                                </p>
                                
                                {{-- Action buttons --}}
                                <div class="flex items-center gap-3 mt-2">
                                    @if(!$notif->is_read)
                                        <form method="POST" action="{{ route('notifications.read', $notif) }}">
                                            @csrf
                                            <button type="submit" class="text-xs text-blue-600 hover:text-blue-800 hover:underline font-medium">
                                                @if(isset($notif->data['form_id']))
                                                    Lihat Form →
                                                @else
                                                    Tandai Sudah Dibaca
                                                @endif
                                            </button>
                                        </form>
                                    @else
                                        @if(isset($notif->data['form_id']))
                                            <a href="{{ route('form-list.show', $notif->data['form_id']) }}" 
                                               class="text-xs text-gray-400 hover:text-indigo-600 hover:underline font-medium">
                                                Lihat Form →
                                            </a>
                                        @endif
                                    @endif

                                    {{-- Delete button (always visible) --}}
                                    <form method="POST" action="{{ route('notifications.destroy', $notif) }}"
                                          onsubmit="return confirm('Hapus notifikasi ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-400 hover:text-red-600 hover:underline font-medium">
                                            🗑 Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>

                            {{-- Unread dot indicator --}}
                            @if(!$notif->is_read)
                                <div class="w-2.5 h-2.5 rounded-full bg-blue-500 flex-shrink-0 mt-1.5"></div>
                            @endif
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <p class="text-lg font-medium">Tidak ada notifikasi</p>
                            <p class="text-sm mt-1">Notifikasi akan muncul ketika ada aktivitas baru</p>
                        </div>
                    @endforelse
                </div>
            </div>

            @if($notifications->hasPages())
                <div class="mt-4">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
