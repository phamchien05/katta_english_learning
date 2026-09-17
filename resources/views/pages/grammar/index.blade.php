<x-app-layout :title="$title" :icon="$icon">
    <div class="max-w-4xl mx-auto mt-6">
        <a href="{{ route('home') }}" class="text-sm text-gray-400 hover:text-katta-primary transition">
            {{ __('grammar.back_to_home') }}
        </a>

        <h1 class="text-2xl font-bold text-gray-800 mt-2">{{ __('nav.grammar') }}</h1>
        <p class="text-gray-400 mt-1">{{ __('grammar.index_subtitle') }}</p>

        {{-- 2 thẻ lớn cạnh nhau: Xem lý thuyết / Bắt đầu luyện tập --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
            <a href="{{ route('grammar.theory') }}"
               class="group bg-white rounded-3xl shadow-sm p-8 hover:shadow-md hover:-translate-y-0.5 transition">
                <div class="w-14 h-14 rounded-2xl bg-katta-bg text-katta-primary flex items-center justify-center">
                    <i data-lucide="book-open" class="w-7 h-7"></i>
                </div>
                <p class="font-bold text-xl text-gray-800 mt-5">{{ __('grammar.card_theory_title') }}</p>
                <p class="text-sm text-gray-400 mt-2 leading-relaxed">{{ __('grammar.card_theory_desc') }}</p>
                <p class="text-sm text-katta-primary font-semibold mt-5 flex items-center gap-1">
                    <span>{{ __('grammar.card_theory_title') }}</span>
                    <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition"></i>
                </p>
            </a>

            <a href="{{ route('grammar.practice') }}"
               class="group bg-white rounded-3xl shadow-sm p-8 hover:shadow-md hover:-translate-y-0.5 transition">
                <div class="w-14 h-14 rounded-2xl bg-rose-50 text-katta-accent flex items-center justify-center">
                    <i data-lucide="pencil" class="w-7 h-7"></i>
                </div>
                <p class="font-bold text-xl text-gray-800 mt-5">{{ __('grammar.card_practice_title') }}</p>
                <p class="text-sm text-gray-400 mt-2 leading-relaxed">{{ __('grammar.card_practice_desc') }}</p>
                <p class="text-sm text-katta-primary font-semibold mt-5 flex items-center gap-1">
                    <span>{{ __('grammar.card_practice_title') }}</span>
                    <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition"></i>
                </p>
            </a>
        </div>
    </div>
</x-app-layout>
