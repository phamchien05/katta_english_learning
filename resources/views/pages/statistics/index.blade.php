<x-app-layout :title="$title" :icon="$icon">
    @php
        $tiles = [
            ['label' => __('statistics.tile_streak'), 'value' => $streak, 'caption' => __('statistics.tile_streak_caption'), 'icon' => 'flame'],
            ['label' => __('statistics.tile_sessions'), 'value' => $sessionsCompleted, 'caption' => __('statistics.tile_sessions_caption'), 'icon' => 'graduation-cap'],
            ['label' => __('statistics.tile_words_mastered'), 'value' => $vocabMastered, 'caption' => __('statistics.tile_words_mastered_caption'), 'icon' => 'book'],
            ['label' => __('statistics.tile_reading'), 'value' => $articlesRead, 'caption' => __('statistics.tile_reading_caption'), 'icon' => 'bookmark'],
            ['label' => __('statistics.tile_today'), 'value' => $todayDurationLabel, 'caption' => __('statistics.tile_today_caption'), 'icon' => 'clock'],
            ['label' => __('statistics.tile_total'), 'value' => $totalDurationLabel, 'caption' => __('statistics.tile_total_caption'), 'icon' => 'hourglass'],
        ];

        // Ngưỡng màu độ chính xác - đúng quy ước đã dùng ở Tiến trình (mục 10)
        $accuracyColor = fn (int $p) => $p >= 70 ? 'bg-emerald-500' : ($p >= 40 ? 'bg-amber-500' : 'bg-katta-accent');
        $accuracyText = fn (int $p) => $p >= 70 ? 'text-emerald-600' : ($p >= 40 ? 'text-amber-600' : 'text-katta-accent');
    @endphp

    <div class="max-w-4xl mx-auto mt-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('statistics.page_title') }}</h1>
        <p class="text-gray-400 mt-1">{{ __('statistics.page_subtitle') }}</p>

        {{-- 6 ô số liệu tổng quan --}}
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-6">
            @foreach ($tiles as $tile)
                <div class="bg-white rounded-2xl shadow-sm p-5">
                    <div class="flex items-center gap-1.5 text-[11px] font-bold tracking-widest text-gray-400 uppercase">
                        <i data-lucide="{{ $tile['icon'] }}" class="w-3.5 h-3.5"></i>
                        {{ $tile['label'] }}
                    </div>
                    <p class="text-3xl font-semibold text-gray-800 mt-2">{{ $tile['value'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $tile['caption'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Lịch đánh dấu ngày học --}}
        <div class="mt-6">
            @livewire('statistics.calendar')
        </div>

        {{-- Từ vựng theo cấp độ --}}
        <div class="bg-white rounded-2xl shadow-sm p-5 mt-6">
            <h2 class="font-semibold text-gray-800 mb-4">{{ __('statistics.vocabulary_by_level_title') }}</h2>

            @if ($vocabByLevel->isEmpty())
                <p class="text-sm text-gray-400">{{ __('statistics.vocabulary_by_level_empty') }}</p>
            @else
                <div class="space-y-4">
                    @foreach ($vocabByLevel as $row)
                        @php $percent = min(100, (int) round($row['mastered'] / max(1, $row['total']) * 100)); @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="font-medium text-gray-700">{{ $row['level'] }}</span>
                                <span class="text-gray-500">{{ __('statistics.words_mastered_count', ['mastered' => $row['mastered'], 'total' => $row['total']]) }}</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full bg-katta-primary rounded-full" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Độ chính xác từng module --}}
        <div class="bg-white rounded-2xl shadow-sm p-5 mt-6 mb-10">
            <h2 class="font-semibold text-gray-800 mb-4">{{ __('statistics.accuracy_title') }}</h2>

            @if ($accuracy->isEmpty())
                <p class="text-sm text-gray-400">{{ __('statistics.accuracy_empty') }}</p>
            @else
                <div class="space-y-4">
                    @foreach ($accuracy as $row)
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="font-medium text-gray-700">{{ __('statistics.type_' . $row['type']) }}</span>
                                <span class="{{ $accuracyText($row['percent']) }} font-semibold">{{ $row['percent'] }}%</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full {{ $accuracyColor($row['percent']) }} rounded-full" style="width: {{ $row['percent'] }}%"></div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">{{ __('statistics.accuracy_sessions', ['count' => $row['sessions']]) }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
