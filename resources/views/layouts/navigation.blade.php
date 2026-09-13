<!-- Mobile overlay -->
<div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-sm lg:hidden" @click="sidebarOpen = false" style="display: none;"></div>

<!-- Sidebar Layout -->
<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-72 bg-[#d1dff6] border-r border-[#bbcae5] transition-transform duration-300 ease-in-out lg:static lg:translate-x-0 flex flex-col shadow-2xl lg:shadow-none">
    <!-- Logo area -->
    <div class="h-16 flex items-center justify-between px-6 bg-white/40 border-b border-[#bbcae5]">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <x-application-logo class="block h-10 w-auto drop-shadow-sm transition-transform hover:scale-105" />
            <span class="font-extrabold text-[#1a365d] tracking-tight text-base">Lab UPA Terpadu</span>
        </a>
        <!-- Close button mobile -->
        <button @click="sidebarOpen = false" class="lg:hidden text-[#2c5282] hover:text-[#1a365d] bg-white/40 p-1.5 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    <!-- Nav Links container -->
    <div class="flex-1 overflow-y-auto py-6 px-4 space-y-2 relative">
        @php
            $userRole = Auth::user()->role_id ?? 0;
            $linkClass = "flex items-center px-4 py-3 text-sm font-semibold rounded-xl transition-all duration-200 group relative overflow-hidden";
            $activeClass = "bg-[#2b6cb0] text-white shadow-md shadow-[#2b6cb0]/40";
            $inactiveClass = "text-[#2c5282] hover:bg-white/60 hover:text-[#1a365d] hover:shadow-sm";
        @endphp

        <!-- Dashboard -->
        <a href="{{ route('dashboard') }}" class="{{ $linkClass }} {{ request()->routeIs('dashboard') ? $activeClass : $inactiveClass }}">
            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('dashboard') ? 'text-blue-200' : 'text-[#4299e1] group-hover:text-[#2b6cb0]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            {{ __('Dashboard') }}
        </a>

        {{-- Super Admin Menu --}}
        @if($userRole == \App\Enums\Role::SUPER_ADMIN)
            <div class="pt-5 pb-2 px-4 text-[11px] font-bold text-[#4a5568] uppercase tracking-widest">Manajemen Super</div>
            <a href="{{ route('users.index') }}" class="{{ $linkClass }} {{ request()->routeIs('users.*') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('users.*') ? 'text-blue-200' : 'text-[#4299e1] group-hover:text-[#2b6cb0]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                {{ __('Kelola User') }}
            </a>
        @endif

        {{-- Admin Menu --}}
        @if($userRole == \App\Enums\Role::ADMIN)
            <div class="pt-5 pb-2 px-4 text-[11px] font-bold text-[#4a5568] uppercase tracking-widest">Menu Admin</div>
            <a href="{{ route('form.index') }}" class="{{ $linkClass }} {{ request()->routeIs('form.index') || request()->routeIs('form.show') || request()->routeIs('form.create') || request()->routeIs('form.edit') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 mr-3 {{ (request()->routeIs('form.index')||request()->routeIs('form.*')) && !request()->routeIs('form.input-lhp') && !request()->routeIs('form.kirim-customer') ? 'text-blue-200' : 'text-[#4299e1] group-hover:text-[#2b6cb0]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                {{ __('Form Pengujian') }}
            </a>
            <a href="{{ route('parameters.index') }}" class="{{ $linkClass }} {{ request()->routeIs('parameters.*') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('parameters.*') ? 'text-blue-200' : 'text-[#4299e1] group-hover:text-[#2b6cb0]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                {{ __('Kelola Parameter') }}
            </a>
            <a href="{{ route('units.index') }}" class="{{ $linkClass }} {{ request()->routeIs('units.*') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('units.*') ? 'text-blue-200' : 'text-[#4299e1] group-hover:text-[#2b6cb0]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                {{ __('Master Satuan') }}
            </a>
            @php $lhpReadyCount = \App\Models\FormPengujian::where('status', 'kirim_customer')->count(); @endphp
            <a href="{{ route('admin.lhp-ready') }}" class="{{ $linkClass }} {{ request()->routeIs('admin.lhp-ready') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('admin.lhp-ready') ? 'text-blue-200' : 'text-[#4299e1] group-hover:text-[#2b6cb0]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                {{ __('LHP Siap Kirim') }}
                @if($lhpReadyCount > 0)
                    <span class="ml-auto inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold rounded-full bg-amber-400 text-white">
                        {{ $lhpReadyCount }}
                    </span>
                @endif
            </a>
        @endif

        {{-- Kepala UPA Menu --}}
        @if($userRole == \App\Enums\Role::KEPALA_UPA)
            <div class="pt-5 pb-2 px-4 text-[11px] font-bold text-[#4a5568] uppercase tracking-widest">Pimpinan</div>
            <a href="{{ route('kepala-upa.dashboard') }}" class="{{ $linkClass }} {{ request()->routeIs('kepala-upa.*') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('kepala-upa.*') ? 'text-blue-200' : 'text-[#4299e1] group-hover:text-[#2b6cb0]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                {{ __('Antrian TTD LHP') }}
            </a>
            <a href="{{ route('form.index') }}" class="{{ $linkClass }} {{ request()->routeIs('form.index') || request()->routeIs('form.show') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('form.index') || request()->routeIs('form.show') ? 'text-blue-200' : 'text-[#4299e1] group-hover:text-[#2b6cb0]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                {{ __('Form Pengujian') }}
            </a>
            <a href="{{ route('signature.show') }}" class="{{ $linkClass }} {{ request()->routeIs('signature.*') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('signature.*') ? 'text-blue-200' : 'text-[#4299e1] group-hover:text-[#2b6cb0]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                {{ __('Tanda Tangan') }}
            </a>
        @endif

        {{-- Kepala Divisi Menu --}}
        @if($userRole == \App\Enums\Role::KEPALA_DIVISI)
            <div class="pt-5 pb-2 px-4 text-[11px] font-bold text-[#4a5568] uppercase tracking-widest">Divisi</div>
            <a href="{{ route('kepala-divisi.dashboard') }}" class="{{ $linkClass }} {{ request()->routeIs('kepala-divisi.*') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('kepala-divisi.*') ? 'text-blue-200' : 'text-[#4299e1] group-hover:text-[#2b6cb0]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ __('Verifikasi Divisi') }}
            </a>
        @endif

        {{-- Analitik Menu (Accessible by Admin and Kepala UPA) --}}
        @if(in_array($userRole, [\App\Enums\Role::ADMIN, \App\Enums\Role::KEPALA_UPA]))
            <div class="pt-5 pb-2 px-4 text-[11px] font-bold text-[#4a5568] uppercase tracking-widest">Analitik</div>
            <a href="{{ route('statistics.index') }}" class="{{ $linkClass }} {{ request()->routeIs('statistics.*') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('statistics.*') ? 'text-blue-200' : 'text-[#4299e1] group-hover:text-[#2b6cb0]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                {{ __('Statistik Pengujian') }}
            </a>
        @endif

        {{-- Analis Menu --}}
        @if($userRole == \App\Enums\Role::ANALIS)
            <div class="pt-5 pb-2 px-4 text-[11px] font-bold text-[#4a5568] uppercase tracking-widest">Laboratorium</div>
            <a href="{{ route('analis.dashboard') }}" class="{{ $linkClass }} {{ request()->routeIs('analis.*') ? $activeClass : $inactiveClass }}">
                <svg class="w-5 h-5 mr-3 {{ request()->routeIs('analis.*') ? 'text-blue-200' : 'text-[#4299e1] group-hover:text-[#2b6cb0]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                {{ __('Tugas Saya (LCP)') }}
            </a>
        @endif
    </div>

    <!-- Sidebar Footer -->
    <div class="p-4 bg-white/40 border-t border-[#bbcae5] backdrop-blur-sm mt-auto shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] text-center">
        <p class="text-[10px] text-[#4a5568] font-bold uppercase tracking-widest mb-1">Sistem UPA</p>
        <p class="text-[10px] text-[#718096]">Universitas Lampung</p>
    </div>
</aside>
