<x-app-layout :title="$title" :icon="$icon">
    <div class="max-w-4xl mx-auto mt-6 text-center">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('vocabulary.select_level_title') }}</h1>
        <p class="text-gray-400 mt-1">{{ __('vocabulary.select_level_subtitle') }}</p>

        {{-- Toggle phương hướng học - placeholder cho tương lai (hiện chỉ hỗ trợ EN -> VI) --}}
        <div class="flex items-center justify-center gap-3 mt-5">
            <span class="text-xs font-bold tracking-widest text-gray-400">{{ __('vocabulary.direction_label') }}</span>
            <span class="px-4 py-1.5 rounded-full bg-white shadow-sm text-sm font-semibold text-gray-700">EN → VI</span>
        </div>

        {{-- Lưới 6 cấp độ CEFR --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mt-8 text-left">
            @foreach ($levels as $level)
                <a href="{{ route('vocabulary.test', $level) }}"
                   class="group bg-white rounded-2xl shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition">
                    <div class="flex items-start justify-between">
                        <div class="w-11 h-11 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
                            <i data-lucide="book" class="w-5 h-5"></i>
                        </div>
                        <span class="w-8 h-8 rounded-full bg-katta-bg text-katta-primary flex items-center justify-center group-hover:bg-katta-primary group-hover:text-white transition">
                            <i data-lucide="play" class="w-3.5 h-3.5 ml-0.5"></i>
                        </span>
                    </div>
                    <p class="font-bold text-lg text-gray-800 mt-4">{{ $level }}</p>
                    <p class="text-xs text-gray-400">{{ __('vocabulary.level_subtitle', ['level' => $level]) }}</p>
                </a>
            @endforeach
        </div>
    </div>
</x-app-layout>
