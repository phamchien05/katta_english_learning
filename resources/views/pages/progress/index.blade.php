<x-app-layout :title="$title" :icon="$icon">
    <div class="max-w-4xl mx-auto mt-6" x-data="{ tab: 'all' }">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('progress.page_title') }}</h1>
        <p class="text-gray-400 mt-1">{{ __('progress.sessions_completed', ['count' => $history->count()]) }}</p>

        {{-- Pill lọc theo loại --}}
        <div class="flex flex-wrap items-center gap-2 mt-5 mb-6">
            <button type="button" @click="tab = 'all'"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition"
                :class="tab === 'all' ? 'bg-katta-primary text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                {{ __('progress.tab_all') }}
            </button>
            @foreach (['vocabulary', 'grammar', 'reading', 'translate'] as $type)
                <button type="button" @click="tab = '{{ $type }}'"
                    class="px-4 py-2 rounded-xl text-sm font-semibold transition"
                    :class="tab === '{{ $type }}' ? 'bg-katta-primary text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                    {{ __('progress.tab_' . $type) }}
                </button>
            @endforeach
        </div>

        @if ($history->isEmpty())
            <div class="bg-white rounded-2xl shadow-sm p-10 text-center text-gray-400">
                {{ __('progress.empty_state') }}
            </div>
        @else
            <div class="space-y-3">
                @foreach ($history as $entry)
                    @php
                        $total = $entry['total'] ?: 1;
                        $percent = (int) round($entry['score'] / $total * 100);
                        $percentColor = $percent >= 70 ? 'text-emerald-600' : ($percent >= 40 ? 'text-amber-600' : 'text-katta-accent');
                    @endphp
                    <div x-data="{ open: false }"
                         x-show="tab === 'all' || tab === '{{ $entry['type'] }}'"
                         class="bg-white rounded-2xl shadow-sm overflow-hidden">
                        <button type="button" @click="open = !open"
                                class="w-full flex flex-wrap items-center gap-3 p-4 text-left">
                            <span class="shrink-0 text-xs font-semibold px-2.5 py-1 rounded-full {{ $typeStyle[$entry['type']] }}">
                                {{ __('progress.type_' . $entry['type']) }}
                            </span>
                            <span class="flex-1 min-w-[140px] font-semibold text-gray-800 truncate">{{ $entry['title'] }}</span>

                            <span class="text-right shrink-0">
                                @if ($entry['detail_kind'] === 'qa')
                                    <span class="block text-sm text-gray-500">{{ __('progress.correct_count', ['score' => $entry['score'], 'total' => $entry['total']]) }}</span>
                                @endif
                                <span class="block text-xs text-gray-400">{{ $entry['date']?->format('d/m/Y H:i') }}</span>
                            </span>

                            <span class="shrink-0 font-bold text-lg {{ $percentColor }}">{{ $percent }}%</span>
                            <i data-lucide="chevron-down" class="w-4 h-4 text-gray-300 transition-transform shrink-0" :class="open ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="open" x-cloak class="border-t border-gray-100 p-4 space-y-2">
                            @if ($entry['detail_kind'] === 'qa')
                                @foreach ($entry['items'] as $item)
                                    <div class="flex items-start gap-3 p-3 rounded-xl {{ $item['is_correct'] ? 'bg-emerald-50' : 'bg-rose-50' }}">
                                        <i data-lucide="{{ $item['is_correct'] ? 'check-circle' : 'x-circle' }}"
                                           class="w-5 h-5 shrink-0 mt-0.5 {{ $item['is_correct'] ? 'text-emerald-600' : 'text-katta-accent' }}"></i>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-800">{{ $item['question'] }}</p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ __('progress.your_answer') }}
                                                <span class="{{ $item['is_correct'] ? 'text-emerald-700' : 'text-katta-accent' }} font-medium">
                                                    {{ $item['user_answer'] !== '' ? $item['user_answer'] : __('progress.no_answer') }}
                                                </span>
                                            </p>
                                            @unless ($item['is_correct'])
                                                <p class="text-xs text-gray-500">
                                                    {{ __('progress.correct_answer_label') }}
                                                    <span class="text-emerald-700 font-medium">{{ $item['correct_answer'] }}</span>
                                                </p>
                                            @endunless
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="space-y-3 text-sm">
                                    <div>
                                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">{{ __('progress.original_text') }}</p>
                                        <p class="text-gray-700 whitespace-pre-line">{{ $entry['source_text'] }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">{{ __('progress.your_translation') }}</p>
                                        <p class="text-gray-700 whitespace-pre-line">{{ $entry['user_translation'] }}</p>
                                    </div>
                                    @if ($entry['feedback'])
                                        <div>
                                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">{{ __('progress.ai_feedback') }}</p>
                                            <p class="text-gray-600 whitespace-pre-line">{{ $entry['feedback'] }}</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
