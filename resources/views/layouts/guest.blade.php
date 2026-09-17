<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Katta') }}</title>

        <!-- Font hỗ trợ tiếng Việt tốt -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        {{-- Nền gradient tím giống banner trang chủ (mục 3), có hình cầu mờ trang trí --}}
        <div class="relative min-h-screen flex flex-col items-center justify-center px-4 py-10 overflow-hidden bg-gradient-to-br from-indigo-900 to-purple-600">
            <div class="pointer-events-none absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white/10 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-24 -left-24 w-80 h-80 rounded-full bg-white/10 blur-3xl"></div>

            {{-- Logo (khối hoàn chỉnh icon + chữ "Katta" đã có sẵn trong ảnh, không cần thêm text riêng) --}}
            <a href="{{ route('login') }}" class="relative z-10 flex flex-col items-center gap-3 mb-6">
                <x-logo size="w-16 h-20" />
                <span class="text-sm text-white/70">{{ __('Học tiếng Anh mỗi ngày cùng Katta') }}</span>
            </a>

            {{-- Card chứa form --}}
            <div class="relative z-10 w-full sm:max-w-md px-6 py-8 bg-white shadow-xl overflow-hidden rounded-2xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
