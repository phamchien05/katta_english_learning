<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? $title . ' - ' : '' }}{{ config('app.name', 'Katta') }}</title>

        <!-- Font hỗ trợ tiếng Việt tốt -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Icon set: lucide (qua CDN, theo gợi ý ở mục 17) -->
        <script src="https://unpkg.com/lucide@latest"></script>

        <!-- Scripts / styles biên dịch qua Vite -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="font-sans antialiased" x-data="{ mobileSidebarOpen: false }">

        <div class="flex min-h-screen bg-katta-bg">
            {{-- Sidebar desktop --}}
            @include('layouts.partials.sidebar')

            {{-- Sidebar mobile (trượt ra khi bấm hamburger) --}}
            <div x-show="mobileSidebarOpen" x-cloak class="fixed inset-0 z-40 lg:hidden">
                <div class="absolute inset-0 bg-black/40" @click="mobileSidebarOpen = false"></div>
                <div class="relative w-[280px] h-full">
                    @include('layouts.partials.sidebar')
                </div>
            </div>

            {{-- Nội dung chính --}}
            <div class="flex-1 min-w-0 relative">
                {{-- Watermark trang trí phía sau nội dung --}}
                <div class="pointer-events-none select-none absolute inset-0 overflow-hidden opacity-[0.03] -z-0">
                    <span class="absolute top-10 -left-10 text-[10rem] font-extrabold rotate-[-12deg] text-katta-primary">Katta</span>
                    <span class="absolute bottom-0 right-0 text-[9rem] font-extrabold rotate-[8deg] text-katta-primary">Anh</span>
                </div>

                <div class="relative z-10">
                    {{-- Thanh trên cùng mobile: nút mở sidebar --}}
                    <div class="lg:hidden flex items-center gap-3 px-4 pt-4">
                        <button @click="mobileSidebarOpen = true" class="text-katta-sidebar">
                            <i data-lucide="menu" class="w-6 h-6"></i>
                        </button>
                        <x-logo size="w-8 h-8" />
                    </div>

                    @include('layouts.partials.header', ['title' => $title, 'icon' => $icon])

                    <main class="px-6 lg:px-10 pb-10">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>

        @livewireScripts
        <script>
            // Render icon lucide sau khi DOM sẵn sàng, và mỗi khi Livewire cập nhật DOM
            function renderLucideIcons() { if (window.lucide) { window.lucide.createIcons(); } }
            document.addEventListener('DOMContentLoaded', renderLucideIcons);
            document.addEventListener('livewire:navigated', renderLucideIcons);
            document.addEventListener('livewire:load', renderLucideIcons);
        </script>
    </body>
</html>
