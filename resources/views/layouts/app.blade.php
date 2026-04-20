<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Lab UPA Terpadu') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        
        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('images/unila-logo.png') }}">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <style>
            body {
                font-family: 'Inter', sans-serif;
            }
        </style>
    </head>
    <body class="font-sans antialiased text-gray-900 selection:bg-indigo-500 selection:text-white" x-data="{ sidebarOpen: false }">
        <div class="flex h-screen overflow-hidden bg-gradient-to-br from-slate-50 via-gray-50 to-zinc-100">
            <!-- Sidebar Navigation (Include) -->
            @include('layouts.navigation')

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0 overflow-hidden relative z-10 w-full">
                <!-- Background Decorative Blobs -->
                <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none z-[-1]">
                    <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-indigo-50 mix-blend-multiply filter blur-3xl opacity-70 animate-blob"></div>
                    <div class="absolute -top-[20%] -right-[10%] w-[50%] h-[50%] rounded-full bg-blue-50 mix-blend-multiply filter blur-3xl opacity-70 animate-blob animation-delay-2000"></div>
                    <div class="absolute -bottom-[20%] left-[20%] w-[50%] h-[50%] rounded-full bg-sky-50 mix-blend-multiply filter blur-3xl opacity-70 animate-blob animation-delay-4000"></div>
                </div>

                <!-- Topbar (Mobile Burger, Breadcrumbs, User Menu) -->
                @include('layouts.topbar')

                <!-- Scrollable Content wrapper -->
                <main class="flex-1 overflow-y-auto">
                    <div class="relative max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
