<x-app-layout :title="$title" :icon="$icon">
    <div class="max-w-4xl mx-auto mt-6" x-data="{ tab: 'levels' }">
        {{-- Tab chọn cấp độ / đã dịch --}}
        <div class="flex items-center justify-center gap-2 mb-6">
            <button type="button" @click="tab = 'levels'"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition"
                :class="tab === 'levels' ? 'bg-katta-primary text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                {{ __('translate.tab_levels') }}
            </button>
            <button type="button" @click="tab = 'history'"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition"
                :class="tab === 'history' ? 'bg-katta-primary text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                {{ __('translate.tab_history') }} ({{ $history->count() }})
            </button>
        </div>

        {{-- ===== Tab: Chọn cấp độ ===== --}}
        <div x-show="tab === 'levels'" class="text-center">
            <h1 class="text-2xl font-bold text-gray-800">{{ __('translate.select_level_title') }}</h1>
            <p class="text-gray-400 mt-1">{{ __('translate.select_level_subtitle') }}</p>

            {{-- Chiều dịch - bấm để đổi qua lại EN<->VI --}}
            <div class="flex items-center justify-center gap-3 mt-5">
                <span class="text-xs font-bold tracking-widest text-gray-400">{{ __('translate.mode_label') }}</span>
                <a href="{{ route('translate.direction.switch', $direction === 'en_vi' ? 'vi_en' : 'en_vi') }}"
                   class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white shadow-sm text-sm font-semibold text-gray-700 hover:shadow-md transition">
                    {{ $direction === 'en_vi' ? 'EN → VI' : 'VI → EN' }}
                    <i data-lucide="repeat" class="w-3.5 h-3.5 text-katta-primary"></i>
                </a>
            </div>

            {{-- Lưới cấp độ A1-C1 --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mt-8 text-left">
                @foreach ($levels as $level)
                    <a href="{{ route('translate.practice', $level) }}"
                       class="group bg-white rounded-2xl shadow-sm p-5 hover:shadow-md hover:-translate-y-0.5 transition">
                        <div class="flex items-start justify-between">
                            <div class="w-11 h-11 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                                <i data-lucide="book" class="w-5 h-5"></i>
                            </div>
                            <span class="w-8 h-8 rounded-full bg-katta-bg text-katta-primary flex items-center justify-center group-hover:bg-katta-primary group-hover:text-white transition">
                                <i data-lucide="play" class="w-3.5 h-3.5 ml-0.5"></i>
                            </span>
                        </div>
                        <p class="font-bold text-lg text-gray-800 mt-4">{{ $level }}</p>
                        <p class="text-xs text-gray-400">{{ __('translate.level_subtitle', ['level' => $level]) }}</p>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- ===== Tab: Đã dịch ===== --}}
        <div x-show="tab === 'history'" x-cloak>
            @if ($history->isEmpty())
                <div class="bg-white rounded-2xl shadow-sm p-10 text-center text-gray-400">
                    {{ __('translate.history_empty') }}
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($history as $item)
                        <div class="bg-white rounded-2xl shadow-sm p-4 flex items-center gap-4">
                            <div class="shrink-0 w-12 h-12 rounded-xl flex items-center justify-center font-bold text-sm
                                        {{ $item->ai_score >= 70 ? 'bg-emerald-100 text-emerald-600' : ($item->ai_score >= 40 ? 'bg-amber-100 text-amber-600' : 'bg-rose-100 text-katta-accent') }}">
                                {{ $item->ai_score }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-400 mb-0.5">
                                    {{ $item->passage->level ?? '?' }} · {{ $item->passage && $item->passage->direction === 'en_vi' ? 'EN → VI' : 'VI → EN' }}
                                    · {{ $item->created_at->diffForHumans() }}
                                </p>
                                <p class="text-sm text-gray-700 truncate">{{ $item->passage->source_text ?? '' }}</p>
                            </div>
                            @if ($item->passage)
                                <a href="{{ route('translate.practice', ['level' => $item->passage->level, 'passage' => $item->passage_id]) }}"
                                   class="shrink-0 px-4 py-2 rounded-xl text-sm font-semibold bg-katta-bg text-katta-primary hover:bg-katta-primary hover:text-white transition">
                                    {{ __('translate.retry') }}
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
