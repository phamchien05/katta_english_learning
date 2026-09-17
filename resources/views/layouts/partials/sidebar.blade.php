{{-- Sidebar trái cố định (~300px), nền tím than đậm (mục 2) --}}
<aside class="hidden lg:flex flex-col w-[300px] shrink-0 h-screen sticky top-0 bg-katta-sidebar text-white/90">

    {{-- Logo (khối hoàn chỉnh icon + chữ "Katta" đã có sẵn trong ảnh, không cần thêm text riêng) --}}
    <a href="{{ route('home') }}" class="flex items-center px-6 py-6 shrink-0">
        <x-logo size="w-10 h-10" />
    </a>

    {{-- Menu điều hướng --}}
    <nav class="flex-1 overflow-y-auto px-3 space-y-1">
        @foreach (config('katta.nav') as $item)
            @php
                $isActive = request()->routeIs($item['route']) || request()->routeIs($item['route'] . '.*');
                $isDisabled = isset($item['badge']); // ví dụ "Nói" - sắp ra mắt
            @endphp

            <a href="{{ route($item['route']) }}"
               class="group flex items-center gap-3 px-3 py-2.5 mr-2 rounded-xl text-sm font-medium transition
                      {{ $isActive ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}
                      {{ $isDisabled ? 'opacity-60' : '' }}">
                <i data-lucide="{{ $item['icon'] }}" class="w-5 h-5 shrink-0"></i>
                <span class="flex-1 truncate">{{ __($item['label']) }}</span>

                @if ($isDisabled)
                    <span class="text-[10px] uppercase tracking-wide bg-katta-accent/90 text-white px-1.5 py-0.5 rounded-full">
                        {{ __($item['badge']) }}
                    </span>
                @elseif ($isActive)
                    <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                @endif
            </a>
        @endforeach
    </nav>

    {{-- Khối user ở cuối sidebar --}}
    <div class="border-t border-white/10 px-4 py-4 flex items-center gap-3 shrink-0">
        <x-avatar :name="auth()->user()->name ?? '?'" />
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name ?? '' }}</p>
            <p class="text-xs text-white/50 truncate">{{ auth()->user()->email ?? '' }}</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" title="{{ __('nav.logout') }}" class="text-white/60 hover:text-white transition">
                <i data-lucide="log-out" class="w-5 h-5"></i>
            </button>
        </form>
    </div>
</aside>
