<x-app-layout :title="$title" :icon="$icon">
    @php
        // Bảng class màu cho lưới tính năng - viết literal để Tailwind JIT quét thấy và sinh CSS
        $colorClasses = [
            'indigo' => 'bg-indigo-100 text-indigo-600',
            'blue' => 'bg-blue-100 text-blue-600',
            'amber' => 'bg-amber-100 text-amber-600',
            'rose' => 'bg-rose-100 text-rose-600',
            'yellow' => 'bg-yellow-100 text-yellow-600',
            'purple' => 'bg-purple-100 text-purple-600',
            'pink' => 'bg-pink-100 text-pink-600',
            'gray' => 'bg-gray-100 text-gray-400',
        ];

        // Format tổng thời gian học thành "1h30" hoặc "45m"
        $totalMinutes = intdiv($totalStudySeconds, 60);
        $totalTimeLabel = $totalMinutes >= 60
            ? intdiv($totalMinutes, 60) . 'h' . str_pad($totalMinutes % 60, 2, '0', STR_PAD_LEFT)
            : $totalMinutes . 'm';
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-4">
        {{-- ===== Cột chính ===== --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Banner chào hỏi (mục 3) --}}
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-900 to-purple-600 text-white p-8 shadow-lg">
                {{-- Hình cầu mờ trang trí --}}
                <div class="pointer-events-none absolute right-0 top-1/2 -translate-y-1/2 w-72 h-72 rounded-full bg-white/10 blur-3xl"></div>
                <div class="pointer-events-none absolute right-10 top-1/2 -translate-y-1/2 w-44 h-44 rounded-full bg-gradient-to-br from-purple-300 to-indigo-300 opacity-70"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center gap-6">
                    <div class="flex-1">
                        <p class="text-xs font-semibold tracking-widest text-white/70 uppercase">{{ __($greetingKey) }} ,</p>
                        <h2 class="text-3xl font-bold mt-1">
                            {{ $user->name }} <span>👋</span>
                        </h2>
                        <p class="text-white/80 mt-2">{{ __('home.subtitle') }}</p>

                        <div class="flex flex-wrap gap-3 mt-5">
                            <span class="px-4 py-2 rounded-full bg-white/15 text-sm font-medium">🔥 {{ __('home.pill_streak', ['n' => $streak]) }}</span>
                            <span class="px-4 py-2 rounded-full bg-white/15 text-sm font-medium">🎓 {{ __('home.pill_sessions', ['n' => $sessionsCompleted]) }}</span>
                            <span class="px-4 py-2 rounded-full bg-white/15 text-sm font-medium">🕐 {{ __('home.pill_minutes', ['n' => $minutesToday]) }}</span>
                        </div>
                    </div>

                    {{-- Khung nổi: Thống kê dịch thuật --}}
                    <div class="relative z-10 w-full md:w-60 shrink-0 bg-white/95 text-gray-800 rounded-2xl shadow-xl p-4">
                        <p class="text-[11px] font-bold tracking-widest text-gray-400 uppercase mb-2">{{ __('home.translation_stats_title') }}</p>
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                                <i data-lucide="globe" class="w-4 h-4"></i>
                            </div>
                            <div>
                                <p class="text-xl font-bold leading-none">{{ $translationCount }}</p>
                                <p class="text-xs text-gray-400">{{ __('home.translation_stats_count') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between text-xs text-gray-500 mt-3">
                            <span>{{ __('home.translation_stats_avg') }}</span>
                            <span class="font-semibold text-gray-700">{{ __('home.translation_stats_points', ['n' => $translationAvgScore]) }}</span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-gray-100 mt-1.5 overflow-hidden">
                            <div class="h-full bg-katta-primary" style="width: {{ min(100, $translationAvgScore) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tất cả các tính năng --}}
            <div>
                <h3 class="font-semibold text-gray-700 mb-3">{{ __('home.features_title') }}</h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach (config('katta.home_features') as $feature)
                        @php $isDisabled = isset($feature['badge']); @endphp

                        <{{ $isDisabled ? 'div' : 'a' }}
                            @if (! $isDisabled) href="{{ route($feature['route']) }}" @endif
                            class="relative bg-white rounded-2xl shadow-sm p-5 transition {{ $isDisabled ? 'opacity-50' : 'hover:shadow-md hover:-translate-y-0.5' }}"
                        >
                            @if ($isDisabled)
                                <span class="absolute top-3 right-3 text-[10px] font-bold uppercase tracking-wide text-gray-400">{{ __($feature['badge']) }}</span>
                            @else
                                <i data-lucide="chevron-right" class="absolute top-4 right-4 w-4 h-4 text-gray-300"></i>
                            @endif

                            <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3 {{ $colorClasses[$feature['color']] }}">
                                <i data-lucide="{{ $feature['icon'] }}" class="w-5 h-5"></i>
                            </div>
                            <p class="font-semibold text-gray-800">{{ __($feature['label']) }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ __($feature['desc']) }}</p>
                        </{{ $isDisabled ? 'div' : 'a' }}>
                    @endforeach
                </div>
            </div>

            {{-- Hành trình học tập của bạn --}}
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-semibold text-gray-700">{{ __('home.journey_title') }}</h3>
                        <p class="text-xs text-gray-400">{{ __('home.journey_subtitle') }}</p>
                    </div>
                    <span class="text-[11px] font-bold uppercase tracking-wide px-3 py-1 rounded-full
                                  {{ $isActiveThisWeek ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-100 text-gray-400' }}">
                        {{ $isActiveThisWeek ? __('home.badge_active') : __('home.badge_inactive') }}
                    </span>
                </div>
                <canvas id="weeklyChart" height="90"></canvas>
            </div>

            {{-- Hàng thống kê nhỏ --}}
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                @foreach ([
                    ['icon' => 'flame', 'value' => $streak, 'label' => __('home.stat_streak'), 'color' => 'text-orange-500 bg-orange-50'],
                    ['icon' => 'check-circle-2', 'value' => $vocabMastered, 'label' => __('home.stat_vocab_mastered'), 'color' => 'text-katta-primary bg-purple-50'],
                    ['icon' => 'graduation-cap', 'value' => $sessionsCompleted, 'label' => __('home.stat_sessions_completed'), 'color' => 'text-emerald-500 bg-emerald-50'],
                    ['icon' => 'book-marked', 'value' => $articlesRead, 'label' => __('home.stat_articles_read'), 'color' => 'text-katta-accent bg-rose-50'],
                    ['icon' => 'clock', 'value' => $totalTimeLabel, 'label' => __('home.stat_total_time'), 'color' => 'text-blue-500 bg-blue-50'],
                ] as $stat)
                    <div class="bg-white rounded-2xl shadow-sm p-4">
                        <div class="w-9 h-9 rounded-full flex items-center justify-center mb-2 {{ $stat['color'] }}">
                            <i data-lucide="{{ $stat['icon'] }}" class="w-4 h-4"></i>
                        </div>
                        <p class="text-xl font-bold text-gray-800">{{ $stat['value'] }}</p>
                        <p class="text-xs text-gray-400">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ===== Cột phụ: Hoạt động gần đây ===== --}}
        <div class="lg:col-span-1">
            <div class="lg:sticky lg:top-6 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-700">{{ __('home.recent_activity_title') }}</h3>
                        <a href="{{ route('progress.index') }}" class="text-xs font-bold text-katta-primary hover:underline">
                            {{ __('home.recent_activity_view_all') }}
                        </a>
                    </div>

                    @if ($recentActivity->isEmpty())
                        <div class="text-center py-8">
                            <i data-lucide="inbox" class="w-8 h-8 text-gray-300 mx-auto mb-2"></i>
                            <p class="text-sm text-gray-400">{{ __('home.recent_activity_empty') }}</p>
                        </div>
                    @else
                        <ul class="space-y-3">
                            @foreach ($recentActivity as $activity)
                                <li class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-katta-bg text-katta-primary flex items-center justify-center shrink-0">
                                        <i data-lucide="check" class="w-4 h-4"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-700 truncate">{{ ucfirst($activity->type) }}</p>
                                        <p class="text-xs text-gray-400">{{ $activity->completed_at->diffForHumans() }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Biểu đồ Chart.js cho "Hành trình học tập của bạn" --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        (function () {
            const ctx = document.getElementById('weeklyChart');
            if (!ctx || !window.Chart) return;

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($weekly->keys()),
                    datasets: [{
                        data: @json($weekly->values()),
                        borderColor: '#7C3AED',
                        backgroundColor: 'rgba(124, 58, 237, 0.08)',
                        borderWidth: 2,
                        pointBackgroundColor: '#7C3AED',
                        tension: 0.35,
                        fill: true,
                    }],
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#F3F4F6' } },
                        x: { grid: { display: false } },
                    },
                },
            });
        })();
    </script>
</x-app-layout>
