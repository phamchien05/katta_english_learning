@php
    // Ngày hiện tại format động bằng Carbon, theo ngôn ngữ đang chọn
    $now = \Carbon\Carbon::now()->locale(app()->getLocale());
    $dateStr = app()->getLocale() === 'vi'
        ? \Illuminate\Support\Str::ucfirst($now->isoFormat('dddd, [ngày] D [tháng] M'))
        : $now->isoFormat('dddd, MMMM D');

    // Câu nhắn ủng hộ ngẫu nhiên, đổi mỗi lần tải trang
    $tagline = collect(config('katta.header_taglines'))->random();
@endphp

<header class="flex flex-wrap items-center justify-between gap-3 px-6 lg:px-10 py-5">
    {{-- Bên trái: icon + tên trang + ngày --}}
    <div class="flex items-center gap-2 min-w-0">
        <i data-lucide="{{ $icon }}" class="w-5 h-5 text-katta-primary shrink-0"></i>
        <h1 class="text-lg font-semibold text-gray-800 truncate">{{ $title }}</h1>
        <span class="text-sm text-gray-400 hidden sm:inline">· {{ $dateStr }}</span>
    </div>

    {{-- Giữa: lời nhắn ủng hộ ngẫu nhiên --}}
    <p class="hidden md:block flex-1 text-center text-sm italic font-semibold text-katta-primary truncate px-4">
        {{ $tagline }}
    </p>

    {{-- Bên phải: đổi ngôn ngữ, chuông thông báo, avatar --}}
    <div class="flex items-center gap-4 shrink-0">
        <a href="{{ route('locale.switch', app()->getLocale() === 'vi' ? 'en' : 'vi') }}"
           class="text-xs font-bold px-2.5 py-1 rounded-full border border-katta-primary/30 text-katta-primary hover:bg-katta-primary/10 transition">
            {{ app()->getLocale() === 'vi' ? 'EN' : 'VI' }}
        </a>

        {{-- Chuông thông báo --}}
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="relative text-gray-400 hover:text-katta-primary transition">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <span class="absolute -top-0.5 -right-0.5 w-2 h-2 bg-katta-accent rounded-full"></span>
            </button>
            <div x-show="open" x-cloak @click.outside="open = false"
                 class="absolute right-0 mt-3 w-64 bg-white rounded-2xl shadow-lg border border-gray-100 p-4 text-sm text-gray-500 z-50">
                {{ __('Chưa có thông báo nào.') }}
            </div>
        </div>

        {{-- Avatar user - click mở dropdown --}}
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open">
                <x-avatar :name="auth()->user()->name ?? '?'" />
            </button>
            <div x-show="open" x-cloak @click.outside="open = false"
                 class="absolute right-0 mt-3 w-48 bg-white rounded-2xl shadow-lg border border-gray-100 py-2 z-50">
                <a href="{{ route('profile') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:bg-katta-bg">
                    <i data-lucide="user" class="w-4 h-4"></i> {{ __('nav.profile') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-katta-accent hover:bg-katta-bg">
                        <i data-lucide="log-out" class="w-4 h-4"></i> {{ __('nav.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
