<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        <title>{{ config('app.name', 'Lab UPA Terpadu') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('images/unila-logo.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <!-- Fallback to CDN just in case build is missing -->
            <script src="https://cdn.tailwindcss.com"></script>
            <style>body { font-family: 'Inter', sans-serif; }</style>
        @endif
    </head>
    <body class="antialiased text-slate-800 selection:bg-blue-500 selection:text-white">
        <!-- Background Wrap -->
        <div class="relative min-h-screen flex flex-col justify-center items-center overflow-hidden bg-gradient-to-br from-slate-50 via-[#e0eafc] to-[#cfdef3]">
            
            <!-- Animated decorative blobs -->
            <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none z-0">
                <div class="absolute -top-[20%] -left-[10%] w-[60%] h-[60%] rounded-full bg-blue-300 mix-blend-multiply filter blur-[120px] opacity-40 animate-pulse"></div>
                <div class="absolute top-[40%] -right-[20%] w-[50%] h-[50%] rounded-full bg-indigo-200 mix-blend-multiply filter blur-[120px] opacity-40 animate-pulse" style="animation-delay: 2s;"></div>
            </div>

            <!-- Top Header Navigation -->
            <div class="absolute top-0 right-0 w-full p-6 z-50 flex justify-end items-center">
                @if (Route::has('login'))
                    <div class="space-x-2 sm:space-x-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="font-semibold text-sm sm:text-base text-slate-600 hover:text-blue-700 transition-colors">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="font-semibold text-sm sm:text-base text-slate-600 hover:text-blue-700 transition-colors">Log in</a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="ml-2 font-semibold text-sm sm:text-base text-white bg-blue-600 px-4 py-2 sm:px-5 sm:py-2.5 rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all">Register</a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>

            <!-- Main Hero Content -->
            <main class="relative z-10 w-full max-w-3xl px-6 mx-auto text-center">
                <!-- Glassmorphism Card Container -->
                <div class="bg-white/60 backdrop-blur-xl p-10 sm:p-14 rounded-3xl shadow-2xl border border-white/60 transform transition-all hover:scale-[1.01] duration-500">
                    
                    <!-- UNILA Logo -->
                    <img src="{{ asset('images/unila-logo.png') }}" alt="Logo UNILA" class="w-32 sm:w-40 h-auto mx-auto mb-8 drop-shadow-lg transition-transform hover:-translate-y-1 duration-300">
                    
                    <!-- Title -->
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-[#1a202c] tracking-tight mb-4 leading-tight">
                        Sistem Informasi
                        <span class="block text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 mt-2">Lab UPA Terpadu</span>
                    </h1>
                    
                    <!-- Subtitle -->
                    <p class="text-base sm:text-lg text-slate-600 mb-10 max-w-lg mx-auto font-medium leading-relaxed">
                        Platform manajemen dokumen pengujian dan verifikasi terintegrasi untuk laboratorium Universitas Lampung.
                    </p>

                    <!-- CTA Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="w-full sm:w-auto px-8 py-3.5 text-base font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl shadow-lg shadow-blue-500/30 hover:shadow-blue-500/50 hover:-translate-y-0.5 transition-all duration-200">
                                Buka Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="w-full sm:w-auto px-8 py-3.5 text-base font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl shadow-xl shadow-blue-500/30 hover:shadow-blue-500/50 hover:-translate-y-1 transition-all duration-200">
                                Masuk ke Aplikasi
                            </a>
                        @endauth
                    </div>
                </div>

                <!-- Footer text -->
                <div class="mt-10 text-sm text-slate-500/80 font-semibold tracking-wide uppercase">
                    &copy; {{ date('Y') }} Universitas Lampung
                </div>
            </main>
        </div>
    </body>
</html>
