<x-app-layout :title="$title" :icon="$icon">
    @php
        // Icon + màu theo từng chủ đề - viết literal để Tailwind JIT quét thấy
        $topicStyle = [
            'parts-of-speech' => ['bg' => 'bg-indigo-50 border-indigo-100', 'icon' => 'bg-indigo-100 text-indigo-600', 'lucide' => 'list'],
            'tenses' => ['bg' => 'bg-sky-50 border-sky-100', 'icon' => 'bg-sky-100 text-sky-600', 'lucide' => 'clock'],
            'sentence-structures' => ['bg' => 'bg-emerald-50 border-emerald-100', 'icon' => 'bg-emerald-100 text-emerald-600', 'lucide' => 'layout-grid'],
            'question-forms' => ['bg' => 'bg-amber-50 border-amber-100', 'icon' => 'bg-amber-100 text-amber-600', 'lucide' => 'help-circle'],
            'common-structures' => ['bg' => 'bg-rose-50 border-rose-100', 'icon' => 'bg-rose-100 text-katta-accent', 'lucide' => 'puzzle'],
        ];
    @endphp

    <div class="max-w-5xl mx-auto mt-6" x-data="{ tab: 'topics' }">
        <a href="{{ route('grammar.index') }}" class="text-sm text-gray-400 hover:text-katta-primary transition">
            {{ __('grammar.back_to_home') }}
        </a>

        <h1 class="text-2xl font-bold text-gray-800 mt-2">{{ __('grammar.practice_select_title') }}</h1>
        <p class="text-gray-400 mt-1">{{ __('grammar.practice_select_subtitle') }}</p>

        {{-- Tab Chủ đề / Các đề đã làm --}}
        <div class="flex items-center gap-2 mt-5 mb-6">
            <button type="button" @click="tab = 'topics'"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition"
                :class="tab === 'topics' ? 'bg-katta-primary text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                {{ __('grammar.practice_tab_topics') }}
            </button>
            <button type="button" @click="tab = 'history'"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition"
                :class="tab === 'history' ? 'bg-katta-primary text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                {{ __('grammar.practice_tab_history') }} ({{ $history->count() }})
            </button>
        </div>

        {{-- ===== Tab: Chủ đề (5 thẻ) ===== --}}
        <div x-show="tab === 'topics'" class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            @foreach ($topics as $topicKey => $topicLabel)
                @php $style = $topicStyle[$topicKey]; @endphp
                <a href="{{ route('grammar.practice.start', $topicKey) }}"
                   class="group relative bg-white rounded-2xl border p-5 hover:shadow-md hover:-translate-y-0.5 transition {{ $style['bg'] }}">
                    <div class="flex items-start justify-between">
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center {{ $style['icon'] }}">
                            <i data-lucide="{{ $style['lucide'] }}" class="w-5 h-5"></i>
                        </div>
                        <span class="w-9 h-9 rounded-full bg-katta-bg text-katta-primary flex items-center justify-center group-hover:bg-katta-primary group-hover:text-white transition shrink-0">
                            <i data-lucide="play" class="w-4 h-4 ml-0.5"></i>
                        </span>
                    </div>
                    <p class="font-bold text-base text-gray-800 mt-4 uppercase tracking-wide">{{ __('grammar.practice_topics.' . $topicKey . '.title') }}</p>
                    <p class="text-xs text-gray-400 mt-1 truncate">{{ __('grammar.practice_topics.' . $topicKey . '.desc') }}</p>
                </a>
            @endforeach
        </div>

        {{-- ===== Tab: Các đề đã làm ===== --}}
        <div x-show="tab === 'history'" x-cloak>
            @if ($history->isEmpty())
                <div class="bg-white rounded-2xl shadow-sm p-10 text-center text-gray-400">
                    {{ __('grammar.practice_history_empty') }}
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($history as $set)
                        <div class="bg-white rounded-2xl shadow-sm p-4 flex items-center gap-4">
                            <div class="shrink-0 w-12 h-12 rounded-xl flex items-center justify-center font-bold text-sm
                                        {{ $set->score >= $set->total * 0.7 ? 'bg-emerald-100 text-emerald-600' : ($set->score >= $set->total * 0.4 ? 'bg-amber-100 text-amber-600' : 'bg-rose-100 text-katta-accent') }}">
                                {{ $set->score }}/{{ $set->total }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-400 mb-0.5">
                                    {{ __('grammar.practice_topics.' . $set->topic_key . '.title') }} · {{ $set->completed_at?->diffForHumans() }}
                                </p>
                                <p class="text-sm text-gray-700 font-semibold truncate">
                                    {{ __('grammar.quiz_title', ['topic' => __('grammar.practice_topics.' . $set->topic_key . '.title')]) }}
                                </p>
                            </div>
                            <a href="{{ route('grammar.practice.show', $set) }}"
                               class="shrink-0 px-4 py-2 rounded-xl text-sm font-semibold bg-katta-bg text-katta-primary hover:bg-katta-primary hover:text-white transition">
                                {{ __('grammar.practice_review') }}
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
