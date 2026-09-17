<x-app-layout :title="$title" :icon="$icon">
    @php
        $statusStyle = [
            'pending' => 'bg-amber-100 text-amber-700',
            'in_review' => 'bg-sky-100 text-sky-700',
            'resolved' => 'bg-emerald-100 text-emerald-700',
            'rejected' => 'bg-gray-200 text-gray-500',
        ];
        $categoryLabel = [
            'bug' => __('feedback.category_bug'),
            'suggestion' => __('feedback.category_suggestion'),
            'other' => __('feedback.category_other'),
        ];
    @endphp

    <div class="max-w-2xl mx-auto mt-6" x-data="{ tab: 'new' }">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('feedback.page_title') }}</h1>

        <div class="flex items-center gap-2 mt-5 mb-6">
            <button type="button" @click="tab = 'new'"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition"
                :class="tab === 'new' ? 'bg-katta-primary text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                {{ __('feedback.tab_new') }}
            </button>
            <button type="button" @click="tab = 'history'"
                class="px-4 py-2 rounded-xl text-sm font-semibold transition"
                :class="tab === 'history' ? 'bg-katta-primary text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">
                {{ __('feedback.tab_history') }} ({{ $history->count() }})
            </button>
        </div>

        <div x-show="tab === 'new'">
            <livewire:feedback.new-form :contextUrl="$contextUrl" />
        </div>

        <div x-show="tab === 'history'" x-cloak>
            @if ($history->isEmpty())
                <div class="bg-white rounded-2xl shadow-sm p-10 text-center text-gray-400">
                    {{ __('feedback.history_empty') }}
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($history as $item)
                        <div x-data="{ open: false }" class="bg-white rounded-2xl shadow-sm overflow-hidden">
                            <button type="button" @click="open = !open" class="w-full flex items-center gap-3 p-4 text-left">
                                <span class="shrink-0 text-xs font-semibold px-2.5 py-1 rounded-full {{ $statusStyle[$item->status] ?? $statusStyle['pending'] }}">
                                    {{ __('feedback.status_' . $item->status) }}
                                </span>
                                <span class="flex-1 min-w-0 font-medium text-gray-800 truncate">{{ $item->title }}</span>
                                <span class="shrink-0 text-xs text-gray-400">{{ $item->created_at->format('d M Y') }}</span>
                                <i data-lucide="chevron-down" class="w-4 h-4 text-gray-300 transition-transform shrink-0" :class="open ? 'rotate-180' : ''"></i>
                            </button>

                            <div x-show="open" x-cloak class="border-t border-gray-100 p-4 space-y-3">
                                <span class="inline-block text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">
                                    {{ $categoryLabel[$item->category] ?? $item->category }}
                                </span>
                                <p class="text-sm text-gray-600 whitespace-pre-line">{{ $item->message }}</p>
                                @if ($item->screenshot_path)
                                    <img src="{{ asset('storage/' . $item->screenshot_path) }}" class="max-h-64 rounded-xl border border-gray-200">
                                @endif
                                @if ($item->context_url)
                                    <p class="text-xs text-gray-400 truncate">
                                        {{ __('feedback.context_label') }} {{ $item->context_url }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
