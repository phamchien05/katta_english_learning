<x-app-layout :title="$title" :icon="$icon">
    @php
        // Màu nền theo chủ đề - viết literal để Tailwind JIT quét thấy
        $topicStyle = [
            'general' => ['bg' => 'bg-white border-gray-100', 'icon' => 'bg-gray-100 text-gray-500', 'lucide' => 'headphones'],
            'everyday_conversation' => ['bg' => 'bg-amber-50 border-amber-100', 'icon' => 'bg-amber-100 text-amber-600', 'lucide' => 'message-circle'],
            'social_monologue' => ['bg' => 'bg-sky-50 border-sky-100', 'icon' => 'bg-sky-100 text-sky-600', 'lucide' => 'megaphone'],
            'academic_discussion' => ['bg' => 'bg-emerald-50 border-emerald-100', 'icon' => 'bg-emerald-100 text-emerald-600', 'lucide' => 'users'],
            'academic_lecture' => ['bg' => 'bg-rose-50 border-rose-100', 'icon' => 'bg-rose-100 text-katta-accent', 'lucide' => 'graduation-cap'],
        ];
    @endphp

    <div class="max-w-6xl mx-auto mt-6" x-data="{ tab: 'all', topicFilter: 'all', q: '' }">
        {{-- Tab Tất cả / Đã nghe --}}
        <div class="flex items-center gap-2 mb-5">
            <button type="button" @click="tab = 'all'"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition"
                :class="tab === 'all' ? 'bg-katta-primary text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                {{ __('listening.tab_all') }}
            </button>
            <button type="button" @click="tab = 'history'"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition"
                :class="tab === 'history' ? 'bg-katta-primary text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                {{ __('listening.tab_history') }} ({{ $history->count() }})
            </button>
        </div>

        {{-- ===== Tab: Tất cả (danh sách nhóm theo chủ đề) ===== --}}
        <div x-show="tab === 'all'">
            <h1 class="text-2xl font-bold text-gray-800">{{ __('nav.listening') }}</h1>
            <p class="text-gray-400 mt-1">{{ __('listening.subtitle') }}</p>

            <div class="relative mt-4 max-w-md">
                <i data-lucide="search" class="w-4 h-4 text-gray-300 absolute left-3 top-1/2 -translate-y-1/2"></i>
                <input type="text" x-model="q" placeholder="{{ __('listening.search_placeholder') }}"
                       class="w-full pl-9 rounded-xl border-gray-200 text-sm focus:border-katta-primary focus:ring-katta-primary">
            </div>

            <div class="flex flex-wrap gap-2 mt-4">
                <button type="button" @click="topicFilter = 'all'"
                    class="px-3 py-1.5 rounded-full text-xs font-semibold transition"
                    :class="topicFilter === 'all' ? 'bg-katta-sidebar text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                    {{ __('listening.tab_all') }}
                </button>
                @foreach ($topics as $topic)
                    <button type="button" @click="topicFilter = '{{ $topic }}'"
                        class="px-3 py-1.5 rounded-full text-xs font-semibold transition"
                        :class="topicFilter === '{{ $topic }}' ? 'bg-katta-sidebar text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                        {{ __('listening.topics.' . $topic) }}
                    </button>
                @endforeach
            </div>

            <div class="mt-6 space-y-8">
                @forelse ($grouped as $topic => $passages)
                    @php $style = $topicStyle[$topic] ?? $topicStyle['general']; @endphp
                    <div x-show="topicFilter === 'all' || topicFilter === '{{ $topic }}'">
                        <div class="flex items-center gap-2 mb-3">
                            <i data-lucide="{{ $style['lucide'] }}" class="w-4 h-4 text-gray-400"></i>
                            <h2 class="font-semibold text-gray-700">{{ __('listening.topics.' . $topic) }}</h2>
                            @isset($fixedLevels[$topic])
                                <span class="text-xs text-gray-400">({{ $fixedLevels[$topic] }})</span>
                            @endisset
                        </div>

                        @if ($topic === 'general')
                            {{-- Tổng quan: tổ chức theo cấp độ CEFR, bấm vào random 1 bài trong kho của cấp đó --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach (['A1', 'A2', 'B1', 'B2', 'C1'] as $level)
                                    <a href="{{ route('listening.general', $level) }}"
                                       class="rounded-2xl border p-4 hover:shadow-md hover:-translate-y-0.5 transition {{ $style['bg'] }}">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center mb-3 {{ $style['icon'] }}">
                                            <i data-lucide="{{ $style['lucide'] }}" class="w-4 h-4"></i>
                                        </div>
                                        <p class="font-semibold text-gray-800 text-sm leading-snug">{{ $level }} Listening</p>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $level }}</p>
                                        <p class="text-xs text-katta-primary font-semibold mt-3">{{ __('listening.start_listening') }}</p>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach ($passages as $passage)
                                    <a href="{{ route('listening.show', $passage) }}"
                                       x-show="q === '' || '{{ str_replace("'", '', strtolower($passage->title)) }}'.includes(q.toLowerCase())"
                                       class="rounded-2xl border p-4 hover:shadow-md hover:-translate-y-0.5 transition {{ $style['bg'] }}">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center mb-3 {{ $style['icon'] }}">
                                            <i data-lucide="{{ $style['lucide'] }}" class="w-4 h-4"></i>
                                        </div>
                                        <p class="font-semibold text-gray-800 text-sm leading-snug">{{ $passage->title }}</p>
                                        <p class="text-xs text-katta-primary font-semibold mt-3">{{ __('listening.start_listening') }}</p>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="bg-white rounded-2xl shadow-sm p-10 text-center text-gray-400">
                        {{ __('listening.no_results') }}
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ===== Tab: Đã nghe ===== --}}
        <div x-show="tab === 'history'" x-cloak>
            @if ($history->isEmpty())
                <div class="bg-white rounded-2xl shadow-sm p-10 text-center text-gray-400">
                    {{ __('listening.history_empty') }}
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($history as $item)
                        <div class="bg-white rounded-2xl shadow-sm p-4 flex items-center gap-4">
                            <div class="shrink-0 w-12 h-12 rounded-xl flex items-center justify-center font-bold text-sm
                                        {{ $item->score >= $item->total * 0.7 ? 'bg-emerald-100 text-emerald-600' : ($item->score >= $item->total * 0.4 ? 'bg-amber-100 text-amber-600' : 'bg-rose-100 text-katta-accent') }}">
                                {{ $item->score }}/{{ $item->total }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-400 mb-0.5">
                                    {{ __('listening.topics.' . ($item->passage->topic ?? 'general')) }} · {{ $item->created_at->diffForHumans() }}
                                </p>
                                <p class="text-sm text-gray-700 font-semibold truncate">{{ $item->passage->title ?? '' }}</p>
                            </div>
                            @if ($item->passage)
                                <a href="{{ route('listening.show', $item->passage) }}"
                                   class="shrink-0 px-4 py-2 rounded-xl text-sm font-semibold bg-katta-bg text-katta-primary hover:bg-katta-primary hover:text-white transition">
                                    {{ __('listening.retry') }}
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
