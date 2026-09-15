<div class="sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-gray-100 h-16 flex items-center justify-between px-4 sm:px-6">
    <div class="flex items-center">
        <!-- Hamburger Button (Mobile Only) -->
        <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700 focus:outline-none p-2 -ml-2 mr-2 rounded-md">
            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <!-- Sidebar Toggle (Desktop Only) -->
        <button @click="sidebarCollapsed = !sidebarCollapsed" class="hidden lg:flex items-center justify-center text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none p-2 -ml-2 mr-2 rounded-md transition-colors" title="Toggle sidebar">
            <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <!-- Page Header -->
        @isset($header)
            <div class="font-semibold text-lg text-gray-800 leading-tight hidden lg:block">
                {{ $header }}
            </div>
        @endisset
    </div>

    <!-- Topbar Actions (Notification & user menu) -->
    <div class="flex items-center space-x-4">
        <!-- Notification Bell -->
        @php
            $unreadNotifications = \App\Models\Notification::forUser(auth()->user()->user_id)->unread()->latest()->take(5)->get();
            $unreadCount = \App\Models\Notification::forUser(auth()->user()->user_id)->unread()->count();
        @endphp
        <x-dropdown align="right" width="80">
            <x-slot name="trigger">
                <button class="relative inline-flex items-center p-2 text-gray-500 hover:text-gray-700 bg-white shadow-sm rounded-full border border-gray-100 focus:outline-none transition hover:shadow">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    @if($unreadCount > 0)
                        <span class="absolute -top-1 -right-1 flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 rounded-full border-2 border-white">
                            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                        </span>
                    @endif
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="px-4 py-2 border-b bg-gray-50">
                    <span class="font-semibold text-gray-700">Notifikasi</span>
                    @if($unreadCount > 0)
                        <span class="text-xs text-gray-500">({{ $unreadCount }} belum dibaca)</span>
                    @endif
                </div>
                
                <div class="max-h-80 overflow-y-auto">
                    @forelse($unreadNotifications as $notif)
                        <form method="POST" action="{{ route('notifications.read', $notif) }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-3 border-b hover:bg-gray-50 {{ !$notif->is_read ? 'bg-blue-50/50' : '' }}">
                                <div class="flex items-start space-x-2">
                                    <span class="text-lg">{{ $notif->icon }}</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $notif->title }}</p>
                                        <p class="text-xs text-gray-500 truncate">{{ $notif->message }}</p>
                                        <p class="text-xs text-gray-400 mt-1">{{ $notif->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            </button>
                        </form>
                    @empty
                        <div class="px-4 py-6 text-center text-gray-500 text-sm">
                            Tidak ada notifikasi baru
                        </div>
                    @endforelse
                </div>
                
                <div class="px-4 py-2 border-t bg-gray-50 text-center">
                    <a href="{{ route('notifications.index') }}" class="text-sm text-blue-600 hover:text-blue-800 hover:underline">
                        Lihat Semua Notifikasi
                    </a>
                </div>
            </x-slot>
        </x-dropdown>

        <!-- Settings Dropdown -->
        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button class="inline-flex items-center px-4 py-2 border border-gray-100 text-sm font-medium rounded-full text-gray-600 bg-white shadow-sm hover:text-gray-800 hover:shadow focus:outline-none transition ease-in-out duration-150">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs">
                            {{ substr(Auth::user()->full_name ?? Auth::user()->username, 0, 1) }}
                        </div>
                        <span class="hidden sm:block max-w-[120px] truncate">{{ Auth::user()->full_name ?? Auth::user()->username }}</span>
                    </div>

                    <div class="ms-1 hidden sm:block">
                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="px-4 py-3 border-b border-gray-100 sm:hidden">
                    <p class="text-sm text-gray-900 font-medium">{{ Auth::user()->full_name ?? Auth::user()->username }}</p>
                    <p class="text-xs text-gray-500 truncate">{{ Auth::user()->username }}</p>
                </div>

                <x-dropdown-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-dropdown-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-dropdown-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();" class="text-red-600 hover:text-red-800">
                        {{ __('Log Out') }}
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
</div>
